<?php

namespace App\Http\Controllers;

use App\Models\Prestamo;
use App\Models\Cliente;
use App\Models\Empleado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index()
    {
        $adminId = Auth::user()->adminId();

        $kpis = Cache::remember("dashboard_kpis_{$adminId}", 180, fn() => [
            'total_prestamos'   => Prestamo::where('admin_id', $adminId)->count(),
            'prestamos_activos' => Prestamo::where('admin_id', $adminId)->whereIn('estatus', ['Activo', 'Atrasado'])->count(),
            'prestamos_mora'    => Prestamo::where('admin_id', $adminId)->where('estatus', 'Atrasado')->count(),
            'total_clientes'    => Cliente::where('admin_id', $adminId)->where('activo', true)->count(),
            'total_empleados'   => Empleado::where('admin_id', $adminId)->where('activo', true)->count(),
            'cartera_total'     => Prestamo::where('admin_id', $adminId)->whereIn('estatus', ['Activo', 'Atrasado'])->sum('saldo_actual'),
        ]);

        $prestamos = Prestamo::with(['cliente', 'promotor', 'cobrador'])
            ->where('admin_id', $adminId)
            ->whereIn('estatus', ['Activo', 'Atrasado', 'Pendiente'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('admin.dashboard', compact('kpis', 'prestamos'));
    }
}
