<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Prestamo;
use App\Models\Pago;
use App\Models\Cliente;
use App\Models\Empleado;
use App\Models\PrestamoActividad;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\PrestamoArchivo;
use Illuminate\Validation\Rule;
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

        // Map: client_id => ['promotor' => nombre, 'id' => prestamo_id] — mismo admin, préstamo activo
        $clientesConPrestamo = Cache::remember("clientes_con_prestamo_{$adminId}", 60, fn() =>
            Prestamo::whereIn('cliente_id', $clientes->pluck('id'))
                ->where('admin_id', $adminId)
                ->whereIn('estatus', ['Activo', 'Atrasado', 'Pendiente'])
                ->with('promotor')
                ->get()
                // Self-heal: préstamos ya liquidados que quedaron con estatus activo
                // se finalizan aquí y no aparecen como "préstamo activo a refinanciar"
                ->reject(fn($p) => $p->finalizarSiLiquidado())
                ->keyBy('cliente_id')
                ->map(fn($p) => [
                    'promotor' => $p->promotor?->nombre ?? 'otro promotor',
                    'id'       => $p->id,
                ])
                ->toArray()
        );

        // Map: client_id => {ine, comprobante} — to skip re-uploading docs for existing clients
        $clientesConDocs = Prestamo::where('admin_id', $adminId)
            ->where(fn($q) => $q->whereNotNull('doc_ine')->orWhereNotNull('doc_comprobante'))
            ->get(['cliente_id', 'doc_ine', 'doc_comprobante'])
            ->groupBy('cliente_id')
            ->map(fn($loans) => [
                'ine'         => $loans->whereNotNull('doc_ine')->isNotEmpty(),
                'comprobante' => $loans->whereNotNull('doc_comprobante')->isNotEmpty(),
            ])
            ->toArray();

        // Map: client_id => admin_nombre for clients whose CURP has an active loan with another admin
        // Map: client_id => admin_nombre for cross-admin active loans (wrapped in try/catch for safety)
        $clientesConPrestamoCruzado = [];
        try {
            $curpMap = $clientes->whereNotNull('curp')->pluck('curp', 'id')->toArray(); // id => curp
            if (!empty($curpMap)) {
                $activosCruzados = Prestamo::whereIn('estatus', ['Activo', 'Atrasado', 'Pendiente'])
                    ->whereHas('cliente', function ($q) use ($adminId, $curpMap) {
                        $q->whereIn('curp', array_values($curpMap))->where('admin_id', '!=', $adminId);
                    })
                    ->with(['cliente' => fn($q) => $q->select('id', 'curp', 'admin_id')])
                    ->get();

                if ($activosCruzados->isNotEmpty()) {
                    $adminIds     = $activosCruzados->map(fn($p) => $p->cliente?->admin_id)->filter()->unique()->toArray();
                    $adminNombres = \App\Models\User::whereIn('id', $adminIds)->pluck('nombre', 'id');

                    $curpToAdmin = [];
                    foreach ($activosCruzados as $p) {
                        $curp = $p->cliente?->curp;
                        if ($curp && !isset($curpToAdmin[$curp])) {
                            $curpToAdmin[$curp] = $adminNombres[$p->cliente->admin_id] ?? 'otro administrador';
                        }
                    }
                    foreach ($curpMap as $clienteId => $curp) {
                        if (isset($curpToAdmin[$curp])) {
                            $clientesConPrestamoCruzado[$clienteId] = $curpToAdmin[$curp];
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // Si falla la verificación cruzada, no bloqueamos la página
            $clientesConPrestamoCruzado = [];
        }

        return view('admin.prestamo_nuevo', compact('clientes', 'clientesConPrestamo', 'clientesConDocs', 'clientesConPrestamoCruzado'));
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
            // Allow up to 1 year in the past to support offline sync
            'fecha_inicio'        => 'required|date|after_or_equal:' . now()->subYear()->toDateString(),
            'fecha_primer_cobro'  => 'required|date|after_or_equal:' . now()->subYear()->toDateString(),
            // Scoped al tenant: impide asignar un promotor de OTRO admin (IDOR cross-tenant)
            'promotor_id'         => ['nullable', Rule::exists('empleados', 'id')->where('admin_id', auth()->user()->adminId())],
        ];

        if ($desembolsar) {
            // INE y comprobante son opcionales si el cliente ya los tiene en un préstamo anterior
            $clienteId        = (int)$request->input('cliente_id');
            $tieneIne         = $clienteId && Prestamo::where('cliente_id', $clienteId)->whereNotNull('doc_ine')->exists();
            $tieneComprobante = $clienteId && Prestamo::where('cliente_id', $clienteId)->whereNotNull('doc_comprobante')->exists();

            $rules += [
                'doc_ine'           => ($tieneIne         ? 'nullable' : 'required') . '|file|mimes:jpg,jpeg,png,pdf|max:10240',
                'doc_pagare'        => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
                'doc_comprobante'   => ($tieneComprobante ? 'nullable' : 'required') . '|file|mimes:jpg,jpeg,png,pdf|max:10240',
                'doc_foto_domicilio'=> 'nullable|file|mimes:jpg,jpeg,png|max:10240',
            ];
        }

        $data = $request->validate($rules);

        $user     = Auth::user();
        $empleado = $user->empleado;
        $isAdmin  = in_array('admin', $user->getAllRoles());

        // ── Warn / Redirect: mismo admin ya tiene préstamo activo → refinanciamiento ─────────
        $prestamoActivoMismo = Prestamo::where('cliente_id', $data['cliente_id'])
            ->where('admin_id', auth()->user()->adminId())
            ->whereIn('estatus', ['Activo', 'Atrasado', 'Pendiente'])
            ->with('promotor')
            ->first();

        // Self-heal: si el préstamo "activo" en realidad ya está liquidado,
        // finalizarlo y no mostrar la advertencia de refinanciamiento
        if ($prestamoActivoMismo && $prestamoActivoMismo->finalizarSiLiquidado()) {
            $prestamoActivoMismo = null;
        }

        if ($prestamoActivoMismo) {
            if ($request->input('confirmar_mismo_admin')) {
                // Admin confirmó → llevarlo al préstamo activo para refinanciar
                return redirect()->route('prestamos.show', $prestamoActivoMismo->id)
                    ->with('abrir_refinanciar', true);
            }
            $promotorNombre = $prestamoActivoMismo->promotor?->nombre ?? 'otro promotor';
            return redirect()->back()
                ->withInput()
                ->with('warning_mismo_admin', $promotorNombre)
                ->with('warning_mismo_admin_id', $prestamoActivoMismo->id);
        }
        // ─────────────────────────────────────────────────────────────────────────────────────

        // Warn (not block): Active loan with a DIFFERENT admin — record for flash, never redirect back
        $warningCruzado = null;
        try {
            $clienteX = Cliente::find($data['cliente_id']);
            if ($clienteX && $clienteX->curp) {
                $prestamoCruzado = Prestamo::whereHas('cliente', function ($q) use ($clienteX) {
                    $q->where('curp', $clienteX->curp)->where('admin_id', '!=', $clienteX->admin_id);
                })->whereIn('estatus', ['Activo', 'Atrasado', 'Pendiente'])->first();

                if ($prestamoCruzado) {
                    $adminUser      = \App\Models\User::find($prestamoCruzado->admin_id);
                    $warningCruzado = $adminUser?->nombre ?? $adminUser?->usuario ?? 'otro administrador';
                }
            }
        } catch (\Throwable $e) {
            // Si falla la verificación cruzada, continuamos sin bloquear
        }

        $monto_entregado    = (float)$data['monto_entregado'];
        $monto_retornar     = (float)$data['monto_retornar'];
        $num_pagos          = (int)$data['num_pagos'];
        $frecuencia         = $data['frecuencia'];
        $fecha_inicio       = $data['fecha_inicio'];
        $fecha_primer_cobro = $data['fecha_primer_cobro'];

        $dias_map = ['Diario' => 1, 'Semanal' => 7, 'Quincenal' => 14, 'Mensual' => 30];
        $dias = $dias_map[$frecuencia];

        // Cuota redondeada al múltiplo de $5 más cercano; el último pago absorbe el residuo
        $cuota_base  = $num_pagos > 1 ? (float)((int) round($monto_retornar / $num_pagos / 5) * 5) : $monto_retornar;
        $ultimo_pago = $num_pagos > 1 ? round($monto_retornar - $cuota_base * ($num_pagos - 1), 2) : $monto_retornar;

        $cliente = Cliente::where('id', $data['cliente_id'])->where('admin_id', auth()->user()->adminId())->firstOrFail();

        // Admin: no usar su propio registro de empleado como promotor —
        // usar el promotor del cliente, o el seleccionado en el form
        if ($isAdmin) {
            $promotor_id = $request->input('promotor_id') ?: $cliente->promotor_id;
        } else {
            // Promo: asignarse a sí mismo como promotor
            $promotor_id = $empleado?->id ?? $cliente->promotor_id;
        }

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

        $adminId = auth()->user()->adminId();
        Cache::forget("dashboard_kpis_{$adminId}");
        Cache::forget("dashboard_prestamos_{$adminId}");
        Cache::forget("clientes_con_prestamo_{$adminId}");

        $msg      = $desembolsar ? 'Préstamo creado y desembolsado correctamente.' : 'Préstamo creado correctamente.';
        $redirect = redirect()->route('prestamos.show', $prestamo->id)->with('success', $msg);
        if ($warningCruzado) {
            $redirect = $redirect->with('warning', "⚠ Este cliente ya tiene un préstamo activo con el administrador \"{$warningCruzado}\".");
        }
        return $redirect;
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

        // Self-heal: finalizar préstamos ya liquidados que quedaron con estatus activo
        $prestamo->finalizarSiLiquidado();

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
        $archivos    = PrestamoArchivo::where('prestamo_id', $id)
                           ->with('subidoPor')
                           ->orderByDesc('created_at')
                           ->get();

        return view('admin.prestamo_detalle', compact('prestamo', 'pagos', 'interesInfo', 'actividad', 'archivos'));
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

        // El estatus NO es editable — se actualiza automáticamente por el sistema
        // Empleados scoped al tenant: impide enlazar personal de OTRO admin (IDOR cross-tenant)
        $data = $request->validate([
            'cobrador_id'     => ['nullable', Rule::exists('empleados', 'id')->where('admin_id', $adminId)],
            'promotor_id'     => ['nullable', Rule::exists('empleados', 'id')->where('admin_id', $adminId)],
            'desembolso_id'   => ['nullable', Rule::exists('empleados', 'id')->where('admin_id', $adminId)],
            'interes_diario'  => 'nullable|numeric|min:0',
        ]);

        $update = [
            'cobrador_id'   => $data['cobrador_id']   ?? null,
            'promotor_id'   => $data['promotor_id']   ?? null,
            'desembolso_id' => $data['desembolso_id'] ?? null,
        ];
        if (isset($data['interes_diario'])) {
            $update['interes_diario'] = (float)$data['interes_diario'];
        }
        $estatusAnterior   = $prestamo->estatus;
        $cobradorAnterior  = $prestamo->cobrador_id;
        $promotorAnterior  = $prestamo->promotor_id;
        $prestamo->update($update);

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
            // Activo / Atrasado — saldos pendientes por componente
            $data = $request->validate([
                'principal_pendiente' => 'required|numeric|min:0',
                'interes_pendiente'   => 'required|numeric|min:0',
                'interes_acumulado'   => 'required|numeric|min:0',
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
            // Cuota redondeada al múltiplo de $5 más cercano; el último pago absorbe el residuo
            $cuotaBase    = $numPagos > 1 ? (float)((int) round($monto / $numPagos / 5) * 5) : $monto;
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
            // ── Activo / Atrasado: saldos por componente ──────────────────
            $principalPendiente = round((float)$data['principal_pendiente'], 2);
            $interesPendiente   = round((float)$data['interes_pendiente'], 2);
            $moraPendiente      = round((float)$data['interes_acumulado'], 2);

            // saldo_actual = principal + interés (mora es aparte)
            $nuevoSaldo = round($principalPendiente + $interesPendiente, 2);

            if (round($prestamo->saldo_actual, 2) !== $nuevoSaldo)
                $cambios[] = 'Saldo pendiente: $' . number_format($prestamo->saldo_actual, 2) . ' → $' . number_format($nuevoSaldo, 2) . ' (principal $' . number_format($principalPendiente, 2) . ' + interés $' . number_format($interesPendiente, 2) . ')';

            $prestamo->saldo_actual = $nuevoSaldo;

            // ── Sincronizar monto y monto_entregado con los nuevos saldos ─
            // Calculamos cuánto ya fue cobrado (plan-based) para derivar los nuevos totales acordados.
            // Así: monto_entregado - cobradoCapital = principalPendiente
            //       (monto - monto_entregado) - cobradoInteres = interesPendiente
            $pagosYaCobrados = Pago::where('prestamo_id', $id)
                ->whereIn('estatus', ['Pagado', 'Parcial'])
                ->get()
                ->filter(fn($p) => !in_array($p->tipo_pago ?? 'plan', ['congelado', 'liquidado']));

            $capitalYaCobrado  = round($pagosYaCobrados->sum('capital'), 2);
            $interesYaCobrado  = round($pagosYaCobrados->sum('interes'), 2);

            $nuevoMtoEntregado = round($principalPendiente + $capitalYaCobrado, 2);
            $nuevoMto          = round($nuevoMtoEntregado + $interesPendiente + $interesYaCobrado, 2);

            $prestamo->monto_entregado = $nuevoMtoEntregado;
            $prestamo->monto           = $nuevoMto;

            // ── Redistribuir interés pendiente en los pagos pendientes ────
            $pendingPagos  = Pago::where('prestamo_id', $id)
                ->whereIn('estatus', ['Pendiente', 'Atrasado'])
                ->orderBy('numero_pago')
                ->get();

            $interesActual = round($pendingPagos->sum('interes'), 2);

            if ($pendingPagos->count() > 0 && $interesActual !== $interesPendiente) {
                $cambios[] = 'Interés pendiente: $' . number_format($interesActual, 2) . ' → $' . number_format($interesPendiente, 2);

                $n        = $pendingPagos->count();
                $asignado = 0.0;
                $saldoBase = $principalPendiente;

                foreach ($pendingPagos as $idx => $pago) {
                    $isLast  = ($idx === $n - 1);
                    $intPago = $isLast
                        ? round($interesPendiente - $asignado, 2)
                        : floor($interesPendiente / $n * 100) / 100;

                    $intPago   = max(0, min($intPago, $pago->monto_cuota));
                    $capPago   = round($pago->monto_cuota - $intPago, 2);
                    $saldoBase = max(0, round($saldoBase - $capPago, 2));

                    $pago->interes        = $intPago;
                    $pago->capital        = $capPago;
                    $pago->saldo_restante = $saldoBase;
                    $pago->save();

                    $asignado += $intPago;
                }
            }

            // ── Mora ──────────────────────────────────────────────────────
            if (round($prestamo->interes_acumulado, 2) !== $moraPendiente)
                $cambios[] = 'Mora: $' . number_format($prestamo->interes_acumulado, 2) . ' → $' . number_format($moraPendiente, 2);
            $prestamo->interes_acumulado = $moraPendiente;

            // ── Auto-finalizar si los tres componentes son 0 ─────────────
            if ($principalPendiente <= 0 && $interesPendiente <= 0 && $moraPendiente <= 0) {
                $prestamo->estatus             = 'Finalizado';
                $prestamo->saldo_actual        = 0;
                $prestamo->interes_acumulado   = 0;
                $prestamo->interes_activo      = false;
                $prestamo->interes_mora_activo = false;
                $prestamo->interes_diario      = 0;

                // Marcar todos los pagos pendientes como liquidados
                Pago::where('prestamo_id', $id)
                    ->whereIn('estatus', ['Pendiente', 'Atrasado'])
                    ->update([
                        'estatus'       => 'Pagado',
                        'monto_cobrado' => 0,
                        'tipo_cobro'    => 'completo',
                        'tipo_pago'     => 'liquidado',
                        'fecha_pago'    => now()->toDateString(),
                        'nota_cobro'    => 'Liquidado por ajuste manual de saldos',
                    ]);

                $cambios[] = '✓ Préstamo finalizado por ajuste de saldos a $0.';
            }
        }

        // Mora para préstamos Pendientes (también editable en ese formulario)
        if ($esPendiente && isset($data['interes_acumulado'])) {
            if (round($prestamo->interes_acumulado, 2) !== round((float)$data['interes_acumulado'], 2))
                $cambios[] = 'Mora: $' . number_format($prestamo->interes_acumulado, 2) . ' → $' . number_format($data['interes_acumulado'], 2);
            $prestamo->interes_acumulado = round((float)$data['interes_acumulado'], 2);
        }

        $prestamo->save();

        if (!empty($cambios)) {
            PrestamoActividad::log($id, 'ajuste',
                'Campos editados por ' . Auth::user()->usuario . ': ' . implode('; ', $cambios) . '.',
                ['cambios' => $cambios]
            );
        }

        return redirect()->route('prestamos.show', $id)->with('success', 'Saldos actualizados correctamente.');
    }

    /**
     * Refinance an active/atrasado loan.
     *   capital (new) = remaining debt + nuevo_efectivo
     *   monto   (new) = capital × (1 + rentabilidad/100)
     * Saves the signed pagaré document. Promotor/cobrador are inherited from the old loan.
     */
    public function refinanciar(Request $request, $id)
    {
        $adminId  = auth()->user()->adminId();
        $prestamo = Prestamo::where('id', $id)->where('admin_id', $adminId)->firstOrFail();

        if (!in_array($prestamo->estatus, ['Activo', 'Atrasado'])) {
            return redirect()->route('prestamos.show', $id)
                ->with('error', 'Solo se pueden refinanciar préstamos activos o atrasados.');
        }

        $data = $request->validate([
            'nuevo_efectivo'     => 'required|numeric|min:0',
            'rentabilidad'       => 'required|numeric|min:0',
            'num_pagos'          => 'required|integer|min:1',
            'frecuencia'         => 'required|in:Diario,Semanal,Quincenal,Mensual',
            'fecha_inicio'       => 'required|date',
            'fecha_primer_cobro' => 'required|date',
            'doc_pagare'         => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        // ── Cálculo de montos (capital e interés separados) ─────────────
        // capital_viejo + capital_nuevo = nuevo monto_entregado
        // interes_viejo + rendimiento_nuevo = nuevo interés acordado
        // monto_total = monto_entregado + interés

        // Reconstruir la separación capital/interés del préstamo anterior
        // usando la misma lógica interés-primero que usa la vista
        $interesAcordadoAnterior = max(0, round((float)$prestamo->monto - (float)$prestamo->monto_entregado, 2));
        $interesPool = $interesAcordadoAnterior;
        $capCobrado  = 0.0;
        $intCobrado  = 0.0;
        $pagosHist = Pago::where('prestamo_id', $id)
            ->whereIn('estatus', ['Pagado', 'Parcial'])
            ->get()
            ->filter(fn($p) => !in_array($p->tipo_pago ?? 'plan', ['congelado', 'liquidado']))
            ->sortBy('fecha_pago');
        foreach ($pagosHist as $ph) {
            $cobrado = (float)($ph->monto_cobrado ?? 0);
            if ($cobrado > 0) {
                $intPag  = min($cobrado, max(0, $interesPool));
                $capPag  = $cobrado - $intPag;
                $interesPool -= $intPag;
                $capCobrado  += $capPag;
                $intCobrado  += $intPag;
            }
        }
        $capViejoPend  = round(max(0, (float)$prestamo->monto_entregado - $capCobrado), 2);
        $intViejoPend  = round(max(0, $interesAcordadoAnterior - $intCobrado) + (float)$prestamo->interes_acumulado, 2);

        $nuevoEfectivo    = round((float)$data['nuevo_efectivo'], 2);
        $rentabilidad     = (float)$data['rentabilidad'];
        $rendimientoMonto = round($nuevoEfectivo * $rentabilidad / 100, 2);

        // Nuevo préstamo: capital = cap_viejo + nuevo_efectivo
        //                 interés = int_viejo + rendimiento
        $montoEntregado = round($capViejoPend + $nuevoEfectivo, 2);
        $montoRetornar  = round($montoEntregado + $intViejoPend + $rendimientoMonto, 2);

        $deudaTotal = round($capViejoPend + $intViejoPend, 2); // para el log
        $numPagos       = (int)$data['num_pagos'];
        $frecuencia     = $data['frecuencia'];
        $fechaInicio    = $data['fecha_inicio'];
        $fechaPrimer    = $data['fecha_primer_cobro'];

        $diasMap    = ['Diario' => 1, 'Semanal' => 7, 'Quincenal' => 14, 'Mensual' => 30];
        $dias       = $diasMap[$frecuencia];
        $cuotaBase  = $numPagos > 1 ? (float)((int) round($montoRetornar / $numPagos / 5) * 5) : $montoRetornar;
        $ultimoPago = $numPagos > 1 ? round($montoRetornar - $cuotaBase * ($numPagos - 1), 2) : $montoRetornar;

        $user       = Auth::user();
        $promotorId = $prestamo->promotor_id;
        $cobradorId = $prestamo->cobrador_id;

        // Todo o nada: si falla la creación del nuevo préstamo, su plan de pagos
        // o el guardado del pagaré, el préstamo anterior NO debe quedar cerrado
        DB::beginTransaction();
        try {

        // ── 1. Cerrar préstamo anterior ──────────────────────────────────
        Pago::where('prestamo_id', $id)
            ->whereIn('estatus', ['Pendiente', 'Atrasado'])
            ->update([
                'estatus'       => 'Pagado',
                'monto_cobrado' => 0,
                'tipo_cobro'    => 'completo',
                'tipo_pago'     => 'liquidado',
                'fecha_pago'    => now()->toDateString(),
                'nota_cobro'    => 'Liquidado por refinanciamiento',
            ]);

        $prestamo->estatus             = 'Refinanciado';
        $prestamo->saldo_actual        = 0;
        $prestamo->interes_acumulado   = 0;
        $prestamo->interes_activo      = false;
        $prestamo->interes_mora_activo = false;
        $prestamo->interes_diario      = 0;
        $prestamo->skipStatusLog       = true; // log 'refinanciado' propio abajo; evita duplicado del hook
        $prestamo->save();

        PrestamoActividad::log($id, 'refinanciado',
            'Préstamo refinanciado por ' . $user->usuario . '. ' .
            'Deuda trasladada: $' . number_format($deudaTotal, 2) .
            ($nuevoEfectivo > 0 ? ' + $' . number_format($nuevoEfectivo, 2) . ' nuevo efectivo.' : '.'),
            ['deuda_trasladada' => $deudaTotal, 'nuevo_efectivo' => $nuevoEfectivo]
        );

        // ── 2. Crear nuevo préstamo (ya desembolsado) ────────────────────
        $nuevoPrestamo = Prestamo::create([
            'admin_id'            => $adminId,
            'cliente_id'          => $prestamo->cliente_id,
            'promotor_id'         => $promotorId,
            'cobrador_id'         => $cobradorId,
            'monto'               => $montoRetornar,
            'tasa_diaria'         => 0,
            'num_pagos'           => $numPagos,
            'frecuencia'          => $frecuencia,
            'cuota'               => $cuotaBase,
            'saldo_actual'        => $montoRetornar,
            'interes_acumulado'   => 0,
            'interes_activo'      => false,
            'interes_diario'      => 0,
            'interes_mora_activo' => false,
            'fecha_inicio'        => $fechaInicio,
            'fecha_fin'           => $frecuencia === 'Mensual'
                ? Carbon::parse($fechaPrimer)->addMonths($numPagos - 1)->toDateString()
                : Carbon::parse($fechaPrimer)->addDays($dias * ($numPagos - 1))->toDateString(),
            'estatus'             => 'Activo',
            'monto_entregado'     => $montoEntregado,
            'forma_entrega'       => 'refinanciamiento',
            'fecha_entrega'       => now()->toDateString(),
            'nota_entrega'        => 'Refinanciamiento del préstamo #' . $id .
                '. Deuda anterior: $' . number_format($deudaTotal, 2) .
                ($nuevoEfectivo > 0 ? '. Nuevo efectivo: $' . number_format($nuevoEfectivo, 2) : '') . '.',
            'refinanciado_por'    => $id,
        ]);

        // ── 3. Generar plan de pagos (interés-primero) ───────────────────
        $interesRestante = round($montoRetornar - $montoEntregado, 2);
        $saldo           = $montoEntregado;

        for ($i = 1; $i <= $numPagos; $i++) {
            $fecha = $frecuencia === 'Mensual'
                ? Carbon::parse($fechaPrimer)->addMonths($i - 1)->toDateString()
                : Carbon::parse($fechaPrimer)->addDays($dias * ($i - 1))->toDateString();

            $cuota   = ($i === $numPagos) ? $ultimoPago : $cuotaBase;
            $interes = min($cuota, max(0.0, $interesRestante));
            $capital = round($cuota - $interes, 2);
            $interesRestante = max(0.0, round($interesRestante - $interes, 2));
            $saldo   = max(0.0, round($saldo - $capital, 2));

            Pago::create([
                'prestamo_id'      => $nuevoPrestamo->id,
                'cobrador_id'      => $cobradorId,
                'numero_pago'      => $i,
                'monto_cuota'      => $cuota,
                'interes'          => $interes,
                'capital'          => $capital,
                'saldo_restante'   => $saldo,
                'monto_cobrado'    => null,
                'tipo_cobro'       => null,
                'nota_cobro'       => null,
                'fecha_programada' => $fecha,
                'fecha_pago'       => null,
                'estatus'          => 'Pendiente',
            ]);
        }

        // ── 4. Guardar pagaré ────────────────────────────────────────────
        $file    = $request->file('doc_pagare');
        $carpeta = public_path('documentos/prestamo_' . $nuevoPrestamo->id);
        if (!file_exists($carpeta)) mkdir($carpeta, 0775, true);
        $nombre = 'pagare_' . time() . '_' . uniqid() . '.' . strtolower($file->getClientOriginalExtension());
        $file->move($carpeta, $nombre);
        $nuevoPrestamo->doc_pagare = 'documentos/prestamo_' . $nuevoPrestamo->id . '/' . $nombre;
        $nuevoPrestamo->save();

        PrestamoActividad::log($nuevoPrestamo->id, 'creado',
            'Préstamo creado por refinanciamiento del #' . $id . ' por ' . $user->usuario . '. ' .
            'Deuda traspasada: $' . number_format($deudaTotal, 2) .
            ($nuevoEfectivo > 0
                ? '. Nuevo efectivo: $' . number_format($nuevoEfectivo, 2) .
                  ' + rendimiento (' . $rentabilidad . '%): $' . number_format($rendimientoMonto, 2)
                : '') .
            '. Total a retornar: $' . number_format($montoRetornar, 2) . ' en ' . $numPagos . ' pagos.',
            ['prestamo_origen_id' => $id, 'deuda_anterior' => $deudaTotal,
             'nuevo_efectivo' => $nuevoEfectivo, 'rendimiento' => $rendimientoMonto,
             'rentabilidad_pct' => $rentabilidad]
        );

        DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return redirect()->route('prestamos.show', $id)
                ->with('error', 'No se pudo completar el refinanciamiento. No se realizó ningún cambio. Detalle: ' . $e->getMessage());
        }

        Cache::forget("dashboard_kpis_{$adminId}");
        Cache::forget("dashboard_prestamos_{$adminId}");
        Cache::forget("clientes_con_prestamo_{$adminId}");

        return redirect()->route('prestamos.show', $nuevoPrestamo->id)
            ->with('success', 'Refinanciamiento completado. Nuevo préstamo #' . $nuevoPrestamo->id . ' activo.');
    }

    /**
     * Cancel a pending loan (sets status to Retirado).
     */
    public function cancelar($id)
    {
        $user     = auth()->user();
        $adminId  = $user->adminId();
        $roles    = $user->getAllRoles();
        $isAdmin  = in_array('admin', $roles);
        $prestamo = Prestamo::where('id', $id)->where('admin_id', $adminId)->firstOrFail();

        if ($prestamo->estatus !== 'Pendiente') {
            return redirect()->back()->with('error', 'Solo se pueden cancelar préstamos en estado Pendiente.');
        }

        // Promotor solo puede cancelar sus propios préstamos
        if (!$isAdmin && in_array('promo', $roles)) {
            $empleado = $user->empleado;
            if (!$empleado || (int)$prestamo->promotor_id !== (int)$empleado->id) {
                return redirect()->back()->with('error', 'Solo puedes cancelar préstamos que tú mismo creaste.');
            }
        }

        $prestamo->estatus       = 'Retirado';
        $prestamo->skipStatusLog = true; // mensaje propio abajo; evita duplicado del hook
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

    public function subirArchivo(Request $request, $id)
    {
        $adminId  = auth()->user()->adminId();
        $prestamo = Prestamo::where('id', $id)->where('admin_id', $adminId)->firstOrFail();

        $request->validate([
            'archivo' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ], [
            'archivo.mimes' => 'Solo se permiten archivos PDF, JPG, JPEG o PNG.',
            'archivo.max'   => 'El archivo no puede superar 10 MB.',
        ]);

        $file    = $request->file('archivo');
        $carpeta = public_path('documentos/prestamo_' . $prestamo->id);
        if (!file_exists($carpeta)) mkdir($carpeta, 0775, true);

        $ext    = strtolower($file->getClientOriginalExtension());
        $nombre = 'arch_' . time() . '_' . uniqid() . '.' . $ext;
        $file->move($carpeta, $nombre);

        PrestamoArchivo::create([
            'prestamo_id'     => $prestamo->id,
            'subido_por'      => auth()->id(),
            'nombre_original' => $file->getClientOriginalName(),
            'ruta'            => 'documentos/prestamo_' . $prestamo->id . '/' . $nombre,
            'tipo_archivo'    => $ext,
            'tamano'          => $file->getSize(),
        ]);

        PrestamoActividad::log($prestamo->id, 'configuracion',
            'Archivo subido: ' . $file->getClientOriginalName() . ' por ' . Auth::user()->usuario . '.',
            ['archivo' => $file->getClientOriginalName()]
        );

        return redirect()->route('prestamos.show', $id)->with('success', 'Archivo subido correctamente.');
    }

    public function eliminarArchivo($id, $archivoId)
    {
        $adminId  = auth()->user()->adminId();
        $prestamo = Prestamo::where('id', $id)->where('admin_id', $adminId)->firstOrFail();
        $archivo  = PrestamoArchivo::where('id', $archivoId)->where('prestamo_id', $id)->firstOrFail();

        $ruta = public_path($archivo->ruta);
        if (file_exists($ruta)) unlink($ruta);

        $nombreOriginal = $archivo->nombre_original;
        $archivo->delete();

        PrestamoActividad::log($prestamo->id, 'configuracion',
            'Archivo eliminado: ' . $nombreOriginal . ' por ' . Auth::user()->usuario . '.',
            ['archivo' => $nombreOriginal]
        );

        return redirect()->route('prestamos.show', $id)->with('success', 'Archivo eliminado.');
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

        // Cuota redondeada al múltiplo de $5 más cercano; el último pago absorbe el residuo
        $cuota_base  = $num_pagos > 1 ? (float)((int) round($monto_retornar / $num_pagos / 5) * 5) : $monto_retornar;
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
