<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\SalaryReceipt;
use App\Services\SalaryReceiptPdfSplitter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalaryReceiptController extends Controller
{
    /**
     * Listado general de recibos de sueldo con filtrado reactivo y métricas.
     */
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $status = $request->input('status', 'todos');
        $period = $request->input('period', 'todos');

        $query = SalaryReceipt::query()
            ->with(['employee.user'])
            ->where('borrado', false);

        if ($status !== 'todos') {
            $query->where('status', $status);
        }

        if ($period !== 'todos') {
            $query->where('period', $period);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('period', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%{$search}%")
                  ->orWhereHas('employee', function ($empQuery) use ($search) {
                      $empQuery->where('first_name', 'like', "%{$search}%")
                               ->orWhere('last_name', 'like', "%{$search}%")
                               ->orWhere('cuil', 'like', "%{$search}%")
                               ->orWhere('id', 'like', "%{$search}%");
                  });
            });
        }

        $recibos = $query->orderByDesc('id')
            ->with(['audits.user'])
            ->get()
            ->map(function ($rec) {
                $emp = $rec->employee;
                return [
                    'id' => $rec->id,
                    'employee_id' => $rec->employee_id,
                    'legajo' => $emp ? $emp->id : '—',
                    'nombre' => $emp ? "{$emp->last_name}, {$emp->first_name}" : 'Personal desvinculado',
                    'nombre_completo' => $emp ? $emp->full_name : 'Personal desvinculado',
                    'cuil' => $emp ? ($emp->cuil ?: '—') : '—',
                    'puesto' => $emp ? ($emp->position ?: 'Chofer') : '—',
                    'period' => $rec->period,
                    'gross_amount' => (float) $rec->gross_amount,
                    'gross_formatted' => '$ ' . number_format((float) $rec->gross_amount, 2, ',', '.'),
                    'deductions_amount' => (float) $rec->deductions_amount,
                    'deductions_formatted' => '$ ' . number_format((float) $rec->deductions_amount, 2, ',', '.'),
                    'net_amount' => (float) $rec->net_amount,
                    'net_formatted' => '$ ' . number_format((float) $rec->net_amount, 2, ',', '.'),
                    'status' => $rec->status ?: 'generado',
                    'status_label' => $this->formatStatusLabel($rec->status),
                    'file_url' => $rec->file_path ? asset('storage/' . $rec->file_path) : null,
                    'employer_signed' => !empty($rec->employer_signed_at),
                    'employer_signed_at' => $rec->employer_signed_at ? $rec->employer_signed_at->format('d/m/Y H:i') : null,
                    'notified_at' => $rec->notified_at ? $rec->notified_at->format('d/m/Y H:i') : null,
                    'employee_signed' => !empty($rec->employee_signed_at),
                    'employee_signed_at' => $rec->employee_signed_at ? $rec->employee_signed_at->format('d/m/Y H:i') : null,
                    'employee_signature_url' => $rec->employee_signature_path ? asset('storage/' . $rec->employee_signature_path) : null,
                    'legal_accepted' => (bool) $rec->legal_accepted,
                    'created_at' => $rec->created_at ? $rec->created_at->format('d/m/Y') : null,
                    'audits' => $rec->audits->map(fn ($a) => [
                        'id'         => $a->id,
                        'event'      => $a->event,
                        'user_name'  => $a->user_name,
                        'batch_id'   => $a->batch_id,
                        'occurred_at'=> $a->occurred_at?->format('d/m/Y H:i'),
                        'metadata'   => $a->metadata,
                    ])->values(),
                ];
            });

        // Nómina activa para selección
        $employees = Employee::where('borrado', false)
            ->where('status', 'activo')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'position', 'cuil']);

        // Períodos únicos registrados
        $availablePeriods = SalaryReceipt::where('borrado', false)
            ->distinct()
            ->pluck('period')
            ->toArray();

        // Contadores del circuito
        $metrics = [
            'total' => SalaryReceipt::where('borrado', false)->count(),
            'generados' => SalaryReceipt::where('borrado', false)->where('status', 'generado')->count(),
            'firmados_empresa' => SalaryReceipt::where('borrado', false)->where('status', 'firmado_empresa')->count(),
            'firmados_empleado' => SalaryReceipt::where('borrado', false)->whereIn('status', ['firmado_empleado', 'archivado'])->count(),
        ];

        return Inertia::render('RRHH/Recibos', [
            'recibos' => $recibos,
            'employees' => $employees,
            'availablePeriods' => $availablePeriods,
            'metrics' => $metrics,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'period' => $period,
            ],
        ]);
    }

    /**
     * import() eliminado — unificado bajo analyzeBulk()/confirmBulk(), que
     * ahora manejan tanto el PDF masivo con todos los empleados como la
     * subida de un recibo suelto (cuando no se detecta ningún CUIL en el
     * documento, se trata como un único grupo pendiente de asignación
     * manual). Ver SalaryReceiptPdfSplitter::analyze().
     */

    /**
     * Paso 1 de la importación masiva: recibe UN PDF con todos los recibos
     * de la nómina (formato típico de exportación del liquidador de sueldos),
     * lo guarda temporalmente y devuelve la detección automática por CUIL
     * para que RRHH revise/corrija antes de confirmar. No crea nada en DB.
     */
    public function analyzeBulk(Request $request, SalaryReceiptPdfSplitter $splitter)
    {
        $request->validate([
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:20480'], // máx 20 MB
        ]);

        $tempPath = $request->file('pdf')->store('receipts/tmp', 'local');
        $absolutePath = Storage::disk('local')->path($tempPath);

        $result = $splitter->analyze($absolutePath);

        return response()->json([
            'temp_token'       => $tempPath,
            'total_pages'      => $result['total_pages'],
            'suggested_period' => $result['suggested_period'],
            'groups'           => $result['groups'],
        ]);
    }

    /**
     * Paso 2: con el temp_token del análisis y los grupos ya revisados/
     * corregidos por RRHH, divide el PDF y crea un SalaryReceipt por
     * cada grupo confirmado con empleado asignado.
     */
    public function confirmBulk(Request $request, SalaryReceiptPdfSplitter $splitter)
    {
        $validated = $request->validate([
            'temp_token'                     => ['required', 'string'],
            'period'                         => ['required', 'string', 'max:50'],
            'groups'                         => ['required', 'array', 'min:1'],
            'groups.*.employee_id'           => ['required', 'exists:employees,id'],
            'groups.*.pages'                 => ['required', 'array', 'min:1'],
            'groups.*.pages.*'               => ['required', 'integer', 'min:1'],
            'groups.*.gross_amount'          => ['nullable', 'numeric', 'min:0'],
            'groups.*.deductions_amount'     => ['nullable', 'numeric', 'min:0'],
            'groups.*.net_amount'            => ['nullable', 'numeric', 'min:0'],
            'groups.*.employee_already_signed' => ['nullable', 'boolean'],
            'groups.*.employer_already_signed' => ['nullable', 'boolean'],
        ]);

        if (! Storage::disk('local')->exists($validated['temp_token'])) {
            return redirect()->back()->withErrors(['temp_token' => 'El archivo temporal expiró, volvé a subir el PDF.']);
        }

        $sourcePath = Storage::disk('local')->path($validated['temp_token']);
        $created = 0;
        $user = $request->user();
        $now = now();

        foreach ($validated['groups'] as $group) {
            $fileName = 'receipts/' . uniqid('bulk_', true) . '.pdf';
            $destPath = Storage::disk('public')->path($fileName);
            $splitter->extractPages($sourcePath, $group['pages'], $destPath);

            $employerSigned = $group['employer_already_signed'] ?? false;
            $employeeSigned = $group['employee_already_signed'] ?? false;

            // El PDF histórico ya trae el sello de ambas partes (proceso viejo
            // vía Adobe/TCPDF — sin firma criptográfica real, ver nota en
            // SalaryReceiptPdfSplitter). No tiene sentido pedirle al chofer
            // que vuelva a firmar en newHarvest algo que el proceso anterior
            // ya cerró, así que el recibo entra directo con el estado que
            // corresponda según lo detectado.
            $status = 'generado';
            if ($employerSigned && $employeeSigned) {
                $status = 'firmado_empleado';
            } elseif ($employerSigned) {
                $status = 'firmado_empresa';
            }

            SalaryReceipt::create([
                'employee_id'             => $group['employee_id'],
                'period'                  => $validated['period'],
                'gross_amount'            => $group['gross_amount'] ?? 0,
                'deductions_amount'       => $group['deductions_amount'] ?? 0,
                'net_amount'              => $group['net_amount'] ?? 0,
                'file_path'               => $fileName,
                'status'                  => $status,
                'employer_signed_at'      => $employerSigned ? $now : null,
                'employer_signature_path' => $employerSigned ? 'historico_pdf' : null,
                'employee_signed_at'      => $employeeSigned ? $now : null,
                'legal_accepted'          => $employeeSigned,
                'borrado'                 => false,
            ]);
            $created++;
        }

        Storage::disk('local')->delete($validated['temp_token']);

        return redirect()->back()->with('message', "{$created} recibos importados y divididos correctamente.");
    }

    /**
     * Firma de empresa por lote — Lote 4.
     *
     * Recibe un array de IDs de recibos, verifica que todos sean válidos
     * y aplica la firma de la empresa sobre cada uno dentro de una transacción.
     *
     * MODO SIMULADO (desarrollo): mientras no haya token/certificado USB real,
     * se guarda únicamente employer_signed_at y el nombre del firmante.
     * En producción (Lote 9) este método se extiende para disparar la firma
     * criptográfica real sobre el PDF — el flujo de confirmación y los estados
     * quedan exactamente igual.
     *
     * El flag NEWHARVEST_FIRMA_MODO=simulado|real en .env controla el modo.
     */
    public function signBatch(Request $request)
    {
        $validated = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:salary_receipts,id'],
        ]);

        $user = $request->user();
        $signerName = trim("{$user->first_name} {$user->last_name}");
        $now = now();
        $modo = config('newharvest.firma_modo', 'simulado');

        $recibos = SalaryReceipt::whereIn('id', $validated['ids'])
            ->where('borrado', false)
            ->whereNotIn('status', ['firmado_empresa', 'archivado'])
            ->get();

        if ($recibos->isEmpty()) {
            return redirect()->back()->withErrors([
                'batch' => 'No hay recibos válidos para firmar en la selección.',
            ]);
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($recibos, $signerName, $now, $modo) {
            foreach ($recibos as $receipt) {
                $receipt->update([
                    'employer_signed_at'      => $now,
                    'employer_signature_path' => $modo === 'simulado'
                        ? 'simulado'   // En Lote 9 se reemplaza por la ruta real del PDF firmado
                        : null,        // placeholder hasta integrar el token USB
                    'status' => 'firmado_empresa',
                ]);
            }
        });

        $count = $recibos->count();
        $modoLabel = $modo === 'simulado' ? ' (modo simulado)' : '';

        return redirect()->back()->with(
            'message',
            "✓ {$count} " . ($count === 1 ? 'recibo firmado' : 'recibos firmados') . " por {$signerName}{$modoLabel}."
        );
    }

    /**
     * store eliminado — recibos solo se crean por importación de PDF externo.
     */

    /**
     * Reasignación: corrige employee_id o period si se importó incorrectamente.
     * No permite modificar montos — el PDF es la fuente de verdad.
     */
    public function update(Request $request, $id)
    {
        $receipt = SalaryReceipt::where('borrado', false)->findOrFail($id);

        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'period'      => ['required', 'string', 'max:50'],
        ]);

        $receipt->update($validated);

        return redirect()->back()->with('message', "Recibo #{$receipt->id} reasignado correctamente.");
    }

    /**
     * Baja lógica del recibo.
     */
    public function destroy($id)
    {
        $receipt = SalaryReceipt::where('borrado', false)->findOrFail($id);
        $receipt->borrado = true;
        $receipt->save();

        return redirect()->back()->with('message', "Recibo #{$receipt->id} eliminado.");
    }

    /**
     * Exportación de la nómina liquidada a CSV / Excel con codificación UTF-8.
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $search = $request->input('search', '');
        $status = $request->input('status', 'todos');
        $period = $request->input('period', 'todos');

        $query = SalaryReceipt::query()->with('employee')->where('borrado', false);

        if ($status !== 'todos') {
            $query->where('status', $status);
        }
        if ($period !== 'todos') {
            $query->where('period', $period);
        }
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('period', 'like', "%{$search}%")
                  ->orWhereHas('employee', function ($empQuery) use ($search) {
                      $empQuery->where('first_name', 'like', "%{$search}%")
                               ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        $records = $query->orderByDesc('id')->get();
        $filename = "recibos_sueldo_" . date('Ymd_His') . ".csv";

        return response()->streamDownload(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8

            fputcsv($handle, [
                'ID Recibo',
                'Legajo',
                'Apellido y Nombre',
                'CUIL',
                'Período',
                'Sueldo Bruto ($)',
                'Deducciones ($)',
                'Neto a Cobrar ($)',
                'Estado',
                'Firma Empresa',
                'Firma Empleado',
            ], ';');

            foreach ($records as $item) {
                $emp = $item->employee;
                fputcsv($handle, [
                    $item->id,
                    $emp ? $emp->id : '',
                    $emp ? "{$emp->last_name}, {$emp->first_name}" : '',
                    $emp ? $emp->cuil : '',
                    $item->period,
                    number_format((float)$item->gross_amount, 2, ',', '.'),
                    number_format((float)$item->deductions_amount, 2, ',', '.'),
                    number_format((float)$item->net_amount, 2, ',', '.'),
                    $this->formatStatusLabel($item->status),
                    $item->employer_signed_at ? 'Firmado (' . $item->employer_signed_at->format('d/m/Y') . ')' : 'Pendiente',
                    $item->employee_signed_at ? 'Firmado (' . $item->employee_signed_at->format('d/m/Y') . ')' : 'Pendiente',
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Firma individual de un recibo (desde el modal de detalle o acciones de fila).
     */
    public function signSingle(Request $request, $id)
    {
        $receipt = SalaryReceipt::where('borrado', false)
            ->whereNotIn('status', ['firmado_empresa', 'firmado_empleado', 'archivado'])
            ->findOrFail($id);

        $user     = $request->user();
        $now      = now();
        $modo     = config('newharvest.firma_modo', 'simulado');

        $receipt->update([
            'employer_signed_at'      => $now,
            'employer_signature_path' => $modo === 'simulado' ? 'simulado' : null,
            'status'                  => 'firmado_empresa',
        ]);

        return redirect()->back()->with('message', "Recibo #{$receipt->id} firmado por {$user->first_name} {$user->last_name}.");
    }

    /**
     * Notifica al empleado que tiene un recibo disponible para firmar.
     * Hoy registra la fecha y queda lista la UI. Push/email se integra en Fase 3.
     */
    public function notify(Request $request, $id)
    {
        $receipt = SalaryReceipt::where('borrado', false)
            ->where('status', 'firmado_empresa')
            ->findOrFail($id);

        $user = $request->user();

        $receipt->update([
            'status'              => 'notificado',
            'notified_at'         => now(),
            'notified_by_user_id' => $user->id_usuario,
        ]);

        // TODO Fase 3: disparar push Firebase al chofer $receipt->employee->user

        return redirect()->back()->with('message', "Empleado notificado. Cuando Firebase esté configurado recibirá un push.");
    }

    private function formatStatusLabel(?string $status): string
    {
        return match ($status) {
            'generado'         => 'Subido',
            'notificado'       => 'Notificado',
            'leido'            => 'Visto por empleado',
            'firmado_empresa'  => 'Firmado — empresa',
            'firmado_empleado' => 'Completo',
            'archivado'        => 'En Drive',
            default            => 'Subido',
        };
    }
}