<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Empleado;
use App\Models\User;
use App\Models\Prestamo;
use App\Models\Pago;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;

class EmpleadoController extends Controller
{
    public function index()
    {
        $adminId = auth()->user()->adminId();

        $todos = Empleado::where('activo', true)
            ->where('admin_id', $adminId)
            ->with(['usuario', 'promotor'])
            ->get();

        $promotores = $todos->filter(fn($e) => $e->hasRole('promo'))->values();
        $cobradores = $todos->filter(fn($e) => $e->hasRole('collector'))->values();
        $desembolso = $todos->filter(fn($e) => $e->hasRole('desembolso'))->values();

        // Pass promotors list so the create/edit modal can offer a selector
        return view('admin.empleados', compact('promotores', 'cobradores', 'desembolso'));
    }

    public function show($id)
    {
        $adminId  = auth()->user()->adminId();
        $empleado = Empleado::where('id', $id)->where('admin_id', $adminId)->firstOrFail();
        $roles    = $empleado->roles ?? [$empleado->puesto];

        // Aggregate data for all roles this employee has
        $prestamosActivos = collect();
        $pendientes       = collect();
        $historial        = collect();

        if (in_array('promo', $roles)) {
            $prestamosActivos = $prestamosActivos->merge(
                Prestamo::with('cliente')
                    ->where('promotor_id', $id)
                    ->whereIn('estatus', ['Activo', 'Atrasado'])
                    ->get()
            );
            $pendientes = $pendientes->merge(
                Prestamo::with('cliente')
                    ->where('promotor_id', $id)
                    ->where('estatus', 'Pendiente')
                    ->orderBy('created_at', 'desc')
                    ->get()
            );
        }

        if (in_array('collector', $roles)) {
            $prestamosActivos = $prestamosActivos->merge(
                Prestamo::with('cliente')
                    ->where('cobrador_id', $id)
                    ->whereIn('estatus', ['Activo', 'Atrasado'])
                    ->get()
            );
            $historial = $historial->merge(
                Pago::with('prestamo.cliente')
                    ->where('cobrador_id', $id)
                    ->whereIn('estatus', ['Pagado', 'Parcial'])
                    ->orderBy('fecha_pago', 'desc')
                    ->limit(20)
                    ->get()
            );
        }

        if (in_array('desembolso', $roles)) {
            $prestamosActivos = $prestamosActivos->merge(
                Prestamo::with('cliente')
                    ->where('desembolso_id', $id)
                    ->whereIn('estatus', ['Activo', 'Atrasado'])
                    ->get()
            );
        }

        // Deduplicate by id
        $prestamosActivos = $prestamosActivos->unique('id')->values();

        return view('admin.empleado_detalle', compact('empleado', 'prestamosActivos', 'pendientes', 'historial'));
    }

    public function create()
    {
        return redirect()->route('empleados.index');
    }

    public function edit($id)
    {
        return redirect()->route('empleados.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'usuario'      => 'required|string|max:60|unique:users,usuario',
            'password'     => 'required|string|min:4',
            'nombre'       => 'required|string|max:120',
            'roles'        => 'required|array|min:1',
            'roles.*'      => 'in:promo,collector,desembolso',
            'rango'        => 'required|string',
            'capacidad'    => 'nullable|numeric|min:0',
            'promotor_id'  => 'nullable|exists:empleados,id',
        ]);

        $roles       = $request->roles;
        $primaryRole = Empleado::primaryRole($roles);

        // Promotor assignment only makes sense for non-promo workers
        $promotorId = in_array('promo', $roles) ? null : ($request->promotor_id ?: null);

        $user = User::create([
            'usuario'  => $request->usuario,
            'password' => Hash::make($request->password),
            'puesto'   => $primaryRole,
            'activo'   => true,
        ]);

        Empleado::create([
            'admin_id'         => auth()->user()->id,
            'promotor_id'      => $promotorId,
            'usuario_id'       => $user->id,
            'nombre'           => $request->nombre,
            'celular'          => $request->celular,
            'email'            => $request->email,
            'puesto'           => $primaryRole,
            'roles'            => $roles,
            'rango'            => $request->rango ?? 'Bronce',
            'capacidad_maxima' => $request->capacidad ?? 0,
            'activo'           => true,
        ]);

        Cache::forget('empleados_activos_' . auth()->user()->adminId());
        Cache::forget('dashboard_kpis_' . auth()->user()->adminId());
        return redirect()->route('empleados.index')->with('success', 'Empleado creado correctamente.');
    }

    public function update(Request $request, $id)
    {
        $adminId  = auth()->user()->adminId();
        $empleado = Empleado::where('id', $id)->where('admin_id', $adminId)->firstOrFail();

        $request->validate([
            'nombre'      => 'required|string|max:120',
            'roles'       => 'required|array|min:1',
            'roles.*'     => 'in:promo,collector,desembolso',
            'rango'       => 'required|string',
            'capacidad'   => 'nullable|numeric|min:0',
            'password'    => 'nullable|string|min:4',
            'promotor_id' => 'nullable|exists:empleados,id',
        ]);

        $roles       = $request->roles;
        $primaryRole = Empleado::primaryRole($roles);

        // Update linked user
        if ($empleado->usuario) {
            $updateUser = ['puesto' => $primaryRole];
            if ($request->filled('usuario')) {
                $conflict = User::where('usuario', $request->usuario)
                    ->where('id', '!=', $empleado->usuario_id)
                    ->first();
                if ($conflict) {
                    return redirect()->back()->with('error', 'Ese nombre de usuario ya está en uso.');
                }
                $updateUser['usuario'] = $request->usuario;
            }
            // Update password only if provided
            if ($request->filled('password')) {
                $updateUser['password'] = Hash::make($request->password);
            }
            $empleado->usuario->update($updateUser);
        }

        $promotorId = in_array('promo', $roles) ? null : ($request->promotor_id ?: null);

        $empleado->update([
            'promotor_id'      => $promotorId,
            'nombre'           => $request->nombre,
            'celular'          => $request->celular,
            'email'            => $request->email,
            'puesto'           => $primaryRole,
            'roles'            => $roles,
            'rango'            => $request->rango,
            'capacidad_maxima' => $request->capacidad ?? 0,
        ]);

        Cache::forget('empleados_activos_' . $adminId);
        Cache::forget('dashboard_kpis_' . $adminId);
        return redirect()->route('empleados.index')->with('success', 'Empleado actualizado correctamente.');
    }

    public function destroy($id)
    {
        $adminId  = auth()->user()->adminId();
        $empleado = Empleado::where('id', $id)->where('admin_id', $adminId)->firstOrFail();
        $empleado->update(['activo' => false]);

        if ($empleado->usuario) {
            $empleado->usuario->update(['activo' => false]);
        }

        Cache::forget('empleados_activos_' . $adminId);
        Cache::forget('dashboard_kpis_' . $adminId);
        return redirect()->route('empleados.index')->with('success', 'Empleado desactivado correctamente.');
    }
}
