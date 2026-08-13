<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200)
            ->assertSee('SELON BEAUTY')
            ->assertSee('Email / Nomor HP');
    }

    public function test_users_can_authenticate_using_email(): void
    {
        $user = User::create([
            'name' => 'Owner Test',
            'email' => 'owner@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'login' => 'owner@selonbeauty.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/admin/dashboard');
    }

    public function test_users_can_authenticate_using_phone(): void
    {
        $user = User::create([
            'name' => 'Karyawan Phone',
            'phone' => '081234567890',
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'login' => '6281234567890', // test variation 628... vs stored 08...
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/app/dashboard');
    }

    public function test_employee_can_authenticate_using_employee_code(): void
    {
        $emp = \App\Models\Employee::create([
            'employee_code' => 'SB-777',
            'full_name' => 'Karyawan Kode',
            'status' => 'active',
        ]);

        $user = User::create([
            'employee_id' => $emp->id,
            'name' => 'Karyawan Kode',
            'email' => 'kode777@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'login' => 'SB-777',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/app/dashboard');
    }

    public function test_users_cannot_authenticate_with_invalid_password(): void
    {
        User::create([
            'name' => 'Test User',
            'email' => 'user@selonbeauty.com',
            'password' => Hash::make('correct-password'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'login' => 'user@selonbeauty.com',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('login');
    }

    public function test_login_rate_limiting_blocks_after_5_failed_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'login' => 'blocked@selonbeauty.com',
                'password' => 'wrong-pass',
            ]);
        }

        $response = $this->post('/login', [
            'login' => 'blocked@selonbeauty.com',
            'password' => 'wrong-pass',
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertStringContainsString('Terlalu banyak percobaan login', session('errors')->first('login'));
    }

    public function test_session_is_regenerated_after_successful_login(): void
    {
        $user = User::create([
            'name' => 'Session Test',
            'email' => 'session@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $previousSessionId = session()->getId();

        $this->post('/login', [
            'login' => 'session@selonbeauty.com',
            'password' => 'password123',
        ]);

        $this->assertNotEquals($previousSessionId, session()->getId());
    }

    public function test_users_can_logout(): void
    {
        $user = User::create([
            'name' => 'Logout User',
            'email' => 'logout@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $response = $this->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }

    public function test_employee_is_forbidden_from_accessing_admin_dashboard(): void
    {
        $employee = User::create([
            'name' => 'Ayu Karyawan',
            'email' => 'ayu@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        $response = $this->actingAs($employee)->get('/admin/dashboard');

        $response->assertRedirect('/app/dashboard');
        $response->assertSessionHas('error');
    }

    public function test_admin_and_owner_can_access_admin_dashboard(): void
    {
        $admin = User::create([
            'name' => 'Admin Toko',
            'email' => 'admin@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $owner = User::create([
            'name' => 'Owner Utama',
            'email' => 'owner@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->actingAs($admin)->get('/admin/dashboard')->assertStatus(200);
        $this->actingAs($owner)->get('/admin/dashboard')->assertStatus(200);
    }

    public function test_owner_and_employee_can_access_employee_dashboard(): void
    {
        $employee = User::create([
            'name' => 'Employee Test',
            'email' => 'emp@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        $owner = User::create([
            'name' => 'Owner Test',
            'email' => 'owner2@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->actingAs($employee)->get('/app/dashboard')->assertStatus(200);
        $this->actingAs($owner)->get('/app/dashboard')->assertStatus(200);
    }
}
