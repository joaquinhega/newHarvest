<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\SalaryReceipt;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
                    'employee_signed' => !empty($rec->employee_signed_at),
                    'employee_signed_at' => $rec->employee_signed_at ? $rec->employee_signed_at->format('d/m/Y H:i') : null,
                    'employee_signature_url' => $rec->employee_signature_path ? asset('storage/' . $rec->employee_signature_path) : null,
                    'legal_accepted' => (bool) $rec->legal_accepted,
                    'created_at' => $rec->created_at ? $rec->created_at->format('d/m/Y') : null,
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
     * Alta manual de un recibo individual.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'period' => ['required', 'string', 'max:50'],
            'gross_amount' => ['required', 'numeric', 'min:0'],
            'deductions_amount' => ['nullable', 'numeric', 'min:0'],
            'net_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $validated['deductions_amount'] = $validated['deductions_amount'] ?? 0;
        $validated['status'] = 'generado';
        $validated['borrado'] = false;

        $receipt = SalaryReceipt::create($validated);

        return redirect()->back()->with('message', "Recibo #{$receipt->id} creado exitosamente.");
    }

    /**
     * Modificación de montos o período.
     */
    public function update(Request $request, $id)
    {
        $receipt = SalaryReceipt::where('borrado', false)->findOrFail($id);

        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'period' => ['required', 'string', 'max:50'],
            'gross_amount' => ['required', 'numeric', 'min:0'],
            'deductions_amount' => ['nullable', 'numeric', 'min:0'],
            'net_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $receipt->update($validated);

        return redirect()->back()->with('message', "Recibo #{$receipt->id} actualizado.");
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

    private function formatStatusLabel(?string $status): string
    {
        return match ($status) {
            'generado' => 'Generado',
            'notificado' => 'Notificado',
            'leido' => 'Leído',
            'firmado_empresa' => 'Firmado — empresa',
            'firmado_empleado' => 'Firmado — empleado',
            'archivado' => 'Archivado',
            default => 'Generado',
        };
    }
}