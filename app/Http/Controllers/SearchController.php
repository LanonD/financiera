<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cliente;
use App\Models\Prestamo;
use Illuminate\Support\Facades\Auth;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $adminId   = Auth::user()->adminId();
        $q         = $request->query('q', '');
        $clientes  = collect();
        $prestamos = collect();

        if ($q) {
            $clientes = Cliente::with('promotor')
                ->where('admin_id', $adminId)
                ->where(function ($query) use ($q) {
                    $query->where('nombre', 'like', "%{$q}%")
                          ->orWhere('celular', 'like', "%{$q}%");
                })
                ->get();

            // Get loans related to the found clients (already scoped via admin_id)
            $clienteIds = $clientes->pluck('id');
            $prestamos = Prestamo::with('cliente')
                ->where('admin_id', $adminId)
                ->whereIn('cliente_id', $clienteIds)
                ->get();
        }

        return view('admin.busqueda', compact('q', 'clientes', 'prestamos'));
    }
}
