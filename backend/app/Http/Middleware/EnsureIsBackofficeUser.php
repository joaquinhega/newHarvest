<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsBackofficeUser
{
    /**
     * Roles habilitados para el backoffice web.
     *
     * - 'rrhh':  personal interno de New Harvest (operaciones + RRHH)
     * - 'admin': desarrollador/superusuario técnico (mismo acceso que rrhh hoy,
     *            diferenciado para el Panel de Diagnóstico/Telemetría en Fase 4.5)
     *
     * Los choferes (role='chofer') solo acceden por la app mobile.
     * A futuro (sistema.newharvest.com.ar) se puede redirigir
     * automáticamente según rol en lugar de bloquear.
     */
    private const BACKOFFICE_ROLES = ['rrhh', 'admin'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user || !$user->role) {
            Auth::logout();
            return redirect()->route('login')
                ->withErrors(['username' => 'Tu cuenta no tiene un rol asignado. Contactá al administrador.']);
        }

        if (!in_array($user->role->name, self::BACKOFFICE_ROLES)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['username' => 'Este panel es exclusivo para administración. Usá la app móvil de New Harvest.']);
        }

        return $next($request);
    }
}
