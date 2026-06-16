<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Empleado;
use App\Models\Prestamo;
use App\Models\Cliente;
use App\Models\AdminNota;
use App\Models\Pago;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class OwnerController extends Controller
{
    /**
     * Dashboard principal del owner: lista de todos los admins.
     */
    public function index()
    {
        // Admins: usuarios con puesto = 'admin' (excluye al owner)
        $admins = User::where('puesto', 'admin')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function (User $u) {

                $empleados = Empleado::where('admin_id', $u->id)
                    ->where('activo', true)
                    ->orderBy('nombre')
                    ->get();

                $clientes = Cliente::where('admin_id', $u->id)
                    ->where('activo', true)
                    ->orderBy('nombre')
                    ->get();

                // ALL prestamos (all statuses) for rich detail
                $allPrestamos = Prestamo::with('cliente')
                    ->where('admin_id', $u->id)
                    ->orderBy('created_at', 'desc')
                    ->get();

                $prestamos       = $allPrestamos->whereIn('estatus', ['Activo', 'Atrasado']);
                $porEstatus      = $allPrestamos->groupBy('estatus')->map->count();
                $deployedIds     = $allPrestamos->whereIn('estatus', ['Activo','Atrasado','Finalizado'])->pluck('id');
                $capitalDesplegado = $allPrestamos->whereIn('estatus', ['Activo','Atrasado','Finalizado'])->sum('monto_entregado');
                $totalAcordado     = $allPrestamos->whereIn('estatus', ['Activo','Atrasado','Finalizado'])->sum('monto');
                $totalCobrado      = $deployedIds->isNotEmpty()
                    ? (float) Pago::whereIn('prestamo_id', $deployedIds->all())
                        ->whereIn('estatus', ['Pagado','Parcial'])->sum('monto_cobrado')
                    : 0.0;
                $saldoPendiente  = $allPrestamos->whereIn('estatus', ['Activo','Atrasado'])->sum('saldo_actual');
                $moraPendiente   = $allPrestamos->whereIn('estatus', ['Activo','Atrasado'])->sum('interes_acumulado');
                $rendimientoPct  = $capitalDesplegado > 0
                    ? round(max(0, $totalCobrado - $capitalDesplegado) / $capitalDesplegado * 100, 1)
                    : 0;

                $u->stats = [
                    'empleados' => $empleados->count(),
                    'clientes'  => $clientes->count(),
                    'prestamos' => $prestamos->count(),
                ];

                $u->detalle = [
                    'empleados'          => $empleados,
                    'clientes'           => $clientes,
                    'prestamos'          => $allPrestamos,
                    'por_estatus'        => $porEstatus,
                    'capital_desplegado' => $capitalDesplegado,
                    'total_acordado'     => $totalAcordado,
                    'total_cobrado'      => $totalCobrado,
                    'saldo_pendiente'    => $saldoPendiente,
                    'mora_pendiente'     => $moraPendiente,
                    'rendimiento_pct'    => $rendimientoPct,
                ];

                $u->notas = AdminNota::where('admin_id', $u->id)
                    ->orderBy('created_at', 'desc')
                    ->get();

                return $u;
            });

        $totales = [
            'total'     => $admins->count(),
            'activos'   => $admins->where('activo', true)->count(),
            'inactivos' => $admins->where('activo', false)->count(),
        ];

        return view('owner.dashboard', compact('admins', 'totales'));
    }

    /**
     * Dashboard detallado de un administrador individual.
     */
    public function show(int $id)
    {
        $admin = User::where('id', $id)->where('puesto', 'admin')->firstOrFail();

        $deployedStatuses = ['Activo', 'Atrasado', 'Finalizado'];
        $activeStatuses   = ['Activo', 'Atrasado'];

        $allPrestamos = Prestamo::with(['cliente', 'promotor'])
            ->where('admin_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        $deployed   = $allPrestamos->filter(fn($p) => in_array($p->estatus, $deployedStatuses));
        $activos    = $allPrestamos->filter(fn($p) => in_array($p->estatus, $activeStatuses));
        $atrasados  = $allPrestamos->where('estatus', 'Atrasado');
        $finalizados = $allPrestamos->where('estatus', 'Finalizado');
        $pendientes = $allPrestamos->where('estatus', 'Pendiente');
        $retirados  = $allPrestamos->where('estatus', 'Retirado');

        $capitalDesplegado = (float) $deployed->sum('monto_entregado');
        $totalAcordado     = (float) $deployed->sum('monto');
        $interesEsperado   = max(0.0, round($totalAcordado - $capitalDesplegado, 2));
        $capitalPendiente  = (float) $activos->sum('saldo_actual');
        $moraPendiente     = (float) $activos->sum('interes_acumulado');
        $capitalRiesgo     = (float) $atrasados->sum('saldo_actual');

        $deployedIds = $deployed->pluck('id');
        $activeIds   = $activos->pluck('id');

        $totalCobrado = $deployedIds->isNotEmpty()
            ? (float) Pago::whereIn('prestamo_id', $deployedIds)
                ->whereIn('estatus', ['Pagado', 'Parcial'])->sum('monto_cobrado')
            : 0.0;

        // Capital recuperado: sumar 'capital' SOLO de filas que cobraron dinero real
        // (monto_cobrado > 0). Al liquidar un préstamo, sus cuotas 'plan' quedan en
        // estatus Pagado con monto_cobrado = 0 pero conservan su capital/interes
        // programado, y la fila 'extra' del cobro real ya carga ese mismo principal.
        // Sumar todas las filas Pagado duplicaría el capital (mismo criterio que el
        // cálculo de refinanciamiento, que excluye filas liquidadas/congeladas).
        $capitalRecuperado = $deployedIds->isNotEmpty()
            ? (float) Pago::whereIn('prestamo_id', $deployedIds)
                ->whereIn('estatus', ['Pagado', 'Parcial'])
                ->where('monto_cobrado', '>', 0)
                ->sum('capital')
            : 0.0;
        // Blindaje: nunca puede recuperarse más capital del que se prestó.
        $capitalRecuperado = min($capitalRecuperado, $capitalDesplegado);

        // Interés cobrado = todo lo cobrado por encima del capital recuperado
        // (interés ordinario + moratorio realmente cobrado). Es la ganancia realizada.
        $interesCobranzaReal = max(0.0, round($totalCobrado - $capitalRecuperado, 2));
        $gananciaNetaAprox   = $interesCobranzaReal;
        $roi = $capitalDesplegado > 0
            ? round($gananciaNetaAprox / $capitalDesplegado * 100, 2) : 0;

        // ── PAR (Portfolio at Risk) ───────────────────────────────
        $date30 = now()->subDays(30)->toDateString();
        $date60 = now()->subDays(60)->toDateString();
        $date90 = now()->subDays(90)->toDateString();

        $overdueIds30 = $overdueIds60 = $overdueIds90 = collect();
        if ($activeIds->isNotEmpty()) {
            $overdueIds30 = Pago::whereIn('prestamo_id', $activeIds)
                ->whereIn('estatus', ['Pendiente', 'Atrasado', 'Parcial'])
                ->where('fecha_programada', '<=', $date30)
                ->distinct()->pluck('prestamo_id');
            $overdueIds60 = Pago::whereIn('prestamo_id', $activeIds)
                ->whereIn('estatus', ['Pendiente', 'Atrasado', 'Parcial'])
                ->where('fecha_programada', '<=', $date60)
                ->distinct()->pluck('prestamo_id');
            $overdueIds90 = Pago::whereIn('prestamo_id', $activeIds)
                ->whereIn('estatus', ['Pendiente', 'Atrasado', 'Parcial'])
                ->where('fecha_programada', '<=', $date90)
                ->distinct()->pluck('prestamo_id');
        }

        $saldoActivo = $capitalPendiente;
        $par30Saldo  = (float) $activos->whereIn('id', $overdueIds30->toArray())->sum('saldo_actual');
        $par60Saldo  = (float) $activos->whereIn('id', $overdueIds60->toArray())->sum('saldo_actual');
        $par90Saldo  = (float) $activos->whereIn('id', $overdueIds90->toArray())->sum('saldo_actual');
        $par30 = $saldoActivo > 0 ? round($par30Saldo / $saldoActivo * 100, 1) : 0;
        $par60 = $saldoActivo > 0 ? round($par60Saldo / $saldoActivo * 100, 1) : 0;
        $par90 = $saldoActivo > 0 ? round($par90Saldo / $saldoActivo * 100, 1) : 0;

        $nActivos   = $activos->count();
        $nAtrasados = $atrasados->count();
        $npl = $nActivos > 0 ? round($nAtrasados / $nActivos * 100, 1) : 0;

        // ── Gráfica mensual (12 meses) ─────────────────────────────
        $chartLabels = $chartDesembolsos = $chartCobros = [];

        $cobrosRaw = DB::table('pagos')
            ->join('prestamos', 'pagos.prestamo_id', '=', 'prestamos.id')
            ->selectRaw('DATE_FORMAT(pagos.fecha_pago, "%Y-%m") as mes, SUM(pagos.monto_cobrado) as total')
            ->where('prestamos.admin_id', $id)
            ->whereIn('pagos.estatus', ['Pagado', 'Parcial'])
            ->whereNotNull('pagos.fecha_pago')
            ->where('pagos.fecha_pago', '>=', now()->subMonths(11)->startOfMonth()->toDateString())
            ->groupBy('mes')->pluck('total', 'mes');

        for ($i = 11; $i >= 0; $i--) {
            $fecha  = now()->subMonths($i);
            $mesKey = $fecha->format('Y-m');
            $des = $allPrestamos
                ->filter(fn($p) => $p->fecha_entrega && $p->fecha_entrega->format('Y-m') === $mesKey)
                ->sum('monto_entregado');
            $chartLabels[]      = $fecha->locale('es')->isoFormat('MMM YY');
            $chartDesembolsos[] = (float) $des;
            $chartCobros[]      = (float) ($cobrosRaw[$mesKey] ?? 0);
        }

        // ── Distribución por estatus ──────────────────────────────
        $porEstatus = [
            'Activo'     => $allPrestamos->where('estatus', 'Activo')->count(),
            'Atrasado'   => $nAtrasados,
            'Pendiente'  => $pendientes->count(),
            'Finalizado' => $finalizados->count(),
            'Retirado'   => $retirados->count(),
        ];

        // ── Métricas de cartera ───────────────────────────────────
        $totalPrestamos  = $allPrestamos->count();
        $ticketPromedio  = $deployed->count() > 0 ? round($deployed->avg('monto_entregado'), 0) : 0;
        $montoMax        = $deployed->isNotEmpty() ? (float) $deployed->max('monto_entregado') : 0;
        $montoMin        = $deployed->isNotEmpty() ? (float) $deployed->min('monto_entregado') : 0;
        $duracionPromedio = $deployed->count() > 0 ? round($deployed->avg('num_pagos'), 0) : 0;

        // ── Próximos cobros (30 días) ─────────────────────────────
        $proximosPagos = collect();
        if ($activeIds->isNotEmpty()) {
            $proximosPagos = Pago::with(['prestamo.cliente'])
                ->whereIn('prestamo_id', $activeIds)
                ->whereIn('estatus', ['Pendiente', 'Parcial'])
                ->whereBetween('fecha_programada', [now()->toDateString(), now()->addDays(30)->toDateString()])
                ->orderBy('fecha_programada')
                ->limit(60)
                ->get();
        }

        // ── Tabla de clientes ─────────────────────────────────────
        $clientesConPrestamo = $activos->keyBy('cliente_id');
        $clientesActivos     = Cliente::where('admin_id', $id)->where('activo', true)->orderBy('nombre')->get();

        $proximoPorPrestamo = collect();
        if ($activeIds->isNotEmpty()) {
            $proximoPorPrestamo = Pago::whereIn('prestamo_id', $activeIds)
                ->whereIn('estatus', ['Pendiente', 'Atrasado', 'Parcial'])
                ->orderBy('fecha_programada')
                ->get()
                ->groupBy('prestamo_id')
                ->map->first();
        }

        // ── Notas / auditoría ─────────────────────────────────────
        $notas = AdminNota::where('admin_id', $id)->orderBy('created_at', 'desc')->limit(30)->get();

        // ── Empleados ─────────────────────────────────────────────
        $empleados = Empleado::where('admin_id', $id)->where('activo', true)->orderBy('nombre')->get();

        // ── Alertas inteligentes ──────────────────────────────────
        $alertas = [];
        if ($par30 > 20)
            $alertas[] = ['tipo' => 'danger', 'icon' => '🚨', 'titulo' => 'PAR30 crítico',
                'msg' => "El {$par30}% de la cartera tiene pagos con más de 30 días de atraso."];
        if ($npl > 30)
            $alertas[] = ['tipo' => 'danger', 'icon' => '⚠️', 'titulo' => 'NPL elevado',
                'msg' => "El {$npl}% de los préstamos activos están en estatus Atrasado."];
        if ($capitalDesplegado > 0 && $saldoActivo > 0 && ($capitalRiesgo / $saldoActivo * 100) > 40)
            $alertas[] = ['tipo' => 'warning', 'icon' => '🔶', 'titulo' => 'Alta concentración de riesgo',
                'msg' => 'Más del 40% del saldo activo corresponde a préstamos atrasados.'];
        if ($nActivos > 0 && $nAtrasados / $nActivos > 0.5)
            $alertas[] = ['tipo' => 'warning', 'icon' => '📉', 'titulo' => 'Alta mora en cartera',
                'msg' => 'Más de la mitad de los préstamos activos presentan atraso de pago.'];
        if (empty($alertas) && $nActivos > 0)
            $alertas[] = ['tipo' => 'success', 'icon' => '✅', 'titulo' => 'Cartera saludable',
                'msg' => 'No se detectaron alertas críticas en la cartera activa.'];

        $montoCobradoUlt30 = $deployedIds->isNotEmpty()
            ? (float) Pago::whereIn('prestamo_id', $deployedIds)
                ->whereIn('estatus', ['Pagado', 'Parcial'])
                ->where('fecha_pago', '>=', now()->subDays(30)->toDateString())
                ->sum('monto_cobrado')
            : 0.0;

        return view('owner.admin_detalle', compact(
            'admin', 'allPrestamos', 'activos', 'atrasados', 'finalizados', 'pendientes', 'retirados',
            'capitalDesplegado', 'totalAcordado', 'interesEsperado',
            'capitalPendiente', 'moraPendiente', 'capitalRiesgo',
            'capitalRecuperado', 'interesCobranzaReal',
            'totalCobrado', 'gananciaNetaAprox', 'roi',
            'par30', 'par60', 'par90', 'npl',
            'par30Saldo', 'par60Saldo', 'par90Saldo', 'saldoActivo',
            'nActivos', 'nAtrasados',
            'chartLabels', 'chartDesembolsos', 'chartCobros',
            'porEstatus', 'totalPrestamos', 'ticketPromedio', 'montoMax', 'montoMin', 'duracionPromedio',
            'proximosPagos', 'clientesActivos', 'clientesConPrestamo', 'proximoPorPrestamo',
            'notas', 'empleados', 'alertas', 'montoCobradoUlt30'
        ));
    }

    /**
     * Mostrar el formulario para crear un nuevo admin.
     */
    public function create()
    {
        return view('owner.dashboard', ['showCreate' => true]);
    }

    /**
     * Guardar nuevo usuario administrador.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'usuario'     => 'required|string|max:60|unique:users,usuario',
            'nombre'      => 'nullable|string|max:120',
            'alias'       => 'nullable|string|max:80',
            'password'    => 'required|string|min:6|confirmed',
            'celular'     => 'nullable|string|max:20',
            'presupuesto' => 'nullable|numeric|min:0',
        ]);

        User::create([
            'usuario'     => $data['usuario'],
            'nombre'      => $data['nombre'] ?? null,
            'alias'       => $data['alias'] ?? null,
            'password'    => Hash::make($data['password']),
            'puesto'      => 'admin',
            'activo'      => true,
            'celular'     => $data['celular'] ?? null,
            'presupuesto' => $data['presupuesto'] ?? 0,
        ]);

        return redirect()->route('owner.dashboard')
            ->with('success', "Admin \"{$data['usuario']}\" creado correctamente.");
    }

    /**
     * Actualizar datos del admin (celular, presupuesto).
     */
    public function update(Request $request, int $id)
    {
        $user = User::where('id', $id)->where('puesto', 'admin')->firstOrFail();

        $request->validate([
            'nombre'      => 'nullable|string|max:120',
            'alias'       => 'nullable|string|max:80',
            'usuario'     => 'nullable|string|max:60|unique:users,usuario,' . $user->id,
            'celular'     => 'nullable|string|max:20',
            'presupuesto' => 'nullable|numeric|min:0',
        ]);

        if ($request->filled('nombre')) {
            $user->nombre = $request->nombre;
        }

        if ($request->filled('usuario')) {
            $user->usuario = $request->usuario;
        }

        $user->alias       = $request->alias ?? null;
        $user->celular     = $request->celular;
        $user->presupuesto = $request->presupuesto ?? 0;
        $user->save();

        return redirect()->route('owner.dashboard')
            ->with('success', "Datos de \"{$user->usuario}\" actualizados.");
    }

    /**
     * Activar o desactivar un admin.
     */
    public function toggle(int $id)
    {
        $user = User::where('id', $id)->where('puesto', 'admin')->firstOrFail();

        $user->activo = !$user->activo;
        $user->save();

        $estado = $user->activo ? 'activado' : 'desactivado';

        return redirect()->route('owner.dashboard')
            ->with('success', "Usuario \"{$user->usuario}\" {$estado}.");
    }

    /**
     * El owner cambia su propia contraseña.
     */
    public function changeOwnPassword(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'password'         => 'required|string|min:6|confirmed',
        ], [
            'current_password.required' => 'Ingresa tu contraseña actual.',
            'password.required'         => 'Ingresa la nueva contraseña.',
            'password.min'              => 'La nueva contraseña debe tener al menos 6 caracteres.',
            'password.confirmed'        => 'Las contraseñas nuevas no coinciden.',
        ]);

        if ($validator->fails()) {
            return redirect()->route('owner.dashboard')
                ->withErrors($validator, 'own_password')
                ->with('show_own_password_modal', true);
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return redirect()->route('owner.dashboard')
                ->withErrors(['current_password' => 'La contraseña actual es incorrecta.'], 'own_password')
                ->with('show_own_password_modal', true);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return redirect()->route('owner.dashboard')
            ->with('success', '✓ Tu contraseña fue actualizada correctamente.');
    }

    /**
     * Resetear la contraseña de un admin.
     */
    public function resetPassword(Request $request, int $id)
    {
        $user = User::where('id', $id)->where('puesto', 'admin')->firstOrFail();

        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:6|confirmed',
        ], [
            'password.required'  => 'Ingresa una nueva contraseña.',
            'password.min'       => 'La contraseña debe tener al menos 6 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        if ($validator->fails()) {
            return redirect()->route('owner.dashboard')
                ->withErrors($validator)
                ->with('reset_admin_id', $id)
                ->with('reset_usuario', $user->usuario);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return redirect()->route('owner.dashboard')
            ->with('success', "Contraseña de \"{$user->usuario}\" actualizada correctamente.");
    }

    /**
     * Eliminar un admin permanentemente.
     */
    public function destroy(int $id)
    {
        $user = User::where('id', $id)->where('puesto', 'admin')->firstOrFail();

        $nombre = $user->usuario;
        $user->delete();

        return redirect()->route('owner.dashboard')
            ->with('success', "Usuario \"{$nombre}\" eliminado.");
    }

    /**
     * Guardar una nota sobre un admin.
     */
    public function storeNota(Request $request, int $id)
    {
        User::where('id', $id)->where('puesto', 'admin')->firstOrFail();

        $request->validate([
            'contenido' => 'required|string|max:2000',
        ]);

        AdminNota::create([
            'admin_id'  => $id,
            'contenido' => trim($request->contenido),
        ]);

        $back = url()->previous();
        $detalle = route('owner.admins.show', $id);

        if (str_contains($back, "/owner/admins/{$id}")) {
            return redirect($detalle)->with('success', 'Nota guardada.');
        }

        return redirect()->route('owner.dashboard')
            ->with('success', 'Nota guardada.')
            ->with('open_notas_admin', $id);
    }

    /**
     * Dashboard de rendimientos: métricas financieras por administrador.
     */
    public function rendimientos(Request $request)
    {
        $deployedStatuses = ['Activo', 'Atrasado', 'Finalizado'];
        $activeStatuses   = ['Activo', 'Atrasado'];

        // ── Filtro de fechas para la contabilidad consolidada (flujo del periodo) ──
        $periodoActivo = false;
        $pDesde = $pHasta = null;
        if ($request->filled('desde') && $request->filled('hasta')) {
            try {
                $pDesde = \Carbon\Carbon::parse($request->query('desde'))->startOfDay();
                $pHasta = \Carbon\Carbon::parse($request->query('hasta'))->endOfDay();
                $periodoActivo = $pDesde->lte($pHasta);
            } catch (\Throwable $e) {
                $periodoActivo = false;
            }
        }

        $admins   = User::where('puesto', 'admin')->orderBy('created_at', 'desc')->get();
        $adminIds = $admins->pluck('id')->all();

        // ── Chart: últimos 90 días de desembolsos y cobros por admin ──
        $chartFrom = now()->subDays(89)->toDateString();

        // Desembolsos diarios por admin y estatus (el estatus permite filtrar el flujo
        // diario al togglear el donut de "Distribución por estatus")
        $desembolsosRaw = DB::table('prestamos')
            ->selectRaw('admin_id, estatus, DATE(fecha_entrega) as fecha, SUM(monto_entregado) as total')
            ->whereIn('admin_id', $adminIds)
            ->whereNotNull('fecha_entrega')
            ->where('fecha_entrega', '>=', $chartFrom)
            ->groupBy('admin_id', 'estatus', DB::raw('DATE(fecha_entrega)'))
            ->get()
            ->groupBy('admin_id');

        // Cobros diarios por admin y estatus (del préstamo)
        $cobrosRaw = DB::table('pagos')
            ->join('prestamos', 'pagos.prestamo_id', '=', 'prestamos.id')
            ->selectRaw('prestamos.admin_id, prestamos.estatus as estatus, DATE(pagos.fecha_pago) as fecha, SUM(pagos.monto_cobrado) as total')
            ->whereIn('prestamos.admin_id', $adminIds)
            ->whereNotNull('pagos.fecha_pago')
            ->where('pagos.fecha_pago', '>=', $chartFrom)
            ->whereIn('pagos.estatus', ['Pagado', 'Parcial'])
            ->groupBy('prestamos.admin_id', 'prestamos.estatus', DB::raw('DATE(pagos.fecha_pago)'))
            ->get()
            ->groupBy('admin_id');

        // Rango de 90 días
        $dateRange = [];
        $cur = \Carbon\Carbon::parse($chartFrom);
        while ($cur->lte(now()->startOfDay())) {
            $dateRange[] = $cur->toDateString();
            $cur->addDay();
        }

        $stats = $admins->map(function (User $admin) use (
            $deployedStatuses, $activeStatuses,
            $desembolsosRaw, $cobrosRaw, $dateRange
        ) {
            $allPrestamos = Prestamo::where('admin_id', $admin->id)->get();
            $byEstatus    = $allPrestamos->groupBy('estatus');

            $deployed = $allPrestamos->filter(fn($p) => in_array($p->estatus, $deployedStatuses));
            $activos  = $allPrestamos->filter(fn($p) => in_array($p->estatus, $activeStatuses));

            $capital_desplegado = (float) $deployed->sum('monto_entregado');
            $total_acordado     = (float) $deployed->sum('monto');
            $interes_esperado   = max(0, round($total_acordado - $capital_desplegado, 2));
            $saldo_pendiente    = (float) $activos->sum('saldo_actual');
            $mora_pendiente     = (float) $activos->sum('interes_acumulado');

            $pids = $deployed->pluck('id');
            $total_cobrado = $pids->isNotEmpty()
                ? (float) Pago::whereIn('prestamo_id', $pids)
                    ->whereIn('estatus', ['Pagado', 'Parcial'])
                    ->sum('monto_cobrado')
                : 0.0;

            // Capital recuperado: sólo filas con cobro real (monto_cobrado > 0); sumar
            // 'capital' sobre todas las filas Pagado duplica el principal cuando un
            // préstamo se liquidó (las cuotas plan liquidadas conservan su capital).
            $capital_recuperado = $pids->isNotEmpty()
                ? (float) Pago::whereIn('prestamo_id', $pids)
                    ->whereIn('estatus', ['Pagado', 'Parcial'])
                    ->where('monto_cobrado', '>', 0)
                    ->sum('capital')
                : 0.0;
            $capital_recuperado = min($capital_recuperado, $capital_desplegado);
            $interes_cobrado = max(0.0, round($total_cobrado - $capital_recuperado, 2));

            // ── Breakdown por estatus (para filtro interactivo del donut) ──
            $pagosXEstatus = collect();
            if ($pids->isNotEmpty()) {
                $pagosXEstatus = DB::table('pagos')
                    ->join('prestamos', 'pagos.prestamo_id', '=', 'prestamos.id')
                    ->whereIn('pagos.prestamo_id', $pids)
                    ->whereIn('pagos.estatus', ['Pagado', 'Parcial'])
                    ->selectRaw('prestamos.estatus as est, SUM(pagos.monto_cobrado) as cobrado')
                    ->groupBy('prestamos.estatus')
                    ->get()->keyBy('est');
            }
            $estatusData = [];
            foreach (['Activo', 'Atrasado', 'Finalizado', 'Pendiente', 'Retirado'] as $_est) {
                $g = $allPrestamos->where('estatus', $_est);
                if ($g->isEmpty()) continue;

                // Pendiente y Retirado = dinero nunca entregado (ticket expirado o aún sin desembolsar).
                // No representan capital desplegado ni generan rendimiento; excluirlos evita métricas distorsionadas.
                if (in_array($_est, ['Pendiente', 'Retirado'])) {
                    $estatusData[$_est] = [
                        'capDes' => 0.0, 'capAcord' => 0.0, 'intEsp' => 0.0,
                        'saldo'  => 0.0, 'mora'     => 0.0, 'cobrado' => 0.0,
                        'intCob' => 0.0, 'rentab'   => 0,   'rnd'     => 0,
                    ];
                    continue;
                }

                $_cap   = (float) $g->sum('monto_entregado');
                $_acord = (float) $g->sum('monto');
                $_iEsp  = max(0.0, round($_acord - $_cap, 2));
                $_saldo = in_array($_est, ['Activo','Atrasado']) ? (float) $g->sum('saldo_actual') : 0.0;
                $_mora  = in_array($_est, ['Activo','Atrasado']) ? (float) $g->sum('interes_acumulado') : 0.0;
                $_row   = $pagosXEstatus->get($_est);
                $_cob   = $_row ? (float) $_row->cobrado : 0.0;
                // Mismo criterio que el cálculo global: interés cobrado = cobrado - capital desplegado
                $_iCob  = max(0.0, round($_cob - $_cap, 2));
                $estatusData[$_est] = [
                    'capDes'  => $_cap,
                    'capAcord'=> $_acord,
                    'intEsp'  => $_iEsp,
                    'saldo'   => $_saldo,
                    'mora'    => $_mora,
                    'cobrado' => $_cob,
                    'intCob'  => $_iCob,
                    'rentab'  => $_cap > 0 ? round($_iEsp / $_cap * 100, 1) : 0,
                    'rnd'     => $_cap > 0 ? round(($_cob - $_cap) / $_cap * 100, 1) : 0,
                ];
            }

            $recuperado_pct  = $total_acordado > 0
                ? min(100, round($total_cobrado / $total_acordado * 100, 1)) : 0;

            // Rendimiento real = (total cobrado - capital desplegado) / capital desplegado
            // Positivo: ya recuperaste el capital y estás ganando interés
            // Negativo: aún no recuperas todo el capital invertido
            $rendimiento_pct = $capital_desplegado > 0
                ? round(($total_cobrado - $capital_desplegado) / $capital_desplegado * 100, 1) : 0;

            // Rentabilidad promedio = interés acordado / capital desplegado (tasa pactada)
            $rentabilidad_pct = $capital_desplegado > 0
                ? round($interes_esperado / $capital_desplegado * 100, 1) : 0;

            $n_activos   = $activos->count();
            $n_atrasados = $byEstatus->get('Atrasado', collect())->count();
            $par         = $n_activos > 0 ? round($n_atrasados / $n_activos * 100, 1) : 0;

            // ── Series de tiempo para gráfica de línea (con desglose por estatus) ──
            $adminDRows = $desembolsosRaw->get($admin->id, collect());
            $adminCRows = $cobrosRaw->get($admin->id, collect());

            // Lookup [estatus][fecha] => total
            $dLookup = [];
            foreach ($adminDRows as $r) { $dLookup[$r->estatus][$r->fecha] = (float) $r->total; }
            $cLookup = [];
            foreach ($adminCRows as $r) { $cLookup[$r->estatus][$r->fecha] = (float) $r->total; }

            // Sólo los estatus con préstamos (los mismos que aparecen en el donut)
            $statusesPresentes = $byEstatus->keys()->all();

            $chartLabels       = [];
            $chartDesembolsos  = [];
            $chartCobros       = [];
            $chartDesByEstatus = [];
            $chartCobByEstatus = [];
            foreach ($statusesPresentes as $est) {
                $chartDesByEstatus[$est] = [];
                $chartCobByEstatus[$est] = [];
            }

            foreach ($dateRange as $date) {
                $chartLabels[] = \Carbon\Carbon::parse($date)->format('d/m');
                $sumD = 0.0; $sumC = 0.0;
                foreach ($statusesPresentes as $est) {
                    $dv = $dLookup[$est][$date] ?? 0.0;
                    $cv = $cLookup[$est][$date] ?? 0.0;
                    $chartDesByEstatus[$est][] = $dv;
                    $chartCobByEstatus[$est][] = $cv;
                    $sumD += $dv; $sumC += $cv;
                }
                $chartDesembolsos[] = $sumD;
                $chartCobros[]      = $sumC;
            }

            return [
                'admin'              => $admin,
                'total'              => $allPrestamos->count(),
                'por_estatus'        => [
                    'Pendiente'  => $byEstatus->get('Pendiente',  collect())->count(),
                    'Activo'     => $byEstatus->get('Activo',     collect())->count(),
                    'Atrasado'   => $byEstatus->get('Atrasado',   collect())->count(),
                    'Finalizado' => $byEstatus->get('Finalizado', collect())->count(),
                    'Retirado'   => $byEstatus->get('Retirado',   collect())->count(),
                ],
                'capital_desplegado' => $capital_desplegado,
                'total_acordado'     => $total_acordado,
                'interes_esperado'   => $interes_esperado,
                'saldo_pendiente'    => $saldo_pendiente,
                'mora_pendiente'     => $mora_pendiente,
                'total_cobrado'      => $total_cobrado,
                'interes_cobrado'    => $interes_cobrado,
                'recuperado_pct'     => $recuperado_pct,
                'rendimiento_pct'    => $rendimiento_pct,
                'rentabilidad_pct'   => $rentabilidad_pct,
                'par'                => $activos->count() > 0
    ? round($byEstatus->get('Atrasado', collect())->count() / $activos->count() * 100, 1)
    : 0,
                'chart_labels'         => $chartLabels,
                'chart_desembolsos'    => $chartDesembolsos,
                'chart_cobros'         => $chartCobros,
                'chart_des_by_estatus' => $chartDesByEstatus,
                'chart_cob_by_estatus' => $chartCobByEstatus,
                'by_estatus'           => $estatusData,
            ];
        });

        // ── Cobranza por origen: cuentas abiertas (Activo/Atrasado) vs finalizadas ──
        $cobAbiertas = 0.0; $cobFinalizadas = 0.0;
        $intAbiertas = 0.0; $intFinalizadas = 0.0;
        $nAbiertas   = 0;   $nFinalizadas   = 0;
        foreach ($stats as $s) {
            foreach (['Activo', 'Atrasado'] as $e) {
                if (isset($s['by_estatus'][$e])) {
                    $cobAbiertas += $s['by_estatus'][$e]['cobrado'];
                    $intAbiertas += $s['by_estatus'][$e]['intCob'];
                }
            }
            if (isset($s['by_estatus']['Finalizado'])) {
                $cobFinalizadas += $s['by_estatus']['Finalizado']['cobrado'];
                $intFinalizadas += $s['by_estatus']['Finalizado']['intCob'];
            }
            $nAbiertas    += $s['por_estatus']['Activo'] + $s['por_estatus']['Atrasado'];
            $nFinalizadas += $s['por_estatus']['Finalizado'];
        }

        $sumCapital  = $stats->sum('capital_desplegado');
        $sumCobrado  = $stats->sum('total_cobrado');
        $sumInteres  = $stats->sum('interes_cobrado');
        $sumAcordado = $stats->sum('total_acordado');

        // ── Global daily chart (sum of all admins) ────────────────────
        $nDays     = count($dateRange);
        $globalDes = array_fill(0, $nDays, 0.0);
        $globalCob = array_fill(0, $nDays, 0.0);
        foreach ($stats as $s) {
            foreach ($s['chart_desembolsos'] as $i => $v) { $globalDes[$i] += $v; }
            foreach ($s['chart_cobros']      as $i => $v) { $globalCob[$i] += $v; }
        }

        $globales = [
            'capital_desplegado' => $sumCapital,
            'total_acordado'     => $sumAcordado,
            'total_cobrado'      => $sumCobrado,
            'interes_cobrado'    => $sumInteres,
            'saldo_pendiente'    => $stats->sum('saldo_pendiente'),
            'mora_pendiente'     => $stats->sum('mora_pendiente'),
            'total_prestamos'    => $stats->sum('total'),
            // Origen de lo cobrado: cuentas abiertas (Activo/Atrasado) vs finalizadas
            'cobrado_abiertas'    => round($cobAbiertas, 2),
            'cobrado_finalizadas' => round($cobFinalizadas, 2),
            'interes_abiertas'    => round($intAbiertas, 2),
            'interes_finalizadas' => round($intFinalizadas, 2),
            'n_abiertas'          => $nAbiertas,
            'n_finalizadas'       => $nFinalizadas,
            // Rendimiento real global = (cobrado - capital) / capital
            'rendimiento_pct'    => $sumCapital > 0 ? round(($sumCobrado - $sumCapital) / $sumCapital * 100, 1) : 0,
            'recuperado_pct'     => $sumAcordado > 0
                ? min(100, round($sumCobrado / $sumAcordado * 100, 1)) : 0,
            // Chart
            'chart_dates'        => $dateRange,
            'chart_labels'       => array_map(fn($d) => \Carbon\Carbon::parse($d)->format('d/m'), $dateRange),
            'chart_desembolsos'  => $globalDes,
            'chart_cobros'       => $globalCob,
            'chart_from'         => $chartFrom,
        ];

        // ── Contabilidad consolidada: histórico completo o flujo del periodo ──
        if ($periodoActivo) {
            $rngDesde = $pDesde->toDateString();
            $rngHasta = $pHasta->toDateString();

            $capDesPeriodo = (float) Prestamo::whereIn('admin_id', $adminIds)
                ->whereIn('estatus', $deployedStatuses)
                ->whereNotNull('fecha_entrega')
                ->whereBetween('fecha_entrega', [$rngDesde, $rngHasta])
                ->sum('monto_entregado');

            $nPrestPeriodo = (int) Prestamo::whereIn('admin_id', $adminIds)
                ->whereIn('estatus', $deployedStatuses)
                ->whereNotNull('fecha_entrega')
                ->whereBetween('fecha_entrega', [$rngDesde, $rngHasta])
                ->count();

            // Cobranza del periodo agrupada por estatus del préstamo (origen)
            $cobXEstatus = DB::table('pagos')
                ->join('prestamos', 'pagos.prestamo_id', '=', 'prestamos.id')
                ->whereIn('prestamos.admin_id', $adminIds)
                ->whereIn('pagos.estatus', ['Pagado', 'Parcial'])
                ->whereBetween('pagos.fecha_pago', [$rngDesde, $rngHasta])
                ->selectRaw('prestamos.estatus as est,
                    SUM(pagos.monto_cobrado) as cobrado,
                    SUM(CASE WHEN pagos.monto_cobrado > 0 THEN pagos.capital ELSE 0 END) as capital')
                ->groupBy('prestamos.estatus')
                ->get()->keyBy('est');

            $totCobPeriodo = (float) $cobXEstatus->sum('cobrado');
            $capRecPeriodo = min((float) $cobXEstatus->sum('capital'), $totCobPeriodo);
            $intCobPeriodo = max(0.0, round($totCobPeriodo - $capRecPeriodo, 2));

            $cobAbP = $capAbP = $cobFinP = $capFinP = 0.0;
            foreach (['Activo', 'Atrasado'] as $e) {
                if ($r = $cobXEstatus->get($e)) { $cobAbP += (float) $r->cobrado; $capAbP += (float) $r->capital; }
            }
            if ($r = $cobXEstatus->get('Finalizado')) { $cobFinP = (float) $r->cobrado; $capFinP = (float) $r->capital; }

            $cuenta = [
                'modo'                => 'periodo',
                'desde'               => $rngDesde,
                'hasta'               => $rngHasta,
                'capital_desplegado'  => $capDesPeriodo,
                'capital_recuperado'  => $capRecPeriodo,
                'interes_cobrado'     => $intCobPeriodo,
                'total_cobrado'       => $totCobPeriodo,
                'total_prestamos'     => $nPrestPeriodo,
                'cobrado_abiertas'    => round($cobAbP, 2),
                'cobrado_finalizadas' => round($cobFinP, 2),
                'interes_abiertas'    => round(max(0.0, $cobAbP  - $capAbP), 2),
                'interes_finalizadas' => round(max(0.0, $cobFinP - $capFinP), 2),
                // Cifras "a hoy": no aplican a un periodo → se ocultan en la vista
                'saldo_pendiente'     => 0.0,
                'mora_pendiente'      => 0.0,
                'total_acordado'      => 0.0,
                'n_abiertas'          => null,
                'n_finalizadas'       => null,
            ];
        } else {
            $cuenta = array_merge($globales, ['modo' => 'historico', 'desde' => null, 'hasta' => null]);
        }

        return view('owner.rendimientos', compact('stats', 'globales', 'cuenta'));
    }

    /**
     * Eliminar una nota.
     */
    public function destroyNota(int $id, AdminNota $nota)
    {
        abort_if($nota->admin_id !== $id, 404);

        $nota->delete();

        return redirect()->route('owner.dashboard')
            ->with('success', 'Nota eliminada.')
            ->with('open_notas_admin', $id);
    }
}