<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\SalaryReceipt;
use App\Models\Sanction;
use App\Models\User;
use Database\Seeders\TestRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HRModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $rrhhUser;
    private User $choferUser;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TestRoleSeeder::class);

        $this->rrhhUser = User::create([
            'id_usuario' => 25237253,
            'username' => 'paulacap',
            'first_name' => 'Paula',
            'last_name' => 'Cappellani',
            'password' => Hash::make('secret'),
            'role_id' => 2, // RRHH
            'active' => true,
        ]);

        $this->choferUser = User::create([
            'id_usuario' => 43942223,
            'username' => 'facundoagu',
            'first_name' => 'Facundo',
            'last_name' => 'Aguilera',
            'password' => Hash::make('secret'),
            'role_id' => 3, // Chofer
            'letter' => 'J',
            'active' => true,
        ]);

        $this->employee = Employee::create([
            'user_id' => $this->choferUser->id_usuario,
            'first_name' => 'Facundo',
            'last_name' => 'Aguilera Anitori',
            'cuil' => '20-43942223-9',
            'position' => 'Chofer',
            'hire_date' => '2026-04-01',
            'birth_date' => '2001-12-13',
            'status' => 'activo',
        ]);
    }

    public function test_rrhh_can_create_and_manage_employee_legajos(): void
    {
        Sanctum::actingAs($this->rrhhUser);

        $response = $this->postJson('/api/v1/employees', [
            'first_name' => 'Hilda Fabiana',
            'last_name' => 'Gatica',
            'cuil' => '27-22309644-7',
            'position' => 'Chofer inicial',
            'hire_date' => '2025-08-01',
            'status' => 'activo',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.cuil', '27-22309644-7');

        $this->assertDatabaseHas('employees', ['cuil' => '27-22309644-7']);
    }

    public function test_salary_receipt_signature_flow_by_employer_and_employee(): void
    {
        // 1. RRHH emite el recibo
        Sanctum::actingAs($this->rrhhUser);
        $receipt = SalaryReceipt::create([
            'employee_id' => $this->employee->id,
            'period' => 'Junio 2026 y 1° SAC',
            'gross_amount' => 1550250.00,
            'deductions_amount' => 275126.00,
            'net_amount' => 1275123.00,
            'status' => 'generado',
        ]);

        // 2. Apoderado de empresa firma
        $signEmployerRes = $this->patchJson("/api/v1/salary-receipts/{$receipt->id}/sign-employer");
        $signEmployerRes->assertStatus(200)
            ->assertJsonPath('data.status', 'firmado_empresa');

        // 3. Chofer ingresa desde la App, revisa y firma con conformidad legal
        Sanctum::actingAs($this->choferUser);
        $signEmployeeRes = $this->postJson("/api/v1/salary-receipts/{$receipt->id}/sign-employee", [
            'legal_accepted' => true,
            'signature_base64' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
        ]);

        $signEmployeeRes->assertStatus(200)
            ->assertJsonPath('data.status', 'firmado_empleado')
            ->assertJsonPath('data.legal_accepted', true);

        $this->assertNotNull($receipt->fresh()->employee_signed_at);
        $this->assertTrue($receipt->fresh()->legal_accepted);
    }

    public function test_driver_can_request_leave_and_rrhh_approves_it(): void
    {
        // Chofer envía solicitud de vacaciones
        Sanctum::actingAs($this->choferUser);
        $resLeave = $this->postJson('/api/v1/leave-requests', [
            'type' => 'vacaciones',
            'days_count' => 10,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-10',
            'diagnosis' => 'Vacaciones anuales reglamentarias',
        ]);

        $resLeave->assertStatus(201);
        $leaveId = $resLeave->json('data.id');

        // RRHH aprueba
        Sanctum::actingAs($this->rrhhUser);
        $resApprove = $this->patchJson("/api/v1/leave-requests/{$leaveId}/approve");
        $resApprove->assertStatus(200)
            ->assertJsonPath('data.status', 'aprobada');

        $this->assertEquals('aprobada', LeaveRequest::find($leaveId)->status);
    }

    public function test_employee_can_confirm_reading_of_a_disciplinary_sanction(): void
    {
        // RRHH emite sanción
        Sanctum::actingAs($this->rrhhUser);
        $sanction = Sanction::create([
            'employee_id' => $this->employee->id,
            'sanction_number' => '287',
            'type' => 'apercibimiento',
            'date' => '2026-08-13',
            'reason' => 'Incumplimiento de normas internas sobre cuidado de la unidad vehicular.',
            'status' => 'pendiente',
        ]);

        // Chofer confirma lectura desde la app
        Sanctum::actingAs($this->choferUser);
        $resRead = $this->patchJson("/api/v1/sanctions/{$sanction->id}/confirm-read");

        $resRead->assertStatus(200)
            ->assertJsonPath('data.status', 'leido');

        $this->assertNotNull($sanction->fresh()->read_at);
    }
}