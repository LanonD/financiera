<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Empleado;
use App\Models\Prestamo;
use App\Models\Cliente;
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
                // Estadísticas scoped a este admin (multi-tenancy)
                $u->stats = [
                    'empleados' => Empleado::where('admin_id', $u->id)->where('activo', true)->count(),
                    'clientes'  => Cliente::where('admin_id', $u->id)->where('activo', true)->count(),
                    'prestamos' => Prestamo::where('admin_id', $u->id)->whereIn('estatus', ['Activo', 'Atrasado'])->count(),
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
     * El owner cambia su propia contraseña.
     */
    public function changeOwnPassword(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'current_password'  => 'required',
            'password'          => 'required|string|min:6|confirmed',
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
}
