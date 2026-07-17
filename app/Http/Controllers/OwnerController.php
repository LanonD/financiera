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
        // Admins: usuarios con puesto = 'admin' (excluye al owner y a los
        // admins sombra que sirven de espacio de datos de carteras financiadas)
        $admins = User::where('puesto', 'admin')
            ->whereNull('cartera_financiada_de')
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
        $admin = User::where('id', $id)->where('puesto', 'admin')->whereNull('cartera_financiada_de')->firstOrFail();

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
        $chartMonths = []; // para el selector de mes del drill-down (valor Y-m + etiqueta)

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
            $chartMonths[]      = ['value' => $mesKey, 'label' => ucfirst($fecha->locale('es')->isoFormat('MMMM YYYY'))];
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
            'chartLabels', 'chartDesembolsos', 'chartCobros', 'chartMonths',
            'porEstatus', 'totalPrestamos', 'ticketPromedio', 'montoMax', 'montoMin', 'duracionPromedio',
            'proximosPagos', 'clientesActivos', 'clientesConPrestamo', 'proximoPorPrestamo',
            'notas', 'empleados', 'alertas', 'montoCobradoUlt30'
        ));
    }

    /**
     * Drill-down de la gráfica "Flujo de Capital": desglose de UN mes por día o
     * por semana. Devuelve JSON para que la vista redibuje la misma gráfica sin
     * recargar. Mismos criterios que la vista mensual de show():
     *   - Desembolsado = SUM(monto_entregado) de préstamos por fecha_entrega.
     *   - Cobrado      = SUM(monto_cobrado) de pagos Pagado/Parcial por fecha_pago.
     */
    public function flujoMensual(int $id, Request $request)
    {
        $admin = User::where('id', $id)->where('puesto', 'admin')
            ->whereNull('cartera_financiada_de')->firstOrFail();

        try {
            $ref = $request->query('mes')
                ? \Carbon\Carbon::createFromFormat('Y-m', $request->query('mes'))->startOfMonth()
                : now()->startOfMonth();
        } catch (\Exception $e) {
            $ref = now()->startOfMonth();
        }
        $granularidad = $request->query('granularidad') === 'semanal' ? 'semanal' : 'diario';

        $inicio = $ref->copy()->startOfMonth();
        $fin    = $ref->copy()->endOfMonth();

        $desRaw = DB::table('prestamos')
            ->selectRaw('DATE(fecha_entrega) as dia, SUM(monto_entregado) as total')
            ->where('admin_id', $id)
            ->whereNotNull('fecha_entrega')
            ->whereBetween('fecha_entrega', [$inicio->toDateString(), $fin->toDateString()])
            ->groupBy('dia')->pluck('total', 'dia');

        $cobRaw = DB::table('pagos')
            ->join('prestamos', 'pagos.prestamo_id', '=', 'prestamos.id')
            ->selectRaw('DATE(pagos.fecha_pago) as dia, SUM(pagos.monto_cobrado) as total')
            ->where('prestamos.admin_id', $id)
            ->whereIn('pagos.estatus', ['Pagado', 'Parcial'])
            ->whereNotNull('pagos.fecha_pago')
            ->whereBetween('pagos.fecha_pago', [$inicio->toDateString(), $fin->toDateString()])
            ->groupBy('dia')->pluck('total', 'dia');

        $labels = $desembolsos = $cobros = [];

        if ($granularidad === 'diario') {
            for ($d = $inicio->copy(); $d->lte($fin); $d->addDay()) {
                $key = $d->toDateString();
                $labels[]      = $d->format('d');
                $desembolsos[] = (float) ($desRaw[$key] ?? 0);
                $cobros[]      = (float) ($cobRaw[$key] ?? 0);
            }
        } else {
            // Semanal: buckets por semana del mes (1–7, 8–14, 15–21, 22–28, 29–fin)
            $buckets  = [];
            $mesCorto = $ref->locale('es')->isoFormat('MMM');
            for ($d = $inicio->copy(); $d->lte($fin); $d->addDay()) {
                $key = $d->toDateString();
                $wi  = intdiv($d->day - 1, 7);
                if (!isset($buckets[$wi])) {
                    $buckets[$wi] = ['ini' => $d->day, 'fin' => $d->day, 'des' => 0.0, 'cob' => 0.0];
                }
                $buckets[$wi]['fin']  = $d->day;
                $buckets[$wi]['des'] += (float) ($desRaw[$key] ?? 0);
                $buckets[$wi]['cob'] += (float) ($cobRaw[$key] ?? 0);
            }
            ksort($buckets);
            foreach ($buckets as $b) {
                $labels[]      = $b['ini'] . '–' . $b['fin'] . ' ' . $mesCorto;
                $desembolsos[] = round($b['des'], 2);
                $cobros[]      = round($b['cob'], 2);
            }
        }

        return response()->json([
            'mes'          => $ref->format('Y-m'),
            'mes_label'    => ucfirst($ref->locale('es')->isoFormat('MMMM [de] YYYY')),
            'granularidad' => $granularidad,
            'labels'       => $labels,
            'desembolsos'  => $desembolsos,
            'cobros'       => $cobros,
        ]);
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
        $user = User::where('id', $id)->where('puesto', 'admin')->whereNull('cartera_financiada_de')->firstOrFail();

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
        $user = User::where('id', $id)->where('puesto', 'admin')->whereNull('cartera_financiada_de')->firstOrFail();

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
        $user = User::where('id', $id)->where('puesto', 'admin')->whereNull('cartera_financiada_de')->firstOrFail();

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
        $user = User::where('id', $id)->where('puesto', 'admin')->whereNull('cartera_financiada_de')->firstOrFail();

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
        User::where('id', $id)->where('puesto', 'admin')->whereNull('cartera_financiada_de')->firstOrFail();

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

        $admins   = User::where('puesto', 'admin')->whereNull('cartera_financiada_de')->orderBy('created_at', 'desc')->get();
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
     * Documento semanal para owner: lectura financiera por administrador.
     */
    public function reporteSemanal(Request $request)
    {
        try {
            $base = $request->query('semana')
                ? \Carbon\Carbon::parse($request->query('semana'))
                : now();
        } catch (\Throwable $e) {
            $base = now();
        }

        $inicio = $base->copy()->startOfWeek(\Carbon\Carbon::MONDAY)->startOfDay();
        $fin    = $base->copy()->endOfWeek(\Carbon\Carbon::SUNDAY)->endOfDay();
        $hoy    = now()->startOfDay();

        // Semana anterior (para variaciones % de los KPI)
        $prevInicio = $inicio->copy()->subWeek();
        $prevFin    = $fin->copy()->subWeek();

        // Días de la semana seleccionada (flujo diario de caja)
        $diasSemana = [];
        for ($d = $inicio->copy(); $d->lte($fin); $d->addDay()) {
            $diasSemana[] = [
                'key'   => $d->toDateString(),
                'label' => ucfirst($d->locale('es')->isoFormat('ddd DD')),
            ];
        }

        $admins = User::with('carteraFinanciada')
            ->where('puesto', 'admin')
            ->whereNull('cartera_financiada_de')
            ->orderBy('nombre')
            ->orderBy('usuario')
            ->get();

        // Filtro por administrador: el reporte completo (KPIs, gráficas, aging,
        // morosos y CSV) se recalcula sólo con el admin seleccionado.
        $adminsLista = $admins;
        $adminSel    = (int) $request->query('admin', 0);
        if ($adminSel > 0 && $admins->contains('id', $adminSel)) {
            $admins = $admins->where('id', $adminSel)->values();
        } else {
            $adminSel = 0;
        }

        // Estatus usados también por el bloque de tendencia (más abajo).
        $deployedStatuses = ['Activo', 'Atrasado', 'Finalizado'];
        $realPayStatuses  = ['Pagado', 'Parcial'];

        // Filas por administrador: agregación en SQL (ver weeklyRowsSql) en
        // lugar de cargar todos los pagos a memoria PHP por cada admin.
        $rows = $this->weeklyRowsSql($admins, $inicio, $fin, $hoy, $prevInicio, $prevFin, $diasSemana);

        $globales = [
            'admins'              => $rows->count(),
            'capital_desplegado'  => $rows->sum('capital_desplegado'),
            'saldo_activo'        => $rows->sum('saldo_activo'),
            'saldo_atrasado'      => $rows->sum('saldo_atrasado'),
            'mora_pendiente'      => $rows->sum('mora_pendiente'),
            'desembolsado_semana' => $rows->sum('desembolsado_semana'),
            'cobrado_semana'      => $rows->sum('cobrado_semana'),
            'interes_semana'      => $rows->sum('interes_semana'),
            'programado_semana'   => $rows->sum('programado_semana'),
            'vencido_monto'       => $rows->sum('vencido_monto'),
            'proximo_programado'  => $rows->sum('proximo_programado'),
            'prestamos_activos'   => $rows->sum('prestamos_activos'),
            'prestamos_atrasados' => $rows->sum('prestamos_atrasados'),
        ];
        $globales['eficiencia'] = $globales['programado_semana'] > 0
            ? round($globales['cobrado_semana'] / $globales['programado_semana'] * 100, 1) : 0.0;
        $globales['riesgo_pct'] = $globales['saldo_activo'] > 0
            ? round($globales['saldo_atrasado'] / $globales['saldo_activo'] * 100, 1) : 0.0;

        // ── Globales adicionales: semana anterior, flujo neto, aging ──
        $globales['cobrado_prev']      = $rows->sum('cobrado_prev');
        $globales['interes_prev']      = $rows->sum('interes_prev');
        $globales['desembolsado_prev'] = $rows->sum('desembolsado_prev');
        $globales['programado_prev']   = $rows->sum('programado_prev');
        $globales['eficiencia_prev']   = $globales['programado_prev'] > 0
            ? round($globales['cobrado_prev'] / $globales['programado_prev'] * 100, 1) : 0.0;
        $globales['flujo_neto']        = round($globales['cobrado_semana'] - $globales['desembolsado_semana'], 2);
        $globales['flujo_neto_prev']   = round($globales['cobrado_prev'] - $globales['desembolsado_prev'], 2);
        $globales['vencido_pagos']     = $rows->sum('vencido_pagos');
        $globales['aging'] = [
            'b1_30'  => round($rows->sum(fn($r) => $r['aging']['b1_30']),  2),
            'b31_60' => round($rows->sum(fn($r) => $r['aging']['b31_60']), 2),
            'b61_90' => round($rows->sum(fn($r) => $r['aging']['b61_90']), 2),
            'b90p'   => round($rows->sum(fn($r) => $r['aging']['b90p']),   2),
        ];

        // Flujo diario global (suma de todos los admins)
        $nDias = count($diasSemana);
        $globalDailyCob = array_fill(0, $nDias, 0.0);
        $globalDailyDes = array_fill(0, $nDias, 0.0);
        foreach ($rows as $r) {
            foreach ($r['daily_cobrado']      as $i => $v) { $globalDailyCob[$i] += $v; }
            foreach ($r['daily_desembolsado'] as $i => $v) { $globalDailyDes[$i] += $v; }
        }
        $globales['daily_labels']      = array_column($diasSemana, 'label');
        $globales['daily_cobrado']     = array_map(fn($v) => round($v, 2), $globalDailyCob);
        $globales['daily_desembolsado']= array_map(fn($v) => round($v, 2), $globalDailyDes);

        // ── Tendencia 8 semanas (cobrado vs programado vs desembolsado) ──
        $adminIdsAll = $rows->flatMap(fn($r) => $r['admin_ids'])->unique()->values()->all();
        $trendStart  = $inicio->copy()->subWeeks(7);

        $cobDia = $progDia = $desDia = collect();
        if (!empty($adminIdsAll)) {
            $cobDia = DB::table('pagos')
                ->join('prestamos', 'pagos.prestamo_id', '=', 'prestamos.id')
                ->whereIn('prestamos.admin_id', $adminIdsAll)
                ->whereIn('pagos.estatus', $realPayStatuses)
                ->where('pagos.monto_cobrado', '>', 0)
                ->whereNotNull('pagos.fecha_pago')
                ->whereBetween('pagos.fecha_pago', [$trendStart->toDateString(), $fin->toDateString()])
                ->selectRaw('DATE(pagos.fecha_pago) as dia, SUM(pagos.monto_cobrado) as total')
                ->groupBy('dia')->pluck('total', 'dia');

            $progDia = DB::table('pagos')
                ->join('prestamos', 'pagos.prestamo_id', '=', 'prestamos.id')
                ->whereIn('prestamos.admin_id', $adminIdsAll)
                ->whereRaw("COALESCE(pagos.tipo_pago, 'plan') NOT IN ('congelado', 'liquidado')")
                ->whereNotNull('pagos.fecha_programada')
                ->whereBetween('pagos.fecha_programada', [$trendStart->toDateString(), $fin->toDateString()])
                ->selectRaw('DATE(pagos.fecha_programada) as dia, SUM(pagos.monto_cuota) as total')
                ->groupBy('dia')->pluck('total', 'dia');

            $desDia = DB::table('prestamos')
                ->whereIn('admin_id', $adminIdsAll)
                ->whereIn('estatus', $deployedStatuses)
                ->whereNotNull('fecha_entrega')
                ->whereBetween('fecha_entrega', [$trendStart->toDateString(), $fin->toDateString()])
                ->selectRaw('DATE(fecha_entrega) as dia, SUM(monto_entregado) as total')
                ->groupBy('dia')->pluck('total', 'dia');
        }

        $tendencia = ['labels' => [], 'cobrado' => [], 'programado' => [], 'desembolsado' => [], 'eficiencia' => []];
        for ($w = 7; $w >= 0; $w--) {
            $ws = $inicio->copy()->subWeeks($w);
            $we = $ws->copy()->addDays(6);
            $c = $g = $d = 0.0;
            for ($cur = $ws->copy(); $cur->lte($we); $cur->addDay()) {
                $k  = $cur->toDateString();
                $c += (float) ($cobDia[$k]  ?? 0);
                $g += (float) ($progDia[$k] ?? 0);
                $d += (float) ($desDia[$k]  ?? 0);
            }
            $tendencia['labels'][]       = $ws->format('d/m') . '–' . $we->format('d/m');
            $tendencia['cobrado'][]      = round($c, 2);
            $tendencia['programado'][]   = round($g, 2);
            $tendencia['desembolsado'][] = round($d, 2);
            $tendencia['eficiencia'][]   = $g > 0 ? round($c / $g * 100, 1) : 0.0;
        }

        $mejor = $rows->first();
        $mayorRiesgo = $rows->sortByDesc('par30')->first();
        $mayorUtilidad = $rows->sortByDesc('interes_semana')->first();

        return view('owner.reporte_semanal', compact(
            'rows', 'globales', 'inicio', 'fin', 'mejor', 'mayorRiesgo', 'mayorUtilidad', 'tendencia',
            'adminsLista', 'adminSel'
        ));
    }

    /**
     * Construye las filas por administrador del reporte semanal usando
     * agregación en SQL (SUM/COUNT con GROUP BY admin_id) en vez de cargar
     * todos los pagos a memoria PHP. Devuelve la misma estructura que el
     * bloque original, pero calculada con ~12 consultas en total en lugar de
     * N cargas completas por administrador.
     */
    private function weeklyRowsSql($admins, $inicio, $fin, $hoy, $prevInicio, $prevFin, array $diasSemana): \Illuminate\Support\Collection
    {
        $deployedStatuses = ['Activo', 'Atrasado', 'Finalizado'];
        $payableStatuses  = ['Pendiente', 'Atrasado', 'Parcial'];
        $realPayStatuses  = ['Pagado', 'Parcial'];

        $iniD  = $inicio->toDateString();
        $finD  = $fin->toDateString();
        $pIniD = $prevInicio->toDateString();
        $pFinD = $prevFin->toDateString();
        $hoyD  = $hoy->toDateString();
        $d30   = $hoy->copy()->subDays(30)->toDateString();
        $d60   = $hoy->copy()->subDays(60)->toDateString();
        $d90   = $hoy->copy()->subDays(90)->toDateString();

        $proxInicio = $fin->copy()->addDay()->startOfDay();
        $proxFin    = $proxInicio->copy()->endOfWeek(\Carbon\Carbon::SUNDAY)->endOfDay();
        $proxIniD   = $proxInicio->toDateString();
        $proxFinD   = $proxFin->toDateString();

        // admin_ids de cada fila (propio + cartera financiada) y su unión
        $rowAdminIds = [];
        $allIds = [];
        foreach ($admins as $admin) {
            $ids = collect([$admin->id, $admin->carteraFinanciada?->id])->filter()->values()->all();
            $rowAdminIds[$admin->id] = $ids;
            foreach ($ids as $id) { $allIds[] = $id; }
        }
        $allIds = array_values(array_unique($allIds));
        if (empty($allIds)) { $allIds = [0]; }

        // ── Agregados de préstamos por admin ──────────────────────────────
        $P = DB::table('prestamos')
            ->whereIn('admin_id', $allIds)
            ->selectRaw(
                "admin_id,
                 SUM(CASE WHEN estatus IN('Activo','Atrasado','Finalizado') THEN monto_entregado ELSE 0 END) capital_desplegado,
                 SUM(CASE WHEN estatus IN('Activo','Atrasado','Finalizado') THEN monto ELSE 0 END) total_acordado,
                 SUM(CASE WHEN estatus IN('Activo','Atrasado','Finalizado') AND fecha_entrega BETWEEN ? AND ? THEN monto_entregado ELSE 0 END) desembolsado_semana,
                 SUM(CASE WHEN estatus IN('Activo','Atrasado','Finalizado') AND fecha_entrega BETWEEN ? AND ? THEN monto_entregado ELSE 0 END) desembolsado_prev,
                 SUM(CASE WHEN estatus IN('Activo','Atrasado','Finalizado') AND fecha_entrega BETWEEN ? AND ? THEN 1 ELSE 0 END) nuevos_prestamos,
                 SUM(CASE WHEN estatus IN('Activo','Atrasado') THEN saldo_actual ELSE 0 END) saldo_activo,
                 SUM(CASE WHEN estatus IN('Activo','Atrasado') THEN interes_acumulado ELSE 0 END) mora_pendiente,
                 SUM(CASE WHEN estatus IN('Activo','Atrasado') THEN 1 ELSE 0 END) prestamos_activos,
                 SUM(CASE WHEN estatus='Atrasado' THEN saldo_actual ELSE 0 END) saldo_atrasado,
                 SUM(CASE WHEN estatus='Atrasado' THEN 1 ELSE 0 END) prestamos_atrasados,
                 COUNT(*) prestamos_total",
                [$iniD, $finD, $pIniD, $pFinD, $iniD, $finD]
            )
            ->groupBy('admin_id')->get()->keyBy('admin_id');

        // Desembolso diario por admin+día
        $Pdia = DB::table('prestamos')
            ->whereIn('admin_id', $allIds)
            ->whereIn('estatus', $deployedStatuses)
            ->whereNotNull('fecha_entrega')
            ->whereBetween('fecha_entrega', [$iniD, $finD])
            ->selectRaw('admin_id, fecha_entrega dia, SUM(monto_entregado) total')
            ->groupBy('admin_id', 'fecha_entrega')->get();

        // ── Agregados de pagos reales (Pagado/Parcial con cobro) por admin ─
        $PGreal = DB::table('pagos as pg')
            ->join('prestamos as p', 'pg.prestamo_id', '=', 'p.id')
            ->whereIn('p.admin_id', $allIds)
            ->whereIn('pg.estatus', $realPayStatuses)
            ->where('pg.monto_cobrado', '>', 0)
            ->selectRaw(
                "p.admin_id,
                 SUM(pg.monto_cobrado) cobrado_total,
                 SUM(pg.capital) capital_real,
                 SUM(CASE WHEN pg.fecha_pago BETWEEN ? AND ? THEN pg.monto_cobrado ELSE 0 END) cobrado_semana,
                 SUM(CASE WHEN pg.fecha_pago BETWEEN ? AND ? THEN pg.capital ELSE 0 END) capital_semana_raw,
                 SUM(CASE WHEN pg.fecha_pago BETWEEN ? AND ? THEN 1 ELSE 0 END) pagos_semana,
                 SUM(CASE WHEN pg.fecha_pago BETWEEN ? AND ? THEN pg.monto_cobrado ELSE 0 END) cobrado_prev,
                 SUM(CASE WHEN pg.fecha_pago BETWEEN ? AND ? THEN pg.capital ELSE 0 END) capital_prev_raw",
                [$iniD, $finD, $iniD, $finD, $iniD, $finD, $pIniD, $pFinD, $pIniD, $pFinD]
            )
            ->groupBy('p.admin_id')->get()->keyBy('admin_id');

        // Cobro diario por admin+día
        $PGdia = DB::table('pagos as pg')
            ->join('prestamos as p', 'pg.prestamo_id', '=', 'p.id')
            ->whereIn('p.admin_id', $allIds)
            ->whereIn('pg.estatus', $realPayStatuses)
            ->where('pg.monto_cobrado', '>', 0)
            ->whereNotNull('pg.fecha_pago')
            ->whereBetween('pg.fecha_pago', [$iniD, $finD])
            ->selectRaw('p.admin_id, pg.fecha_pago dia, SUM(pg.monto_cobrado) total')
            ->groupBy('p.admin_id', 'pg.fecha_pago')->get();

        // Programado (semana actual y anterior); excluye congelado/liquidado
        $PGprog = DB::table('pagos as pg')
            ->join('prestamos as p', 'pg.prestamo_id', '=', 'p.id')
            ->whereIn('p.admin_id', $allIds)
            ->whereRaw("COALESCE(pg.tipo_pago,'plan') NOT IN('congelado','liquidado')")
            ->where(function ($q) use ($iniD, $finD, $pIniD, $pFinD) {
                $q->whereBetween('pg.fecha_programada', [$iniD, $finD])
                  ->orWhereBetween('pg.fecha_programada', [$pIniD, $pFinD]);
            })
            ->selectRaw(
                "p.admin_id,
                 SUM(CASE WHEN pg.fecha_programada BETWEEN ? AND ? THEN pg.monto_cuota ELSE 0 END) programado_semana,
                 SUM(CASE WHEN pg.fecha_programada BETWEEN ? AND ? THEN pg.monto_cuota ELSE 0 END) programado_prev",
                [$iniD, $finD, $pIniD, $pFinD]
            )
            ->groupBy('p.admin_id')->get()->keyBy('admin_id');

        // Pendientes vencidos: monto, conteo y aging (antigüedad de la cuota)
        $PGvenc = DB::table('pagos as pg')
            ->join('prestamos as p', 'pg.prestamo_id', '=', 'p.id')
            ->whereIn('p.admin_id', $allIds)
            ->where('pg.fecha_programada', '<', $hoyD)
            ->whereIn('pg.estatus', $payableStatuses)
            ->whereRaw("COALESCE(pg.tipo_pago,'plan') NOT IN('congelado','liquidado')")
            ->selectRaw(
                "p.admin_id,
                 SUM(GREATEST(0, pg.monto_cuota - COALESCE(pg.monto_cobrado,0))) vencido_monto,
                 COUNT(*) vencido_pagos,
                 SUM(CASE WHEN DATEDIFF(?, pg.fecha_programada) <= 30 THEN GREATEST(0, pg.monto_cuota - COALESCE(pg.monto_cobrado,0)) ELSE 0 END) b1_30,
                 SUM(CASE WHEN DATEDIFF(?, pg.fecha_programada) > 30 AND DATEDIFF(?, pg.fecha_programada) <= 60 THEN GREATEST(0, pg.monto_cuota - COALESCE(pg.monto_cobrado,0)) ELSE 0 END) b31_60,
                 SUM(CASE WHEN DATEDIFF(?, pg.fecha_programada) > 60 AND DATEDIFF(?, pg.fecha_programada) <= 90 THEN GREATEST(0, pg.monto_cuota - COALESCE(pg.monto_cobrado,0)) ELSE 0 END) b61_90,
                 SUM(CASE WHEN DATEDIFF(?, pg.fecha_programada) > 90 THEN GREATEST(0, pg.monto_cuota - COALESCE(pg.monto_cobrado,0)) ELSE 0 END) b90p",
                [$hoyD, $hoyD, $hoyD, $hoyD, $hoyD, $hoyD]
            )
            ->groupBy('p.admin_id')->get()->keyBy('admin_id');

        // PAR30/60/90: saldo de préstamos activos con cuota payable vencida
        $PGpar = DB::table('prestamos as p')
            ->joinSub(
                DB::table('pagos')
                    ->whereIn('estatus', $payableStatuses)
                    ->selectRaw('prestamo_id, MIN(fecha_programada) mind')
                    ->groupBy('prestamo_id'),
                'ov', 'ov.prestamo_id', '=', 'p.id'
            )
            ->whereIn('p.admin_id', $allIds)
            ->whereIn('p.estatus', ['Activo', 'Atrasado'])
            ->selectRaw(
                "p.admin_id,
                 SUM(CASE WHEN ov.mind <= ? THEN p.saldo_actual ELSE 0 END) par30_saldo,
                 SUM(CASE WHEN ov.mind <= ? THEN p.saldo_actual ELSE 0 END) par60_saldo,
                 SUM(CASE WHEN ov.mind <= ? THEN p.saldo_actual ELSE 0 END) par90_saldo",
                [$d30, $d60, $d90]
            )
            ->groupBy('p.admin_id')->get()->keyBy('admin_id');

        // Próximo programado (siguiente semana), cuotas payable
        $PGprox = DB::table('pagos as pg')
            ->join('prestamos as p', 'pg.prestamo_id', '=', 'p.id')
            ->whereIn('p.admin_id', $allIds)
            ->whereBetween('pg.fecha_programada', [$proxIniD, $proxFinD])
            ->whereIn('pg.estatus', $payableStatuses)
            ->selectRaw('p.admin_id, SUM(GREATEST(0, pg.monto_cuota - COALESCE(pg.monto_cobrado,0))) proximo')
            ->groupBy('p.admin_id')->get()->keyBy('admin_id');

        // Clientes activos por admin
        $Cli = DB::table('clientes')
            ->whereIn('admin_id', $allIds)
            ->where('activo', true)
            ->selectRaw('admin_id, COUNT(*) total')
            ->groupBy('admin_id')->get()->keyBy('admin_id');

        // Maps auxiliares admin_id => [dia => total]
        $mapDia = function ($rows) {
            $m = [];
            foreach ($rows as $r) {
                $m[$r->admin_id][substr($r->dia, 0, 10)] = (float) $r->total;
            }
            return $m;
        };
        $desDiaMap = $mapDia($Pdia);
        $cobDiaMap = $mapDia($PGdia);

        // Suma de un campo agregado sobre los admin_ids de la fila
        $sumIds = function (array $ids, $coll, string $field) {
            $t = 0.0;
            foreach ($ids as $id) {
                $row = $coll->get($id);
                if ($row) { $t += (float) $row->$field; }
            }
            return $t;
        };

        $rows = $admins->map(function (User $admin) use (
            $rowAdminIds, $P, $PGreal, $PGprog, $PGvenc, $PGpar, $PGprox, $Cli,
            $desDiaMap, $cobDiaMap, $diasSemana, $hoy, $hoyD, $inicio, $fin,
            $payableStatuses, $sumIds
        ) {
            $ids = $rowAdminIds[$admin->id];

            $capitalDesplegado = round($sumIds($ids, $P, 'capital_desplegado'), 2);
            $totalAcordado     = round($sumIds($ids, $P, 'total_acordado'), 2);
            $saldoActivo       = round($sumIds($ids, $P, 'saldo_activo'), 2);
            $saldoAtrasado     = round($sumIds($ids, $P, 'saldo_atrasado'), 2);
            $moraPendiente     = round($sumIds($ids, $P, 'mora_pendiente'), 2);
            $desembolsadoSemana= round($sumIds($ids, $P, 'desembolsado_semana'), 2);
            $desembolsadoPrev  = round($sumIds($ids, $P, 'desembolsado_prev'), 2);

            $cobradoTotal      = round($sumIds($ids, $PGreal, 'cobrado_total'), 2);
            $capitalRecuperado = min($capitalDesplegado, round($sumIds($ids, $PGreal, 'capital_real'), 2));
            $interesCobradoTotal = max(0.0, round($cobradoTotal - $capitalRecuperado, 2));

            $cobradoSemana = round($sumIds($ids, $PGreal, 'cobrado_semana'), 2);
            $capitalSemana = min($cobradoSemana, round($sumIds($ids, $PGreal, 'capital_semana_raw'), 2));
            $interesSemana = max(0.0, round($cobradoSemana - $capitalSemana, 2));

            $cobradoPrev = round($sumIds($ids, $PGreal, 'cobrado_prev'), 2);
            $capitalPrev = min($cobradoPrev, round($sumIds($ids, $PGreal, 'capital_prev_raw'), 2));
            $interesPrev = max(0.0, round($cobradoPrev - $capitalPrev, 2));

            $programadoCobro = round($sumIds($ids, $PGprog, 'programado_semana'), 2);
            $programadoPrev  = round($sumIds($ids, $PGprog, 'programado_prev'), 2);

            $vencidoMonto = round($sumIds($ids, $PGvenc, 'vencido_monto'), 2);
            $vencidoPagos = (int) $sumIds($ids, $PGvenc, 'vencido_pagos');

            $par30Saldo = round($sumIds($ids, $PGpar, 'par30_saldo'), 2);
            $par60Saldo = round($sumIds($ids, $PGpar, 'par60_saldo'), 2);
            $par90Saldo = round($sumIds($ids, $PGpar, 'par90_saldo'), 2);

            $par30 = $saldoActivo > 0 ? round($par30Saldo / $saldoActivo * 100, 1) : 0.0;
            $par60 = $saldoActivo > 0 ? round($par60Saldo / $saldoActivo * 100, 1) : 0.0;
            $par90 = $saldoActivo > 0 ? round($par90Saldo / $saldoActivo * 100, 1) : 0.0;

            $eficiencia = $programadoCobro > 0 ? round($cobradoSemana / $programadoCobro * 100, 1) : 0.0;
            $roiReal    = $capitalDesplegado > 0 ? round($interesCobradoTotal / $capitalDesplegado * 100, 1) : 0.0;
            $recuperado = $totalAcordado > 0 ? round($cobradoTotal / $totalAcordado * 100, 1) : 0.0;
            $riesgoPct  = $saldoActivo > 0 ? round($saldoAtrasado / $saldoActivo * 100, 1) : 0.0;
            $eficienciaPrev = $programadoPrev > 0 ? round($cobradoPrev / $programadoPrev * 100, 1) : 0.0;

            $proximoProgramado = round($sumIds($ids, $PGprox, 'proximo'), 2);

            // Flujo diario
            $dailyCobrado = [];
            $dailyDesembolsado = [];
            foreach ($diasSemana as $dia) {
                $c = 0.0; $d = 0.0;
                foreach ($ids as $id) {
                    $c += $cobDiaMap[$id][$dia['key']] ?? 0;
                    $d += $desDiaMap[$id][$dia['key']] ?? 0;
                }
                $dailyCobrado[]      = round($c, 2);
                $dailyDesembolsado[] = round($d, 2);
            }

            // Aging
            $aging = [
                'b1_30'  => round($sumIds($ids, $PGvenc, 'b1_30'), 2),
                'b31_60' => round($sumIds($ids, $PGvenc, 'b31_60'), 2),
                'b61_90' => round($sumIds($ids, $PGvenc, 'b61_90'), 2),
                'b90p'   => round($sumIds($ids, $PGvenc, 'b90p'), 2),
            ];

            // Top morosos (hasta 5 atrasados por saldo) + antigüedad de cuota
            $topMorososRaw = DB::table('prestamos as pr')
                ->leftJoin('clientes as c', 'c.id', '=', 'pr.cliente_id')
                ->whereIn('pr.admin_id', $ids)
                ->where('pr.estatus', 'Atrasado')
                ->orderByDesc('pr.saldo_actual')->orderBy('pr.id')
                ->limit(5)
                ->get(['pr.id', 'pr.saldo_actual', 'pr.interes_acumulado', 'c.nombre']);

            $edadMap = [];
            if ($topMorososRaw->isNotEmpty()) {
                $edadMap = DB::table('pagos')
                    ->whereIn('prestamo_id', $topMorososRaw->pluck('id')->all())
                    ->where('fecha_programada', '<', $hoyD)
                    ->whereIn('estatus', $payableStatuses)
                    ->whereRaw("COALESCE(tipo_pago,'plan') NOT IN('congelado','liquidado')")
                    ->selectRaw('prestamo_id, MIN(fecha_programada) oldest')
                    ->groupBy('prestamo_id')->pluck('oldest', 'prestamo_id');
            }
            $topMorosos = $topMorososRaw->map(function ($p) use ($edadMap, $hoy) {
                $oldest = $edadMap[$p->id] ?? null;
                return [
                    'prestamo_id' => $p->id,
                    'cliente'     => $p->nombre ?? 'Sin cliente',
                    'saldo'       => (float) $p->saldo_actual,
                    'mora'        => (float) $p->interes_acumulado,
                    'dias'        => $oldest ? (int) \Carbon\Carbon::parse($oldest)->diffInDays($hoy) : 0,
                ];
            })->values();

            // Top 5 saldos entre activos (concentración)
            $top5Pct = 0.0;
            if ($saldoActivo > 0) {
                $top5 = (float) DB::table(DB::raw('(SELECT saldo_actual FROM prestamos WHERE admin_id IN ('
                        . implode(',', array_fill(0, count($ids), '?'))
                        . ") AND estatus IN('Activo','Atrasado') ORDER BY saldo_actual DESC LIMIT 5) t"))
                    ->selectRaw('COALESCE(SUM(saldo_actual),0) s')
                    ->setBindings($ids)
                    ->value('s');
                $top5Pct = round($top5 / $saldoActivo * 100, 1);
            }

            $prestamosTotal    = (int) $sumIds($ids, $P, 'prestamos_total');
            $prestamosActivos  = (int) $sumIds($ids, $P, 'prestamos_activos');
            $prestamosAtrasados= (int) $sumIds($ids, $P, 'prestamos_atrasados');
            $clientes          = (int) $sumIds($ids, $Cli, 'total');
            $nuevosPrestamos   = (int) $sumIds($ids, $P, 'nuevos_prestamos');
            $pagosSemanaCount  = (int) $sumIds($ids, $PGreal, 'pagos_semana');

            $score = 100;
            $score -= min(35, $par30 * 0.7);
            $score -= min(25, $riesgoPct * 0.45);
            $score -= min(20, $eficiencia < 100 ? (100 - $eficiencia) * 0.25 : 0);
            $score += min(10, $interesSemana > 0 && $capitalDesplegado > 0 ? ($interesSemana / $capitalDesplegado * 100) * 4 : 0);
            $score = max(0, min(100, round($score, 1)));

            $alertas = [];
            if ($par30 >= 25) $alertas[] = 'PAR30 alto: priorizar recuperacion antes de crecer.';
            if ($eficiencia < 80 && $programadoCobro > 0) $alertas[] = 'Cobranza semanal por debajo de lo programado.';
            if ($moraPendiente > 0) $alertas[] = 'Mora pendiente activa: revisar promesas de pago.';
            if ($desembolsadoSemana > $cobradoSemana && $saldoActivo > 0) $alertas[] = 'Crecimiento consume caja esta semana.';
            if (!$alertas) $alertas[] = 'Cartera sin alerta critica esta semana.';

            $recomendacion = 'Mantener ritmo y reinvertir solo en clientes con historial limpio.';
            if ($par30 >= 25 || $riesgoPct >= 35) {
                $recomendacion = 'Congelar crecimiento nuevo y enfocar cobranza en saldos vencidos de mayor monto.';
            } elseif ($eficiencia >= 110 && $par30 < 10 && $interesSemana > 0) {
                $recomendacion = 'Tiene espacio para crecer: aumentar colocacion controlada y conservar disciplina de cobro.';
            } elseif ($eficiencia < 90) {
                $recomendacion = 'Mejorar seguimiento diario: metas por cobrador y cierre de promesas vencidas.';
            }

            return [
                'admin'               => $admin,
                'admin_ids'           => $ids,
                'score'               => $score,
                'recomendacion'       => $recomendacion,
                'alertas'             => $alertas,
                'prestamos_total'     => $prestamosTotal,
                'prestamos_activos'   => $prestamosActivos,
                'prestamos_atrasados' => $prestamosAtrasados,
                'clientes'            => $clientes,
                'capital_desplegado'  => $capitalDesplegado,
                'total_acordado'      => $totalAcordado,
                'saldo_activo'        => $saldoActivo,
                'saldo_atrasado'      => $saldoAtrasado,
                'mora_pendiente'      => $moraPendiente,
                'cobrado_total'       => $cobradoTotal,
                'interes_total'       => $interesCobradoTotal,
                'roi_real'            => $roiReal,
                'recuperado_pct'      => $recuperado,
                'desembolsado_semana' => $desembolsadoSemana,
                'cobrado_semana'      => $cobradoSemana,
                'capital_semana'      => $capitalSemana,
                'interes_semana'      => $interesSemana,
                'programado_semana'   => $programadoCobro,
                'eficiencia'          => $eficiencia,
                'vencido_monto'       => $vencidoMonto,
                'vencido_pagos'       => $vencidoPagos,
                'par30'               => $par30,
                'par60'               => $par60,
                'par90'               => $par90,
                'par30_saldo'         => $par30Saldo,
                'par60_saldo'         => $par60Saldo,
                'par90_saldo'         => $par90Saldo,
                'riesgo_pct'          => $riesgoPct,
                'proximo_programado'  => $proximoProgramado,
                'pagos_semana'        => $pagosSemanaCount,
                'nuevos_prestamos'    => $nuevosPrestamos,
                'cobrado_prev'        => $cobradoPrev,
                'interes_prev'        => $interesPrev,
                'desembolsado_prev'   => $desembolsadoPrev,
                'programado_prev'     => $programadoPrev,
                'eficiencia_prev'     => $eficienciaPrev,
                'flujo_neto'          => round($cobradoSemana - $desembolsadoSemana, 2),
                'flujo_neto_prev'     => round($cobradoPrev - $desembolsadoPrev, 2),
                'daily_cobrado'       => $dailyCobrado,
                'daily_desembolsado'  => $dailyDesembolsado,
                'aging'               => $aging,
                'top_morosos'         => $topMorosos,
                'top5_pct'            => $top5Pct,
            ];
        })->sortByDesc('score')->values();

        return $rows;
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
