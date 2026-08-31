<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AuthenticatedSessionController extends Controller
{
    /**
     * Muestra la vista de login.
     */
    public function create()
    {
        return Inertia::render('Auth/Login');
    }

    /**
     * Procesa el inicio de sesión.
     * Solo roles 'admin' y 'rrhh' pueden acceder al backoffice web.
     * A futuro, desde sistema.newharvest.com.ar se puede redirigir
     * a choferes a la app Flutter según su rol.
     */
    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Login por username únicamente (campo simplificado)
        $credentials = [
            'username' => $request->username,
            'password' => $request->password,
        ];

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'username' => 'Las credenciales ingresadas son incorrectas.',
            ]);
        }

        $user = Auth::user();
        $roleName = $user->role?->name;

        // Bloquear acceso al backoffice para roles no autorizados
        if (!in_array($roleName, ['admin', 'rrhh'])) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'username' => 'Este panel es exclusivo para administración. Usá la app móvil de New Harvest.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended('/vouchers');
    }

    /**
     * Cierra la sesión activa.
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}