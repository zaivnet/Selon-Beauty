<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminThemeAndDashboardUiTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    public function test_admin_dashboard_renders_search_placeholder_and_status_filter(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Status Operasional Hari Ini');
        $response->assertSee('placeholder="Cari karyawan..."', false);
        $response->assertSee('Semua Status');
        $response->assertSee('Hadir / Tepat Waktu');
        $response->assertSee('Terlambat');
        $response->assertSee('Belum Check-in');
        $response->assertSee('Izin / Cuti');
    }

    public function test_admin_layout_includes_theme_switcher_and_fouc_prevention_script(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        
        // FOUC prevention script check in head
        $response->assertSee("localStorage.getItem('attendance-theme')", false);
        $response->assertSee("document.documentElement.classList.add('dark')", false);

        // Theme switcher dropdown check
        $response->assertSee("setTheme('light')", false);
        $response->assertSee("setTheme('dark')", false);
        $response->assertSee("setTheme('system')", false);
        $response->assertSee('Mode Terang');
        $response->assertSee('Mode Gelap');
        $response->assertSee('Ikuti Sistem');
    }

    public function test_admin_layout_uses_light_mode_sidebar_styles(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        
        // Sidebar light mode background and rose active classes
        $response->assertSee('bg-white dark:bg-slate-950', false);
        $response->assertSee('Admin Portal');
        $response->assertSee('border-rose-500', false);
    }

    public function test_employee_portal_dashboard_remains_accessible_without_regression(): void
    {
        $employeeUser = User::create([
            'name' => 'Karyawan Test',
            'email' => 'karyawan@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        Employee::create([
            'user_id' => $employeeUser->id,
            'full_name' => 'Test Employee',
            'employee_code' => 'EMP001',
            'gender' => 'male',
            'join_date' => '2026-01-01',
            'is_active' => true,
        ]);

        $response = $this->actingAs($employeeUser)
            ->get(route('employee.dashboard'));

        $response->assertStatus(200);
    }
}
