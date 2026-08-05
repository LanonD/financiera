<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * La vista global del owner es de sólo lectura: bloquea cualquier request que
 * modifique datos (POST/PUT/PATCH/DELETE) mientras la marca de sesión está
 * activa, salvo las rutas propias del owner y el logout. Es una segunda capa:
 * los handlers de escritura además filtran con where('admin_id', -1), que no
 * encuentra registros.
 */
class VistaGlobalSoloLectura
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->esVistaGlobal() && !$request->isMethodSafe()) {
            $ruta = $request->route()?->getName() ?? '';

            $permitida = $ruta === 'logout'
                || str_starts_with($ruta, 'owner.');

            if (!$permitida) {
                return back()->with('error',
                    'La vista global es de sólo lectura. Para modificar datos usa "Ver como" con el admin correspondiente.');
            }
        }

        return $next($request);
    }
}
