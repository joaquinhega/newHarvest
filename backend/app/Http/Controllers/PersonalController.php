<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PersonalController extends Controller
{
    /**
     * Listado general de legajos con filtros de búsqueda y estado.
     */
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $status = $request->input('status', 'activo');

        $query = Employee::query()
            ->with('user')
            ->where('borrado', false);

        if ($status !== 'todos') {
            $query->where('status', $status);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('cuil', 'like', "%{$search}%")
                  ->orWhere('position', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%{$search}%");
            });
        }

        $personal = $query->orderBy('id', 'asc')
            ->get()
            ->map(function ($emp) {
                return [
                    'id' => $emp->id,
                    'user_id' => $emp->user_id,
                    'nombre_completo' => $emp->full_name,
                    'first_name' => $emp->first_name,
                    'last_name' => $emp->last_name,
                    'cuil' => $emp->cuil ?: '—',
                    'puesto' => $emp->position ?: 'Sin puesto asignado',
                    'antiguedad' => $this->calculateAntiguedad($emp->hire_date),
                    'hire_date' => $emp->hire_date ? $emp->hire_date->format('Y-m-d') : null,
                    'hire_date_formateada' => $emp->hire_date ? $emp->hire_date->format('d/m/Y') : '—',
                    'birth_date' => $emp->birth_date ? $emp->birth_date->format('Y-m-d') : null,
                    'birth_date_formateada' => $emp->birth_date ? $emp->birth_date->format('d/m/Y') : '—',
                    'telefono' => $emp->phone ?: '—',
                    'direccion' => $emp->address ?: '—',
                    'status' => $emp->status ?: 'activo',
                    'usuario' => $emp->user ? $emp->user->username : null,
                ];
            });

        return Inertia::render('RRHH/Personal', [
            'personal' => $personal,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    /**
     * Alta de un nuevo legajo.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'cuil' => ['nullable', 'string', 'max:20', 'unique:employees,cuil'],
            'position' => ['required', 'string', 'max:100'],
            'hire_date' => ['nullable', 'date'],
            'birth_date' => ['nullable', 'date'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:activo,inactivo,licencia'],
            'user_id' => ['nullable', 'exists:users,id_usuario'],
        ]);

        $validated['borrado'] = false;

        $employee = Employee::create($validated);

        return redirect()->back()->with('message', "Legajo #{$employee->id} creado exitosamente.");
    }

    /**
     * Actualización de datos del legajo.
     */
    public function update(Request $request, $id)
    {
        $employee = Employee::where('borrado', false)->findOrFail($id);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'cuil' => ['nullable', 'string', 'max:20', "unique:employees,cuil,{$id}"],
            'position' => ['required', 'string', 'max:100'],
            'hire_date' => ['nullable', 'date'],
            'birth_date' => ['nullable', 'date'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:activo,inactivo,licencia'],
            'user_id' => ['nullable', 'exists:users,id_usuario'],
        ]);

        $employee->update($validated);

        return redirect()->back()->with('message', "Legajo #{$employee->id} actualizado correctamente.");
    }

    /**
     * Baja lógica del legajo.
     */
    public function destroy($id)
    {
        $employee = Employee::where('borrado', false)->findOrFail($id);
        $employee->borrado = true;
        $employee->status = 'inactivo';
        $employee->save();

        return redirect()->back()->with('message', "Legajo #{$employee->id} dado de baja.");
    }

    /**
     * Exportación de la nómina a CSV / Excel con codificación UTF-8.
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $search = $request->input('search', '');
        $status = $request->input('status', 'activo');

        $query = Employee::query()->where('borrado', false);

        if ($status !== 'todos') {
            $query->where('status', $status);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('cuil', 'like', "%{$search}%")
                  ->orWhere('position', 'like', "%{$search}%");
            });
        }

        $employees = $query->orderBy('id', 'asc')->get();
        $filename = "personal_" . date('Ymd_His') . ".csv";

        return response()->streamDownload(function () use ($employees) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8

            fputcsv($handle, [
                'N° Legajo',
                'Apellido',
                'Nombre',
                'CUIL',
                'Puesto',
                'Antigüedad',
                'Fecha Ingreso',
                'Fecha Nacimiento',
                'Teléfono',
                'Estado'
            ], ';');

            foreach ($employees as $emp) {
                fputcsv($handle, [
                    $emp->id,
                    $emp->last_name,
                    $emp->first_name,
                    $emp->cuil ?: '',
                    $emp->position ?: '',
                    $this->calculateAntiguedad($emp->hire_date),
                    $emp->hire_date ? $emp->hire_date->format('d/m/Y') : '',
                    $emp->birth_date ? $emp->birth_date->format('d/m/Y') : '',
                    $emp->phone ?: '',
                    ucfirst($emp->status ?: 'Activo'),
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Cálculo de antigüedad formateada en años y meses.
     */
    private function calculateAntiguedad(?Carbon $hireDate): string
    {
        if (!$hireDate) {
            return '—';
        }

        $now = Carbon::now();
        $years = (int) $hireDate->diffInYears($now);
        $months = (int) $hireDate->copy()->addYears($years)->diffInMonths($now);

        if ($years === 0 && $months === 0) {
            return 'Ingreso reciente';
        }

        $parts = [];
        if ($years > 0) {
            $parts[] = $years . ($years === 1 ? ' año' : ' años');
        }
        if ($months > 0) {
            $parts[] = $months . ($months === 1 ? ' mes' : ' meses');
        }

        return implode(', ', $parts);
    }
}