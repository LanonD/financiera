<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cliente;
use App\Models\Empleado;
use App\Models\Prestamo;
use App\Models\Pago;
use Illuminate\Support\Facades\Auth;

class ClienteController extends Controller
{
    public function index()
    {
        $user   = Auth::user();
        $puesto = $user->puesto;

        $query = Cliente::with(['promotor', 'prestamos' => function ($q) {
            $q->whereIn('estatus', ['Activo', 'Atrasado']);
        }]);

        if (in_array('promo', $user->getAllRoles()) && !in_array('admin', $user->getAllRoles())) {
            $empleado = $user->empleado;
            if ($empleado) {
                $query->where('promotor_id', $empleado->id);
            }
        }

        $clientes = $query->where('activo', true)->orderBy('nombre')->get();

        $promotores = Empleado::whereJsonContains('roles', 'promo')->where('activo', true)->get();

        return view('admin.clientes', compact('clientes', 'promotores', 'puesto'));
    }

    public function create()
    {
        $promotores = Empleado::whereJsonContains('roles', 'promo')->where('activo', true)->get();
        return view('admin.cliente_crear', compact('promotores'));
    }

    public function store(Request $request)
    {
        $user   = Auth::user();
        $isAdmin = in_array('admin', $user->getAllRoles());
        $isPromo = in_array('promo', $user->getAllRoles()) && !$isAdmin;

        $data = $request->validate([
            'nombre'      => 'required|string|max:255',
            'celular'     => 'nullable|string|max:20',
            'email'       => 'nullable|email|max:255',
            'curp'        => 'nullable|string|max:18|unique:clientes,curp',
            'direccion'   => 'nullable|string|max:500',
            'ocupacion'   => 'nullable|in:Empleado,Negocio propio,Independiente,Otro',
            'promotor_id' => $isAdmin ? 'required|exists:empleados,id' : 'nullable|exists:empleados,id',
        ], [
            'curp.unique' => 'Este CURP ya está registrado en el sistema. El cliente ya existe.',
        ]);

        // If promo, assign to themselves
        if ($isPromo) {
            $empleado = $user->empleado;
            if (!$empleado) {
                return redirect()->back()->withErrors(['promotor_id' => 'Tu cuenta no tiene un perfil de empleado asociado. Contacta al administrador.']);
            }
            $data['promotor_id'] = $empleado->id;
        }

        $data['activo'] = true;

        Cliente::create($data);

        return redirect()->route('clientes.index')->with('success', 'Cliente registrado correctamente.');
    }

    public function show($id)
    {
        $cliente = Cliente::with('promotor')->findOrFail($id);

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
        $cliente    = Cliente::findOrFail($id);
        $promotores = Empleado::whereJsonContains('roles', 'promo')->where('activo', true)->get();

        return view('admin.cliente_editar', compact('cliente', 'promotores'));
    }

    public function update(Request $request, $id)
    {
        $cliente = Cliente::findOrFail($id);

        $data = $request->validate([
            'nombre'      => 'required|string|max:255',
            'celular'     => 'nullable|string|max:20',
            'email'       => 'nullable|email|max:255',
            'curp'        => 'nullable|string|max:18|unique:clientes,curp,' . $id,
            'direccion'   => 'nullable|string|max:500',
            'ocupacion'   => 'nullable|in:Empleado,Negocio propio,Independiente,Otro',
            'promotor_id' => 'nullable|exists:empleados,id',
        ], [
            'curp.unique' => 'Este CURP ya está registrado para otro cliente.',
        ]);

        $cliente->update($data);

        return redirect()->route('clientes.show', $id)->with('success', 'Cliente actualizado correctamente.');
    }

    public function showCobrador($id)
    {
        // Collector view of a client (same as show but with back-link to cobros)
        return $this->show($id);
    }
}
