<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Prestamo;
use App\Models\Pago;
use App\Models\Cliente;
use App\Models\Empleado;
use App\Models\PrestamoActividad;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PrestamoController extends Controller
{
    public function index(Request $request)
    {
        $user   = Auth::user();
        $puesto = $user->puesto;

        $adminId = $user->adminId();

        $query = Prestamo::with(['cliente', 'promotor', 'cobrador'])
            ->where('admin_id', $adminId);

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

        $adminId = $user->adminId();
        $query   = Cliente::where('activo', true)->where('admin_id', $adminId);
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
        $desembolsar = $request->boolean('desembolsar');

        $rules = [
            'cliente_id'          => 'required|exists:clientes,id',
            'monto_entregado'     => 'required|numeric|min:1',
            'monto_retornar'      => 'required|numeric|min:1',
            'num_pagos'           => 'required|integer|min:1',
            'frecuencia'          => 'required|in:Diario,Semanal,Quincenal,Mensual',
            // Allow up to 7 days in the past to support offline sync
            'fecha_inicio'        => 'required|date|after_or_equal:' . now()->subDays(7)->toDateString(),
            'fecha_primer_cobro'  => 'required|date|after_or_equal:' . now()->subDays(7)->toDateString(),
        ];

        if ($desembolsar) {
            $rules += [
                'doc_ine'           => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
                'doc_pagare'        => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
                'doc_comprobante'   => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
                'doc_foto_domicilio'=> 'nullable|file|mimes:jpg,jpeg,png|max:10240',
            ];
        }

        $data = $request->validate($rules);

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

        // Cuota en pesos enteros (sin centavos); el último pago absorbe el residuo
        $cuota_base  = $num_pagos > 1 ? (float) floor($monto_retornar / $num_pagos) : $monto_retornar;
        $ultimo_pago = $num_pagos > 1 ? round($monto_retornar - $cuota_base * ($num_pagos - 1), 2) : $monto_retornar;

        $cliente     = Cliente::where('id', $data['cliente_id'])->where('admin_id', auth()->user()->adminId())->firstOrFail();
        $promotor_id = $empleado?->id ?? $cliente->promotor_id;

        // ── Disbursement (optional) ───────────────────────────────────────
        $forma_entrega   = $desembolsar ? $request->input('forma_entrega') : null;
        $fecha_entrega   = $desembolsar ? $request->input('fecha_entrega') : null;
        $nota_entrega    = $desembolsar ? $request->input('nota_entrega')  : null;
        $doc_ine         = null;
        $doc_pagare      = null;
        $doc_comprobante = null;
        $doc_foto        = null;

        if ($desembolsar) {
            $prestamoIdTemp = 'tmp_' . time(); // placeholder; real ID used after create
            // We store docs after creating the prestamo to have its real ID
        }

        $prestamo = Prestamo::create([
            'admin_id'            => auth()->user()->adminId(),
            'cliente_id'          => $data['cliente_id'],
            'promotor_id'         => $promotor_id,
            'cobrador_id'         => null,
            'monto'               => $monto_retornar,
            'tasa_diaria'         => 0,
            'num_pagos'           => $num_pagos,
            'frecuencia'          => $frecuencia,
            'cuota'               => $cuota_base,
            'saldo_actual'        => $monto_retornar,
            'interes_acumulado'   => 0,
            'interes_activo'      => false,
            'interes_diario'      => 0,
            'interes_mora_activo' => false,
            'fecha_inicio'        => $fecha_inicio,
            'fecha_fin'           => Carbon::parse($fecha_primer_cobro)->addDays($dias * ($num_pagos - 1))->toDateString(),
            'estatus'             => $desembolsar ? 'Activo' : 'Pendiente',
            'monto_entregado'     => $monto_entregado,
            'forma_entrega'       => $forma_entrega,
            'fecha_entrega'       => $fecha_entrega,
            'nota_entrega'        => $nota_entrega,
        ]);

        // ── Save uploaded documents now that we have the real prestamo ID ─────
        if ($desembolsar && $request->hasFile('doc_ine')) {
            $carpeta = public_path('documentos/prestamo_' . $prestamo->id);
            if (!file_exists($carpeta)) mkdir($carpeta, 0775, true);

            $guardar = function (string $campo, string $prefijo) use ($request, $carpeta, $prestamo): ?string {
                if (!$request->hasFile($campo)) return null;
                $file = $request->file($campo);
                if (!$file->isValid()) return null;
                $nombre = $prefijo . '_' . time() . '_' . uniqid() . '.' . strtolower($file->getClientOriginalExtension());
                $file->move($carpeta, $nombre);
                return 'documentos/prestamo_' . $prestamo->id . '/' . $nombre;
            };

            $prestamo->doc_ine            = $guardar('doc_ine', 'ine');
            $prestamo->doc_pagare         = $guardar('doc_pagare', 'pagare');
            $prestamo->doc_comprobante    = $guardar('doc_comprobante', 'comprobante');
            $prestamo->doc_foto_domicilio = $guardar('doc_foto_domicilio', 'foto_domicilio');
            $prestamo->save();
        }

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

        PrestamoActividad::log($prestamo->id, 'creado',
            'Préstamo creado por ' . Auth::user()->usuario .
            ' — $' . number_format($monto_entregado, 2) . ' entregados, $' . number_format($monto_retornar, 2) . ' a retornar en ' . $num_pagos . ' pagos ' . strtolower($frecuencia) . 's.',
            ['monto_entregado' => $monto_entregado, 'monto_retornar' => $monto_retornar, 'num_pagos' => $num_pagos, 'frecuencia' => $frecuencia]
        );

        if ($desembolsar) {
            PrestamoActividad::log($prestamo->id, 'desembolso',
                'Desembolsado al momento de la creación. Forma: ' . ($forma_entrega ?? 'no especificada'),
                ['forma_entrega' => $forma_entrega, 'fecha_entrega' => $fecha_entrega]
            );
        }

        $msg = $desembolsar ? 'Préstamo creado y desembolsado correctamente.' : 'Préstamo creado correctamente.';
        return redirect()->route('prestamos.show', $prestamo->id)->with('success', $msg);
    }

    public function show($id)
    {
        $adminId  = auth()->user()->adminId();
        $prestamo = Prestamo::with(['cliente', 'promotor', 'cobrador'])
            ->where('id', $id)
            ->where('admin_id', $adminId)
            ->firstOrFail();

        // Auto-retire pending loans with no disbursement after 5 days
        if ($prestamo->estatus === 'Pendiente' && $prestamo->created_at->diffInDays(now()) >= 5) {
            $prestamo->estatus = 'Retirado';
            $prestamo->save();
        }

        // Auto-change status to Atrasado when there are overdue payments
        // NOTE: mora interest (interes_diario) is NOT auto-set — admin activates it manually
        if (in_array($prestamo->estatus, ['Activo', 'Atrasado'])) {
            $primerVencido = Pago::where('prestamo_id', $id)
                ->whereIn('estatus', ['Pendiente', 'Atrasado'])
                ->where('fecha_programada', '<', now()->toDateString())
                ->orderBy('fecha_programada')
                ->first();

            if ($primerVencido && $prestamo->estatus === 'Activo') {
                $prestamo->estatus = 'Atrasado';
                $prestamo->save();
            }
        }

        // Accumulate daily mora — only for active loans (never for Finalizado/Retirado)
        if ((float)$prestamo->interes_diario > 0
            && in_array($prestamo->estatus, ['Activo', 'Atrasado'])
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

        $pagos       = Pago::where('prestamo_id', $id)->orderBy('fecha_programada')->orderBy('numero_pago')->get();
        $interesInfo = ($prestamo->interes_activo || $prestamo->interes_mora_activo || (float)$prestamo->interes_acumulado > 0) ? true : null;
        $actividad   = \App\Models\PrestamoActividad::where('prestamo_id', $id)
                           ->with('user')
                           ->orderByDesc('created_at')
                           ->get();

        return view('admin.prestamo_detalle', compact('prestamo', 'pagos', 'interesInfo', 'actividad'));
    }

    public function edit($id)
    {
        $adminId    = auth()->user()->adminId();
        $prestamo   = Prestamo::with(['cliente', 'promotor', 'cobrador', 'desembolso'])
            ->where('id', $id)
            ->where('admin_id', $adminId)
            ->firstOrFail();

        $empleados = Empleado::where('admin_id', $adminId)->where('activo', true)->get();

        $cobradores     = $empleados->filter(fn($e) => $e->hasRole('collector'))->values();
        $promotores     = $empleados->filter(fn($e) => $e->hasRole('promo'))->values();
        $desembolsadores= $empleados->filter(fn($e) => $e->hasRole('desembolso'))->values();

        return view('admin.prestamo_editar', compact('prestamo', 'cobradores', 'promotores', 'desembolsadores'));
    }

    public function update(Request $request, $id)
    {
        $adminId  = auth()->user()->adminId();
        $prestamo = Prestamo::where('id', $id)->where('admin_id', $adminId)->firstOrFail();

        $data = $request->validate([
            'estatus'         => 'required|in:Pendiente,Activo,Atrasado,Finalizado,Retirado',
            'cobrador_id'     => 'nullable|exists:empleados,id',
            'promotor_id'     => 'nullable|exists:empleados,id',
            'desembolso_id'   => 'nullable|exists:empleados,id',
            'interes_diario'  => 'nullable|numeric|min:0',
        ]);

        $update = [
            'estatus'       => $data['estatus'],
            'cobrador_id'   => $data['cobrador_id']   ?? null,
            'promotor_id'   => $data['promotor_id']   ?? null,
            'desembolso_id' => $data['desembolso_id'] ?? null,
        ];
        if (isset($data['interes_diario'])) {
            $update['interes_diario'] = (float)$data['interes_diario'];
        }
        // Al revertir a Pendiente, limpiar fecha_entrega para que aparezca en desembolsos
        if ($data['estatus'] === 'Pendiente') {
            $update['fecha_entrega'] = null;
        }
        $estatusAnterior   = $prestamo->estatus;
        $cobradorAnterior  = $prestamo->cobrador_id;
        $promotorAnterior  = $prestamo->promotor_id;
        $prestamo->update($update);

        // Log cambio de estatus
        if ($estatusAnterior !== $data['estatus']) {
            PrestamoActividad::log($id, 'estatus',
                "Estatus cambiado de {$estatusAnterior} a {$data['estatus']}.",
                ['de' => $estatusAnterior, 'a' => $data['estatus']]
            );
        }
        // Log cambio de cobrador
        $nuevoCobradorId = $data['cobrador_id'] ?? null;
        if ($cobradorAnterior !== $nuevoCobradorId) {
            $cobrador = $nuevoCobradorId ? Empleado::find($nuevoCobradorId) : null;
            PrestamoActividad::log($id, 'cobrador',
                $cobrador ? "Cobrador asignado: {$cobrador->nombre}." : 'Cobrador removido.',
                ['cobrador_id' => $nuevoCobradorId, 'nombre' => $cobrador?->nombre]
            );
        }
        // Log cambio de promotor
        $nuevoPromotorId = $data['promotor_id'] ?? null;
        if ($promotorAnterior !== $nuevoPromotorId) {
            $promotor = $nuevoPromotorId ? Empleado::find($nuevoPromotorId) : null;
            PrestamoActividad::log($id, 'configuracion',
                $promotor ? "Promotor cambiado a: {$promotor->nombre}." : 'Promotor removido.',
                ['promotor_id' => $nuevoPromotorId, 'nombre' => $promotor?->nombre]
            );
        }

        return redirect()->route('prestamos.show', $id)->with('success', 'Préstamo actualizado correctamente.');
    }

    /**
     * Quick inline update of mora interest daily rate from detail page
     */
    public function setMora(Request $request, $id)
    {
        $adminId  = auth()->user()->adminId();
        $prestamo = Prestamo::where('id', $id)->where('admin_id', $adminId)->firstOrFail();

        $data = $request->validate([
            'interes_diario' => 'required|numeric|min:0',
        ]);

        $anterior = (float)$prestamo->interes_diario;
        $prestamo->interes_diario = (float)$data['interes_diario'];
        // Set start date if not yet set so we know when to start counting
        if (!$prestamo->fecha_ultimo_interes) {
            $prestamo->fecha_ultimo_interes = now()->toDateString();
        }
        $prestamo->save();

        PrestamoActividad::log($id, 'configuracion',
            'Interés diario por mora actualizado de $' . number_format($anterior, 2) .
            ' a $' . number_format($prestamo->interes_diario, 2) . '/día por ' . Auth::user()->usuario . '.',
            ['anterior' => $anterior, 'nuevo' => $prestamo->interes_diario]
        );

        return redirect()->route('prestamos.show', $id)
            ->with('success', 'Interés diario por mora actualizado a $' . number_format($prestamo->interes_diario, 2) . '/día.');
    }

    /**
     * Admin: edit financial fields — behaviour differs by loan status.
     *
     * PENDIENTE  → full edit: principal, total acordado, mora, fecha_inicio,
     *              fecha_primer_cobro, frecuencia, num_pagos.
     *              Pagos dates are recalculated automatically.
     *
     * ACTIVO/+   → limited edit: interés acordado (= monto - principal) and
     *              mora acumulada. Principal is locked (already delivered).
     */
    public function updateCampos(Request $request, $id)
    {
        $adminId  = auth()->user()->adminId();
        $prestamo = Prestamo::where('id', $id)->where('admin_id', $adminId)->firstOrFail();
        $esPendiente = $prestamo->estatus === 'Pendiente';

        // Bloquear edición en préstamos finalizados/retirados
        if (in_array($prestamo->estatus, ['Finalizado', 'Retirado'])) {
            return redirect()->route('prestamos.edit', $id)
                ->with('error', 'Este préstamo está ' . strtolower($prestamo->estatus) . ' y no puede editarse.');
        }

        $esPendiente = $prestamo->estatus === 'Pendiente';

        // ── Validation ────────────────────────────────────────────────────
        if ($esPendiente) {
            $data = $request->validate([
                'monto_entregado'    => 'required|numeric|min:0',
                'monto'              => 'required|numeric|min:0',
                'interes_acumulado'  => 'required|numeric|min:0',
                'fecha_inicio'       => 'required|date',
                'fecha_primer_cobro' => 'required|date',
                'frecuencia'         => 'required|in:Diario,Semanal,Quincenal,Mensual',
                'num_pagos'          => 'required|integer|min:1',
            ]);
        } else {
            // Activo / Atrasado — solo saldos pendientes
            $data = $request->validate([
                'saldo_actual'      => 'required|numeric|min:0',
                'interes_acumulado' => 'required|numeric|min:0',
            ]);
        }

        $cambios = [];

        if ($esPendiente) {
            // ── Full edit (Pendiente) ──────────────────────────────────────
            if (round($prestamo->monto_entregado, 2) !== round((float)$data['monto_entregado'], 2))
                $cambios[] = 'Principal: $' . number_format($prestamo->monto_entregado, 2) . ' → $' . number_format($data['monto_entregado'], 2);
            if (round($prestamo->monto, 2) !== round((float)$data['monto'], 2))
                $cambios[] = 'Total acordado: $' . number_format($prestamo->monto, 2) . ' → $' . number_format($data['monto'], 2);

            $prestamo->monto_entregado = round((float)$data['monto_entregado'], 2);
            $prestamo->monto           = round((float)$data['monto'], 2);
            $prestamo->saldo_actual    = round((float)$data['monto'], 2); // reset saldo to full
            $prestamo->cuota           = $data['num_pagos'] > 1
                ? ceil((float)$data['monto'] / (int)$data['num_pagos'] / 10) * 10
                : (float)$data['monto'];

            $oldFecha   = $prestamo->fecha_inicio;
            $oldFreq    = $prestamo->frecuencia;
            $oldPrimer  = $prestamo->fecha_primer_cobro ?? null;
            $oldNumPagos= $prestamo->num_pagos;

            $prestamo->fecha_inicio       = $data['fecha_inicio'];
            $prestamo->frecuencia         = $data['frecuencia'];
            $prestamo->num_pagos          = (int)$data['num_pagos'];

            if ($oldFreq !== $data['frecuencia'])
                $cambios[] = 'Frecuencia: ' . $oldFreq . ' → ' . $data['frecuencia'];
            if ((string)$oldNumPagos !== (string)$data['num_pagos'])
                $cambios[] = 'Núm. pagos: ' . $oldNumPagos . ' → ' . $data['num_pagos'];

            // Recalculate pagos dates
            $diasMap = ['Diario' => 1, 'Semanal' => 7, 'Quincenal' => 14, 'Mensual' => 30];
            $dias    = $diasMap[$data['frecuencia']] ?? 30;
            $pagos   = Pago::where('prestamo_id', $id)->orderBy('numero_pago')->get();

            $monto        = round((float)$data['monto'], 2);
            $numPagos     = (int)$data['num_pagos'];
            // Cuota en pesos enteros; último pago absorbe el residuo de centavos
            $cuotaBase    = $numPagos > 1 ? (float) floor($monto / $numPagos) : $monto;
            $ultimoPago   = $numPagos > 1 ? round($monto - $cuotaBase * ($numPagos - 1), 2) : $monto;
            $interesTotal = round($monto - (float)$data['monto_entregado'], 2);
            $interesRest  = $interesTotal;
            $saldo        = round((float)$data['monto_entregado'], 2);

            foreach ($pagos as $i => $pago) {
                $idx   = $i; // 0-based
                $fecha = $data['frecuencia'] === 'Mensual'
                    ? Carbon::parse($data['fecha_primer_cobro'])->addMonths($idx)->toDateString()
                    : Carbon::parse($data['fecha_primer_cobro'])->addDays($dias * $idx)->toDateString();

                $cuota   = ($idx === $numPagos - 1) ? $ultimoPago : $cuotaBase;
                $interes = min($cuota, max(0, $interesRest));
                $capital = round($cuota - $interes, 2);
                $interesRest = max(0, round($interesRest - $interes, 2));
                $saldo   = max(0, round($saldo - $capital, 2));

                $pago->fecha_programada = $fecha;
                $pago->monto_cuota      = $cuota;
                $pago->interes          = $interes;
                $pago->capital          = $capital;
                $pago->saldo_restante   = $saldo;
                $pago->save();
            }

            // Update fecha_fin
            $lastPago = $pagos->last();
            if ($lastPago) $prestamo->fecha_fin = $lastPago->fecha_programada;

            $cambios[] = 'Fechas del plan recalculadas (1er cobro: ' . Carbon::parse($data['fecha_primer_cobro'])->format('d/m/Y') . ').';

        } else {
            // ── Activo / Atrasado: solo saldos pendientes ─────────────────
            if (round($prestamo->saldo_actual, 2) !== round((float)$data['saldo_actual'], 2))
                $cambios[] = 'Saldo pendiente: $' . number_format($prestamo->saldo_actual, 2) . ' → $' . number_format($data['saldo_actual'], 2);

            $prestamo->saldo_actual = round((float)$data['saldo_actual'], 2);
            // interes_acumulado handled below
        }

        // Mora acumulada (both modes)
        if (round($prestamo->interes_acumulado, 2) !== round((float)$data['interes_acumulado'], 2))
            $cambios[] = 'Mora acumulada: $' . number_format($prestamo->interes_acumulado, 2) . ' → $' . number_format($data['interes_acumulado'], 2);
        $prestamo->interes_acumulado = round((float)$data['interes_acumulado'], 2);

        $prestamo->save();

        if (!empty($cambios)) {
            PrestamoActividad::log($id, 'ajuste',
                'Campos editados por ' . Auth::user()->usuario . ': ' . implode('; ', $cambios) . '.',
                ['cambios' => $cambios]
            );
        }

        return redirect()->route('prestamos.edit', $id)->with('success', 'Campos actualizados correctamente.');
    }

    /**
     * Cancel a pending loan (sets status to Retirado).
     */
    public function cancelar($id)
    {
        $adminId  = auth()->user()->adminId();
        $prestamo = Prestamo::where('id', $id)->where('admin_id', $adminId)->firstOrFail();

        if ($prestamo->estatus !== 'Pendiente') {
            return redirect()->back()->with('error', 'Solo se pueden cancelar préstamos en estado Pendiente.');
        }

        $prestamo->estatus = 'Retirado';
        $prestamo->save();

        PrestamoActividad::log($id, 'estatus',
            'Préstamo cancelado por ' . Auth::user()->usuario . '.',
            ['de' => 'Pendiente', 'a' => 'Retirado']
        );

        return redirect()->route('prestamos.show', $id)->with('success', 'Préstamo cancelado correctamente.');
    }

    public function toggleInteres($id)
    {
        $adminId  = auth()->user()->adminId();
        $prestamo = Prestamo::where('id', $id)->where('admin_id', $adminId)->firstOrFail();
        $prestamo->interes_activo = !$prestamo->interes_activo;
        $prestamo->save();

        $estado = $prestamo->interes_activo ? 'activado' : 'pausado';
        PrestamoActividad::log($id, 'configuracion',
            'Interés ' . $estado . ' por ' . Auth::user()->usuario . '.',
            ['interes_activo' => $prestamo->interes_activo]
        );

        return redirect()->route('prestamos.show', $id)->with('success', 'Interés ' . $estado . '.');
    }

    public function toggleMora($id)
    {
        $adminId  = auth()->user()->adminId();
        $prestamo = Prestamo::where('id', $id)->where('admin_id', $adminId)->firstOrFail();
        $prestamo->interes_mora_activo = !$prestamo->interes_mora_activo;

        // When activating, record today as the start date for daily accumulation
        if ($prestamo->interes_mora_activo && !$prestamo->fecha_ultimo_interes) {
            $prestamo->fecha_ultimo_interes = now()->toDateString();
        }

        $prestamo->save();

        $estadoMora = $prestamo->interes_mora_activo ? 'activado' : 'desactivado';
        PrestamoActividad::log($id, 'configuracion',
            'Interés por mora ' . $estadoMora . ' por ' . Auth::user()->usuario . '.',
            ['mora_activo' => $prestamo->interes_mora_activo, 'interes_diario' => $prestamo->interes_diario]
        );

        return redirect()->route('prestamos.show', $id)->with('success', 'Interés por mora ' . $estadoMora . '.');
    }

    /**
     * Admin / Promo: update the payment frequency and recalculate future pending dates.
     */
    public function actualizarFrecuencia(Request $request, $id)
    {
        $request->validate([
            'frecuencia'      => 'required|in:Diario,Semanal,Quincenal,Mensual',
            'fecha_nuevo_inicio' => 'required|date',
        ]);

        $adminId  = auth()->user()->adminId();
        $prestamo = Prestamo::where('id', $id)->where('admin_id', $adminId)->firstOrFail();

        $pagosPendientes = Pago::where('prestamo_id', $id)
            ->whereIn('estatus', ['Pendiente', 'Atrasado'])
            ->where('tipo_pago', 'plan')
            ->orderBy('numero_pago')
            ->get();

        if ($pagosPendientes->isEmpty()) {
            return redirect()->back()->with('error', 'No hay pagos del plan pendientes para reprogramar.');
        }

        $diasMap = ['Diario' => 1, 'Semanal' => 7, 'Quincenal' => 14, 'Mensual' => 30];
        $dias    = $diasMap[$request->frecuencia] ?? 7;
        $fecha   = Carbon::parse($request->fecha_nuevo_inicio);

        foreach ($pagosPendientes as $i => $pago) {
            if ($request->frecuencia === 'Mensual') {
                $pago->fecha_programada = Carbon::parse($request->fecha_nuevo_inicio)->addMonths($i)->toDateString();
            } else {
                $pago->fecha_programada = Carbon::parse($request->fecha_nuevo_inicio)->addDays($dias * $i)->toDateString();
            }
            $pago->save();
        }

        $frecAnterior     = $prestamo->frecuencia;
        $prestamo->frecuencia = $request->frecuencia;
        $prestamo->save();

        PrestamoActividad::log($id, 'configuracion',
            'Frecuencia cambiada de ' . $frecAnterior . ' a ' . $request->frecuencia .
            ' por ' . Auth::user()->usuario . '. ' . $pagosPendientes->count() . ' pagos reprogramados desde ' .
            Carbon::parse($request->fecha_nuevo_inicio)->format('d/m/Y') . '.',
            ['de' => $frecAnterior, 'a' => $request->frecuencia, 'desde' => $request->fecha_nuevo_inicio, 'pagos' => $pagosPendientes->count()]
        );

        return redirect()->back()->with('success', 'Frecuencia actualizada a ' . $request->frecuencia . '. ' . $pagosPendientes->count() . ' pagos reprogramados.');
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

        // Cuota en pesos enteros; último pago absorbe el residuo de centavos
        $cuota_base  = $num_pagos > 1 ? (float) floor($monto_retornar / $num_pagos) : $monto_retornar;
        $ultimo_pago = $num_pagos > 1 ? round($monto_retornar - $cuota_base * ($num_pagos - 1), 2) : $monto_retornar;

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
