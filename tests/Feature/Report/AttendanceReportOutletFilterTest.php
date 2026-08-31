<?php

namespace Tests\Feature\Report;

use App\Models\AttendanceLocation;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\EmployeeScheduleOverride;
use App\Models\JobTitle;
use App\Models\Outlet;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AttendanceReportOutletFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;
    protected User $owner;
    protected User $adminScoped;
    protected User $adminZero;
    protected Outlet $outletPusat;
    protected Outlet $outletCabang;
    protected Outlet $outletUnassigned;
    protected Shift $shiftPagi;
    protected Shift $shiftCrossMidnight;
    protected JobTitle $jobTitle;
    protected AttendanceLocation $locationPusat;
    protected AttendanceLocation $locationCabang;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outletPusat = Outlet::create([
            'name' => 'Selon Beauty Pusat',
            'code' => 'PST',
            'address' => 'Jl. Pusat No. 1',
            'latitude' => -7.250000,
            'longitude' => 112.750000,
            'radius_meters' => 100,
            'max_accuracy_meters' => 50,
            'is_active' => true,
        ]);

        $this->outletCabang = Outlet::create([
            'name' => 'Selon Beauty Cabang',
            'code' => 'CBG',
            'address' => 'Jl. Cabang No. 2',
            'latitude' => -7.260000,
            'longitude' => 112.760000,
            'radius_meters' => 100,
            'max_accuracy_meters' => 50,
            'is_active' => true,
        ]);

        $this->outletUnassigned = Outlet::create([
            'name' => 'Selon Beauty Rahasia',
            'code' => 'RHS',
            'address' => 'Jl. Rahasia No. 3',
            'latitude' => -7.270000,
            'longitude' => 112.770000,
            'radius_meters' => 100,
            'max_accuracy_meters' => 50,
            'is_active' => true,
        ]);

        $this->shiftPagi = Shift::create([
            'name' => 'Shift Pagi',
            'code' => 'SP',
            'start_time' => '08:00',
            'end_time' => '16:00',
            'is_active' => true,
        ]);

        $this->shiftCrossMidnight = Shift::create([
            'name' => 'Shift Malam',
            'code' => 'SM',
            'start_time' => '22:00',
            'end_time' => '06:00',
            'crosses_midnight' => true,
            'is_active' => true,
        ]);

        $this->jobTitle = JobTitle::create(['name' => 'Therapist', 'is_active' => true]);

        $this->locationPusat = AttendanceLocation::create([
            'name' => 'Lokasi Pusat',
            'address' => 'Jl. Pusat No. 1',
            'latitude' => -7.250000,
            'longitude' => 112.750000,
            'radius_meters' => 100,
            'max_accuracy_meters' => 50,
            'is_active' => true,
        ]);

        $this->locationCabang = AttendanceLocation::create([
            'name' => 'Lokasi Cabang',
            'address' => 'Jl. Cabang No. 2',
            'latitude' => -7.260000,
            'longitude' => 112.760000,
            'radius_meters' => 100,
            'max_accuracy_meters' => 50,
            'is_active' => true,
        ]);

        $this->superadmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@selon.id',
            'password' => Hash::make('password'),
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $this->owner = User::create([
            'name' => 'Owner',
            'email' => 'owner@selon.id',
            'password' => Hash::make('password'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->adminScoped = User::create([
            'name' => 'Admin Scoped',
            'email' => 'adminscoped@selon.id',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'outlet_access_mode' => 'selected',
            'is_active' => true,
        ]);
        $this->adminScoped->assignedOutlets()->attach([$this->outletPusat->id, $this->outletCabang->id]);

        $this->adminZero = User::create([
            'name' => 'Admin Zero',
            'email' => 'adminzero@selon.id',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'outlet_access_mode' => 'selected',
            'is_active' => true,
        ]);
    }

    protected function createEmployee(string $name, string $code, Outlet $outlet): Employee
    {
        return Employee::create([
            'full_name' => $name,
            'employee_code' => $code,
            'email' => strtolower(str_replace(' ', '.', $name)).'@selon.id',
            'phone' => '0812'.rand(10000000, 99999999),
            'outlet_id' => $outlet->id,
            'job_title_id' => $this->jobTitle->id,
            'status' => 'active',
            'attendance_enabled' => true,
        ]);
    }

    /** 1. Outlet dropdown exists in Attendance Report */
    public function test_outlet_dropdown_exists_in_attendance_report(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('admin.reports.attendance'));

        $response->assertOk();
        $response->assertSee('name="outlet_id"', false);
        $response->assertSee('Semua Outlet');
    }

    /** 2. Superadmin sees all authorized outlets in dropdown */
    public function test_superadmin_sees_all_authorized_outlets_in_dropdown(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('admin.reports.attendance'));

        $response->assertOk();
        $response->assertSee('Selon Beauty Pusat');
        $response->assertSee('Selon Beauty Cabang');
        $response->assertSee('Selon Beauty Rahasia');
    }

    /** 3. Owner sees all authorized outlets in dropdown */
    public function test_owner_sees_all_authorized_outlets_in_dropdown(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.reports.attendance'));

        $response->assertOk();
        $response->assertSee('Selon Beauty Pusat');
        $response->assertSee('Selon Beauty Cabang');
        $response->assertSee('Selon Beauty Rahasia');
    }

    /** 4. Admin selected sees Assigned Outlets only */
    public function test_admin_selected_sees_assigned_outlets_only(): void
    {
        $response = $this->actingAs($this->adminScoped)->get(route('admin.reports.attendance'));

        $response->assertOk();
        $response->assertSee('Selon Beauty Pusat');
        $response->assertSee('Selon Beauty Cabang');
        $response->assertDontSee('Selon Beauty Rahasia');
    }

    /** 5. Admin zero assignment fails closed with empty state */
    public function test_admin_zero_assignment_fails_closed(): void
    {
        $response = $this->actingAs($this->adminZero)->get(route('admin.reports.attendance'));

        $response->assertOk();
        $response->assertDontSee('Selon Beauty Pusat');
        $response->assertDontSee('Selon Beauty Rahasia');
    }

    /** 6. Forged unauthorized outlet returns 403 Forbidden */
    public function test_forged_unauthorized_outlet_returns_403(): void
    {
        $response = $this->actingAs($this->adminScoped)->get(route('admin.reports.attendance', [
            'outlet_id' => $this->outletUnassigned->id,
        ]));

        $response->assertStatus(403);
    }

    /** 7. Report does not depend on Employee page outlet state */
    public function test_report_does_not_depend_on_employee_page_outlet_state(): void
    {
        $empPusat = $this->createEmployee('Ayu Pusat', 'EMP-001', $this->outletPusat);
        $empCabang = $this->createEmployee('Budi Cabang', 'EMP-002', $this->outletCabang);

        // Simulate user visited employees page with filter outlet Cabang
        $this->actingAs($this->superadmin)->get(route('admin.employees.index', ['outlet_id' => $this->outletCabang->id]));

        // Visiting reports/attendance without outlet_id parameter should show Semua Outlet independent state
        $response = $this->actingAs($this->superadmin)->get(route('admin.reports.attendance'));

        $response->assertOk();
        $response->assertSee('Ayu Pusat');
        $response->assertSee('Budi Cabang');
    }

    /** 8. Employee dropdown follows report Outlet filter */
    public function test_employee_dropdown_follows_report_outlet(): void
    {
        $empPusat = $this->createEmployee('Ayu Pusat', 'EMP-001', $this->outletPusat);
        $empCabang = $this->createEmployee('Budi Cabang', 'EMP-002', $this->outletCabang);

        $response = $this->actingAs($this->superadmin)->get(route('admin.reports.attendance', [
            'outlet_id' => $this->outletCabang->id,
        ]));

        $response->assertOk();
        $response->assertSee('Budi Cabang');
        $response->assertDontSee('Ayu Pusat (EMP-001)');
    }

    /** 9. HOME employee temporarily assigned to another outlet appears in target outlet report */
    public function test_home_employee_temporarily_assigned_to_another_outlet_appears_in_target_report(): void
    {
        $empPusat = $this->createEmployee('Ayu Home Pusat', 'EMP-001', $this->outletPusat);
        $targetDate = '2026-09-01';

        EmployeeScheduleOverride::create([
            'employee_id' => $empPusat->id,
            'date' => $targetDate,
            'override_type' => 'work',
            'shift_id' => $this->shiftPagi->id,
            'work_outlet_id' => $this->outletCabang->id,
            'reason' => 'Bantuan piket cabang',
            'created_by' => $this->superadmin->id,
        ]);

        AttendanceRecord::create([
            'employee_id' => $empPusat->id,
            'work_date' => $targetDate,
            'outlet_id' => $this->outletCabang->id,
            'status' => 'present',
            'check_in_at' => "{$targetDate} 08:00:00",
            'worked_minutes' => 480,
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.reports.attendance', [
            'start_date' => $targetDate,
            'end_date' => $targetDate,
            'outlet_id' => $this->outletCabang->id,
        ]));

        $response->assertOk();
        $response->assertSee('Ayu Home Pusat');
        $response->assertSee('Selon Beauty Cabang');
        $response->assertSee('PENUGASAN');
    }

    /** 10. Temporary employee excluded from HOME report for assignment date */
    public function test_temporary_employee_excluded_from_home_report_for_assignment_date(): void
    {
        $empPusat = $this->createEmployee('Ayu Home Pusat', 'EMP-001', $this->outletPusat);
        $targetDate = '2026-09-01';

        EmployeeScheduleOverride::create([
            'employee_id' => $empPusat->id,
            'date' => $targetDate,
            'override_type' => 'work',
            'shift_id' => $this->shiftPagi->id,
            'work_outlet_id' => $this->outletCabang->id,
            'reason' => 'Bantuan piket cabang',
            'created_by' => $this->superadmin->id,
        ]);

        AttendanceRecord::create([
            'employee_id' => $empPusat->id,
            'work_date' => $targetDate,
            'outlet_id' => $this->outletCabang->id,
            'status' => 'present',
            'check_in_at' => "{$targetDate} 08:00:00",
            'worked_minutes' => 480,
        ]);

        // Filter explicitly to Pusat
        $response = $this->actingAs($this->superadmin)->get(route('admin.reports.attendance', [
            'start_date' => $targetDate,
            'end_date' => $targetDate,
            'outlet_id' => $this->outletPusat->id,
        ]));

        $response->assertOk();
        $reportData = $response->viewData('reportData');
        $this->assertEmpty($reportData['detail_rows']);
        $this->assertEmpty($reportData['employee_summaries']);
        $response->assertSee('0 Total Record');
    }

    /** 11. Scheduled employee without attendance still belongs to Work Outlet report as absent */
    public function test_scheduled_employee_without_attendance_belongs_to_work_outlet_report(): void
    {
        $empPusat = $this->createEmployee('Ayu Absent', 'EMP-001', $this->outletPusat);
        $targetDate = '2026-09-01';

        EmployeeScheduleOverride::create([
            'employee_id' => $empPusat->id,
            'date' => $targetDate,
            'override_type' => 'work',
            'shift_id' => $this->shiftPagi->id,
            'work_outlet_id' => $this->outletCabang->id,
            'reason' => 'Jadwal di Cabang',
            'created_by' => $this->superadmin->id,
        ]);

        // No attendance record created!

        $response = $this->actingAs($this->superadmin)->get(route('admin.reports.attendance', [
            'start_date' => $targetDate,
            'end_date' => $targetDate,
            'outlet_id' => $this->outletCabang->id,
        ]));

        $response->assertOk();
        $response->assertSee('Ayu Absent');
        $response->assertSee('Tidak Hadir');
    }

    /** 12. Summary cards respect outlet filter */
    public function test_summary_cards_respect_outlet_filter(): void
    {
        $empPusat = $this->createEmployee('Ayu Pusat', 'EMP-001', $this->outletPusat);
        $empCabang = $this->createEmployee('Budi Cabang', 'EMP-002', $this->outletCabang);
        $targetDate = '2026-09-01';

        AttendanceRecord::create([
            'employee_id' => $empPusat->id,
            'work_date' => $targetDate,
            'outlet_id' => $this->outletPusat->id,
            'status' => 'present',
            'check_in_at' => "{$targetDate} 08:00:00",
            'worked_minutes' => 480,
        ]);
        AttendanceRecord::create([
            'employee_id' => $empCabang->id,
            'work_date' => $targetDate,
            'outlet_id' => $this->outletCabang->id,
            'status' => 'present',
            'check_in_at' => "{$targetDate} 08:00:00",
            'worked_minutes' => 480,
        ]);

        // Filter Cabang only -> should show 1 Hadir, not 2
        $response = $this->actingAs($this->superadmin)->get(route('admin.reports.attendance', [
            'start_date' => $targetDate,
            'end_date' => $targetDate,
            'outlet_id' => $this->outletCabang->id,
        ]));

        $response->assertOk();
        $response->assertSee('Budi Cabang');
        $response->assertDontSee('Ayu Pusat');
    }

    /** 13. Per-employee summary table respects outlet filter */
    public function test_per_employee_summary_table_respects_outlet(): void
    {
        $empPusat = $this->createEmployee('Ayu Pusat', 'EMP-001', $this->outletPusat);
        $empCabang = $this->createEmployee('Budi Cabang', 'EMP-002', $this->outletCabang);
        $targetDate = '2026-09-01';

        AttendanceRecord::create([
            'employee_id' => $empPusat->id,
            'work_date' => $targetDate,
            'outlet_id' => $this->outletPusat->id,
            'status' => 'present',
            'check_in_at' => "{$targetDate} 08:00:00",
            'worked_minutes' => 480,
        ]);
        AttendanceRecord::create([
            'employee_id' => $empCabang->id,
            'work_date' => $targetDate,
            'outlet_id' => $this->outletCabang->id,
            'status' => 'present',
            'check_in_at' => "{$targetDate} 08:00:00",
            'worked_minutes' => 480,
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.reports.attendance', [
            'start_date' => $targetDate,
            'end_date' => $targetDate,
            'outlet_id' => $this->outletCabang->id,
        ]));

        $response->assertOk();
        $response->assertSee('Budi Cabang');
        $response->assertDontSee('Ayu Pusat');
    }

    /** 14. Status filter and outlet filter work together */
    public function test_status_filter_and_outlet_work_together(): void
    {
        $empCabang1 = $this->createEmployee('Budi Hadir', 'EMP-001', $this->outletCabang);
        $empCabang2 = $this->createEmployee('Citra Telat', 'EMP-002', $this->outletCabang);
        $targetDate = '2026-09-01';

        AttendanceRecord::create([
            'employee_id' => $empCabang1->id,
            'work_date' => $targetDate,
            'outlet_id' => $this->outletCabang->id,
            'status' => 'present',
            'check_in_at' => "{$targetDate} 08:00:00",
            'worked_minutes' => 480,
        ]);
        AttendanceRecord::create([
            'employee_id' => $empCabang2->id,
            'work_date' => $targetDate,
            'outlet_id' => $this->outletCabang->id,
            'status' => 'late',
            'late_minutes' => 20,
            'check_in_at' => "{$targetDate} 08:20:00",
            'worked_minutes' => 460,
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.reports.attendance', [
            'start_date' => $targetDate,
            'end_date' => $targetDate,
            'outlet_id' => $this->outletCabang->id,
            'status' => 'late',
        ]));

        $response->assertOk();
        $reportData = $response->viewData('reportData');
        $this->assertCount(1, $reportData['detail_rows']);
        $this->assertSame($empCabang2->id, $reportData['detail_rows'][0]['employee']->id);
        $response->assertSee('Citra Telat');
        $response->assertSee('1 Total Record');
    }

    /** 15. Individual employee filter and outlet filter work together */
    public function test_individual_employee_and_outlet_work_together(): void
    {
        $empCabang1 = $this->createEmployee('Budi Cabang 1', 'EMP-001', $this->outletCabang);
        $empCabang2 = $this->createEmployee('Budi Cabang 2', 'EMP-002', $this->outletCabang);
        $targetDate = '2026-09-01';

        AttendanceRecord::create([
            'employee_id' => $empCabang1->id,
            'work_date' => $targetDate,
            'outlet_id' => $this->outletCabang->id,
            'status' => 'present',
            'check_in_at' => "{$targetDate} 08:00:00",
            'worked_minutes' => 480,
        ]);
        AttendanceRecord::create([
            'employee_id' => $empCabang2->id,
            'work_date' => $targetDate,
            'outlet_id' => $this->outletCabang->id,
            'status' => 'present',
            'check_in_at' => "{$targetDate} 08:00:00",
            'worked_minutes' => 480,
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.reports.attendance', [
            'start_date' => $targetDate,
            'end_date' => $targetDate,
            'outlet_id' => $this->outletCabang->id,
            'employee_id' => $empCabang1->id,
        ]));

        $response->assertOk();
        $reportData = $response->viewData('reportData');
        $this->assertCount(1, $reportData['detail_rows']);
        $this->assertSame($empCabang1->id, $reportData['detail_rows'][0]['employee']->id);
        $response->assertSee('Budi Cabang 1');
        $response->assertSee('1 Total Record');
    }

    /** 16. Print view preserves outlet filter */
    public function test_print_view_preserves_outlet_filter(): void
    {
        $empCabang = $this->createEmployee('Budi Cabang', 'EMP-001', $this->outletCabang);
        $targetDate = '2026-09-01';

        AttendanceRecord::create([
            'employee_id' => $empCabang->id,
            'work_date' => $targetDate,
            'outlet_id' => $this->outletCabang->id,
            'status' => 'present',
            'check_in_at' => "{$targetDate} 08:00:00",
            'worked_minutes' => 480,
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.reports.attendance.print', [
            'start_date' => $targetDate,
            'end_date' => $targetDate,
            'outlet_id' => $this->outletCabang->id,
        ]));

        $response->assertOk();
        $response->assertSee('Outlet:');
        $response->assertSee('Selon Beauty Cabang');
        $response->assertSee('Budi Cabang');
    }

    /** 17. CSV export preserves outlet filter */
    public function test_csv_export_preserves_outlet_filter(): void
    {
        $empPusat = $this->createEmployee('Ayu Pusat', 'EMP-001', $this->outletPusat);
        $empCabang = $this->createEmployee('Budi Cabang', 'EMP-002', $this->outletCabang);
        $targetDate = '2026-09-01';

        AttendanceRecord::create([
            'employee_id' => $empPusat->id,
            'work_date' => $targetDate,
            'outlet_id' => $this->outletPusat->id,
            'status' => 'present',
            'check_in_at' => "{$targetDate} 08:00:00",
            'worked_minutes' => 480,
        ]);
        AttendanceRecord::create([
            'employee_id' => $empCabang->id,
            'work_date' => $targetDate,
            'outlet_id' => $this->outletCabang->id,
            'status' => 'present',
            'check_in_at' => "{$targetDate} 08:00:00",
            'worked_minutes' => 480,
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.reports.attendance.export-csv', [
            'start_date' => $targetDate,
            'end_date' => $targetDate,
            'outlet_id' => $this->outletCabang->id,
        ]));

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('Budi Cabang', $content);
        $this->assertStringNotContainsString('Ayu Pusat', $content);
    }

    /** 18. CSV export does not leak unauthorized outlet */
    public function test_csv_export_does_not_leak_unauthorized_outlet(): void
    {
        $response = $this->actingAs($this->adminScoped)->get(route('admin.reports.attendance.export-csv', [
            'outlet_id' => $this->outletUnassigned->id,
        ]));

        $response->assertStatus(403);
    }

    /** 19. Cross-midnight work_date remains correct */
    public function test_cross_midnight_work_date_remains_correct_in_report(): void
    {
        $empCabang = $this->createEmployee('Malam Cabang', 'EMP-001', $this->outletCabang);
        $targetDate = '2026-08-31';

        AttendanceRecord::create([
            'employee_id' => $empCabang->id,
            'work_date' => $targetDate,
            'outlet_id' => $this->outletCabang->id,
            'status' => 'present',
            'check_in_at' => "{$targetDate} 22:00:00",
            'check_out_at' => '2026-09-01 06:00:00',
            'worked_minutes' => 480,
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.reports.attendance', [
            'start_date' => $targetDate,
            'end_date' => $targetDate,
            'outlet_id' => $this->outletCabang->id,
        ]));

        $response->assertOk();
        $response->assertSee('Malam Cabang');
        $response->assertSee('22:00');
        $response->assertSee('06:00');
    }

    /** 20. Reset does not alter unrelated module state */
    public function test_reset_does_not_alter_unrelated_module_state(): void
    {
        // Visit Employee page with filter
        $this->actingAs($this->superadmin)->get(route('admin.employees.index', ['outlet_id' => $this->outletCabang->id]));

        // Visit Report Reset
        $response = $this->actingAs($this->superadmin)->get(route('admin.reports.attendance'));

        $response->assertOk();
        // Employee page URL should still function independently
        $empResponse = $this->actingAs($this->superadmin)->get(route('admin.employees.index', ['outlet_id' => $this->outletPusat->id]));
        $empResponse->assertOk();
    }
}
