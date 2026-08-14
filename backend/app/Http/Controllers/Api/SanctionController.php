<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SanctionResource;
use App\Models\Sanction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SanctionController extends Controller
{
    private function canManageAll(Request $request): bool
    {
        return in_array($request->user()?->role?->name, ['admin', 'rrhh'], true);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Sanction::query()->with('employee')->where('borrado', false);

        if (! $this->canManageAll($request)) {
            $query->where('employee_id', $request->user()?->employee?->id);
        }

        $perPage = max(1, min(100, (int) $request->input('per_page', 15)));
        $paginator = $query->orderByDesc('date')->paginate($perPage);

        return $this->successResponse(
            SanctionResource::collection($paginator->getCollection())->resolve(),
            'Sanciones listadas correctamente',
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

    public function store(Request $request): JsonResponse
    {
        if (! $this->canManageAll($request)) {
            return $this->errorResponse('No autorizado para emitir sanciones.', 403);
        }

        $validated = $request->validate([
            'employee_id'     => ['required', 'exists:employees,id'],
            'sanction_number' => ['nullable', 'string', 'max:50'],
            'type'            => ['required', 'in:apercibimiento,suspension'],
            'days_count'      => ['nullable', 'integer', 'min:0'],
            'date'            => ['required', 'date'],
            'reason'          => ['required', 'string'],
        ]);

        $sanction = Sanction::create($validated);
        $sanction->load('employee');

        return $this->successResponse(new SanctionResource($sanction), 'Sanción registrada correctamente', 201);
    }

    public function confirmRead(Request $request, Sanction $sanction): JsonResponse
    {
        $employee = $request->user()?->employee;
        if (! $employee || (int) $sanction->employee_id !== (int) $employee->id) {
            return $this->errorResponse('No autorizado.', 403);
        }

        $sanction->update([
            'status'  => 'leido',
            'read_at' => now()
        ]);

        return $this->successResponse(new SanctionResource($sanction), 'Lectura de sanción confirmada');
    }
}