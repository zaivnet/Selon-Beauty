<?php

namespace Tests\Feature\Report;

use App\Models\AppSetting;
use App\Models\AttendanceLocation;
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
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ReactiveAttendanceReportFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;
    protected User $adminScoped;
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

        // Deterministic outlets
        Outlet::query()->delete();

        $this->outletA = Outlet::create([
            'name' => 'Selon Beauty Pusat',
            'code' => 'PST',
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'radius_meters' => 100,
            'max_accuracy_meters' => 50,
            'is_active' => true,
        ]);

        $this->outletB = Outlet::create([
            'name' => 'Selon Beauty Bandung',
            'code' => 'BDG',
            'latitude' => -6.917464,
            'longitude' => 107.619123,
            'radius_meters' => 150,
            'max_accuracy_meters' => 50,
            'is_active' => true,
        ]);

        $this->outletC = Outlet::create([
            'name' => 'Selon Beauty Surabaya',
            'code' => 'SBY',
            'latitude' => -7.257472,
            'longitude' => 112.752088,
            'radius_meters' => 150,
            'max_accuracy_meters' => 50,
            'is_active' => true,
        ]);

        $this->jobTitle = JobTitle::create([
            'name' => 'Beautician',
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

        $this->superadmin = User::create([
            'name' => 'Super Administrator',
            'email' => 'superadmin@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $this->adminScoped = User::create([
            'name' => 'Admin Pusat Only',
            'email' => 'admin.pusat@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'outlet_id' => $this->outletA->id,
            'outlet_access_mode' => 'selected',
            'is_active' => true,
        ]);
        $this->adminScoped->assignedOutlets()->sync([$this->outletA->id]);

        $this->adminMulti = User::create([
            'name' => 'Admin Regional A+B',
            'email' => 'admin.regional@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'outlet_id' => $this->outletA->id,
            'outlet_access_mode' => 'selected',
            'is_active' => true,
        ]);
        $this->adminMulti->assignedOutlets()->sync([$this->outletA->id, $this->outletB->id]);

        $this->empA = Employee::create([
            'employee_code' => 'EMP-PST-01',
            'full_name' => 'Ayu Pusat',
            'email' => 'ayu@selonbeauty.com',
            'status' => 'active',
            'outlet_id' => $this->outletA->id,
            'job_title_id' => $this->jobTitle->id,
        ]);

        $this->empB = Employee::create([
            'employee_code' => 'EMP-BDG-01',
            'full_name' => 'Bella Bandung',
            'email' => 'bella@selonbeauty.com',
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

        $this->empCross = Employee::create([
            'employee_code' => 'EMP-CRS-01',
            'full_name' => 'Dewi Cross Outlet',
            'email' => 'dewi@selonbeauty.com',
            'status' => 'active',
            'outlet_id' => $this->outletA->id, // Home = Pusat
            'job_title_id' => $this->jobTitle->id,
        ]);
    }

    public function test_all_outlets_filter_displays_all_accessible_employees_and_aggregates_records(): void
    {
        $today = Carbon::now(config('app.timezone'))->toDateString();

        AttendanceRecord::create([
            'employee_id' => $this->empA->id,
            'work_date' => $today,
            'check_in_at' => $today.' 08:00:00',
            'status' => 'present',
            'outlet_id' => $this->outletA->id,
        ]);

        AttendanceRecord::create([
            'employee_id' => $this->empB->id,
            'work_date' => $today,
            'check_in_at' => $today.' 08:05:00',
            'status' => 'present',
            'outlet_id' => $this->outletB->id,
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.reports.attendance', [
            'start_date' => $today,
            'end_date' => $today,
            'outlet_id' => 'all',
        ]));

        $response->assertOk();
        // Employee options in dropdown contains all
        $response->assertSee('Ayu Pusat');
        $response->assertSee('Bella Bandung');
        $response->assertSee('Citra Surabaya');

        // Form does not have Filter or Reset buttons
        $response->assertDontSee('>Filter</button>', false);
        $response->assertDontSee('>Reset</a>', false);
        $response->assertSee('Bersihkan filter');
    }

    public function test_specific_outlet_filter_scopes_employee_options_and_attendance_snapshot(): void
    {
        $today = Carbon::now(config('app.timezone'))->toDateString();

        EmployeeSchedule::create([
            'employee_id' => $this->empA->id,
            'work_date' => $today,
            'shift_id' => $this->morningShift->id,
            'schedule_type' => 'work',
            'work_outlet_id' => $this->outletA->id,
        ]);
        EmployeeSchedule::create([
            'employee_id' => $this->empB->id,
            'work_date' => $today,
            'shift_id' => $this->morningShift->id,
            'schedule_type' => 'work',
            'work_outlet_id' => $this->outletB->id,
        ]);

        AttendanceRecord::create([
            'employee_id' => $this->empA->id,
            'work_date' => $today,
            'check_in_at' => $today.' 08:00:00',
            'status' => 'present',
            'outlet_id' => $this->outletA->id,
        ]);

        AttendanceRecord::create([
            'employee_id' => $this->empB->id,
            'work_date' => $today,
            'check_in_at' => $today.' 08:05:00',
            'status' => 'present',
            'outlet_id' => $this->outletB->id,
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.reports.attendance', [
            'start_date' => $today,
            'end_date' => $today,
            'outlet_id' => $this->outletA->id,
        ]));

        $response->assertOk();
        $employees = $response->viewData('employees');
        $this->assertTrue($employees->contains('id', $this->empA->id));
        $this->assertFalse($employees->contains('id', $this->empB->id));
        $this->assertFalse($employees->contains('id', $this->empC->id));

        $reportData = $response->viewData('reportData');
        $this->assertSame(1, $reportData['global_summary']['present_count']);
    }

    public function test_switching_outlet_resets_incompatible_selected_employee(): void
    {
        $today = Carbon::now(config('app.timezone'))->toDateString();

        // Requesting Outlet A with Employee B (who belongs to Outlet B)
        $response = $this->actingAs($this->superadmin)->get(route('admin.reports.attendance', [
            'start_date' => $today,
            'end_date' => $today,
            'outlet_id' => $this->outletA->id,
            'employee_id' => $this->empB->id, // invalid for Outlet A
        ]));

        $response->assertOk();
        $filters = $response->viewData('filters');
        // Must automatically reset employee_id to null
        $this->assertNull($filters['employee_id']);
    }

    public function test_cross_outlet_employee_is_available_in_dropdown_and_report_for_work_outlet(): void
    {
        $today = Carbon::now(config('app.timezone'))->toDateString();

        // Dewi (Home = Pusat) worked at Bandung on $today via override & attendance snapshot
        EmployeeScheduleOverride::create([
            'employee_id' => $this->empCross->id,
            'date' => $today,
            'override_type' => 'work',
            'shift_id' => $this->morningShift->id,
            'work_outlet_id' => $this->outletB->id,
            'reason' => 'Bantuan cabang',
        ]);

        AttendanceRecord::create([
            'employee_id' => $this->empCross->id,
            'work_date' => $today,
            'check_in_at' => $today.' 08:00:00',
            'status' => 'present',
            'outlet_id' => $this->outletB->id, // Snapshot in Bandung
        ]);

        // When viewing Outlet B (Bandung)
        $response = $this->actingAs($this->superadmin)->get(route('admin.reports.attendance', [
            'start_date' => $today,
            'end_date' => $today,
            'outlet_id' => $this->outletB->id,
        ]));

        $response->assertOk();
        $employees = $response->viewData('employees');
        // Dewi must be selectable under Bandung because she worked there during this period
        $this->assertTrue($employees->contains('id', $this->empCross->id));
        $this->assertTrue($employees->contains('id', $this->empB->id));

        $reportData = $response->viewData('reportData');
        $this->assertSame(1, $reportData['global_summary']['present_count']);
    }

    public function test_scoped_admin_cannot_access_unassigned_outlet(): void
    {
        $today = Carbon::now(config('app.timezone'))->toDateString();

        // Admin Pusat attempts to request Bandung
        $response = $this->actingAs($this->adminScoped)->get(route('admin.reports.attendance', [
            'start_date' => $today,
            'end_date' => $today,
            'outlet_id' => $this->outletB->id,
        ]));

        $response->assertStatus(403);
    }

    public function test_multi_assigned_admin_aggregates_only_assigned_outlets(): void
    {
        $today = Carbon::now(config('app.timezone'))->toDateString();

        $response = $this->actingAs($this->adminMulti)->get(route('admin.reports.attendance', [
            'start_date' => $today,
            'end_date' => $today,
            'outlet_id' => 'all',
        ]));

        $response->assertOk();
        $employees = $response->viewData('employees');
        $this->assertTrue($employees->contains('id', $this->empA->id));
        $this->assertTrue($employees->contains('id', $this->empB->id));
        $this->assertFalse($employees->contains('id', $this->empC->id)); // Surabaya not accessible
    }

    public function test_single_outlet_mode_hides_outlet_dropdown(): void
    {
        $this->outletB->update(['is_active' => false]);
        $this->outletC->update(['is_active' => false]);
        AppSetting::set('outlet_mode', OutletModeService::MODE_SINGLE, 'string', false);

        $response = $this->actingAs($this->superadmin)->get(route('admin.reports.attendance'));
        $response->assertOk();
        $response->assertDontSee('<select name="outlet_id"', false);
    }

    public function test_status_filter_updates_summary_cards_and_detail_rows(): void
    {
        $today = Carbon::now(config('app.timezone'))->toDateString();

        AttendanceRecord::create([
            'employee_id' => $this->empA->id,
            'work_date' => $today,
            'check_in_at' => $today.' 08:00:00',
            'status' => 'present',
            'outlet_id' => $this->outletA->id,
        ]);

        // Filter status = late (0 matches)
        $responseLate = $this->actingAs($this->superadmin)->get(route('admin.reports.attendance', [
            'start_date' => $today,
            'end_date' => $today,
            'status' => 'late',
        ]));

        $responseLate->assertOk();
        $reportData = $responseLate->viewData('reportData');
        $this->assertCount(0, $reportData['detail_rows']);

        // Filter status = present (1 match)
        $responsePresent = $this->actingAs($this->superadmin)->get(route('admin.reports.attendance', [
            'start_date' => $today,
            'end_date' => $today,
            'status' => 'present',
        ]));

        $responsePresent->assertOk();
        $reportDataPresent = $responsePresent->viewData('reportData');
        $this->assertCount(1, $reportDataPresent['detail_rows']);
    }

    public function test_print_view_and_csv_export_share_identical_filter_scoping(): void
    {
        $today = Carbon::now(config('app.timezone'))->toDateString();

        AttendanceRecord::create([
            'employee_id' => $this->empA->id,
            'work_date' => $today,
            'check_in_at' => $today.' 08:00:00',
            'status' => 'present',
            'outlet_id' => $this->outletA->id,
        ]);

        AttendanceRecord::create([
            'employee_id' => $this->empB->id,
            'work_date' => $today,
            'check_in_at' => $today.' 08:05:00',
            'status' => 'present',
            'outlet_id' => $this->outletB->id,
        ]);

        $params = [
            'start_date' => $today,
            'end_date' => $today,
            'outlet_id' => $this->outletA->id,
            'employee_id' => $this->empA->id,
            'status' => 'present',
        ];

        // Print view
        $printResp = $this->actingAs($this->superadmin)->get(route('admin.reports.attendance.print', $params));
        $printResp->assertOk();
        $printResp->assertSee('Ayu Pusat');
        $printResp->assertDontSee('Bella Bandung');

        // CSV export
        $csvResp = $this->actingAs($this->superadmin)->get(route('admin.reports.attendance.export-csv', $params));
        $csvResp->assertOk();
        $this->assertStringContainsString('text/csv', (string) $csvResp->headers->get('Content-Type'));
    }

    public function test_date_range_change_recalculates_employee_relevance_and_resets_stale_employee(): void
    {
        $septemberDate = '2026-09-15';
        $octoberDate = '2026-10-15';

        // Dewi (Home = Pusat) has assignment in Bandung ONLY during September
        EmployeeScheduleOverride::create([
            'employee_id' => $this->empCross->id,
            'date' => $septemberDate,
            'override_type' => 'work',
            'shift_id' => $this->morningShift->id,
            'work_outlet_id' => $this->outletB->id,
            'reason' => 'Bantuan September',
        ]);

        AttendanceRecord::create([
            'employee_id' => $this->empCross->id,
            'work_date' => $septemberDate,
            'check_in_at' => $septemberDate.' 08:00:00',
            'status' => 'present',
            'outlet_id' => $this->outletB->id,
        ]);

        // 1. September period: Dewi is relevant to Bandung
        $responseSept = $this->actingAs($this->superadmin)->get(route('admin.reports.attendance', [
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'outlet_id' => $this->outletB->id,
        ]));
        $responseSept->assertOk();
        $employeesSept = $responseSept->viewData('employees');
        $this->assertTrue($employeesSept->contains('id', $this->empCross->id));

        // 2. October period: Dewi is NOT relevant to Bandung
        $responseOct = $this->actingAs($this->superadmin)->get(route('admin.reports.attendance', [
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
            'outlet_id' => $this->outletB->id,
            'employee_id' => $this->empCross->id, // stale selection
        ]));
        $responseOct->assertOk();
        $employeesOct = $responseOct->viewData('employees');
        $this->assertFalse($employeesOct->contains('id', $this->empCross->id));
        $filtersOct = $responseOct->viewData('filters');
        $this->assertNull($filtersOct['employee_id']);
    }

    public function test_tampering_unauthorized_outlet_id_is_blocked_on_web_print_and_csv(): void
    {
        $today = Carbon::now(config('app.timezone'))->toDateString();
        $tamperedParams = [
            'start_date' => $today,
            'end_date' => $today,
            'outlet_id' => $this->outletB->id, // Admin only has Outlet A
        ];

        // 1. Web report
        $webResp = $this->actingAs($this->adminScoped)->get(route('admin.reports.attendance', $tamperedParams));
        $webResp->assertStatus(403);

        // 2. Print view
        $printResp = $this->actingAs($this->adminScoped)->get(route('admin.reports.attendance.print', $tamperedParams));
        $printResp->assertStatus(403);

        // 3. CSV export
        $csvResp = $this->actingAs($this->adminScoped)->get(route('admin.reports.attendance.export-csv', $tamperedParams));
        $csvResp->assertStatus(403);
    }
}
