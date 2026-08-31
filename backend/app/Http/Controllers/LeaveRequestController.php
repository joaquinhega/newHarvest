<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeaveRequestController extends Controller
{
    /**
     * Listado general de licencias, vacaciones y certificados.
     */
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $status = $request->input('status', 'todos');
        $type = $request->input('type', 'todos');

        $query = LeaveRequest::query()
            ->with(['employee.user', 'reviewer'])
            ->where('borrado', false);

        if ($status !== 'todos') {
            $query->where('status', $status);
        }

        if ($type !== 'todos') {
            $query->where('type', $type);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('diagnosis', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%")
                  ->orWhereHas('employee', function ($empQuery) use ($search) {
                      $empQuery->where('first_name', 'like', "%{$search}%")
                               ->orWhere('last_name', 'like', "%{$search}%")
                               ->orWhere('cuil', 'like', "%{$search}%")
                               ->orWhere('id', 'like', "%{$search}%");
                  });
            });
        }

        $licencias = $query->orderByDesc('created_at')
            ->get()
            ->map(function ($req) {
                $emp = $req->employee;
                return [
                    'id' => $req->id,
                    'employee_id' => $req->employee_id,
                    'legajo' => $emp ? $emp->id : '—',
                    'nombre' => $emp ? "{$emp->last_name}, {$emp->first_name}" : 'Personal desvinculado',
                    'puesto' => $emp ? ($emp->position ?: 'Chofer') : '—',
                    'tipo' => $req->type,
                    'tipo_label' => $this->formatTypeLabel($req->type),
                    'dias_count' => $req->days_count,
                    'start_date' => $req->start_date ? $req->start_date->format('Y-m-d') : null,
                    'end_date' => $req->end_date ? $req->end_date->format('Y-m-d') : null,
                    'periodo' => $this->formatPeriodo($req->start_date, $req->end_date),
                    'diagnosis' => $req->diagnosis ?: 'Sin observaciones',
                    'attachment_path' => $req->attachment_path ? asset('storage/' . $req->attachment_path) : null,
                    'has_attachment' => !empty($req->attachment_path),
                    'status' => $req->status ?: 'pendiente',
                    'reviewer_name' => $req->reviewer ? trim("{$req->reviewer->first_name} {$req->reviewer->last_name}") : null,
                    'action_at' => $req->action_at ? $req->action_at->format('d/m/Y H:i') : null,
                    'created_at' => $req->created_at ? $req->created_at->format('d/m/Y') : null,
                ];
            });

        // Listado de empleados activos para el selector modal de nueva licencia
        $employees = Employee::where('borrado', false)
            ->where('status', 'activo')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'position', 'cuil']);

        // Contadores para métricas de cabecera
        $pendientesCount = LeaveRequest::where('borrado', false)->where('status', 'pendiente')->count();
        $aprobadasCount = LeaveRequest::where('borrado', false)->where('status', 'aprobada')->count();

        return Inertia::render('RRHH/Vacaciones', [
            'licencias' => $licencias,
            'employees' => $employees,
            'metrics' => [
                'pendientes' => $pendientesCount,
                'aprobadas' => $aprobadasCount,
                'total' => $licencias->count(),
            ],
            'filters' => [
                'search' => $search,
                'status' => $status,
                'type' => $type,
            ],
        ]);
    }

    /**
     * Alta manual de licencia / vacaciones / certificado médico desde el Backoffice.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'type' => ['required', 'in:vacaciones,certificado_medico,licencia_especial'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'days_count' => ['nullable', 'integer', 'min:1'],
            'diagnosis' => ['nullable', 'string', 'max:500'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        // Cálculo de días en caso de omitirse
        if (empty($validated['days_count'])) {
            $start = Carbon::parse($validated['start_date']);
            $end = Carbon::parse($validated['end_date']);
            $validated['days_count'] = $start->diffInDays($end) + 1;
        }

        // Subida de comprobante/certificado
        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('certificados', 'public');
            $validated['attachment_path'] = $path;
        }

        $validated['status'] = 'pendiente';
        $validated['borrado'] = false;

        $leave = LeaveRequest::create($validated);

        return redirect()->back()->with('message', "Solicitud #{$leave->id} registrada correctamente.");
    }

    /**
     * Aprobación asíncrona de la solicitud.
     */
    public function approve(Request $request, $id)
    {
        $leave = LeaveRequest::where('borrado', false)->findOrFail($id);
        $leave->status = 'aprobada';
        $leave->approved_by_user_id = auth()->id();
        $leave->action_at = Carbon::now();
        $leave->save();

        return redirect()->back()->with('message', "Solicitud #{$leave->id} aprobada con éxito.");
    }

    /**
     * Rechazo de la solicitud.
     */
    public function reject(Request $request, $id)
    {
        $leave = LeaveRequest::where('borrado', false)->findOrFail($id);
        $leave->status = 'rechazada';
        $leave->approved_by_user_id = auth()->id();
        $leave->action_at = Carbon::now();
        $leave->save();

        return redirect()->back()->with('message', "Solicitud #{$leave->id} rechazada.");
    }

    /**
     * Baja lógica de la solicitud.
     */
    public function destroy($id)
    {
        $leave = LeaveRequest::where('borrado', false)->findOrFail($id);
        $leave->borrado = true;
        $leave->save();

        return redirect()->back()->with('message', "Registro #{$leave->id} eliminado.");
    }

    /**
     * Exportación de la nómina de ausencias a Excel / CSV UTF-8.
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $status = $request->input('status', 'todos');
        $type = $request->input('type', 'todos');
        $search = $request->input('search', '');

        $query = LeaveRequest::query()->with('employee')->where('borrado', false);

        if ($status !== 'todos') {
            $query->where('status', $status);
        }
        if ($type !== 'todos') {
            $query->where('type', $type);
        }
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('diagnosis', 'like', "%{$search}%")
                  ->orWhereHas('employee', function ($empQuery) use ($search) {
                      $empQuery->where('first_name', 'like', "%{$search}%")
                               ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        $records = $query->orderByDesc('start_date')->get();
        $filename = "licencias_vacaciones_" . date('Ymd_His') . ".csv";

        return response()->streamDownload(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8

            fputcsv($handle, [
                'ID',
                'Legajo',
                'Empleado',
                'Tipo',
                'Días',
                'Fecha Desde',
                'Fecha Hasta',
                'Diagnóstico / Motivo',
                'Estado',
            ], ';');

            foreach ($records as $item) {
                $emp = $item->employee;
                fputcsv($handle, [
                    $item->id,
                    $emp ? $emp->id : '',
                    $emp ? "{$emp->last_name}, {$emp->first_name}" : '',
                    $this->formatTypeLabel($item->type),
                    $item->days_count,
                    $item->start_date ? $item->start_date->format('d/m/Y') : '',
                    $item->end_date ? $item->end_date->format('d/m/Y') : '',
                    $item->diagnosis ?: '',
                    ucfirst($item->status),
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function formatTypeLabel(?string $type): string
    {
        return match ($type) {
            'vacaciones' => 'Vacaciones',
            'certificado_medico' => 'Certificado médico',
            'licencia_especial' => 'Licencia especial',
            default => 'Licencia',
        };
    }

    private function formatPeriodo(?Carbon $start, ?Carbon $end): string
    {
        if (!$start || !$end) return '—';
        return $start->format('d/m') . ' – ' . $end->format('d/m/Y');
    }
}