<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cliente;
use App\Models\Empleado;
use App\Models\Prestamo;
use App\Models\Pago;
use App\Models\PrestamoActividad;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Support\PaymentSchedule;

class ClienteController extends Controller
{
    public function index()
    {
        $user   = Auth::user();
        $puesto = $user->puesto;

        $adminId = $user->adminId();

        $query = Cliente::with(['promotor', 'prestamos.pagos'])
            ->where('admin_id', $adminId);

        if (in_array('promo', $user->getAllRoles()) && !in_array('admin', $user->getAllRoles())) {
            $empleado = $user->empleado;
            if ($empleado) {
                $query->where('promotor_id', $empleado->id);
            }
        }

        $clientes = $query->where('activo', true)->orderBy('nombre')->get();

        $promotores = Empleado::whereJsonContains('roles', 'promo')
            ->where('admin_id', $adminId)
            ->where('activo', true)
            ->get();

        return view('admin.clientes', compact('clientes', 'promotores', 'puesto'));
    }

    public function create()
    {
        $adminId    = auth()->user()->adminId();
        $promotores = Empleado::whereJsonContains('roles', 'promo')
            ->where('admin_id', $adminId)
            ->where('activo', true)
            ->get();
        return view('admin.cliente_crear', compact('promotores'));
    }

    public function store(Request $request)
    {
        $user   = Auth::user();
        $isAdmin = in_array('admin', $user->getAllRoles());
        $isPromo = in_array('promo', $user->getAllRoles()) && !$isAdmin;

        $adminId = auth()->user()->adminId();

        $data = $request->validate([
            'nombre'      => 'required|string|max:255',
            'celular'     => 'nullable|string|max:20',
            'email'       => 'nullable|email|max:255',
            'curp'        => ['nullable', 'string', 'max:18',
                                Rule::unique('clientes', 'curp')->where('admin_id', $adminId)],
            'direccion'   => 'nullable|string|max:500',
            'latitud'     => 'nullable|numeric',
            'longitud'    => 'nullable|numeric',
            'ocupacion'   => 'nullable|in:Empleado,Negocio propio,Independiente,Otro',
            'promotor_id' => 'nullable|exists:empleados,id',
        ], [
            'curp.unique' => 'Este CURP ya está registrado para uno de tus clientes.',
        ]);

        // If promo, assign to themselves
        if ($isPromo) {
            $empleado = $user->empleado;
            if (!$empleado) {
                return redirect()->back()->withErrors(['promotor_id' => 'Tu cuenta no tiene un perfil de empleado asociado. Contacta al administrador.']);
            }
            $data['promotor_id'] = $empleado->id;
        }

        $data['activo']   = true;
        $data['admin_id'] = $adminId;

        $cliente = Cliente::create($data);

        // Inform if this CURP is already registered by another admin
        $avisoOtroAdmin = '';
        if (!empty($data['curp'])) {
            $otroAdmin = Cliente::where('curp', $data['curp'])
                ->where('admin_id', '!=', $adminId)
                ->exists();
            if ($otroAdmin) {
                $avisoOtroAdmin = ' Nota: este cliente ya existe con otro administrador. Verifica que no tenga un préstamo activo antes de otorgar uno nuevo.';
            }
        }

        return redirect()->route('clientes.index')
            ->with('success', 'Cliente registrado correctamente.' . $avisoOtroAdmin);
    }

    public function show($id)
    {
        $adminId = auth()->user()->adminId();
        $cliente = Cliente::with('promotor')
            ->where('id', $id)
            ->where('admin_id', $adminId)
            ->firstOrFail();

        $prestamos = Prestamo::with(['pagos', 'promotor'])
            ->where('cliente_id', $id)
            ->orderByDesc('id')
            ->get()
            ->map(function ($loan) {
                $loan->pagos = $loan->pagos->map(function ($p) {
                    // Calculate dias_diff
                    if (in_array($p->estatus, ['Pagado', 'Parcial']) && $p->fecha_pago && $p->fecha_programada) {
                        $programada = strtotime($p->fecha_programada);
                        $realPago   = strtotime($p->fecha_pago);
                        $p->dias_diff = (int)(($realPago - $programada) / 86400);
                    } else {
                        $p->dias_diff = null;
                    }
                    return $p;
                });
                return $loan;
            });

        $puesto = Auth::user()->puesto;

        return view('admin.cliente_detalle', compact('cliente', 'prestamos', 'puesto'));
    }

