<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Services\EmployeeCredentialService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PersonalController extends Controller
{
    /**
     * Roles seleccionables desde el ABM de Personal. 'admin' queda afuera:
     * es exclusivo del superusuario técnico y no se asigna desde acá.
     */
    private const ASSIGNABLE_ROLES = ['chofer', 'rrhh'];

    public function __construct(private readonly EmployeeCredentialService $credentials)
    {
    }
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
                    'tiene_acceso' => (bool) ($emp->user && $emp->user->active),
                    'usuario' => $emp->user->username ?? null,
                    'rol' => $emp->user->role->name ?? null,
                    'letra' => $emp->user->letter ?? null,
                    'user_active' => $emp->user->active ?? false,
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
     * Sugiere usuario y (si corresponde) letra de chofer en base al
     * nombre/apellido/rol tipeados, para autocompletar el formulario.
     * Ambos valores quedan editables en el front — esto es solo un default.
     */
    public function suggestCredentials(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'role' => ['nullable', Rule::in(self::ASSIGNABLE_ROLES)],
            'exclude_user_id' => ['nullable', 'integer'],
            'current_letter' => ['nullable', 'string', 'size:1'],
        ]);

        $username = $this->credentials->suggestUsername(
            $validated['first_name'],
            $validated['last_name'],
            $validated['exclude_user_id'] ?? null,
        );

        $letter = null;
        if (($validated['role'] ?? null) === 'chofer') {
            $letter = $this->credentials->suggestNextLetter($validated['current_letter'] ?? null);
        }

        return response()->json(['username' => $username, 'letter' => $letter]);
    }

    /**
     * Alta de un nuevo legajo.
     */
    public function store(Request $request)
    {
        $grantAccess = $request->boolean('grant_access');

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'cuil' => [$grantAccess ? 'required' : 'nullable', 'string', 'max:20', 'unique:employees,cuil'],
            'position' => ['required', 'string', 'max:100'],
            'hire_date' => ['nullable', 'date'],
            'birth_date' => ['nullable', 'date'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:activo,inactivo,licencia'],
            ...$this->accessValidationRules($grantAccess),
        ]);

        $employee = DB::transaction(function () use ($validated, $grantAccess) {
            $employee = Employee::create([
                ...collect($validated)->only([
                    'first_name', 'last_name', 'cuil', 'position',
                    'hire_date', 'birth_date', 'phone', 'address', 'status',
                ])->all(),
                'borrado' => false,
            ]);

            if ($grantAccess) {
                $employee->user_id = $this->createUser($employee, $validated);
                $employee->save();
            }

            return $employee;
        });

        $message = "Legajo #{$employee->id} creado exitosamente.";
        if ($grantAccess) {
            $dni = $this->credentials->extractDniFromCuil($employee->cuil);
            $message .= " Usuario: {$validated['username']} · Contraseña inicial: {$dni}.";
        }

        return redirect()->back()->with('message', $message);
    }

    /**
     * Actualización de datos del legajo.
     */
    public function update(Request $request, $id)
    {
        $employee = Employee::where('borrado', false)->with('user')->findOrFail($id);
        $grantAccess = $request->boolean('grant_access');

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'cuil' => [$grantAccess ? 'required' : 'nullable', 'string', 'max:20', "unique:employees,cuil,{$id}"],
            'position' => ['required', 'string', 'max:100'],
            'hire_date' => ['nullable', 'date'],
            'birth_date' => ['nullable', 'date'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:activo,inactivo,licencia'],
            ...$this->accessValidationRules($grantAccess, $employee->user_id),
        ]);

        // El id_usuario (PK de `users`) es el DNI y no se puede recrear sin
        // perder los tokens/vouchers/combustible ya asociados. Si el legajo
        // ya tiene cuenta, no dejamos que el CUIL editado apunte a otro DNI.
        if ($grantAccess && $employee->user) {
            $dni = $this->credentials->extractDniFromCuil($validated['cuil']);
            if ($dni !== $employee->user->id_usuario) {
                throw ValidationException::withMessages([
                    'cuil' => "El CUIL no coincide con el DNI del usuario existente (#{$employee->user->id_usuario}). "
                        . 'Para corregirlo: quitá el acceso, corregí el CUIL y volvé a otorgarlo.',
                ]);
            }
        }

        DB::transaction(function () use ($employee, $validated, $grantAccess) {
            $employee->update(collect($validated)->only([
                'first_name', 'last_name', 'cuil', 'position',
                'hire_date', 'birth_date', 'phone', 'address', 'status',
            ])->all());

            if ($grantAccess) {
                if ($employee->user) {
                    $this->updateUser($employee->user, $validated);
                } else {
                    $employee->user_id = $this->createUser($employee, $validated);
                    $employee->save();
                }
            } elseif ($employee->user) {
                // No se borra: sus vouchers/combustible tienen ON DELETE
                // CASCADE contra `users`. Solo se desactiva el login.
                $employee->user->update(['active' => false]);
            }
        });

        return redirect()->back()->with('message', "Legajo #{$employee->id} actualizado correctamente.");
    }

    /**
     * Baja lógica del legajo. Si tenía acceso al sistema, se desactiva el
     * login (sin borrar el usuario, para no perder su historial operativo).
     */
    public function destroy($id)
    {
        $employee = Employee::where('borrado', false)->with('user')->findOrFail($id);
        $employee->borrado = true;
        $employee->status = 'inactivo';
        $employee->save();

        $employee->user?->update(['active' => false]);

        return redirect()->back()->with('message', "Legajo #{$employee->id} dado de baja.");
    }

    /**
     * Restablece la contraseña del usuario del legajo a su DNI (el valor
     * inicial de siempre). Útil si el empleado la perdió o la cambió.
     */
    public function resetPassword($id)
    {
        $employee = Employee::where('borrado', false)->with('user')->findOrFail($id);

        if (!$employee->user) {
            return redirect()->back()->with('error', "El legajo #{$employee->id} no tiene acceso al sistema.");
        }

        $dni = $this->credentials->extractDniFromCuil($employee->cuil) ?? $employee->user->id_usuario;
        $employee->user->update(['password' => Hash::make((string) $dni)]);

        return redirect()->back()->with(
            'message',
            "Contraseña de {$employee->full_name} restablecida al DNI ({$dni})."
        );
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
     * Reglas de validación del bloque "Acceso al sistema". $ignoreUserId
     * excluye el propio usuario del legajo al chequear unicidad de username
     * (para poder guardar una edición sin tocar ese campo).
     */
    private function accessValidationRules(bool $grantAccess, ?int $ignoreUserId = null): array
    {
        $usernameRule = Rule::unique('users', 'username');
        if ($ignoreUserId) {
            $usernameRule = $usernameRule->ignore($ignoreUserId, 'id_usuario');
        }

        return [
            'grant_access' => ['required', 'boolean'],
            'role' => [$grantAccess ? 'required' : 'nullable', Rule::in(self::ASSIGNABLE_ROLES)],
            'username' => [
                $grantAccess ? 'required' : 'nullable',
                'string', 'max:50', 'regex:/^[a-z0-9._-]+$/',
                $usernameRule,
            ],
            'letter' => ['nullable', 'string', 'size:1', 'alpha'],
        ];
    }

    /**
     * Crea el usuario del legajo (DNI como id_usuario y como contraseña
     * inicial) y devuelve su id_usuario para vincularlo al Employee.
     */
    private function createUser(Employee $employee, array $validated): int
    {
        $dni = $this->credentials->extractDniFromCuil($employee->cuil);

        if (!$dni) {
            throw ValidationException::withMessages([
                'cuil' => 'El CUIL debe tener el formato NN-DNI-N para poder generar el acceso al sistema.',
            ]);
        }

        if (User::where('id_usuario', $dni)->exists()) {
            throw ValidationException::withMessages([
                'cuil' => "Ya existe un usuario con el DNI {$dni}. Revisá si esta persona ya tiene legajo cargado.",
            ]);
        }

        User::create([
            'id_usuario' => $dni,
            'username' => $validated['username'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'password' => Hash::make((string) $dni),
            'role_id' => Role::where('name', $validated['role'])->value('id'),
            'letter' => $validated['role'] === 'chofer' ? ($validated['letter'] ?? null) : null,
            'active' => true,
        ]);

        return $dni;
    }

    /**
     * Actualiza el usuario ya vinculado al legajo (rol, usuario, letra) y
     * lo reactiva por si estaba deshabilitado.
     */
    private function updateUser(User $user, array $validated): void
    {
        $user->update([
            'username' => $validated['username'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'role_id' => Role::where('name', $validated['role'])->value('id'),
            'letter' => $validated['role'] === 'chofer' ? ($validated['letter'] ?? null) : null,
            'active' => true,
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