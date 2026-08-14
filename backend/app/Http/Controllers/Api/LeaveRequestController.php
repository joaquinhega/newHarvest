<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LeaveRequestStoreRequest;
use App\Http\Resources\LeaveRequestResource;
use App\Models\LeaveRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveRequestController extends Controller
{
    private function canManageAll(Request $request): bool
    {
        return in_array($request->user()?->role?->name, ['admin', 'rrhh'], true);
    }

    public function index(Request $request): JsonResponse
    {
        $query = LeaveRequest::query()->with(['employee', 'reviewer'])->where('borrado', false);

        if (! $this->canManageAll($request)) {
            $query->where('employee_id', $request->user()?->employee?->id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $perPage = max(1, min(100, (int) $request->input('per_page', 15)));
        $paginator = $query->orderByDesc('id')->paginate($perPage);

        return $this->successResponse(
            LeaveRequestResource::collection($paginator->getCollection())->resolve(),
            'Solicitudes de licencia y vacaciones listadas correctamente',
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

    public function store(LeaveRequestStoreRequest $request): JsonResponse
    {
        $data = $request->validated();
        
        // Si no se envía employee_id, se toma el del chofer autenticado
        if (empty($data['employee_id'])) {
            $data['employee_id'] = $request->user()?->employee?->id;
            if (! $data['employee_id']) {
                return $this->errorResponse('El usuario no posee legajo asignado.', 422);
            }
        }

        if ($request->hasFile('attachment')) {
            $data['attachment_path'] = $request->file('attachment')->store('certificados', 'public');
        }

        $leave = LeaveRequest::create($data);
        $leave->load('employee');

        return $this->successResponse(new LeaveRequestResource($leave), 'Solicitud enviada correctamente', 201);
    }

    public function approve(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        if (! $this->canManageAll($request)) {
            return $this->errorResponse('No autorizado para aprobar solicitudes.', 403);
        }

        $leaveRequest->update([
            'status'              => 'aprobada',
            'approved_by_user_id' => $request->user()?->id_usuario,
            'action_at'           => now(),
        ]);

        return $this->successResponse(new LeaveRequestResource($leaveRequest), 'Solicitud aprobada correctamente');
    }

    public function reject(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        if (! $this->canManageAll($request)) {
            return $this->errorResponse('No autorizado para rechazar solicitudes.', 403);
        }

        $leaveRequest->update([
            'status'              => 'rechazada',
            'approved_by_user_id' => $request->user()?->id_usuario,
            'action_at'           => now(),
        ]);

        return $this->successResponse(new LeaveRequestResource($leaveRequest), 'Solicitud rechazada');
    }
}