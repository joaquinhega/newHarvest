<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\VoucherStoreRequest;
use App\Http\Requests\Api\VoucherUpdateRequest;
use App\Http\Resources\VoucherResource;
use App\Models\Company;
use App\Models\Voucher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VoucherController extends Controller
{
    private function canManageAll(Request $request): bool
    {
        $roleName = $request->user()?->role?->name;

        return in_array($roleName, ['admin', 'rrhh'], true);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Voucher::query()->with(['company', 'user'])
            ->where('borrado', false);

        if (! $this->canManageAll($request)) {
            $query->where('user_id', $request->user()?->id_usuario);
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->integer('company_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
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
            VoucherResource::collection($paginator->getCollection())->resolve(),
            'Vouchers listados correctamente',
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

    public function store(VoucherStoreRequest $request): JsonResponse
    {
        $companyId = $request->validated('company_id');
        $companyName = $request->validated('company_name');

        if ($companyId) {
            $companyName = $companyName ?: Company::query()->whereKey($companyId)->value('name');
        }

        $signaturePath = $this->storeSignature($request->validated('signature_base64'));

        $voucher = DB::transaction(function () use ($request, $companyName, $signaturePath) {
            return Voucher::create([
                'remito_code' => $request->validated('remito_code'),
                'company_id' => $request->validated('company_id'),
                'company_name' => $companyName,
                'user_id' => $request->user()->id_usuario,
                'passenger_name' => $request->validated('passenger_name'),
                'origin' => $request->validated('origin'),
                'pickup_time' => $this->normalizeTime($request->validated('pickup_time')),
                'destination' => $request->validated('destination'),
                'dropoff_time' => $this->normalizeTime($request->validated('dropoff_time')),
                'wait_time' => $request->validated('wait_time'),
                'signature_path' => $signaturePath,
                'date' => $request->validated('date'),
                'amount' => $request->validated('amount'),
                'observation' => $request->validated('observation'),
                'status' => 'pendiente',
                'borrado' => false,
            ]);
        });

        $voucher->load(['company', 'user']);

        return $this->successResponse(
            new VoucherResource($voucher),
            'Voucher creado correctamente',
            201
        );
    }

    public function show(Request $request, Voucher $voucher): JsonResponse
    {
        if ($voucher->borrado) {
            abort(404, 'Voucher no encontrado.');
        }

        $this->authorizeVoucherAccess($request, $voucher);

        $voucher->load(['company', 'user']);

        return $this->successResponse(
            new VoucherResource($voucher),
            'Voucher obtenido correctamente'
        );
    }

    public function update(VoucherUpdateRequest $request, Voucher $voucher): JsonResponse
    {
        if ($voucher->borrado) {
            abort(404, 'Voucher no encontrado.');
        }

        $this->authorizeVoucherAccess($request, $voucher);

        $payload = $request->validated();

        if (array_key_exists('pickup_time', $payload)) {
            $payload['pickup_time'] = $this->normalizeTime($payload['pickup_time']);
        }

        if (array_key_exists('dropoff_time', $payload)) {
            $payload['dropoff_time'] = $this->normalizeTime($payload['dropoff_time']);
        }

        if (array_key_exists('company_id', $payload)) {
            if ($payload['company_id']) {
                $payload['company_name'] = Company::query()
                    ->whereKey($payload['company_id'])
                    ->value('name') ?? ($payload['company_name'] ?? $voucher->company_name);
            }
        }

        if (array_key_exists('signature_base64', $payload)) {
            $payload['signature_path'] = $this->storeSignature($payload['signature_base64']);
            unset($payload['signature_base64']);
        }

        $voucher->fill($payload);
        $voucher->save();

        $voucher->load(['company', 'user']);

        return $this->successResponse(
            new VoucherResource($voucher),
            'Voucher actualizado correctamente'
        );
    }

    public function destroy(Request $request, Voucher $voucher): JsonResponse
    {
        if ($voucher->borrado) {
            abort(404, 'Voucher no encontrado.');
        }

        $this->authorizeVoucherAccess($request, $voucher);

        $voucher->update(['borrado' => true]);

        return $this->successResponse(null, 'Voucher eliminado lógicamente', 200);
    }

    public function approve(Request $request, Voucher $voucher): JsonResponse
    {
        if ($voucher->borrado) {
            abort(404, 'Voucher no encontrado.');
        }

        if (! $this->canManageAll($request)) {
            return $this->errorResponse('No autorizado para aprobar vouchers.', 403);
        }

        $validated = $request->validate([
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
        ]);

        if (array_key_exists('company_id', $validated)) {
            $voucher->company_id = $validated['company_id'];
        }

        if ($voucher->company_id) {
            $voucher->company_name = Company::query()->whereKey($voucher->company_id)->value('name');
        }

        $voucher->status = 'aprobado';
        $voucher->save();

        $voucher->load(['company', 'user']);

        return $this->successResponse(
            new VoucherResource($voucher),
            'Voucher aprobado correctamente'
        );
    }

    private function authorizeVoucherAccess(Request $request, Voucher $voucher): void
    {
        if ($this->canManageAll($request)) {
            return;
        }

        if ((int) $voucher->user_id !== (int) $request->user()?->id_usuario) {
            abort(403, 'No autorizado para acceder a este voucher.');
        }
    }

    private function normalizeTime(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $parts = explode(':', $value);
        if (count($parts) < 2) {
            return $value;
        }

        return str_pad($parts[0], 2, '0', STR_PAD_LEFT) . ':' . str_pad($parts[1], 2, '0', STR_PAD_LEFT);
    }

    private function storeSignature(?string $base64): ?string
    {
        if (! $base64) {
            return null;
        }

        $payload = preg_replace('#^data:image/\w+;base64,#i', '', $base64) ?? $base64;
        $payload = str_replace(' ', '+', $payload);
        $binary = base64_decode($payload, true);

        if ($binary === false) {
            return null;
        }

        $fileName = 'signatures/vouchers/firma_' . uniqid('', true) . '.png';
        Storage::disk('public')->put($fileName, $binary);

        return 'storage/' . $fileName;
    }
}