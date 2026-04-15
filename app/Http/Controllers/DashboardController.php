<?php

namespace App\Http\Controllers;

use App\Models\Prestamo;
use App\Models\Cliente;
use App\Models\Empleado;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $kpis = [
            'total_prestamos'   => Prestamo::count(),
            'prestamos_activos' => Prestamo::whereIn('estatus', ['Activo', 'Atrasado'])->count(),
            'prestamos_mora'    => Prestamo::where('estatus', 'Atrasado')->count(),
            'total_clientes'    => Cliente::where('activo', true)->count(),
            'total_empleados'   => Empleado::where('activo', true)->count(),
            'cartera_total'     => Prestamo::whereIn('estatus', ['Activo', 'Atrasado'])->sum('saldo_actual'),
        ];

        $prestamos = Prestamo::with(['cliente', 'promotor', 'cobrador'])
            ->whereIn('estatus', ['Activo', 'Atrasado', 'Pendiente'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('admin.dashboard', compact('kpis', 'prestamos'));
    }
}
