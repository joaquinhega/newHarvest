<?php

namespace Tests\Feature;

use App\Models\Combustible;
use App\Models\Company;
use App\Models\User;
use App\Models\Voucher;
use Database\Seeders\TestRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OperativoModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $chofer1;
    private User $chofer2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TestRoleSeeder::class);

        $this->admin = User::create([
            'id_usuario' => 11111111,
            'username' => 'admin_nh',
            'first_name' => 'Admin',
            'last_name' => 'New Harvest',
            'password' => Hash::make('secret'),
            'role_id' => 1,
            'active' => true,
        ]);

        $this->chofer1 = User::create([
            'id_usuario' => 22222222,
            'username' => 'chofer_facundo',
            'first_name' => 'Facundo',
            'last_name' => 'Aguilera',
            'password' => Hash::make('secret'),
            'role_id' => 3,
            'letter' => 'J',
            'active' => true,
        ]);

        $this->chofer2 = User::create([
            'id_usuario' => 33333333,
            'username' => 'chofer_manuel',
            'first_name' => 'Manuel',
            'last_name' => 'Palacios',
            'password' => Hash::make('secret'),
            'role_id' => 3,
            'letter' => 'C',
            'active' => true,
        ]);
    }

    public function test_driver_can_create_voucher_with_complete_fields(): void
    {
        Sanctum::actingAs($this->chofer1);
        $company = Company::create(['name' => 'Rayen Cura', 'borrado' => false]);

        $payload = [
            'remito_code' => 'C1801',
            'company_id' => $company->id,
            'passenger_name' => 'Lais Stigare x2',
            'origin' => 'Huentala Hotel',
            'pickup_time' => '08:10',
            'destination' => 'Verallia',
            'dropoff_time' => '08:30',
            'wait_time' => '20 min',
            'date' => '2026-08-13',
            'amount' => 15000.00,
            'observation' => 'Traslado de personal corporativo',
            'signature_base64' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
        ];

        $response = $this->postJson('/api/v1/vouchers', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.remito_code', 'C1801')
            ->assertJsonPath('data.status', 'pendiente');

        $this->assertDatabaseHas('vouchers', [
            'remito_code' => 'C1801',
            'user_id' => $this->chofer1->id_usuario,
            'passenger_name' => 'Lais Stigare x2',
            'amount' => 15000.00,
        ]);
    }

    public function test_driver_can_only_see_their_own_vouchers_while_admin_sees_all(): void
    {
        Voucher::create([
            'remito_code' => 'V001',
            'user_id' => $this->chofer1->id_usuario,
            'passenger_name' => 'Pax Chofer 1',
            'origin' => 'A', 'destination' => 'B',
            'pickup_time' => '08:00', 'dropoff_time' => '09:00',
            'date' => '2026-08-13',
        ]);

        Voucher::create([
            'remito_code' => 'V002',
            'user_id' => $this->chofer2->id_usuario,
            'passenger_name' => 'Pax Chofer 2',
            'origin' => 'C', 'destination' => 'D',
            'pickup_time' => '10:00', 'dropoff_time' => '11:00',
            'date' => '2026-08-13',
        ]);

        // Chofer 1 consulta
        Sanctum::actingAs($this->chofer1);
        $responseChofer = $this->getJson('/api/v1/vouchers');
        $responseChofer->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.remito_code', 'V001');

        // Admin consulta
        Sanctum::actingAs($this->admin);
        $responseAdmin = $this->getJson('/api/v1/vouchers');
        $responseAdmin->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_driver_cannot_approve_fuel_receipt_but_admin_can(): void
    {
        $combustible = Combustible::create([
            'remito_code' => 'COMB-992757',
            'user_id' => $this->chofer1->id_usuario,
            'driver_name' => 'Facundo Aguilera',
            'plate' => 'AG040QW',
            'date' => '2026-08-13',
            'amount' => 193952.00,
            'status' => 'pendiente',
        ]);

        // Chofer intenta aprobar (403 Forbidden)
        Sanctum::actingAs($this->chofer1);
        $resForbidden = $this->patchJson("/api/v1/combustibles/{$combustible->id}/approve");
        $resForbidden->assertStatus(403);

        // Admin aprueba
        Sanctum::actingAs($this->admin);
        $resApproved = $this->patchJson("/api/v1/combustibles/{$combustible->id}/approve");
        $resApproved->assertStatus(200)
            ->assertJsonPath('data.status', 'aprobado');

        $this->assertEquals('aprobado', $combustible->fresh()->status);
    }
}