<?php

namespace Tests\Feature\Security;

use App\Http\Controllers\Auth\SetupController;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\User;
use App\Services\UserRoleService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FirstRunSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_fresh_database_has_no_default_users(): void
    {
        $this->assertEquals(0, User::count());
        $this->assertEquals(0, Employee::count());
    }

    public function test_setup_page_available_when_no_active_superadmin_exists(): void
    {
        $response = $this->get('/setup');
        $response->assertOk();
        $response->assertSee('Inisialisasi Superadmin Utama');
    }

    public function test_root_redirects_to_setup_when_no_active_superadmin_exists(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('/setup');

        $responseAdmin = $this->get('/admin/dashboard');
        $responseAdmin->assertRedirect('/setup');

        $responseLogin = $this->get('/login');
        $responseLogin->assertOk();
    }

    public function test_setup_can_create_first_superadmin(): void
    {
        $response = $this->post('/setup', [
            'name' => 'First Superadmin',
            'email' => 'superadmin@selonbeauty.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'name' => 'First Superadmin',
            'email' => 'superadmin@selonbeauty.com',
            'role' => 'superadmin',
            'is_active' => true,
        ]);
    }

    public function test_first_superadmin_role_is_superadmin_and_is_active(): void
    {
        $this->post('/setup', [
            'name' => 'Superadmin One',
            'email' => 'adminone@selonbeauty.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::where('email', 'adminone@selonbeauty.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('superadmin', $user->role);
        $this->assertTrue((bool) $user->is_active);
        $this->assertNull($user->employee_id);
    }

    public function test_first_superadmin_password_is_hashed(): void
    {
        $this->post('/setup', [
            'name' => 'Hashed Admin',
            'email' => 'hashed@selonbeauty.com',
            'password' => 'secretpassword123',
            'password_confirmation' => 'secretpassword123',
        ]);

        $user = User::where('email', 'hashed@selonbeauty.com')->first();
        $this->assertTrue(Hash::check('secretpassword123', $user->password));
        $this->assertNotEquals('secretpassword123', $user->password);
    }

    public function test_setup_does_not_create_employee_automatically(): void
    {
        $this->post('/setup', [
            'name' => 'Pure Superadmin',
            'email' => 'pure@selonbeauty.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertEquals(0, Employee::count());
    }

    public function test_setup_ignores_forged_role_and_is_active_input(): void
    {
        $this->post('/setup', [
            'name' => 'Forged Input Admin',
            'email' => 'forged@selonbeauty.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'employee',
            'is_active' => 0,
            'employee_id' => 999,
        ]);

        $user = User::where('email', 'forged@selonbeauty.com')->first();
        $this->assertEquals('superadmin', $user->role);
        $this->assertTrue((bool) $user->is_active);
        $this->assertNull($user->employee_id);
    }

    public function test_setup_rejects_invalid_email(): void
    {
        $response = $this->post('/setup', [
            'name' => 'Invalid Email Admin',
            'email' => 'not-an-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertEquals(0, User::count());
    }

    public function test_setup_enforces_password_policy(): void
    {
        // Password too short (< 8 chars)
        $responseShort = $this->post('/setup', [
            'name' => 'Short Pass Admin',
            'email' => 'short@selonbeauty.com',
            'password' => '12345',
            'password_confirmation' => '12345',
        ]);
        $responseShort->assertSessionHasErrors(['password']);

        // Password mismatch
        $responseMismatch = $this->post('/setup', [
            'name' => 'Mismatch Pass Admin',
            'email' => 'mismatch@selonbeauty.com',
            'password' => 'password123',
            'password_confirmation' => 'differentpass',
        ]);
        $responseMismatch->assertSessionHasErrors(['password']);

        $this->assertEquals(0, User::count());
    }

    public function test_setup_blocked_after_active_superadmin_exists(): void
    {
        User::create([
            'name' => 'Existing Superadmin',
            'email' => 'existing@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $responseGet = $this->get('/setup');
        $responseGet->assertRedirect('/login');

        $responsePost = $this->post('/setup', [
            'name' => 'Hacker Admin',
            'email' => 'hacker@selonbeauty.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $responsePost->assertRedirect('/login');
        $this->assertDatabaseMissing('users', ['email' => 'hacker@selonbeauty.com']);
    }

    public function test_inactive_superadmin_alone_does_not_permanently_lock_recovery(): void
    {
        User::create([
            'name' => 'Inactive Superadmin',
            'email' => 'inactive@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'superadmin',
            'is_active' => false,
        ]);

        $response = $this->get('/setup');
        $response->assertOk();

        $responsePost = $this->post('/setup', [
            'name' => 'New Active Superadmin',
            'email' => 'activeadmin@selonbeauty.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $responsePost->assertRedirect('/login');
        $this->assertDatabaseHas('users', [
            'email' => 'activeadmin@selonbeauty.com',
            'role' => 'superadmin',
            'is_active' => true,
        ]);
    }

    public function test_existing_non_superadmin_users_do_not_block_first_superadmin_setup(): void
    {
        User::create([
            'name' => 'Orphan Employee User',
            'email' => 'employee@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        $response = $this->get('/setup');
        $response->assertOk();

        $responsePost = $this->post('/setup', [
            'name' => 'First Superadmin',
            'email' => 'firstadmin@selonbeauty.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $responsePost->assertRedirect('/login');
        $this->assertDatabaseHas('users', ['email' => 'firstadmin@selonbeauty.com', 'role' => 'superadmin']);
    }

    public function test_first_superadmin_can_login_and_reach_admin_dashboard(): void
    {
        $this->post('/setup', [
            'name' => 'Superadmin Login',
            'email' => 'loginadmin@selonbeauty.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $responseLogin = $this->post('/login', [
            'login' => 'loginadmin@selonbeauty.com',
            'password' => 'password123',
        ]);

        $responseLogin->assertRedirect('/admin/dashboard');
        $this->assertAuthenticated();
    }

    public function test_last_active_superadmin_protection_remains_working(): void
    {
        $superadmin = User::create([
            'name' => 'Sole Superadmin',
            'email' => 'sole@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $userRoleService = new UserRoleService();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Minimal satu Superadmin aktif harus tetap tersedia.');

        $userRoleService->ensureSuperadminSafety($superadmin, newRole: 'admin', newIsActive: true);
    }

    public function test_no_production_seeder_creates_dummy_users(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->assertEquals(0, User::count());
        $this->assertEquals(0, Employee::count());
    }
}
