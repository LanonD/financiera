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

        // Buscar usuario primero para verificar activo antes de hacer login
        $user = \App\Models\User::where('usuario', $request->usuario)->first();

        if (!$user || !\Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
            RateLimiter::hit($throttleKey, 300);
            throw ValidationException::withMessages([
                'usuario' => 'Usuario o contraseña incorrectos.',
            ]);
        }

        if (!$user->activo) {
            RateLimiter::hit($throttleKey, 300);
            throw ValidationException::withMessages([
                'usuario' => 'Tu cuenta está desactivada.',
            ]);
        }

        Auth::login($user, $request->boolean('remember'));

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        return redirect()->route(Auth::user()->dashboardRoute())
            ->with('just_logged_in', true);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
