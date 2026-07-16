<?php

namespace App\Http\Controllers;

use App\Models\Financiamiento;
use App\Models\User;
use Illuminate\Support\Str;

class CarteraController extends Controller
{
    /**
     * Entrar a una de las 2 carteras del admin financiado. Las carteras no
     * comparten información: la financiada opera sobre su propio admin
     * "sombra" (clientes, préstamos, cobros y equipo desde cero).
     */
    public function entrar(string $cartera)
    {
        $user = auth()->user();

        if ($cartera === 'financiada' && $user->esAdminFinanciado()) {
            // Espacio de datos de la cartera financiada (se crea la primera vez).
            // El usuario sombra no inicia sesión: contraseña aleatoria irrecuperable.
            User::firstOrCreate(
                ['cartera_financiada_de' => $user->id],
                [
                    'usuario'  => Str::limit($user->usuario, 45, '') . '.financiada',
                    'nombre'   => 'Cartera financiada · ' . ($user->alias ?: ($user->nombre ?: $user->usuario)),
                    'password' => Str::random(40),
                    'puesto'   => 'admin',
                    'activo'   => true,
                ]
            );
            session(['cartera_activa' => 'financiada']);
        } else {
            session(['cartera_activa' => 'propia']);
        }

        return redirect()->route('dashboard');
    }

    /**
     * Financiamientos activos del admin autenticado.
     */
    private function financiamientosActivos()
    {
        return Financiamiento::where('admin_id', auth()->user()->id)
            ->where('estatus', 'Activo')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Selector de cartera al iniciar sesión: el admin financiado escoge entre
     * su cartera de siempre (clientes/préstamos/cobros) y la cartera financiada.
     */
    public function seleccion()
    {
        $financiamientos = $this->financiamientosActivos();

        if ($financiamientos->isEmpty()) {
            return redirect()->route('dashboard');
        }

        $hoy = now()->startOfDay();
        $financiamientos->each(fn($f) => $f->estado = $f->estadoCobros($hoy));

        $resumen = [
            'capital'        => (float) $financiamientos->sum('capital_actual'),
            'proximo'        => $this->proximoPago($financiamientos),
            'atrasados'      => (int) $financiamientos->sum(fn($f) => $f->estado->atrasados),
            'monto_atrasado' => (float) $financiamientos->sum(fn($f) => $f->estado->monto_atrasado),
        ];

        return view('admin.carteras', compact('financiamientos', 'resumen'));
    }

    /**
     * Cartera financiada del admin: capital que trabaja, cuánto y cuándo debe
     * pagar, e historial de pagos. Solo lectura: los cobros los registra el
     * supervisor y el acuerdo lo controla el owner. No se muestra el reparto
     * interno entre inversores.
     */
    public function financiada()
    {
        $hoy = now()->startOfDay();

        $financiamientos = $this->financiamientosActivos()
            ->map(function (Financiamiento $f) use ($hoy) {
                $f->estado = $f->estadoCobros($hoy);
                // Pagos realizados (los cobros de rendimiento), más reciente primero
                $f->pagos  = $f->movimientos->where('tipo', 'rendimiento')->values();

                // Serie teórico vs real (misma serie que ve el owner:
                // los pagos aparecen en su fecha real, no en el corte teórico)
                $f->grafica = $f->serieComparativa($hoy);

                return $f;
            });

        if ($financiamientos->isEmpty()) {
            return redirect()->route('dashboard');
        }

        $resumen = [
            'capital'        => (float) $financiamientos->sum('capital_actual'),
            'pagado'         => (float) $financiamientos->sum(fn($f) => $f->pagos->sum('monto')),
            'proximo'        => $this->proximoPago($financiamientos),
            'monto_atrasado' => (float) $financiamientos->sum(fn($f) => $f->estado->monto_atrasado),
            'atrasados'      => (int) $financiamientos->sum(fn($f) => $f->estado->atrasados),
        ];

        return view('admin.financiamiento', compact('financiamientos', 'resumen'));
    }

    /**
     * Próximo pago AGREGADO para el resumen del admin. Cuando tiene más de una
     * inversión y varias vencen la misma fecha (lo normal), se muestra UN solo
     * monto —la suma de todo lo que toca pagar ese día— en vez del de una sola,
     * que confundía (parecía que solo debía una parte). El detalle sigue
     * mostrando cada inversión por separado. Cada financiamiento debe traer
     * ->estado (estadoCobros) cargado.
     */
    private function proximoPago($financiamientos): object
    {
        $primero = $financiamientos->sortBy(fn($f) => $f->estado->proximo)->first();
        $fecha   = $primero->estado->proximo;
        $mismos  = $financiamientos->filter(fn($f) => $f->estado->proximo->isSameDay($fecha));

        return (object) [
            'fecha'   => $fecha,
            'monto'   => round($mismos->sum(fn($f) => $f->estado->restante), 2),
            'cuenta'  => $mismos->count(),
            'total'   => $financiamientos->count(),
            'parcial' => $mismos->contains(fn($f) => $f->estado->parcial),
        ];
    }
}
