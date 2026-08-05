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
            'total_prestamos'   => Prestamo::deAdmin($adminId)->count(),
            'prestamos_activos' => Prestamo::deAdmin($adminId)->whereIn('estatus', ['Activo', 'Atrasado'])->count(),
            'prestamos_mora'    => Prestamo::deAdmin($adminId)->where('estatus', 'Atrasado')->count(),
            'total_clientes'    => Cliente::deAdmin($adminId)->where('activo', true)->count(),
            'total_empleados'   => Empleado::deAdmin($adminId)->where('activo', true)->count(),
            'cartera_total'     => Prestamo::deAdmin($adminId)->whereIn('estatus', ['Activo', 'Atrasado'])->sum('saldo_actual'),
        ]);

        $prestamos = Prestamo::with(['cliente', 'promotor', 'cobrador'])
            ->deAdmin($adminId)
            ->whereIn('estatus', ['Activo', 'Atrasado', 'Pendiente'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('admin.dashboard', compact('kpis', 'prestamos'));
    }
}