    public function edit($id)
    {
        $adminId    = auth()->user()->adminId();
        $cliente    = Cliente::where('id', $id)->where('admin_id', $adminId)->firstOrFail();
        $promotores = Empleado::whereJsonContains('roles', 'promo')
            ->where('admin_id', $adminId)
            ->where('activo', true)
            ->get();

        return view('admin.cliente_editar', compact('cliente', 'promotores'));
    }

    public function update(Request $request, $id)
    {
        $adminId = auth()->user()->adminId();
        $cliente = Cliente::where('id', $id)->where('admin_id', $adminId)->firstOrFail();

        $data = $request->validate([
            'nombre'      => 'required|string|max:255',
            'celular'     => 'nullable|string|max:20',
            'email'       => 'nullable|email|max:255',
            'curp'        => ['nullable', 'string', 'max:18',
                                Rule::unique('clientes', 'curp')->where('admin_id', $adminId)->ignore($id)],
            'direccion'   => 'nullable|string|max:500',
            'latitud'     => 'nullable|numeric',
            'longitud'    => 'nullable|numeric',
            'ocupacion'   => 'nullable|in:Empleado,Negocio propio,Independiente,Otro',
            'promotor_id' => 'nullable|exists:empleados,id',
        ], [
            'curp.unique' => 'Este CURP ya está registrado para otro cliente tuyo.',
        ]);

        $cliente->update($data);

        return redirect()->route('clientes.show', $id)->with('success', 'Cliente actualizado correctamente.');
    }

    public function showCobrador($id)
    {
        // Collector view of a client (same as show but with back-link to cobros)
        return $this->show($id);
    }

    // ── Combined: create client + loan + disburse in one flow ─────────────────

    public function createWithPrestamo()
    {
        $adminId    = auth()->user()->adminId();
        $promotores = Empleado::whereJsonContains('roles', 'promo')
            ->where('admin_id', $adminId)
            ->where('activo', true)
            ->get();

        return view('admin.cliente_prestamo_nuevo', compact('promotores'));
    }

