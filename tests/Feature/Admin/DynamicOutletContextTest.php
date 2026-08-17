<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\Outlet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DynamicOutletContextTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->artisan('db:seed', ['--class' => 'DatabaseSeeder']);
    }

    public function test_employee_index_global_does_not_contain_hardcoded_company_name()
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN->value]);

        $response = $this->actingAs($superadmin)->get(route('admin.employees.index'));

        $response->assertStatus(200);
        $response->assertSee('<title>Kelola Karyawan', false);
        $response->assertSee('Daftar Karyawan');
        $response->assertDontSee('SELON BEAUTY');
    }

    public function test_employee_index_with_selected_outlet_uses_dynamic_name()
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN->value]);
        $outletAlpha = Outlet::create(['name' => 'Outlet Alpha', 'code' => 'ALPHA', 'timezone' => 'Asia/Jakarta']);
        $outletBeta = Outlet::create(['name' => 'Outlet Beta', 'code' => 'BETA', 'timezone' => 'Asia/Jakarta']);

        // Test Alpha
        $responseAlpha = $this->actingAs($superadmin)->get(route('admin.employees.index', ['outlet_id' => $outletAlpha->id]));
        $responseAlpha->assertStatus(200);
        $responseAlpha->assertSee('Daftar Karyawan — Outlet Alpha');
        $responseAlpha->assertDontSee('Daftar Karyawan — Outlet Beta');

        // Test Beta
        $responseBeta = $this->actingAs($superadmin)->get(route('admin.employees.index', ['outlet_id' => $outletBeta->id]));
        $responseBeta->assertStatus(200);
        $responseBeta->assertSee('Daftar Karyawan — Outlet Beta');
        $responseBeta->assertDontSee('Daftar Karyawan — Outlet Alpha');
    }

    public function test_employee_index_for_single_outlet_admin_uses_dynamic_name()
    {
        $outletAlpha = Outlet::create(['name' => 'Outlet Alpha', 'code' => 'ALPHA', 'timezone' => 'Asia/Jakarta']);
        
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
        
        // Setup admin outlet context
        $adminEmployee = Employee::create([
            'employee_code' => 'EMP-001',
            'full_name' => 'Admin User',
            'email' => 'admin@example.com',
            'user_id' => $admin->id,
            'outlet_id' => $outletAlpha->id,
            'join_date' => now()->format('Y-m-d')
        ]);

        $response = $this->actingAs($admin)->get(route('admin.employees.index'));

        $response->assertStatus(200);
        $response->assertSee('Daftar Karyawan — Outlet Alpha');
    }

    public function test_shift_index_does_not_contain_hardcoded_company_name()
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN->value]);

        $response = $this->actingAs($superadmin)->get(route('admin.shifts.index'));

        $response->assertStatus(200);
        $response->assertSee('Daftar Shift Kerja');
        $response->assertDontSee('SELON BEAUTY');
    }
}
