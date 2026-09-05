<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SalaryReceiptStoreRequest;
use App\Http\Resources\SalaryReceiptResource;
use App\Models\SalaryReceipt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SalaryReceiptController extends Controller
{
    private function canManageAll(Request $request): bool
    {
        return in_array($request->user()?->role?->name, ['admin', 'rrhh'], true);
    }

    public function index(Request $request): JsonResponse
    {
        $query = SalaryReceipt::query()->with('employee')->where('borrado', false);

        if (! $this->canManageAll($request)) {
            $employeeId = $request->user()?->employee?->id;
            $query->where('employee_id', $employeeId);
        } elseif ($request->filled('employee_id')) {
            $query->where('employee_id', $request->integer('employee_id'));
        }

        if ($request->filled('period')) {
            $query->where('period', $request->string('period'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $perPage = max(1, min(100, (int) $request->input('per_page', 15)));
        $paginator = $query->orderByDesc('id')->paginate($perPage);

        return $this->successResponse(
            SalaryReceiptResource::collection($paginator->getCollection())->resolve(),
            'Recibos de sueldo listados correctamente',
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

    public function store(SalaryReceiptStoreRequest $request): JsonResponse
    {
        if (! $this->canManageAll($request)) {
            return $this->errorResponse('No autorizado para emitir recibos de sueldo.', 403);
        }

        $data = $request->validated();
        $concepts = $data['concepts'] ?? null;
        unset($data['concepts']);

        $receipt = DB::transaction(function () use ($data, $concepts) {
            $receipt = SalaryReceipt::create($data);

            if (! empty($concepts)) {
                $this->syncConcepts($receipt, $concepts);
                $receipt->recalculateTotalsFromConcepts()->save();
            }

            return $receipt;
        });

        $receipt->load(['employee', 'concepts']);

        return $this->successResponse(
            new SalaryReceiptResource($receipt),
            'Recibo de sueldo emitido correctamente',
            201
        );
    }

    /**
     * Reemplaza todos los conceptos del recibo por los recibidos, preservando el orden de llegada.
     */
    private function syncConcepts(SalaryReceipt $receipt, array $concepts): void
    {
        $receipt->concepts()->delete();

        foreach (array_values($concepts) as $index => $concept) {
            $receipt->concepts()->create([
                'code' => $concept['code'] ?? null,
                'description' => $concept['description'],
                'quantity' => $concept['quantity'] ?? null,
                'remunerative_amount' => $concept['remunerative_amount'] ?? 0,
                'non_remunerative_amount' => $concept['non_remunerative_amount'] ?? 0,
                'deduction_amount' => $concept['deduction_amount'] ?? 0,
                'sort_order' => $index,
            ]);
        }
    }

    public function show(Request $request, SalaryReceipt $salaryReceipt): JsonResponse
    {
        if ($salaryReceipt->borrado) abort(404, 'Recibo no encontrado.');
        
        if (! $this->canManageAll($request) && (int) $salaryReceipt->employee_id !== (int) $request->user()?->employee?->id) {
            return $this->errorResponse('No autorizado para ver este recibo.', 403);
        }

        // Marca como leído si es el empleado quien lo abre
        if (! $this->canManageAll($request) && in_array($salaryReceipt->status, ['generado', 'notificado'])) {
            $salaryReceipt->update(['status' => 'leido']);
        }

        $salaryReceipt->load(['employee', 'concepts']);

        return $this->successResponse(new SalaryReceiptResource($salaryReceipt), 'Recibo obtenido');
    }

    public function signEmployer(Request $request, SalaryReceipt $salaryReceipt): JsonResponse
    {
        if (! $this->canManageAll($request)) {
            return $this->errorResponse('No autorizado para firmar por la empresa.', 403);
        }

        $salaryReceipt->update([
            'employer_signed_at' => now(),
            'status' => 'firmado_empresa'
        ]);

        return $this->successResponse(new SalaryReceiptResource($salaryReceipt), 'Recibo firmado por el apoderado');
    }

    public function signEmployee(Request $request, SalaryReceipt $salaryReceipt): JsonResponse
    {
        $employee = $request->user()?->employee;
        if (! $employee || (int) $salaryReceipt->employee_id !== (int) $employee->id) {
            return $this->errorResponse('No autorizado para firmar este recibo.', 403);
        }

        if (! $salaryReceipt->employer_signed_at) {
            return $this->errorResponse('Este recibo todavía no fue firmado por la empresa. Esperá la notificación para poder firmarlo.', 422);
        }

        $request->validate([
            'signature_base64' => ['required', 'string'],
            'legal_accepted'   => ['required', 'accepted'],
        ]);

        $signaturePath = $this->saveBase64Signature($request->string('signature_base64'), 'firmas_recibos');

        $salaryReceipt->update([
            'employee_signature_path' => $signaturePath,
            'employee_signed_at'      => now(),
            'legal_accepted'          => true,
            'status'                  => 'firmado_empleado'
        ]);

        return $this->successResponse(new SalaryReceiptResource($salaryReceipt), 'Recibo firmado por el empleado');
    }

    private function saveBase64Signature(string $base64String, string $folder): ?string
    {
        try {
            if (preg_match('/^data:image\/(\w+);base64,/', $base64String, $type)) {
                $base64String = substr($base64String, strpos($base64String, ',') + 1);
                $type = strtolower($type[1]);
            } else {
                $type = 'png';
            }

            $imageData = base64_decode($base64String);
            if ($imageData === false) return null;

            $fileName = "{$folder}/sig_" . Str::random(20) . ".{$type}";
            Storage::disk('public')->put($fileName, $imageData);

            return $fileName;
        } catch (\Exception $e) {
            return null;
        }
    }
}