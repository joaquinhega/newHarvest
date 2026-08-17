<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user() ? [
                    'id_usuario' => $request->user()->id_usuario ?? $request->user()->id,
                    'nombre' => $request->user()->first_name ?? $request->user()->nombre ?? $request->user()->username ?? 'Admin',
                    'apellido' => $request->user()->last_name ?? $request->user()->apellido ?? '',
                    'email' => $request->user()->email ?? '',
                    'Rol' => $request->user()->role->name ?? $request->user()->Rol ?? 'RRHH',
                ] : null,
            ],
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ]);
    }
}