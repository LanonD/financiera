<?php

namespace App\Http\Controllers;

use App\Models\Financiamiento;
use Illuminate\Http\Request;

class SupervisorController extends Controller
{
    /**
     * Panel del supervisor de admins: a quién le toca cobrar el rendimiento
     * del periodo y registro del cobro. Solo lectura del acuerdo; el control
     * de la inversión y de los inversores es exclusivo del owner.
     */
    public function index()
    {
        $hoy = now()->startOfDay();

        $financiamientos = Financiamiento::with(['admin', 'inversores'])
            ->where('estatus', 'Activo')
            ->get()
            ->map(function (Financiamiento $f) use ($hoy) {
                // Rendimientos ya registrados (cobros al admin), más reciente primero
                $cobros = $f->movimientos()->with('registradoPor')
                    ->where('tipo', 'rendimiento')
                    ->get();

                // Calendario anclado al inicio del financiamiento: el primer
                // cobro toca un periodo (semana/mes) después del arranque y
                // cada cobro registrado cubre un periodo del calendario.
                $n        = $cobros->count();
                $vencidos = 0;
                while ($vencidos < 520 && $f->fechaCobro($vencidos + 1)->lte($hoy)) {
                    $vencidos++;
                }

                $f->cobros           = $cobros;
                $f->ultimo_cobro     = $cobros->first();
                $f->cobros_atrasados = max(0, $vencidos - $n);
                // El pendiente más antiguo o, si va al corriente, el siguiente futuro
                $f->proximo_cobro    = $f->fechaCobro($n + 1);

                return $f;
            })
            ->sortBy('proximo_cobro')
            ->values();

        $atrasadas = $financiamientos->filter(fn($f) => $f->cobros_atrasados > 0);

        $resumen = [
            'cuentas'        => $financiamientos->count(),
            'siguiente'      => $financiamientos->first(),
            'atrasadas'      => $atrasadas->count(),
            'monto_atrasado' => (float) $atrasadas->sum(fn($f) => $f->cobros_atrasados * $f->rendimiento_periodo),
            'cobrado_mes'    => (float) $financiamientos->sum(
                fn($f) => $f->cobros->filter(fn($m) => $m->fecha->gte(now()->startOfMonth()))->sum('monto')
            ),
        ];

        return view('supervisor.cobros', compact('financiamientos', 'resumen'));
    }

    /**
     * Registrar el cobro del rendimiento del periodo al admin financiado.
     * Usa la misma lógica de reparto que el owner (retornos fijos + reinversión).
     */
    public function storeRendimiento(Request $request, int $id)
    {
        $f = Financiamiento::with('inversores')->where('estatus', 'Activo')->findOrFail($id);

        $data = $request->validate([
            'monto' => 'required|numeric|min:0.01',
            'fecha' => 'required|date',
            'nota'  => 'nullable|string|max:255',
        ]);

        // El supervisor siempre capitaliza la reinversión: es la regla del
        // acuerdo (interés compuesto); la excepción la maneja el owner.
        $f->registrarRendimiento(
            round((float) $data['monto'], 2),
            $data['fecha'],
            $data['nota'] ?? null,
            true,
            auth()->user()->id
        );

        $nombre = $f->admin->alias ?: ($f->admin->nombre ?: $f->admin->usuario);

        return redirect()->route('supervisor.cobros.index')
            ->with('success', "Cobro registrado a {$nombre}.")
            ->with('open_financiamiento', $f->id);
    }
}
