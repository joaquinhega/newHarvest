<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CompanyStoreRequest;
use App\Http\Requests\Api\CompanyUpdateRequest;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    private function canManageCompanies(Request $request): bool
    {
        $roleName = $request->user()?->role?->name;

        return in_array($roleName, ['admin', 'rrhh'], true);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Company::query()
            ->withCount('vouchers')
            ->where('borrado', false);

        if ($request->filled('search')) {
            $search = '%' . $request->string('search') . '%';
            $query->where('name', 'like', $search);
        }

        $perPage = max(1, min(100, (int) $request->input('per_page', 15)));
        $paginator = $query->orderBy('name')->paginate($perPage);

        return $this->successResponse(
            CompanyResource::collection($paginator->getCollection())->resolve(),
            'Empresas listadas correctamente',
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

    public function store(CompanyStoreRequest $request): JsonResponse
    {
        if (! $this->canManageCompanies($request)) {
            return $this->errorResponse('No autorizado para crear empresas.', 403);
        }

        $company = Company::create([
            'name' => $request->validated('name'),
            'logo_path' => $request->validated('logo_path'),
            'borrado' => false,
        ]);

        $company->loadCount('vouchers');

        return $this->successResponse(
            new CompanyResource($company),
            'Empresa creada correctamente',
            201
        );
    }

    public function show(Request $request, Company $company): JsonResponse
    {
        if ($company->borrado) {
            abort(404, 'Empresa no encontrada.');
        }

        $company->loadCount('vouchers');

        return $this->successResponse(
            new CompanyResource($company),
            'Empresa obtenida correctamente'
        );
    }

    public function update(CompanyUpdateRequest $request, Company $company): JsonResponse
    {
        if (! $this->canManageCompanies($request)) {
            return $this->errorResponse('No autorizado para actualizar empresas.', 403);
        }

        if ($company->borrado) {
            abort(404, 'Empresa no encontrada.');
        }

        $company->fill($request->validated());
        $company->save();

        $company->loadCount('vouchers');

        return $this->successResponse(
            new CompanyResource($company),
            'Empresa actualizada correctamente'
        );
    }

    public function destroy(Request $request, Company $company): JsonResponse
    {
        if (! $this->canManageCompanies($request)) {
            return $this->errorResponse('No autorizado para eliminar empresas.', 403);
        }

        if ($company->borrado) {
            abort(404, 'Empresa no encontrada.');
        }

        $company->update(['borrado' => true]);

        return $this->successResponse(null, 'Empresa eliminada lógicamente', 200);
    }
}