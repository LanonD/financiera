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
        $q = $request->query('q', '');
        $clientes = collect();
        $prestamos = collect();

        if ($q) {
            $clientes = Cliente::with('promotor')
                ->where(function ($query) use ($q) {
                    $query->where('nombre', 'like', "%{$q}%")
                          ->orWhere('celular', 'like', "%{$q}%");
                })
                ->get();

            // Get loans related to the found clients
            $clienteIds = $clientes->pluck('id');
            $prestamos = Prestamo::with('cliente')
                ->whereIn('cliente_id', $clienteIds)
                ->get();
        }

        return view('admin.busqueda', compact('q', 'clientes', 'prestamos'));
    }
}
