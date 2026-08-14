<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\TestRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthAndSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TestRoleSeeder::class);
    }

    public function test_health_check_returns_success_status(): void
    {
        $response = $this->getJson('/api/v1/health-check');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'New Harvest API v1 funcionando correctamente',
            ]);
    }

    public function test_user_can_login_with_correct_credentials(): void
    {
        User::create([
            'id_usuario' => 43942223,
            'username' => 'facundoagu',
            'first_name' => 'Facundo',
            'last_name' => 'Aguilera',
            'email' => 'facundo@newharvest.com.ar',
            'password' => Hash::make('secret123'),
            'role_id' => 3,
            'letter' => 'J',
            'active' => true,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'facundoagu',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'token_type',
                    'access_token',
                    'user' => ['id_usuario', 'username', 'role'],
                ],
                'status_code',
                'timestamp',
            ]);
    }

    public function test_login_fails_with_invalid_password(): void
    {
        User::create([
            'id_usuario' => 43942223,
            'username' => 'facundoagu',
            'first_name' => 'Facundo',
            'last_name' => 'Aguilera',
            'password' => Hash::make('secret123'),
            'role_id' => 3,
            'active' => true,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'facundoagu',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson(['success' => false]);
    }

    public function test_authenticated_user_can_logout_and_revoke_token(): void
    {
        $user = User::create([
            'id_usuario' => 43942223,
            'username' => 'facundoagu',
            'first_name' => 'Facundo',
            'last_name' => 'Aguilera',
            'password' => Hash::make('secret123'),
            'role_id' => 3,
            'active' => true,
        ]);

        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertCount(0, $user->fresh()->tokens);
    }
}