<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsBackofficeUser
{
    /**
     * Roles habilitados para acceder al backoffice web.
     * Choferes y empresas solo pueden usar la app mobile.
     */
    private const ALLOWED_ROLES = ['admin', 'rrhh'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user || !$user->role) {
            Auth::logout();
            return redirect()->route('login')
                ->withErrors(['username' => 'Tu cuenta no tiene un rol asignado. Contactá al administrador.']);
        }

        $roleName = $user->role->name;

        if (!in_array($roleName, self::ALLOWED_ROLES)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // A futuro (sistema.newharvest.com.ar) se puede redirigir a la app
            // según el rol: choferes → app Flutter, empresas → portal cliente.
            // Por ahora se bloquea con mensaje claro.
            return redirect()->route('login')
                ->withErrors(['username' => 'Este panel es exclusivo para administración. Usá la app móvil de New Harvest.']);
        }

        return $next($request);
    }
}
