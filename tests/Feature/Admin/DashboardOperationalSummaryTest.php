<?php

namespace Tests\Feature\Admin;

use App\Models\AppSetting;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\EmployeeScheduleOverride;
use App\Models\JobTitle;
use App\Models\Outlet;
use App\Models\Shift;
use App\Models\User;
use App\Services\OutletModeService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardOperationalSummaryTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;
    protected User $adminSingle;
    protected User $adminMulti;
    protected Outlet $outletA;
    protected Outlet $outletB;
    protected Outlet $outletC;
    protected Shift $morningShift;
    protected JobTitle $jobTitle;
    protected Employee $empA;
    protected Employee $empB;
    protected Employee $empC;
    protected Employee $empCross;

    protected function setUp(): void
    {
        parent::setUp();

        AppSetting::set('outlet_mode', OutletModeService::MODE_MULTI, 'string', false);

        // Clean any existing outlets to guarantee deterministic count
        Outlet::query()->delete();

        // 3 Active Outlets
        $this->outletA = Outlet::create([
            'name' => 'Outlet Pusat Jakarta',
            'code' => 'OUT-JKT',
            'latitude' => -6.175110,
            'longitude' => 106.827220,
            'radius_meters' => 100,
            'max_accuracy_meters' => 50,
            'is_active' => true,
        ]);

        $this->outletB = Outlet::create([
            'name' => 'Outlet Cabang Bandung',
            'code' => 'OUT-BDG',
            'latitude' => -6.917464,
            'longitude' => 107.619123,
            'radius_meters' => 150,
            'max_accuracy_meters' => 50,
            'is_active' => true,
        ]);

        $this->outletC = Outlet::create([
            'name' => 'Outlet Cabang Surabaya',
            'code' => 'OUT-SBY',
            'latitude' => -7.257472,
            'longitude' => 112.752088,
            'radius_meters' => 150,
            'max_accuracy_meters' => 50,
            'is_active' => true,
        ]);

        $this->jobTitle = JobTitle::create([
            'name' => 'Stylist Specialist',
            'is_active' => true,
        ]);

        $this->morningShift = Shift::create([
            'name' => 'Shift Pagi',
            'code' => 'PAGI',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'check_in_open_minutes_before' => 30,
            'check_out_open_minutes_before' => 30,
            'is_active' => true,
        ]);

        // Superadmin
        $this->superadmin = User::create([
            'name' => 'Super Administrator',
            'email' => 'superadmin@selonbeauty.com',
            'password' => bcrypt('password'),
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        // Admin Single (Only Outlet A)
        $this->adminSingle = User::create([
            'name' => 'Admin Jakarta',
            'email' => 'admin.jkt@selonbeauty.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'outlet_id' => $this->outletA->id,
            'outlet_access_mode' => 'selected',
            'is_active' => true,
        ]);
        $this->adminSingle->assignedOutlets()->sync([$this->outletA->id]);

        // Admin Multi (Outlet A and Outlet B only, not C)
        $this->adminMulti = User::create([
            'name' => 'Admin Regional Jkt-Bdg',
            'email' => 'admin.regional@selonbeauty.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'outlet_id' => $this->outletA->id,
            'outlet_access_mode' => 'selected',
            'is_active' => true,
        ]);
        $this->adminMulti->assignedOutlets()->sync([$this->outletA->id, $this->outletB->id]);

        // Employees
        $this->empA = Employee::create([
            'employee_code' => 'EMP-JKT-01',
            'full_name' => 'Andi Jakarta',
            'email' => 'andi@selonbeauty.com',
            'status' => 'active',
            'outlet_id' => $this->outletA->id,
            'job_title_id' => $this->jobTitle->id,
        ]);

        $this->empB = Employee::create([
            'employee_code' => 'EMP-BDG-01',
            'full_name' => 'Budi Bandung',
            'email' => 'budi@selonbeauty.com',
            'status' => 'active',
            'outlet_id' => $this->outletB->id,
            'job_title_id' => $this->jobTitle->id,
        ]);

        $this->empC = Employee::create([
            'employee_code' => 'EMP-SBY-01',
            'full_name' => 'Citra Surabaya',
            'email' => 'citra@selonbeauty.com',
            'status' => 'active',
            'outlet_id' => $this->outletC->id,
            'job_title_id' => $this->jobTitle->id,
        ]);
    }

    public function test_superadmin_defaults_to_all_outlets_view_and_aggregates_all_outlets(): void
    {
        $today = Carbon::now(config('app.timezone'))->toDateString();

        // Schedules for today
        EmployeeSchedule::create([
            'employee_id' => $this->empA->id,
            'shift_id' => $this->morningShift->id,
            'work_date' => $today,
            'work_outlet_id' => $this->outletA->id,
        ]);
        EmployeeSchedule::create([
            'employee_id' => $this->empB->id,
            'shift_id' => $this->morningShift->id,
            'work_date' => $today,
            'work_outlet_id' => $this->outletB->id,
        ]);
        EmployeeSchedule::create([
            'employee_id' => $this->empC->id,
            'shift_id' => $this->morningShift->id,
            'work_date' => $today,
            'work_outlet_id' => $this->outletC->id,
        ]);

        // Attendance: empA is Present
        AttendanceRecord::create([
            'employee_id' => $this->empA->id,
            'work_date' => $today,
            'check_in_at' => Carbon::now(config('app.timezone'))->setTime(7, 55),
            'status' => 'present',
            'outlet_id' => $this->outletA->id,
        ]);

        // Superadmin views dashboard
        $response = $this->actingAs($this->superadmin)->get(route('admin.dashboard'));
        $response->assertOk();
        $response->assertViewIs('admin.dashboard_global');
        $response->assertSee('Semua Outlet');
        $response->assertSee('Outlet Pusat Jakarta');
        $response->assertSee('Outlet Cabang Bandung');
        $response->assertSee('Outlet Cabang Surabaya');

        $globalData = $response->viewData('globalData');
        $this->assertSame(3, $globalData['global_kpi']['total_outlets']);
        $this->assertSame(3, $globalData['global_kpi']['total_employees']);
        $this->assertSame(1, $globalData['global_kpi']['present_today']);
    }

    public function test_superadmin_can_filter_to_single_outlet_and_view_metrics(): void
    {
        $today = Carbon::now(config('app.timezone'))->toDateString();

        EmployeeSchedule::create([
            'employee_id' => $this->empA->id,
            'shift_id' => $this->morningShift->id,
            'work_date' => $today,
            'work_outlet_id' => $this->outletA->id,
        ]);
        EmployeeSchedule::create([
            'employee_id' => $this->empB->id,
            'shift_id' => $this->morningShift->id,
            'work_date' => $today,
            'work_outlet_id' => $this->outletB->id,
        ]);

        AttendanceRecord::create([
            'employee_id' => $this->empA->id,
            'work_date' => $today,
            'check_in_at' => Carbon::now(config('app.timezone'))->setTime(7, 55),
            'status' => 'present',
            'outlet_id' => $this->outletA->id,
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.dashboard', ['outlet_id' => $this->outletA->id]));
        $response->assertOk();
        $response->assertViewIs('admin.dashboard');
        $response->assertViewHas('requestedOutletId', $this->outletA->id);

        $metrics = $response->viewData('metrics');
        $this->assertSame(1, $metrics['total_employees']);
        $this->assertSame(1, $metrics['present_today']);
    }

    public function test_admin_single_outlet_cannot_see_other_outlets_and_has_no_redundant_semua_outlet(): void
    {
        $today = Carbon::now(config('app.timezone'))->toDateString();

        EmployeeSchedule::create([
            'employee_id' => $this->empA->id,
            'shift_id' => $this->morningShift->id,
            'work_date' => $today,
            'work_outlet_id' => $this->outletA->id,
        ]);
        EmployeeSchedule::create([
            'employee_id' => $this->empB->id,
            'shift_id' => $this->morningShift->id,
            'work_date' => $today,
            'work_outlet_id' => $this->outletB->id,
        ]);

        $response = $this->actingAs($this->adminSingle)->get(route('admin.dashboard'));
        $response->assertOk();
        $response->assertViewIs('admin.dashboard');
        $response->assertViewHas('requestedOutletId', $this->outletA->id);

        // Does not show dropdown or "Semua Outlet" option
        $response->assertDontSee('<select name="outlet_id"', false);
        $response->assertSee('Outlet: Outlet Pusat Jakarta');
        $response->assertDontSee('Budi Bandung');

        // URL tampering to unassigned Outlet B does not leak data
        $responseTamper = $this->actingAs($this->adminSingle)->get(route('admin.dashboard', ['outlet_id' => $this->outletB->id]));
        $responseTamper->assertOk();
        $responseTamper->assertViewIs('admin.dashboard');
        $responseTamper->assertViewHas('requestedOutletId', $this->outletA->id);
        $responseTamper->assertDontSee('Budi Bandung');
    }

    public function test_admin_multi_outlet_aggregates_only_assigned_outlets(): void
    {
        $today = Carbon::now(config('app.timezone'))->toDateString();

        EmployeeSchedule::create([
            'employee_id' => $this->empA->id,
            'shift_id' => $this->morningShift->id,
            'work_date' => $today,
            'work_outlet_id' => $this->outletA->id,
        ]);
        EmployeeSchedule::create([
            'employee_id' => $this->empB->id,
            'shift_id' => $this->morningShift->id,
            'work_date' => $today,
            'work_outlet_id' => $this->outletB->id,
        ]);
        EmployeeSchedule::create([
            'employee_id' => $this->empC->id,
            'shift_id' => $this->morningShift->id,
            'work_date' => $today,
            'work_outlet_id' => $this->outletC->id,
        ]);

        // Default view for Admin Multi is Semua Outlet in their scope (Outlet A + B)
        $response = $this->actingAs($this->adminMulti)->get(route('admin.dashboard'));
        $response->assertOk();
        $response->assertViewIs('admin.dashboard_global');
        $response->assertSee('Semua Outlet');
        $response->assertSee('Outlet Pusat Jakarta');
        $response->assertSee('Outlet Cabang Bandung');
        $response->assertDontSee('Outlet Cabang Surabaya');

        $globalData = $response->viewData('globalData');
        $this->assertSame(2, $globalData['global_kpi']['total_outlets']);
        $this->assertSame(2, $globalData['global_kpi']['total_employees']);

        // Explicit Semua Outlet request (?outlet_id=0 or ?outlet_id=all)
        $responseExplicit = $this->actingAs($this->adminMulti)->get(route('admin.dashboard', ['outlet_id' => '0']));
        $responseExplicit->assertOk();
        $responseExplicit->assertViewIs('admin.dashboard_global');

        // Filter to single assigned outlet (Outlet B)
        $responseB = $this->actingAs($this->adminMulti)->get(route('admin.dashboard', ['outlet_id' => $this->outletB->id]));
        $responseB->assertOk();
        $responseB->assertViewIs('admin.dashboard');
        $responseB->assertViewHas('requestedOutletId', $this->outletB->id);
        $metricsB = $responseB->viewData('metrics');
        $this->assertSame(1, $metricsB['total_employees']);
    }

    public function test_temporary_cross_outlet_assignment_counted_in_work_outlet_and_once_in_all_outlets(): void
    {
        $today = Carbon::now(config('app.timezone'))->toDateString();

        // Cross-outlet employee: Home Outlet is A, but will be assigned to work in Outlet B
        $empCross = Employee::create([
            'employee_code' => 'EMP-CROSS-01',
            'full_name' => 'Dedi Cross Outlet',
            'email' => 'dedi@selonbeauty.com',
            'status' => 'active',
            'outlet_id' => $this->outletA->id,
            'job_title_id' => $this->jobTitle->id,
        ]);

        // Andi in Outlet A
        EmployeeSchedule::create([
            'employee_id' => $this->empA->id,
            'shift_id' => $this->morningShift->id,
            'work_date' => $today,
            'work_outlet_id' => $this->outletA->id,
        ]);

        // Budi in Outlet B
        EmployeeSchedule::create([
            'employee_id' => $this->empB->id,
            'shift_id' => $this->morningShift->id,
            'work_date' => $today,
            'work_outlet_id' => $this->outletB->id,
        ]);

        // Citra in Outlet C
        EmployeeSchedule::create([
            'employee_id' => $this->empC->id,
            'shift_id' => $this->morningShift->id,
            'work_date' => $today,
            'work_outlet_id' => $this->outletC->id,
        ]);

        // Dedi (Home = A) temporarily assigned to WORK at Outlet B via override
        EmployeeScheduleOverride::create([
            'employee_id' => $empCross->id,
            'date' => $today,
            'override_type' => 'work',
            'shift_id' => $this->morningShift->id,
            'work_outlet_id' => $this->outletB->id,
            'reason' => 'Bantuan operasional',
        ]);

        // Dedi clocks in at Outlet B
        AttendanceRecord::create([
            'employee_id' => $empCross->id,
            'work_date' => $today,
            'check_in_at' => Carbon::now(config('app.timezone'))->setTime(8, 5),
            'status' => 'present',
            'outlet_id' => $this->outletB->id,
        ]);

        // 1. View Outlet A: Dedi must NOT be counted as workforce of Outlet A today (only Andi = 1)
        $responseA = $this->actingAs($this->superadmin)->get(route('admin.dashboard', ['outlet_id' => $this->outletA->id]));
        $responseA->assertOk();
        $metricsA = $responseA->viewData('metrics');
        $this->assertSame(1, $metricsA['total_employees']);
        $this->assertSame(0, $metricsA['present_today']);

        // 2. View Outlet B: Dedi MUST be counted as workforce of Outlet B today (Budi + Dedi = 2)
        $responseB = $this->actingAs($this->superadmin)->get(route('admin.dashboard', ['outlet_id' => $this->outletB->id]));
        $responseB->assertOk();
        $metricsB = $responseB->viewData('metrics');
        $this->assertSame(2, $metricsB['total_employees']);
        $this->assertSame(1, $metricsB['present_today']);

        // 3. View Semua Outlet: Dedi is counted exactly once (Andi + Budi + Citra + Dedi = 4)
        $responseAll = $this->actingAs($this->superadmin)->get(route('admin.dashboard', ['outlet_id' => '0']));
        $responseAll->assertOk();
        $globalData = $responseAll->viewData('globalData');
        $this->assertSame(4, $globalData['global_kpi']['total_employees']);
        $this->assertSame(1, $globalData['global_kpi']['present_today']);
    }

    public function test_single_outlet_mode_hides_outlet_filter_and_renders_cleanly(): void
    {
        // Deactivate other outlets and switch to Single Outlet Mode with Outlet A
        $this->outletB->update(['is_active' => false]);
        $this->outletC->update(['is_active' => false]);
        AppSetting::set('outlet_mode', OutletModeService::MODE_SINGLE, 'string', false);

        $response = $this->actingAs($this->superadmin)->get(route('admin.dashboard'));
        $response->assertOk();
        $response->assertViewIs('admin.dashboard');
        $response->assertViewHas('requestedOutletId', $this->outletA->id);

        // Filter dropdown must not be shown
        $response->assertDontSee('<select name="outlet_id"', false);
        $response->assertSee('Outlet: Outlet Pusat Jakarta');
    }
}
