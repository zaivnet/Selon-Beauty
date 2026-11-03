<?php

namespace Tests\Feature\Admin;

use App\Models\Employee;
use App\Models\Outlet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminExecutiveShellTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $owner;
    protected User $superadmin;
    protected User $employeeUser;
    protected Outlet $outlet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outlet = Outlet::create([
            'name' => 'Outlet Utama',
            'code' => 'OUT01',
            'address' => 'Jl. Jenderal Sudirman No. 1',
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'radius_meters' => 100,
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@example.test',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->owner = User::create([
            'name' => 'Owner Test',
            'email' => 'owner@example.test',
            'password' => Hash::make('password123'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->superadmin = User::create([
            'name' => 'Superadmin Test',
            'email' => 'superadmin@example.test',
            'password' => Hash::make('password123'),
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $this->employeeUser = User::create([
            'name' => 'Employee Test',
            'email' => 'employee@example.test',
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        Employee::create([
            'user_id' => $this->employeeUser->id,
            'outlet_id' => $this->outlet->id,
            'full_name' => 'Employee Test',
            'employee_code' => 'EMP001',
            'gender' => 'male',
            'join_date' => '2026-01-01',
            'is_active' => true,
        ]);
    }

    public function test_sidebar_renders_5_functional_navigation_groups(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $response->assertStatus(200);

        // Group 1: Overview
        $response->assertSee('Overview');
        $response->assertSee(route('admin.dashboard'));
        $response->assertSee(route('admin.operational-exceptions.index'));

        // Group 2: Operasional
        $response->assertSee('Operasional');
        $response->assertSee(route('admin.attendance.index'));
        $response->assertSee(route('admin.shifts.index'));
        $response->assertSee(route('admin.schedules.index'));

        // Group 3: Karyawan & SDM
        $response->assertSee('Karyawan & SDM');
        $response->assertSee(route('admin.employees.index'));
        $response->assertSee(route('admin.job-titles.index'));
        $response->assertSee(route('admin.leave-requests.index'));
        $response->assertSee(route('admin.overtime-requests.index'));
        $response->assertSee(route('admin.shift-swaps.index'));

        // Group 4: Laporan
        $response->assertSee('Laporan');
        $response->assertSee(route('admin.reports.attendance'));

        // Group 5: Konfigurasi & Sistem
        $response->assertSee('Konfigurasi & Sistem');
        $response->assertSee(route('admin.settings.attendance'));
    }

    public function test_rbac_menu_visibility_isolates_restricted_modules(): void
    {
        // 1. Regular Admin: Sees Outlet, but does NOT see Audit Trail
        $responseAdmin = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $responseAdmin->assertSee(route('admin.outlets.index'));
        $responseAdmin->assertDontSee(route('admin.audit-logs.index'));

        // 2. Owner: Sees both Outlet and Audit Trail
        $responseOwner = $this->actingAs($this->owner)->get(route('admin.dashboard'));
        $responseOwner->assertSee(route('admin.outlets.index'));
        $responseOwner->assertSee(route('admin.audit-logs.index'));

        // 3. Superadmin: Sees both Outlet and Audit Trail
        $responseSuper = $this->actingAs($this->superadmin)->get(route('admin.dashboard'));
        $responseSuper->assertSee(route('admin.outlets.index'));
        $responseSuper->assertSee(route('admin.audit-logs.index'));

        // 4. Employee: Accessing admin route gets redirected or 403
        $responseEmp = $this->actingAs($this->employeeUser)->get(route('admin.dashboard'));
        $responseEmp->assertRedirect(route('employee.dashboard'));
    }

    public function test_sidebar_collapsible_and_mobile_drawer_architecture(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $response->assertStatus(200);

        // Sidebar collapsible state attributes
        $response->assertSee('localStorage.getItem(\'admin_sidebar_collapsed\')', false);
        $response->assertSee('md:w-64', false);
        $response->assertSee('md:w-20', false);
        $response->assertSee(':title="collapsed ? \'Dashboard\' : \'\'"', false);
        $response->assertSee('title="Ciutkan Sidebar"', false);

        // Mobile drawer escape key listener and close button
        $response->assertSee('@keydown.window.escape="mobileOpen = false"', false);
        $response->assertSee('title="Buka Menu Mobile"', false);
    }

    public function test_topbar_renders_breadcrumbs_theme_toggle_and_employee_portal_link(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $response->assertStatus(200);

        // Breadcrumb
        $response->assertSee('Portal Admin');
        $response->assertSee('App Karyawan');
        $response->assertSee(route('employee.dashboard'));

        // Theme Toggle Options
        $response->assertSee("setTheme('light')", false);
        $response->assertSee("setTheme('dark')", false);
        $response->assertSee("setTheme('system')", false);
        $response->assertSee('Mode Terang');
        $response->assertSee('Mode Gelap');
        $response->assertSee('Ikuti Sistem');
    }

    public function test_dashboard_renders_executive_kpi_cards_and_workforce_banner(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $response->assertStatus(200);

        // Workforce control banner
        $response->assertSee('Workforce Control');
        $response->assertSee('Status Operasional Hari Ini');

        // Executive KPI Cards
        $response->assertSee('ui-kpi-card', false);
        $response->assertSee('Total Karyawan');
        $response->assertSee('Hadir Hari Ini');
        $response->assertSee('Terlambat');
        $response->assertSee('Belum Check-in');
        $response->assertSee('Izin / Cuti / Sakit');
    }
}