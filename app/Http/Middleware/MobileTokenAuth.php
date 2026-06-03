<?php

namespace App\Http\Middleware;

use App\Models\MobileAccessToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class MobileTokenAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $plainToken = $request->bearerToken();

        if (!$plainToken) {
            return response()->json(['ok' => false, 'error' => 'Token requerido.'], 401);
        }

        $token = MobileAccessToken::with('user')
            ->where('token_hash', hash('sha256', $plainToken))
            ->whereNull('revoked_at')
            ->first();

        if (!$token || ($token->expires_at && $token->expires_at->isPast())) {
            return response()->json(['ok' => false, 'error' => 'Token invalido o vencido.'], 401);
        }

        if (!$token->user || !$token->user->activo) {
            return response()->json(['ok' => false, 'error' => 'Usuario inactivo.'], 403);
        }

        $token->forceFill(['last_used_at' => now()])->save();
        Auth::setUser($token->user);
        $request->setUserResolver(fn() => $token->user);

        return $next($request);
    }
}
