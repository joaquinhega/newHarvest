<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CombustibleStoreRequest;
use App\Http\Requests\Api\CombustibleUpdateRequest;
use App\Http\Resources\CombustibleResource;
use App\Models\Combustible;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CombustibleController extends Controller
{
    private function canManageAll(Request $request): bool
    {
        $roleName = $request->user()?->role?->name;

        return in_array($roleName, ['admin', 'rrhh'], true);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Combustible::query()
            ->with('user')
            ->where('borrado', false);

        if (! $this->canManageAll($request)) {
            $query->where('user_id', $request->user()?->id_usuario);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('plate')) {
            $query->where('plate', 'like', '%' . $request->string('plate') . '%');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date('date_to'));
        }

        $perPage = max(1, min(100, (int) $request->input('per_page', 15)));
        $paginator = $query->orderByDesc('date')->paginate($perPage);

        return $this->successResponse(
            CombustibleResource::collection($paginator->getCollection())->resolve(),
            'Remitos de combustible listados correctamente',
            200,
            [
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                    'has_more_pages' => $paginator->hasMorePages(),
                ],
            ]
        );
    }

    public function store(CombustibleStoreRequest $request): JsonResponse
    {
        $driverName = $request->validated('driver_name');

        if (! $driverName) {
            $user = $request->user();
            $driverName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
            if ($driverName === '') {
                $driverName = $user->username;
            }
        }

        $combustible = Combustible::create([
            'remito_code' => $request->validated('remito_code'),
            'user_id' => $request->user()->id_usuario,
            'driver_name' => $driverName,
            'plate' => strtoupper($request->validated('plate')),
            'date' => $request->validated('date'),
            'amount' => $request->validated('amount'),
            'status' => 'pendiente',
            'borrado' => false,
        ]);

        $combustible->load('user');

        return $this->successResponse(
            new CombustibleResource($combustible),
            'Remito de combustible creado correctamente',
            201
        );
    }

    public function show(Request $request, Combustible $combustible): JsonResponse
    {
        if ($combustible->borrado) {
            abort(404, 'Remito de combustible no encontrado.');
        }

        $this->authorizeCombustibleAccess($request, $combustible);

        $combustible->load('user');

        return $this->successResponse(
            new CombustibleResource($combustible),
            'Remito de combustible obtenido correctamente'
        );
    }

    public function update(CombustibleUpdateRequest $request, Combustible $combustible): JsonResponse
    {
        if ($combustible->borrado) {
            abort(404, 'Remito de combustible no encontrado.');
        }

        $this->authorizeCombustibleAccess($request, $combustible);

        $payload = $request->validated();
        if (isset($payload['plate'])) {
            $payload['plate'] = strtoupper($payload['plate']);
        }

        $combustible->fill($payload);
        $combustible->save();

        $combustible->load('user');

        return $this->successResponse(
            new CombustibleResource($combustible),
            'Remito de combustible actualizado correctamente'
        );
    }

    public function destroy(Request $request, Combustible $combustible): JsonResponse
    {
        if ($combustible->borrado) {
            abort(404, 'Remito de combustible no encontrado.');
        }

        $this->authorizeCombustibleAccess($request, $combustible);

        $combustible->update(['borrado' => true]);

        return $this->successResponse(null, 'Remito de combustible eliminado lógicamente', 200);
    }

    public function approve(Request $request, Combustible $combustible): JsonResponse
    {
        if ($combustible->borrado) {
            abort(404, 'Remito de combustible no encontrado.');
        }

        if (! $this->canManageAll($request)) {
            return $this->errorResponse('No autorizado para aprobar remitos de combustible.', 403);
        }

        $combustible->status = 'aprobado';
        $combustible->save();

        $combustible->load('user');

        return $this->successResponse(
            new CombustibleResource($combustible),
            'Remito de combustible aprobado correctamente'
        );
    }

    private function authorizeCombustibleAccess(Request $request, Combustible $combustible): void
    {
        if ($this->canManageAll($request)) {
            return;
        }

        if ((int) $combustible->user_id !== (int) $request->user()?->id_usuario) {
            abort(403, 'No autorizado para acceder a este remito de combustible.');
        }
    }
}