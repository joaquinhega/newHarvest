<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
            ->with('role')
            ->where('active', true)
            ->where(function ($query) use ($credentials) {
                $query->where('username', $credentials['login'])
                    ->orWhere('email', $credentials['login'])
                    ->orWhere('id_usuario', $credentials['login']);
            })
            ->first();

        // Si el usuario no existe o la contraseña es inválida -> 401 Unauthorized
        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return $this->errorResponse('Credenciales incorrectas.', 401);
        }

        $roleName = $user->role?->name ?? 'chofer';
        $token = $user->createToken('api-login', [
            'role:' . $roleName,
        ]);

        return $this->successResponse([
            'token_type' => 'Bearer',
            'access_token' => $token->plainTextToken,
            'user' => [
                'id_usuario' => $user->id_usuario,
                'username' => $user->username,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'letter' => $user->letter,
                'active' => (bool) $user->active,
                'role' => [
                    'id' => $user->role?->id,
                    'name' => $user->role?->name,
                    'description' => $user->role?->description,
                ],
            ],
        ], 'Login exitoso', 200);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return $this->successResponse(null, 'Logout exitoso', 200);
    }
}