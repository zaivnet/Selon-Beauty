<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmployeePortalThemeTest extends TestCase
{
    use RefreshDatabase;

    protected User $employeeUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employeeUser = User::create([
            'name' => 'Karyawan Test',
            'email' => 'karyawan@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        Employee::create([
            'user_id' => $this->employeeUser->id,
            'full_name' => 'Karyawan Test',
            'employee_code' => 'EMP001',
            'gender' => 'female',
            'join_date' => '2026-01-01',
            'is_active' => true,
        ]);

        $this->employeeUser->refresh();
    }

    public function test_employee_layout_includes_shared_theme_bootstrap_script(): void
    {
        $response = $this->actingAs($this->employeeUser)
            ->get(route('employee.dashboard'));

        $response->assertStatus(200);

        // Shared Theme Bootstrap Check in head
        $response->assertSee("window.applyAttendanceTheme", false);
        $response->assertSee("localStorage.getItem('attendance-theme')", false);
        $response->assertSee("document.documentElement.classList.add('dark')", false);
    }

    public function test_employee_dashboard_includes_dark_classes_for_shell_and_cards(): void
    {
        $response = $this->actingAs($this->employeeUser)
            ->get(route('employee.dashboard'));

        $response->assertStatus(200);

        // Shell & Canvas Dark classes check
        $response->assertSee("dark:bg-slate-950", false);
        $response->assertSee("dark:bg-slate-900", false);
        $response->assertSee("dark:border-slate-800", false);
        $response->assertSee("dark:bg-slate-950/95", false);
    }

    public function test_employee_profile_includes_theme_switcher_controls(): void
    {
        $response = $this->actingAs($this->employeeUser)
            ->get(route('employee.profile.index'));

        $response->assertStatus(200);

        // Profile Theme Switcher Card check
        $response->assertSee('Tampilan &amp; Tema Portal', false);
        $response->assertSee("setPortalTheme('system')", false);
        $response->assertSee("setPortalTheme('light')", false);
        $response->assertSee("setPortalTheme('dark')", false);
    }
}
