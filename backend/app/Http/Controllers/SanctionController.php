<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Sanction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SanctionController extends Controller
{
    /**
     * Listado general de sanciones y medidas disciplinarias.
     */
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $type = $request->input('type', 'todos');
        $status = $request->input('status', 'todos');

        $query = Sanction::query()
            ->with(['employee.user'])
            ->where('borrado', false);

        if ($type !== 'todos') {
            $query->where('type', $type);
        }

        if ($status !== 'todos') {
            $query->where('status', $status);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('reason', 'like', "%{$search}%")
                  ->orWhere('sanction_number', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%{$search}%")
                  ->orWhereHas('employee', function ($empQuery) use ($search) {
                      $empQuery->where('first_name', 'like', "%{$search}%")
                               ->orWhere('last_name', 'like', "%{$search}%")
                               ->orWhere('cuil', 'like', "%{$search}%")
                               ->orWhere('id', 'like', "%{$search}%");
                  });
            });
        }

        $sanciones = $query->orderByDesc('date')
            ->orderByDesc('id')
            ->get()
            ->map(function ($sanc) {
                $emp = $sanc->employee;
                return [
                    'id' => $sanc->id,
                    'sanction_number' => $sanc->sanction_number ?: str_pad($sanc->id, 3, '0', STR_PAD_LEFT),
                    'code' => 'N° ' . ($sanc->sanction_number ?: str_pad($sanc->id, 3, '0', STR_PAD_LEFT)),
                    'employee_id' => $sanc->employee_id,
                    'legajo' => $emp ? $emp->id : '—',
                    'nombre' => $emp ? "{$emp->last_name}, {$emp->first_name}" : 'Personal desvinculado',
                    'nombre_completo' => $emp ? $emp->full_name : 'Personal desvinculado',
                    'puesto' => $emp ? ($emp->position ?: 'Chofer') : '—',
                    'cuil' => $emp ? ($emp->cuil ?: '—') : '—',
                    'type' => $sanc->type,
                    'type_label' => ucfirst($sanc->type),
                    'date' => $sanc->date ? $sanc->date->format('Y-m-d') : '',
                    'fecha_formateada' => $sanc->date ? $sanc->date->format('d/m/Y') : '—',
                    'days_count' => (int) ($sanc->days_count ?: 0),
                    'reason' => $sanc->reason ?: 'Sin motivo descripto',
                    'status' => $sanc->status ?: 'pendiente',
                    'read_at' => $sanc->read_at ? $sanc->read_at->format('d/m/Y H:i') : null,
                    'signed_at' => $sanc->signed_at ? $sanc->signed_at->format('d/m/Y H:i') : null,
                    'is_signed' => !empty($sanc->signed_at) || $sanc->status === 'firmado' || $sanc->status === 'leido',
                    'signature_url' => $sanc->signature_path ? asset('storage/' . $sanc->signature_path) : null,
                    'status_label' => match($sanc->status) {
                        'pendiente'  => 'Pendiente',
                        'leido'      => 'Leído',
                        'firmado'    => 'Firmado',
                        'archivado'  => 'Archivado',
                        default      => ucfirst($sanc->status ?: 'pendiente'),
                    },
                    'file_path' => $sanc->file_path ?: null,
                ];
            });

        // Nómina de empleados activos para el modal de carga
        $employees = Employee::where('borrado', false)
            ->where('status', 'activo')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'position', 'cuil']);

        // Métricas de cabecera
        $totalCount = Sanction::where('borrado', false)->count();
        $apercibimientosCount = Sanction::where('borrado', false)->where('type', 'apercibimiento')->count();
        $suspensionesCount = Sanction::where('borrado', false)->where('type', 'suspension')->count();
        $pendientesFirmaCount = Sanction::where('borrado', false)->where('status', 'pendiente')->count();

        return Inertia::render('RRHH/Sanciones', [
            'sanciones' => $sanciones,
            'employees' => $employees,
            'metrics' => [
                'total' => $totalCount,
                'apercibimientos' => $apercibimientosCount,
                'suspensiones' => $suspensionesCount,
                'pendientes_firma' => $pendientesFirmaCount,
            ],
            'filters' => [
                'search' => $search,
                'type' => $type,
                'status' => $status,
            ],
        ]);
    }

    /**
     * Alta de sanción / medida disciplinaria.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'sanction_number' => ['nullable', 'string', 'max:50'],
            'type' => ['required', 'in:apercibimiento,suspension'],
            'date' => ['required', 'date'],
            'days_count' => ['nullable', 'integer', 'min:0'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        if ($validated['type'] === 'apercibimiento') {
            $validated['days_count'] = 0;
        }

        if (empty($validated['sanction_number'])) {
            $nextId = (Sanction::max('id') ?? 0) + 1;
            $validated['sanction_number'] = str_pad($nextId, 3, '0', STR_PAD_LEFT);
        }

        $validated['status'] = 'pendiente';
        $validated['borrado'] = false;

        $sanction = Sanction::create($validated);

        return redirect()->back()->with('message', "Sanción N° {$sanction->sanction_number} registrada correctamente.");
    }

    /**
     * Actualización de la medida disciplinaria.
     */
    public function update(Request $request, $id)
    {
        $sanction = Sanction::where('borrado', false)->findOrFail($id);

        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'sanction_number' => ['nullable', 'string', 'max:50'],
            'type' => ['required', 'in:apercibimiento,suspension'],
            'date' => ['required', 'date'],
            'days_count' => ['nullable', 'integer', 'min:0'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        if ($validated['type'] === 'apercibimiento') {
            $validated['days_count'] = 0;
        }

        $sanction->update($validated);

        return redirect()->back()->with('message', "Sanción N° {$sanction->sanction_number} modificada.");
    }

    /**
     * Baja lógica.
     */
    public function destroy($id)
    {
        $sanction = Sanction::where('borrado', false)->findOrFail($id);
        $sanction->borrado = true;
        $sanction->save();

        return redirect()->back()->with('message', "Sanción N° {$sanction->sanction_number} eliminada.");
    }

    /**
     * Exportación de la nómina a CSV UTF-8.
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $search = $request->input('search', '');
        $type = $request->input('type', 'todos');
        $status = $request->input('status', 'todos');

        $query = Sanction::query()->with('employee')->where('borrado', false);

        if ($type !== 'todos') {
            $query->where('type', $type);
        }
        if ($status !== 'todos') {
            $query->where('status', $status);
        }
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('reason', 'like', "%{$search}%")
                  ->orWhere('sanction_number', 'like', "%{$search}%")
                  ->orWhereHas('employee', function ($empQuery) use ($search) {
                      $empQuery->where('first_name', 'like', "%{$search}%")
                               ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        $records = $query->orderByDesc('date')->get();
        $filename = "sanciones_" . date('Ymd_His') . ".csv";

        return response()->streamDownload(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8

            fputcsv($handle, [
                'N° Sanción',
                'Legajo',
                'Empleado',
                'Tipo',
                'Fecha',
                'Días Suspensión',
                'Motivo',
                'Estado Firma / Lectura',
            ], ';');

            foreach ($records as $item) {
                $emp = $item->employee;
                fputcsv($handle, [
                    $item->sanction_number ?: $item->id,
                    $emp ? $emp->id : '',
                    $emp ? "{$emp->last_name}, {$emp->first_name}" : '',
                    ucfirst($item->type),
                    $item->date ? $item->date->format('d/m/Y') : '',
                    $item->type === 'suspension' ? $item->days_count : 0,
                    $item->reason,
                    ucfirst($item->status),
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}