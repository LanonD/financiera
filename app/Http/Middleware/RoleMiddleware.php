<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!$request->user()) {
            return redirect()->route('login');
        }

        if (!$request->user()->activo) {
            auth()->logout();
            return redirect()->route('login')->withErrors(['usuario' => 'Tu cuenta está desactivada.']);
        }

        // Check against ALL roles the user has (supports multi-role employees)
        if (!array_intersect($roles, $request->user()->getAllRoles())) {
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }

        return $next($request);
    }
}
