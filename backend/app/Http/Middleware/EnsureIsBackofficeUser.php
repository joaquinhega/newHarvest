<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsBackofficeUser
{
    /**
     * Solo el rol 'rrhh' puede acceder al backoffice web.
     * Los choferes únicamente usan la app mobile.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user || !$user->role) {
            Auth::logout();
            return redirect()->route('login')
                ->withErrors(['username' => 'Tu cuenta no tiene un rol asignado. Contactá al administrador.']);
        }

        if ($user->role->name !== 'rrhh') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // A futuro (sistema.newharvest.com.ar) redirigir choferes a la app Flutter.
            return redirect()->route('login')
                ->withErrors(['username' => 'Este panel es exclusivo para administración. Usá la app móvil de New Harvest.']);
        }

        return $next($request);
    }
}