    public function storeWithPrestamo(Request $request)
    {
        $withPrestamo = $request->boolean('with_prestamo');
        $desembolsar  = $request->boolean('desembolsar');

        // ── Validation ──────────────────────────────────────────────────────
        $adminId = auth()->user()->adminId();

        $rules = [
            'nombre'      => 'required|string|max:255',
            'celular'     => 'nullable|string|max:20',
            'email'       => 'nullable|email|max:255',
            'curp'        => ['nullable', 'string', 'max:18',
                                Rule::unique('clientes', 'curp')->where('admin_id', $adminId)],
            'ocupacion'   => 'nullable|in:Empleado,Negocio propio,Independiente,Otro',
            'direccion'   => 'nullable|string|max:500',
            'latitud'     => 'nullable|numeric',
            'longitud'    => 'nullable|numeric',
            'promotor_id' => 'nullable|exists:empleados,id',
        ];

        if ($withPrestamo) {
            $rules += [
                'monto_entregado'    => 'required|numeric|min:1',
                'monto_retornar'     => 'required|numeric|min:1',
                'num_pagos'          => 'required|integer|min:1',
                'frecuencia'         => 'required|in:Diario,Semanal,Quincenal,Mensual',
                'descanso_domingos'  => 'nullable|boolean',
                'fecha_inicio'        => 'required|date|after_or_equal:' . now()->subYear()->toDateString(),
                'fecha_primer_cobro'  => 'required|date|after_or_equal:' . now()->subYear()->toDateString(),
            ];
            if ($desembolsar) {
                $rules += [
                    'forma_entrega'      => 'nullable|string|max:50',
                    'fecha_entrega'      => 'nullable|date',
                    'nota_entrega'       => 'nullable|string|max:1000',
                    'doc_ine'            => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
                    'doc_pagare'         => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
                    'doc_comprobante'    => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
                    'doc_foto_domicilio' => 'nullable|file|mimes:jpg,jpeg,png|max:10240',
                ];
            }
        }

        $data = $request->validate($rules, [
            'curp.unique' => 'Este CURP ya está registrado para uno de tus clientes.',
        ]);

        // ── Create client ────────────────────────────────────────────────────
        $user     = Auth::user();
        $empleado = $user->empleado;

        $promotorId = $data['promotor_id'] ?? null;
        if ($user->puesto === 'promo' && $empleado) {
            $promotorId = $empleado->id;
        }

        $cliente = Cliente::create([
            'admin_id'    => $adminId,
            'promotor_id' => $promotorId,
            'nombre'      => $data['nombre'],
            'celular'     => $data['celular'] ?? null,
            'email'       => $data['email'] ?? null,
            'curp'        => $data['curp'] ?? null,
            'ocupacion'   => $data['ocupacion'] ?? null,
            'direccion'   => $data['direccion'] ?? null,
            'latitud'     => $data['latitud'] ?? null,
            'longitud'    => $data['longitud'] ?? null,
            'activo'      => true,
        ]);

        // Inform if CURP already exists with another admin
        $avisoOtroAdmin = '';
        if (!empty($cliente->curp)) {
            $otroAdmin = Cliente::where('curp', $cliente->curp)
                ->where('admin_id', '!=', $adminId)
                ->exists();
            if ($otroAdmin) {
                $avisoOtroAdmin = ' Nota: este cliente ya existe con otro administrador.';
            }
        }

        if (!$withPrestamo) {
            return redirect()->route('clientes.show', $cliente->id)
                ->with('success', 'Cliente registrado correctamente.' . $avisoOtroAdmin);
        }

        // ── Cross-admin active loan block ─────────────────────────────────────
        if (!empty($cliente->curp)) {
            $prestamoCruzado = Prestamo::whereHas('cliente', function ($q) use ($cliente, $adminId) {
                $q->where('curp', $cliente->curp)->where('admin_id', '!=', $adminId);
            })->whereIn('estatus', ['Activo', 'Atrasado', 'Pendiente'])->first();

            if ($prestamoCruzado) {
                $adminNombre = \App\Models\User::find($prestamoCruzado->admin_id)?->nombre
                    ?? \App\Models\User::find($prestamoCruzado->admin_id)?->usuario
                    ?? 'otro administrador';
                return redirect()->route('clientes.show', $cliente->id)
                    ->with('success', 'Cliente registrado correctamente.')
                    ->with('warning', "No se creó el préstamo: este cliente ya tiene un préstamo activo con el administrador \"{$adminNombre}\". La deuda debe ser pagada antes de otorgar uno nuevo.");
            }
        }

        // ── Create loan ──────────────────────────────────────────────────────
        $monto_entregado    = (float)$data['monto_entregado'];
        $monto_retornar     = (float)$data['monto_retornar'];
        $num_pagos          = (int)$data['num_pagos'];
        $frecuencia         = $data['frecuencia'];
        $fecha_inicio       = $data['fecha_inicio'];
        $fecha_primer_cobro = $data['fecha_primer_cobro'];
        $descansoDomingos   = $request->boolean('descanso_domingos');
        $fechasPago         = PaymentSchedule::buildDates($fecha_primer_cobro, $num_pagos, $frecuencia, $descansoDomingos);

        // Cuota redondeada al múltiplo de $5 más cercano; el último pago absorbe el residuo
        $cuota_base  = $num_pagos > 1 ? (float)((int) round($monto_retornar / $num_pagos / 5) * 5) : $monto_retornar;
        $ultimo_pago = $num_pagos > 1 ? round($monto_retornar - $cuota_base * ($num_pagos - 1), 2) : $monto_retornar;

        // Admin: usar el promotor del cliente (no el empleado del admin)
        $isAdmin     = in_array('admin', $user->getAllRoles());
        $promotor_id = $isAdmin
            ? $cliente->promotor_id
            : ($empleado?->id ?? $cliente->promotor_id);

        $estatus       = $desembolsar ? 'Activo' : 'Pendiente';
        $forma_entrega = $desembolsar ? ($data['forma_entrega'] ?? null) : null;
        $fecha_entrega = $desembolsar ? ($data['fecha_entrega'] ?? null) : null;
        $nota_entrega  = $desembolsar ? ($data['nota_entrega'] ?? null) : null;

        $prestamo = Prestamo::create([
            'admin_id'            => $adminId,
            'cliente_id'          => $cliente->id,
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
            'fecha_fin'           => $fechasPago[array_key_last($fechasPago)] ?? $fecha_primer_cobro,
            'estatus'             => $estatus,
            'monto_entregado'     => $monto_entregado,
            'forma_entrega'       => $forma_entrega,
            'fecha_entrega'       => $fecha_entrega,
            'nota_entrega'        => $nota_entrega,
            'descanso_domingos'   => $descansoDomingos,
        ]);

        // ── Save uploaded documents (only when desembolsar = true) ────────────
        // Guarda cualquier documento que venga — no requiere que todos estén presentes,
        // ya que el cliente podría tener INE/comprobante de un préstamo anterior.
        if ($desembolsar) {
            $hayArchivo = $request->hasFile('doc_ine')
                       || $request->hasFile('doc_pagare')
                       || $request->hasFile('doc_comprobante')
                       || $request->hasFile('doc_foto_domicilio');

            if ($hayArchivo) {
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
        }

        // ── Payment schedule (interest-first) ────────────────────────────────
        $interes_restante = round($monto_retornar - $monto_entregado, 2);
        $saldo            = $monto_entregado;

        for ($i = 1; $i <= $num_pagos; $i++) {
            $fecha_prog = $fechasPago[$i - 1];
            $cuota      = ($i === $num_pagos) ? $ultimo_pago : $cuota_base;
            $interes    = min($cuota, round($interes_restante, 2));
            $capital    = round($cuota - $interes, 2);
            $interes_restante = max(0, round($interes_restante - $interes, 2));
            $saldo            = max(0, round($saldo - $capital, 2));

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

        // ── Activity log ─────────────────────────────────────────────────────
        PrestamoActividad::log($prestamo->id, 'creado',
            'Préstamo creado con cliente nuevo ' . $cliente->nombre .
            ' por ' . $user->usuario .
            ' — $' . number_format($monto_entregado, 2) . ' entregados, $' .
            number_format($monto_retornar, 2) . ' a retornar en ' . $num_pagos .
            ' pagos ' . strtolower($frecuencia) . 's.',
            ['monto_entregado' => $monto_entregado, 'monto_retornar' => $monto_retornar,
             'num_pagos' => $num_pagos, 'frecuencia' => $frecuencia, 'descanso_domingos' => $descansoDomingos]
        );

        if ($desembolsar) {
            PrestamoActividad::log($prestamo->id, 'desembolso',
                'Desembolsado al momento del registro. Forma: ' . ($forma_entrega ?? 'no especificada'),
                ['forma_entrega' => $forma_entrega, 'fecha_entrega' => $fecha_entrega]
            );
        }

        $msg = 'Cliente y préstamo registrados' . ($desembolsar ? ' y desembolsados' : '') . ' correctamente.';
        return redirect()->route('prestamos.show', $prestamo->id)->with('success', $msg);
    }
}
