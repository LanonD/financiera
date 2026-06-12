<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeceras de seguridad HTTP aplicadas a todas las respuestas.
 * Mitiga clickjacking, MIME sniffing y fuga de URLs internas por Referer.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = [
            // Evita que el sitio sea embebido en un iframe (clickjacking)
            'X-Frame-Options'        => 'SAMEORIGIN',
            // Evita que el navegador "adivine" el tipo MIME de una respuesta
            'X-Content-Type-Options' => 'nosniff',
            // No enviar la URL completa (con IDs de préstamo/cliente) a sitios externos
            'Referrer-Policy'        => 'strict-origin-when-cross-origin',
            // Limita APIs sensibles del navegador
            'Permissions-Policy'     => 'geolocation=(self), camera=(), microphone=(), payment=()',
        ];

        foreach ($headers as $key => $value) {
            if (!$response->headers->has($key)) {
                $response->headers->set($key, $value);
            }
        }

        return $response;
    }
}
