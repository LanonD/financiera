<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Prestamo;
use App\Models\Pago;
use App\Models\Cliente;
use App\Models\Empleado;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PrestamoController extends Controller
{
    public function index(Request $request)
    {
        $user   = Auth::user();
        $puesto = $user->puesto;

        $query = Prestamo::with(['cliente', 'promotor', 'cobrador']);

        if ($puesto === 'promo') {
            $empleado = $user->empleado;
            if ($empleado) {
                $query->where('promotor_id', $empleado->id);
            }
        }

        // Server-side filters
        $filtros = [
            'frecuencia' => $request->query('frecuencia', ''),
            'monto_min'  => (float)$request->query('monto_min', 0),
            'monto_max'  => (float)$request->query('monto_max', 0),
            'desde'      => $request->query('desde', ''),
            'hasta'      => $request->query('hasta', ''),
        ];

        if (!empty($filtros['frecuencia'])) {
            $query->where('frecuencia', $filtros['frecuencia']);
        }
        if ($filtros['monto_min'] > 0) {
            $query->where('monto', '>=', $filtros['monto_min']);
        }
        if ($filtros['monto_max'] > 0) {
            $query->where('monto', '<=', $filtros['monto_max']);
        }
        if (!empty($filtros['desde']) || !empty($filtros['hasta'])) {
            $query->whereHas('pagos', function ($q) use ($filtros) {
                $q->whereIn('estatus', ['Pendiente', 'Atrasado']);
                if (!empty($filtros['desde'])) {
                    $q->where('fecha_programada', '>=', $filtros['desde']);
                }
                if (!empty($filtros['hasta'])) {
                    $q->where('fecha_programada', '<=', $filtros['hasta']);
                }
            });
        }

        $prestamos = $query->orderByDesc('id')->get()->map(function ($p) {
            $next = $p->pagos()->whereIn('estatus', ['Pendiente', 'Atrasado'])->orderBy('fecha_programada')->first();
            $p->proximo_pago = $next?->fecha_programada;
            return $p;
        });

        return view('admin.prestamos', compact('prestamos', 'filtros', 'puesto'));
    }

    public function create()
    {
        $user   = Auth::user();
        $puesto = $user->puesto;

        $query = Cliente::where('activo', true);
        if ($puesto === 'promo') {
            $empleado = $user->empleado;
            if ($empleado) {
                $query->where('promotor_id', $empleado->id);
            }
        }

        $clientes = $query->with('promotor')->orderBy('nombre')->get();

        return view('admin.prestamo_nuevo', compact('clientes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'cliente_id'          => 'required|exists:clientes,id',
            'monto_entregado'     => 'required|numeric|min:1',
            'monto_retornar'      => 'required|numeric|min:1',
            'num_pagos'           => 'required|integer|min:1',
            'frecuencia'          => 'required|in:Diario,Semanal,Quincenal,Mensual',
            'fecha_inicio'        => 'required|date',
            'fecha_primer_cobro'  => 'required|date|after_or_equal:fecha_inicio',
        ]);

        $user     = Auth::user();
        $empleado = $user->empleado;

        $monto_entregado    = (float)$data['monto_entregado'];
        $monto_retornar     = (float)$data['monto_retornar'];
        $num_pagos          = (int)$data['num_pagos'];
        $frecuencia         = $data['frecuencia'];
        $fecha_inicio       = $data['fecha_inicio'];
        $fecha_primer_cobro = $data['fecha_primer_cobro'];

        $dias_map = ['Diario' => 1, 'Semanal' => 7, 'Quincenal' => 14, 'Mensual' => 30];
        $dias = $dias_map[$frecuencia];

        $cuota_base  = $num_pagos > 1 ? ceil($monto_retornar / $num_pagos / 10) * 10 : $monto_retornar;
        $ultimo_pago = max(0, round(($monto_retornar - $cuota_base * ($num_pagos - 1)) * 100) / 100);

        $cliente     = Cliente::findOrFail($data['cliente_id']);
        $promotor_id = $empleado?->id ?? $cliente->promotor_id;

        $prestamo = Prestamo::create([
            'cliente_id'          => $data['cliente_id'],
            'promotor_id'         => $promotor_id,
            'cobrador_id'         => null,
            'monto'               => $monto_retornar,
            'tasa_diaria'         => 0,
            'num_pagos'           => $num_pagos,
            'frecuencia'          => $frecuencia,
            'cuota'               => $cuota_base,
            'saldo_actual'        => $monto_entregado,
            'interes_acumulado'   => 0,
            'interes_activo'      => false,
            'interes_diario'      => 0,
            'interes_mora_activo' => false,
            'fecha_inicio'        => $fecha_inicio,
            'fecha_fin'           => Carbon::parse($fecha_primer_cobro)->addDays($dias * ($num_pagos - 1))->toDateString(),
            'estatus'             => 'Pendiente',
            'monto_entregado'     => $monto_entregado,
            'forma_entrega'       => null,
            'fecha_entrega'       => null,
        ]);

        // Create payment schedule
        $ratio = $monto_retornar > 0 ? $monto_entregado / $monto_retornar : 1;
        $saldo = $monto_entregado;

        for ($i = 1; $i <= $num_pagos; $i++) {
            // Pago 1 → fecha_primer_cobro, pago 2 → +dias, pago 3 → +2*dias, etc.
            $fecha_prog = Carbon::parse($fecha_primer_cobro)->addDays($dias * ($i - 1))->toDateString();
            $cuota      = ($i === $num_pagos) ? $ultimo_pago : $cuota_base;
            $capital    = ($i === $num_pagos) ? $saldo : round($cuota * $ratio * 100) / 100;
            $interes    = round(($cuota - $capital) * 100) / 100;
            $saldo      = max(0, round(($saldo - $capital) * 100) / 100);

            Pago::create([
                'prestamo_id'      => $prestamo->id,
                'cobrador_id'      => null,
                'numero_pago'      => $i,
                'monto_cuota'      => $cuota,
                'interes'          => $interes,
                'capital'          => $capital,
                'saldo_restante'   => $saldo,
                'monto_cobrado'    => null,
                'tipo_cobro'       => null,
                'nota_cobro'       => null,
                'fecha_programada' => $fecha_prog,
                'fecha_pago'       => null,
                'estatus'          => 'Pendiente',
            ]);
        }

        return redirect()->route('prestamos.show', $prestamo->id)->with('success', 'Préstamo creado correctamente.');
    }

    public function show($id)
    {
        $prestamo = Prestamo::with(['cliente', 'promotor', 'cobrador'])->findOrFail($id);
        $pagos    = Pago::where('prestamo_id', $id)->orderBy('numero_pago')->get();

        $interesInfo = ($prestamo->interes_activo || $prestamo->interes_mora_activo) ? true : null;

        return view('admin.prestamo_detalle', compact('prestamo', 'pagos', 'interesInfo'));
    }

    public function edit($id)
    {
        $prestamo  = Prestamo::with(['cliente', 'promotor'])->findOrFail($id);
        $cobradores = Empleado::where('puesto', 'collector')->where('activo', true)->get();
        return view('admin.prestamo_editar', compact('prestamo', 'cobradores'));
    }

    public function update(Request $request, $id)
    {
        $prestamo = Prestamo::findOrFail($id);

        $data = $request->validate([
            'estatus'     => 'required|in:Pendiente,Activo,Atrasado,Finalizado,Retirado',
            'cobrador_id' => 'nullable|exists:empleados,id',
        ]);

        $prestamo->update($data);

        return redirect()->route('prestamos.show', $id)->with('success', 'Préstamo actualizado correctamente.');
    }

    public function toggleInteres($id)
    {
        $prestamo = Prestamo::findOrFail($id);
        $prestamo->interes_activo = !$prestamo->interes_activo;
        $prestamo->save();

        return redirect()->route('prestamos.show', $id)->with('success', 'Interés ' . ($prestamo->interes_activo ? 'activado' : 'pausado') . '.');
    }

    public function toggleMora($id)
    {
        $prestamo = Prestamo::findOrFail($id);
        $prestamo->interes_mora_activo = !$prestamo->interes_mora_activo;
        $prestamo->save();

        return redirect()->route('prestamos.show', $id)->with('success', 'Interés por mora ' . ($prestamo->interes_mora_activo ? 'activado' : 'desactivado') . '.');
    }

    /**
     * Standard amortization: C = P*(r*(1+r)^n)/((1+r)^n - 1)
     */
    public function calcular(Request $request)
    {
        $monto     = (float)$request->input('monto', 0);
        $tasa      = (float)$request->input('tasa', 0);
        $num_pagos = (int)$request->input('num_pagos', 0);
        $frecuencia= $request->input('frecuencia', 'Mensual');
        $fecha_ini = $request->input('fecha_inicio', now()->toDateString());

        if ($monto <= 0 || $num_pagos <= 0 || $tasa <= 0) {
            return response()->json(['error' => 'Datos inválidos'], 400);
        }

        $dias_map = ['Diario' => 1, 'Semanal' => 7, 'Quincenal' => 14, 'Mensual' => 30];
        $dias = $dias_map[$frecuencia] ?? 30;
        $r    = $tasa * $dias;
        $n    = $num_pagos;

        $cuota = $r > 0
            ? $monto * ($r * pow(1 + $r, $n)) / (pow(1 + $r, $n) - 1)
            : $monto / $n;

        $schedule = [];
        $saldo = $monto;
        for ($i = 1; $i <= $n; $i++) {
            $fecha   = Carbon::parse($fecha_ini)->addDays($dias * $i)->toDateString();
            $interes = round($saldo * $r, 2);
            $capital = round($cuota - $interes, 2);
            $saldo   = max(0, round($saldo - $capital, 2));
            $schedule[] = ['numero' => $i, 'fecha' => $fecha, 'cuota' => round($cuota, 2), 'capital' => $capital, 'interes' => $interes, 'saldo' => $saldo];
        }

        return response()->json(['cuota' => round($cuota, 2), 'schedule' => $schedule]);
    }

    /**
     * Fixed payment split proportionally
     */
    public function calcular2(Request $request)
    {
        $monto_entregado    = (float)$request->input('monto_entregado', 0);
        $monto_retornar     = (float)$request->input('monto_retornar', 0);
        $num_pagos          = (int)$request->input('num_pagos', 0);
        $frecuencia         = $request->input('frecuencia', 'Mensual');
        $fecha_ini          = $request->input('fecha_inicio', now()->toDateString());

        if ($monto_entregado <= 0 || $monto_retornar < $monto_entregado || $num_pagos <= 0) {
            return response()->json(['error' => 'Datos inválidos'], 400);
        }

        $dias_map = ['Diario' => 1, 'Semanal' => 7, 'Quincenal' => 14, 'Mensual' => 30];
        $dias = $dias_map[$frecuencia] ?? 30;

        // Primer cobro: se puede enviar explícitamente, si no se calcula como inicio + días
        $fecha_primer_cobro = $request->input('fecha_primer_cobro')
            ?? Carbon::parse($fecha_ini)->addDays($dias)->toDateString();

        $cuota_base  = $num_pagos > 1 ? ceil($monto_retornar / $num_pagos / 10) * 10 : $monto_retornar;
        $ultimo_pago = max(0, round(($monto_retornar - $cuota_base * ($num_pagos - 1)) * 100) / 100);

        $ratio = $monto_retornar > 0 ? $monto_entregado / $monto_retornar : 1;
        $saldo = $monto_entregado;

        $schedule = [];
        for ($i = 1; $i <= $num_pagos; $i++) {
            $fecha   = Carbon::parse($fecha_primer_cobro)->addDays($dias * ($i - 1))->toDateString();
            $cuota   = ($i === $num_pagos) ? $ultimo_pago : $cuota_base;
            $capital = ($i === $num_pagos) ? $saldo : round($cuota * $ratio * 100) / 100;
            $interes = round(($cuota - $capital) * 100) / 100;
            $saldo   = max(0, round(($saldo - $capital) * 100) / 100);
            $schedule[] = ['numero' => $i, 'fecha' => $fecha, 'cuota' => $cuota, 'capital' => $capital, 'interes' => $interes, 'saldo' => $saldo];
        }

        return response()->json(['cuota_base' => $cuota_base, 'ultimo_pago' => $ultimo_pago, 'ganancia' => $monto_retornar - $monto_entregado, 'schedule' => $schedule]);
    }
}
