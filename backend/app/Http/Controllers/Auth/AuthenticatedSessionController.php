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
     *
     * Roles con acceso al backoffice web: 'rrhh' y 'admin'.
     * Los choferes son bloqueados acá antes de generar sesión.
     *
     * A futuro (sistema.newharvest.com.ar), el bloqueo se reemplaza
     * por una redirección a la app Flutter según el rol detectado.
     */
    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

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

        if (!in_array($roleName, ['rrhh', 'admin'])) {
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
