<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Vista admin para el owner: el owner entra a la sesión de un administrador
 * (misma interfaz, mismos datos que vería ese admin) sin conocer su contraseña,
 * conservando en sesión su identidad de owner para poder regresar en un clic.
 */
class ImpersonacionController extends Controller
{
    /**
     * Entrar como el admin indicado. Sólo accesible para el owner (ruta bajo
     * middleware role:owner). No se regenera la sesión para conservar la
     * marca de impersonación.
     */
    public function entrar(Request $request, int $id)
    {
        $admin = User::where('id', $id)
            ->where('puesto', 'admin')
            ->whereNull('cartera_financiada_de')
            ->firstOrFail();

        if (!$admin->activo) {
            return back()->withErrors(['impersonar' => 'No puedes entrar como un admin desactivado (actívalo primero).']);
        }

        session([
            // Ojo: Auth::id() aquí devolvería el nombre de usuario (el modelo
            // usa 'usuario' como auth identifier); se necesita el id numérico.
            'impersonador_id' => Auth::user()->id,
            // Arrancar limpio: que el admin financiado escoja cartera de nuevo
            'cartera_activa'  => null,
            // La impersonación sustituye a la vista global si estaba activa
            'vista_global'    => null,
        ]);

        Auth::login($admin);

        return redirect()->route($admin->dashboardRoute())
            ->with('success', 'Estás viendo el sistema como "' . ($admin->nombre ?: $admin->usuario) . '".');
    }

    /**
     * Volver a la cuenta owner. Accesible desde la sesión impersonada (el
     * usuario autenticado es el admin), por eso sólo exige auth + la marca
     * de sesión creada al entrar.
     */
    public function salir(Request $request)
    {
        $ownerId = session('impersonador_id');

        $owner = $ownerId
            ? User::where('id', $ownerId)->where('puesto', 'owner')->where('activo', true)->first()
            : null;

        if (!$owner) {
            // Sesión sin impersonación activa: no hay a dónde volver
            abort(403, 'No hay una sesión de owner a la cual regresar.');
        }

        session()->forget(['impersonador_id', 'cartera_activa', 'vista_global']);

        Auth::login($owner);

        return redirect()->route('owner.dashboard')
            ->with('success', 'Volviste a tu cuenta de owner.');
    }
}
