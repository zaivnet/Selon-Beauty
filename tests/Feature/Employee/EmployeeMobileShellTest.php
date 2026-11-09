<?php

namespace Tests\Feature\Employee;

use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Outlet;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmployeeMobileShellTest extends TestCase
{
    use RefreshDatabase;

    protected User $employeeUser;
    protected Employee $employee;
    protected Outlet $outlet;
    protected Shift $shift;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outlet = Outlet::create([
            'name' => 'Outlet Pusat Modern',
            'code' => 'OUT-01',
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'radius_meters' => 100,
            'max_accuracy_meters' => 80,
            'is_active' => true,
        ]);

        $this->employee = Employee::create([
            'employee_code' => 'EMP-001',
            'full_name' => 'Bintang Pratama',
            'outlet_id' => $this->outlet->id,
            'attendance_enabled' => true,
            'status' => 'active',
        ]);

        $this->employeeUser = User::create([
            'employee_id' => $this->employee->id,
            'name' => 'Bintang Pratama',
            'email' => 'bintang@absensizaiv.com',
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        $this->shift = Shift::create([
            'name' => 'Shift Reguler Pagi',
            'code' => 'PAGI',
            'start_time' => '08:00',
            'end_time' => '17:00',
            'is_active' => true,
        ]);

        EmployeeSchedule::create([
            'employee_id' => $this->employee->id,
            'work_date' => now()->toDateString(),
            'shift_id' => $this->shift->id,
            'work_outlet_id' => $this->outlet->id,
            'schedule_type' => 'work',
        ]);
    }

    public function test_employee_mobile_shell_contains_branding_and_secondary_identity(): void
    {
        $response = $this->actingAs($this->employeeUser)->get(route('employee.dashboard'));
        $response->assertStatus(200);

        // Secondary product identity
        $response->assertSee('Portal Karyawan · Absensi Zaiv');
        $response->assertSee('viewport-fit=cover', false);
        $response->assertSee('/manifest.webmanifest', false);
    }

    public function test_bottom_navigation_bar_structure_and_active_states(): void
    {
        // 1. Dashboard active state
        $resHome = $this->actingAs($this->employeeUser)->get(route('employee.dashboard'));
        $resHome->assertStatus(200);
        $resHome->assertSee('Bottom Navigation Bar (5 Items Max', false);
        $resHome->assertSee('Home');
        $resHome->assertSee('Jadwal');
        $resHome->assertSee('Absen');
        $resHome->assertSee('Pengajuan');
        $resHome->assertSee('Profil');

        // 2. Schedules active state
        $resSchedule = $this->actingAs($this->employeeUser)->get(route('employee.schedules.index'));
        $resSchedule->assertStatus(200);
        $resSchedule->assertSee('text-rose-600 font-extrabold', false);

        // 3. Leave requests active state
        $resLeave = $this->actingAs($this->employeeUser)->get(route('employee.leave-requests.index'));
        $resLeave->assertStatus(200);
        $resLeave->assertSee('text-rose-600 font-extrabold', false);

        // 4. Profile active state
        $resProfile = $this->actingAs($this->employeeUser)->get(route('employee.profile.index'));
        $resProfile->assertStatus(200);
        $resProfile->assertSee('text-rose-600 font-extrabold', false);
    }

    public function test_theme_switcher_options_available_on_employee_portal(): void
    {
        $response = $this->actingAs($this->employeeUser)->get(route('employee.dashboard'));
        $response->assertStatus(200);

        // Check theme toggle options in header
        $response->assertSee("window.applyAttendanceTheme('light')", false);
        $response->assertSee("window.applyAttendanceTheme('dark')", false);
        $response->assertSee("window.applyAttendanceTheme('system')", false);
    }

    public function test_dashboard_displays_work_status_and_attendance_actions(): void
    {
        $response = $this->actingAs($this->employeeUser)->get(route('employee.dashboard'));
        $response->assertStatus(200);

        // Identity and work status
        $response->assertSee('Bintang Pratama');
        $response->assertSee('Outlet Pusat Modern');
        $response->assertSee('PAGI');
        $response->assertSee('08:00 — 17:00 WIB');

        // GPS contract elements
        $response->assertSee('id="gps-badge-status"', false);
        $response->assertSee('id="gps-info-box"', false);
        $response->assertSee('Perbarui Lokasi');
        $response->assertSee('detectGPSLocation()', false);

        // Camera and face detection contract elements
        $response->assertSee('id="btn-open-camera"', false);
        $response->assertSee('MEMUAT DETEKSI WAJAH...', false);
        $response->assertSee('WAJAH BELUM TERDETEKSI', false);

        // Primary action area
        $response->assertSee('id="absen-card"', false);
        $response->assertSee('id="btn-check-in"', false);
    }

    public function test_non_participating_employee_sees_disabled_guidance(): void
    {
        $this->employee->update(['attendance_enabled' => false]);

        $response = $this->actingAs($this->employeeUser)->get(route('employee.dashboard'));
        $response->assertStatus(200);

        $response->assertSee('Akun ini tidak diwajibkan mengikuti sistem kehadiran.');
        $response->assertDontSee('Absen Masuk');
        $response->assertDontSee('Izin & Cuti');
    }

    public function test_all_employee_views_render_200_for_authenticated_employee(): void
    {
        $routes = [
            'employee.dashboard',
            'employee.schedules.index',
            'employee.monthly-recap.show',
            'employee.leave-requests.index',
            'employee.overtime-requests.index',
            'employee.shift-swaps.index',
            'employee.shift-swaps.create',
            'employee.profile.index',
            'notifications.index',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($this->employeeUser)->get(route($route));
            $response->assertStatus(200, "Failed asserting route {$route} returns HTTP 200");
        }
    }
}

