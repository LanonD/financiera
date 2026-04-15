<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route(Auth::user()->dashboardRoute());
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'usuario'  => 'required|string|max:60',
            'password' => 'required|string|min:4|max:128',
        ]);

        $throttleKey = 'login.' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'usuario' => "Demasiados intentos. Intenta de nuevo en {$seconds} segundos.",
            ]);
        }

        $credentials = [
            'usuario'  => $request->usuario,
            'password' => $request->password,
        ];

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, 300); // 5 minutos de bloqueo
            throw ValidationException::withMessages([
                'usuario' => 'Usuario o contraseña incorrectos.',
            ]);
        }

        // Verificar que esté activo
        if (!Auth::user()->activo) {
            Auth::logout();
            throw ValidationException::withMessages([
                'usuario' => 'Tu cuenta está desactivada.',
            ]);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        return redirect()->route(Auth::user()->dashboardRoute());
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
