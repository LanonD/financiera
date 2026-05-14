<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Empleado;
use App\Models\Prestamo;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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
                // Estadísticas agregadas del sistema asociadas a este admin
                $empleado = $u->empleado;

                // Empleados registrados en el sistema (todos — sin tenancy)
                // Se muestran globales como indicador de actividad
                $u->stats = [
                    'empleados' => Empleado::where('activo', true)->count(),
                    'clientes'  => Cliente::where('activo', true)->count(),
                    'prestamos' => Prestamo::whereIn('estatus', ['Activo', 'Atrasado'])->count(),
                ];
                return $u;
            });

        $totales = [
            'total'    => $admins->count(),
            'activos'  => $admins->where('activo', true)->count(),
            'inactivos'=> $admins->where('activo', false)->count(),
        ];

        return view('owner.dashboard', compact('admins', 'totales'));
    }

    /**
     * Formulario para crear un nuevo admin (vista inline en dashboard).
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
            'usuario'  => 'required|string|max:60|unique:users,usuario',
            'password' => 'required|string|min:6|confirmed',
        ]);

        User::create([
            'usuario'  => $data['usuario'],
            'password' => Hash::make($data['password']),
            'puesto'   => 'admin',
            'activo'   => true,
        ]);

        return redirect()->route('owner.dashboard')
            ->with('success', "Admin \"{$data['usuario']}\" creado correctamente.");
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
}
