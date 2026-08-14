<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\EmployeeStoreRequest;
use App\Http\Requests\Api\EmployeeUpdateRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    private function canManageAll(Request $request): bool
    {
        $roleName = $request->user()?->role?->name;

        return in_array($roleName, ['admin', 'rrhh'], true);
    }

    public function index(Request $request): JsonResponse
    {
        if (! $this->canManageAll($request)) {
            return $this->errorResponse('No autorizado para gestionar el personal.', 403);
        }

        $query = Employee::query()
            ->with('user')
            ->where('borrado', false);

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('cuil', 'like', "%{$search}%");
            });
        }

        if ($request->filled('position')) {
            $query->where('position', $request->string('position'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $perPage = max(1, min(100, (int) $request->input('per_page', 15)));
        $paginator = $query->orderBy('id')->paginate($perPage);

        return $this->successResponse(
            EmployeeResource::collection($paginator->getCollection())->resolve(),
            'Personal listado correctamente',
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

    public function store(EmployeeStoreRequest $request): JsonResponse
    {
        if (! $this->canManageAll($request)) {
            return $this->errorResponse('No autorizado para crear legajos de personal.', 403);
        }

        $employee = Employee::create($request->validated());
        $employee->load('user');

        return $this->successResponse(
            new EmployeeResource($employee),
            'Legajo de personal creado correctamente',
            201
        );
    }

    public function show(Request $request, Employee $employee): JsonResponse
    {
        if ($employee->borrado) {
            abort(404, 'Legajo de personal no encontrado.');
        }

        if (! $this->canManageAll($request) && (int) $employee->user_id !== (int) $request->user()?->id_usuario) {
            return $this->errorResponse('No autorizado para ver este legajo.', 403);
        }

        $employee->load('user');

        return $this->successResponse(
            new EmployeeResource($employee),
            'Legajo obtenido correctamente'
        );
    }

    public function update(EmployeeUpdateRequest $request, Employee $employee): JsonResponse
    {
        if ($employee->borrado) {
            abort(404, 'Legajo de personal no encontrado.');
        }

        if (! $this->canManageAll($request)) {
            return $this->errorResponse('No autorizado para actualizar legajos.', 403);
        }

        $employee->update($request->validated());
        $employee->load('user');

        return $this->successResponse(
            new EmployeeResource($employee),
            'Legajo actualizado correctamente'
        );
    }

    public function destroy(Request $request, Employee $employee): JsonResponse
    {
        if ($employee->borrado) {
            abort(404, 'Legajo de personal no encontrado.');
        }

        if (! $this->canManageAll($request)) {
            return $this->errorResponse('No autorizado para eliminar legajos.', 403);
        }

        $employee->update(['borrado' => true, 'status' => 'inactivo']);

        return $this->successResponse(null, 'Legajo eliminado lógicamente', 200);
    }
}