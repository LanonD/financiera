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

        if (in_array('promo', $user->getAllRoles()) && !in_array('admin', $user->getAllRoles())) {
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
            $p->proximo_pago = $next?->fecha_programada?->toDateString();
            return $p;
        });

        return view('admin.prestamos', compact('prestamos', 'filtros', 'puesto'));
    }

    public function create()
    {
        $user   = Auth::user();
        $puesto = $user->puesto;

        $query = Cliente::where('activo', true);
        if (in_array('promo', $user->getAllRoles()) && !in_array('admin', $user->getAllRoles())) {
            $empleado = $user->empleado;
            if ($empleado) {
                $query->where('promotor_id', $empleado->id);
            }
        }

        $clientes = $query->with('promotor')->orderBy('nombre')->get();

        // Build map: client_id => promotor_nombre for active loans (to warn in the UI)
        $clientesConPrestamo = Prestamo::whereIn('cliente_id', $clientes->pluck('id'))
            ->whereIn('estatus', ['Activo', 'Atrasado', 'Pendiente'])
            ->with('promotor')
            ->get()
            ->keyBy('cliente_id')
            ->map(fn($p) => $p->promotor?->nombre ?? 'otro promotor');

        return view('admin.prestamo_nuevo', compact('clientes', 'clientesConPrestamo'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'cliente_id'          => 'required|exists:clientes,id',
            'monto_entregado'     => 'required|numeric|min:1',
            'monto_retornar'      => 'required|numeric|min:1',
            'num_pagos'           => 'required|integer|min:1',
            'frecuencia'          => 'required|in:Diario,Semanal,Quincenal,Mensual',
            // Allow up to 7 days in the past to support offline sync
            'fecha_inicio'        => 'required|date|after_or_equal:' . now()->subDays(7)->toDateString(),
            'fecha_primer_cobro'  => 'required|date|after_or_equal:' . now()->subDays(7)->toDateString(),
        ]);

        $user     = Auth::user();
        $empleado = $user->empleado;

        // ── Block: One active loan per client ────────────────────────────────
        $prestamoActivo = Prestamo::where('cliente_id', $data['cliente_id'])
            ->whereIn('estatus', ['Activo', 'Atrasado', 'Pendiente'])
            ->with('promotor')
            ->first();

        if ($prestamoActivo) {
            $promotorNombre = $prestamoActivo->promotor?->nombre ?? 'otro promotor';
            return redirect()->back()
                ->withInput()
                ->withErrors(['cliente_id' =>
                    "Este cliente ya tiene un préstamo activo asignado al promotor \u201c{$promotorNombre}\u201d. "
                  . 'No se puede crear otro préstamo mientras haya uno en curso.'
                ]);
        }
        // ────────────────────────────────────────────────────────────────────

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

        // Create payment schedule — interest-first: all interest collected before principal
        $interes_restante = round($monto_retornar - $monto_entregado, 2);
        $saldo            = $monto_entregado;

        for ($i = 1; $i <= $num_pagos; $i++) {
            $fecha_prog = Carbon::parse($fecha_primer_cobro)->addDays($dias * ($i - 1))->toDateString();
            $cuota      = ($i === $num_pagos) ? $ultimo_pago : $cuota_base;
            $interes    = min($cuota, round($interes_restante, 2));
            $capital    = round($cuota - $interes, 2);
            $interes_restante = max(0, round($interes_restante - $interes, 2));
            $saldo      = max(0, round($saldo - $capital, 2));

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

        // Auto-retire pending loans with no disbursement after 5 days
        if ($prestamo->estatus === 'Pendiente' && $prestamo->created_at->diffInDays(now()) >= 5) {
            $prestamo->estatus = 'Retirado';
            $prestamo->save();
        }

        // Auto-activate mora ($10/day default) when there are overdue payments
        if (in_array($prestamo->estatus, ['Activo', 'Atrasado'])) {
            $primerVencido = Pago::where('prestamo_id', $id)
                ->whereIn('estatus', ['Pendiente', 'Atrasado'])
                ->where('fecha_programada', '<', now()->toDateString())
                ->orderBy('fecha_programada')
                ->first();

            if ($primerVencido) {
                $changed = false;
                if ($prestamo->estatus === 'Activo') {
                    $prestamo->estatus = 'Atrasado';
                    $changed = true;
                }
                if ((float)$prestamo->interes_diario == 0) {
                    $prestamo->interes_diario = 10.00;
                    $changed = true;
                }
                if (!$prestamo->interes_mora_activo) {
                    $prestamo->interes_mora_activo = true;
                    $changed = true;
                }
                if (!$prestamo->fecha_ultimo_interes) {
                    $prestamo->fecha_ultimo_interes = $primerVencido->fecha_programada->toDateString();
                    $changed = true;
                }
                if ($changed) $prestamo->save();
            }
        }

        // Accumulate daily mora
        if ((float)$prestamo->interes_diario > 0
            && ($prestamo->interes_mora_activo || $prestamo->estatus === 'Atrasado')) {
            $hoy = now()->toDateString();
            $desdeDate = $prestamo->fecha_ultimo_interes
                ? $prestamo->fecha_ultimo_interes->toDateString()
                : $hoy;
            $dias = (int) Carbon::parse($desdeDate)->diffInDays($hoy);
            if ($dias > 0) {
                $prestamo->interes_acumulado    = round((float)$prestamo->interes_acumulado + ($dias * (float)$prestamo->interes_diario), 2);
                $prestamo->fecha_ultimo_interes = $hoy;
                $prestamo->save();
            }
        }

        $pagos = Pago::where('prestamo_id', $id)->orderBy('numero_pago')->get();
        $interesInfo = ($prestamo->interes_activo || $prestamo->interes_mora_activo || (float)$prestamo->interes_acumulado > 0) ? true : null;

        return view('admin.prestamo_detalle', compact('prestamo', 'pagos', 'interesInfo'));
    }

    public function edit($id)
    {
        $prestamo   = Prestamo::with(['cliente', 'promotor'])->findOrFail($id);
        // Include multi-role employees that have 'collector' among their roles
        $cobradores = Empleado::where('activo', true)->get()->filter(fn($e) => $e->hasRole('collector'))->values();
        return view('admin.prestamo_editar', compact('prestamo', 'cobradores'));
    }

    public function update(Request $request, $id)
    {
        $prestamo = Prestamo::findOrFail($id);

        $data = $request->validate([
            'estatus'         => 'required|in:Pendiente,Activo,Atrasado,Finalizado,Retirado',
            'cobrador_id'     => 'nullable|exists:empleados,id',
            'interes_diario'  => 'nullable|numeric|min:0',
        ]);

        $update = [
            'estatus'     => $data['estatus'],
            'cobrador_id' => $data['cobrador_id'] ?? null,
        ];
        if (isset($data['interes_diario'])) {
            $update['interes_diario'] = (float)$data['interes_diario'];
        }
        // Al revertir a Pendiente, limpiar fecha_entrega para que aparezca en desembolsos
        if ($data['estatus'] === 'Pendiente') {
            $update['fecha_entrega'] = null;
        }
        $prestamo->update($update);

        return redirect()->route('prestamos.show', $id)->with('success', 'Préstamo actualizado correctamente.');
    }

    /**
     * Quick inline update of mora interest daily rate from detail page
     */
    public function setMora(Request $request, $id)
    {
        $prestamo = Prestamo::findOrFail($id);

        $data = $request->validate([
            'interes_diario' => 'required|numeric|min:0',
        ]);

        $prestamo->interes_diario = (float)$data['interes_diario'];
        // Set start date if not yet set so we know when to start counting
        if (!$prestamo->fecha_ultimo_interes) {
            $prestamo->fecha_ultimo_interes = now()->toDateString();
        }
        $prestamo->save();

        return redirect()->route('prestamos.show', $id)
            ->with('success', 'Interés diario por mora actualizado a $' . number_format($prestamo->interes_diario, 2) . '/día.');
    }

    /**
     * Admin: edit principal, total acordado, and mora acumulada directly
     */
    public function updateCampos(Request $request, $id)
    {
        $prestamo = Prestamo::findOrFail($id);

        $data = $request->validate([
            'monto_entregado'   => 'required|numeric|min:0',
            'monto'             => 'required|numeric|min:0',
            'interes_acumulado' => 'required|numeric|min:0',
        ]);

        $prestamo->monto_entregado   = round((float)$data['monto_entregado'], 2);
        $prestamo->monto             = round((float)$data['monto'], 2);
        $prestamo->interes_acumulado = round((float)$data['interes_acumulado'], 2);
        // Keep saldo_actual in sync if principal changed
        if ($prestamo->isDirty('monto_entregado')) {
            $pagado = $prestamo->pagos()->whereIn('estatus', ['Pagado', 'Parcial'])->sum('capital');
            $prestamo->saldo_actual = max(0, round((float)$data['monto_entregado'] - (float)$pagado, 2));
        }
        $prestamo->save();

        return redirect()->route('prestamos.show', $id)->with('success', 'Campos actualizados correctamente.');
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

        // When activating, record today as the start date for daily accumulation
        if ($prestamo->interes_mora_activo && !$prestamo->fecha_ultimo_interes) {
            $prestamo->fecha_ultimo_interes = now()->toDateString();
        }

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
