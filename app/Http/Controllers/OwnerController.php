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

        return redirect()->route('owner.dashboard')
            ->with('success', 'Nota guardada.')
            ->with('open_notas_admin', $id);
    }

    /**
     * Dashboard de rendimientos: métricas financieras por administrador.
     */
    public function rendimientos()
    {
        $deployedStatuses = ['Activo', 'Atrasado', 'Finalizado'];
        $activeStatuses   = ['Activo', 'Atrasado'];

        $admins   = User::where('puesto', 'admin')->orderBy('created_at', 'desc')->get();
        $adminIds = $admins->pluck('id')->all();

        // ── Chart: últimos 90 días de desembolsos y cobros por admin ──
        $chartFrom = now()->subDays(89)->toDateString();

        // Desembolsos diarios por admin
        $desembolsosRaw = DB::table('prestamos')
            ->selectRaw('admin_id, DATE(fecha_entrega) as fecha, SUM(monto_entregado) as total')
            ->whereIn('admin_id', $adminIds)
            ->whereNotNull('fecha_entrega')
            ->where('fecha_entrega', '>=', $chartFrom)
            ->groupBy('admin_id', DB::raw('DATE(fecha_entrega)'))
            ->get()
            ->groupBy('admin_id')
            ->map(fn($rows) => $rows->keyBy('fecha'));

        // Cobros diarios por admin
        $cobrosRaw = DB::table('pagos')
            ->join('prestamos', 'pagos.prestamo_id', '=', 'prestamos.id')
            ->selectRaw('prestamos.admin_id, DATE(pagos.fecha_pago) as fecha, SUM(pagos.monto_cobrado) as total')
            ->whereIn('prestamos.admin_id', $adminIds)
            ->whereNotNull('pagos.fecha_pago')
            ->where('pagos.fecha_pago', '>=', $chartFrom)
            ->whereIn('pagos.estatus', ['Pagado', 'Parcial'])
            ->groupBy('prestamos.admin_id', DB::raw('DATE(pagos.fecha_pago)'))
            ->get()
            ->groupBy('admin_id')
            ->map(fn($rows) => $rows->keyBy('fecha'));

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

            $interes_cobrado = max(0.0, round($total_cobrado - $capital_desplegado, 2));
            $recuperado_pct  = $total_acordado > 0
                ? min(100, round($total_cobrado / $total_acordado * 100, 1)) : 0;
            $rendimiento_pct = $capital_desplegado > 0
                ? round($interes_cobrado / $capital_desplegado * 100, 2) : 0;

            $n_activos   = $activos->count();
            $n_atrasados = $byEstatus->get('Atrasado', collect())->count();
            $par         = $n_activos > 0 ? round($n_atrasados / $n_activos * 100, 1) : 0;

            // ── Series de tiempo para gráfica de línea ───────────────
            $adminD = $desembolsosRaw->get($admin->id, collect());
            $adminC = $cobrosRaw->get($admin->id, collect());

            $chartLabels      = [];
            $chartDesembolsos = [];
            $chartCobros      = [];

            foreach ($dateRange as $date) {
                $chartLabels[]      = \Carbon\Carbon::parse($date)->format('d/m');
                $chartDesembolsos[] = isset($adminD[$date]) ? (float) $adminD[$date]->total : 0;
                $chartCobros[]      = isset($adminC[$date]) ? (float) $adminC[$date]->total : 0;
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
                'par'                => $par,
                'chart_labels'       => $chartLabels,
                'chart_desembolsos'  => $chartDesembolsos,
                'chart_cobros'       => $chartCobros,
            ];
        });

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
            'rendimiento_pct'    => $sumCapital > 0 ? round($sumInteres / $sumCapital * 100, 2) : 0,
            'recuperado_pct'     => $sumAcordado > 0
                ? min(100, round($sumCobrado / $sumAcordado * 100, 1)) : 0,
            // Chart
            'chart_dates'        => $dateRange,
            'chart_labels'       => array_map(fn($d) => \Carbon\Carbon::parse($d)->format('d/m'), $dateRange),
            'chart_desembolsos'  => $globalDes,
            'chart_cobros'       => $globalCob,
            'chart_from'         => $chartFrom,
        ];

        return view('owner.rendimientos', compact('stats', 'globales'));
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