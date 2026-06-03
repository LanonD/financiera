<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\MobileAccessToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class MobileAuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'usuario' => 'required|string|max:60',
            'password' => 'required|string|min:4|max:128',
            'device_name' => 'nullable|string|max:80',
        ]);

        $user = User::where('usuario', $data['usuario'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'usuario' => 'Usuario o contrasena incorrectos.',
            ]);
        }

        if (!$user->activo) {
            throw ValidationException::withMessages([
                'usuario' => 'Tu cuenta esta desactivada.',
            ]);
        }

        [$token, $plainToken] = MobileAccessToken::issueFor($user, $data['device_name'] ?? 'Android');

        return response()->json([
            'ok' => true,
            'token' => $plainToken,
            'expires_at' => $token->expires_at?->toIso8601String(),
            'user' => $this->userPayload($user),
        ]);
    }

    public function logout(Request $request)
    {
        $plainToken = $request->bearerToken();

        if ($plainToken) {
            MobileAccessToken::where('token_hash', hash('sha256', $plainToken))
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);
        }

        return response()->json(['ok' => true]);
    }

    private function userPayload(User $user): array
    {
        $user->loadMissing('empleado');

        return [
            'id' => $user->id,
            'usuario' => $user->usuario,
            'nombre' => $user->nombre,
            'alias' => $user->alias,
            'puesto' => $user->puesto,
            'roles' => $user->getAllRoles(),
            'admin_id' => $user->adminId(),
            'empleado' => $user->empleado,
        ];
    }
}
