<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Prestamo;
use App\Models\Empleado;
use Illuminate\Support\Facades\Auth;

class DesembolsoController extends Controller
{
    /**
     * Show pending disbursements (loans in Pendiente status not yet delivered)
     */
    public function index()
    {
        $user     = Auth::user();
        $empleado = $user->empleado;

        // Desembolso role sees all pending loans, or filter by their assignment
        $query = Prestamo::with(['cliente', 'promotor'])
            ->where('estatus', 'Pendiente')
            ->whereNull('fecha_entrega');

        if ($user->puesto === 'desembolso' && $empleado) {
            $query->where('desembolso_id', $empleado->id);
        }

        $prestamos_pendientes = $query->orderBy('created_at')->get();

        return view('desembolso.desembolsos', compact('prestamos_pendientes'));
    }

    /**
     * Confirm a disbursement (JSON endpoint)
     */
    public function confirmar(Request $request)
    {
        $data = $request->json()->all();

        $prestamoId = $data['prestamo_id'] ?? null;
        $monto      = (float)($data['monto'] ?? 0);
        $forma      = $data['forma'] ?? 'efectivo';
        $hora       = $data['hora'] ?? null;
        $nota       = $data['nota'] ?? null;

        if (!$prestamoId || $monto <= 0) {
            return response()->json(['ok' => false, 'error' => 'Datos incompletos']);
        }

        $prestamo = Prestamo::find($prestamoId);
        if (!$prestamo) {
            return response()->json(['ok' => false, 'error' => 'Préstamo no encontrado']);
        }

        if ($prestamo->estatus !== 'Pendiente') {
            return response()->json(['ok' => false, 'error' => 'Este préstamo ya fue procesado']);
        }

        $fechaEntrega = now()->toDateString();
        if ($hora) {
            $fechaEntrega = now()->toDateString() . ' ' . $hora;
        }

        $empleado = Auth::user()->empleado;

        $prestamo->update([
            'estatus'         => 'Activo',
            'monto_entregado' => $monto,
            'forma_entrega'   => $forma,
            'fecha_entrega'   => now()->toDateString(),
            'nota_entrega'    => $nota,
            'desembolso_id'   => $empleado?->id,
        ]);

        return response()->json(['ok' => true]);
    }
}

