<?php

namespace Tests\Feature\Report;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\Shift;
use App\Models\User;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AttendanceReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $ownerUser;
    protected User $adminUser;
    protected User $employeeUser1;
    protected Employee $employee1;
    protected User $employeeUser2;
    protected Employee $employee2;
    protected Shift $shiftNormal;
    protected Shift $shiftCrossMidnight;
    protected ReportService $reportService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reportService = app(ReportService::class);

        $this->ownerUser = User::create([
            'name' => 'Owner Utama',
            'email' => 'owner@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->adminUser = User::create([
            'name' => 'Admin Toko',
            'email' => 'admin@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->employee1 = Employee::create([
            'employee_code' => 'SB-001',
            'full_name' => 'Ayu Pratama',
            'status' => 'active',
        ]);
        $this->employeeUser1 = User::create([
            'employee_id' => $this->employee1->id,
            'name' => 'Ayu Pratama',
            'email' => 'ayu@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        $this->employee2 = Employee::create([
            'employee_code' => 'SB-002',
            'full_name' => 'Budi Santoso',
            'status' => 'active',
        ]);
        $this->employeeUser2 = User::create([
            'employee_id' => $this->employee2->id,
            'name' => 'Budi Santoso',
            'email' => 'budi@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        $this->shiftNormal = Shift::create([
            'name' => 'Shift Pagi',
            'code' => 'PAGI',
            'start_time' => '08:00',
            'end_time' => '16:00',
            'is_active' => true,
        ]);

        $this->shiftCrossMidnight = Shift::create([
            'name' => 'Shift Malam',
            'code' => 'MALAM',
            'start_time' => '20:00',
            'end_time' => '06:00',
            'crosses_midnight' => true,
            'is_active' => true,
        ]);
    }

    public function test_report_calculates_present_correctly(): void
    {
        $date = '2026-08-10';
        $sch = EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $date,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        AttendanceRecord::create([
            'employee_id' => $this->employee1->id,
            'work_schedule_id' => $sch->id,
            'work_date' => $date,
            'status' => 'present',
            'check_in_at' => "{$date} 07:55:00",
            'check_out_at' => "{$date} 16:05:00",
            'late_minutes' => 0,
            'worked_minutes' => 490,
        ]);

        $report = $this->reportService->generateAttendanceReport([
            'start_date' => $date,
            'end_date' => $date,
            'employee_id' => $this->employee1->id,
        ]);

        $this->assertEquals(1, $report['global_summary']['present_count']);
        $this->assertEquals(0, $report['global_summary']['late_count']);
        $this->assertEquals(0, $report['global_summary']['absent_count']);
    }

    public function test_report_calculates_late_correctly(): void
    {
        $date = '2026-08-10';
        $sch = EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $date,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        AttendanceRecord::create([
            'employee_id' => $this->employee1->id,
            'work_schedule_id' => $sch->id,
            'work_date' => $date,
            'status' => 'late',
            'check_in_at' => "{$date} 08:25:00",
            'check_out_at' => "{$date} 16:00:00",
            'late_minutes' => 25,
            'worked_minutes' => 455,
        ]);

        $report = $this->reportService->generateAttendanceReport([
            'start_date' => $date,
            'end_date' => $date,
            'employee_id' => $this->employee1->id,
        ]);

        $this->assertEquals(1, $report['global_summary']['present_count']);
        $this->assertEquals(1, $report['global_summary']['late_count']);
        $this->assertEquals(25, $report['global_summary']['total_late_minutes']);
    }

    public function test_report_calculates_absent_correctly(): void
    {
        $pastDate = '2026-08-01'; // Past work day
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $pastDate,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        // No attendance and no leave record
        $report = $this->reportService->generateAttendanceReport([
            'start_date' => $pastDate,
            'end_date' => $pastDate,
            'employee_id' => $this->employee1->id,
        ]);

        $this->assertEquals(1, $report['global_summary']['absent_count']);
        $this->assertEquals(0, $report['global_summary']['present_count']);
    }

    public function test_off_employee_not_counted_absent(): void
    {
        $date = '2026-08-10';
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $date,
            'schedule_type' => 'off',
        ]);

        $report = $this->reportService->generateAttendanceReport([
            'start_date' => $date,
            'end_date' => $date,
            'employee_id' => $this->employee1->id,
        ]);

        $this->assertEquals(0, $report['global_summary']['absent_count']);
        $this->assertEquals(0, $report['global_summary']['scheduled_work_days']);
    }

    public function test_holiday_not_counted_absent(): void
    {
        $date = '2026-08-10';
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $date,
            'schedule_type' => 'holiday',
        ]);

        $report = $this->reportService->generateAttendanceReport([
            'start_date' => $date,
            'end_date' => $date,
            'employee_id' => $this->employee1->id,
        ]);

        $this->assertEquals(0, $report['global_summary']['absent_count']);
        $this->assertEquals(0, $report['global_summary']['scheduled_work_days']);
    }

    public function test_unscheduled_employee_not_counted_absent(): void
    {
        $date = '2026-08-10';
        // No schedule created for employee 1

        $report = $this->reportService->generateAttendanceReport([
            'start_date' => $date,
            'end_date' => $date,
            'employee_id' => $this->employee1->id,
        ]);

        $this->assertEquals(0, $report['global_summary']['absent_count']);
        $this->assertEquals(0, $report['global_summary']['scheduled_work_days']);
    }

    public function test_approved_permission_counted_correctly(): void
    {
        $date = '2026-08-10';
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $date,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        LeaveRequest::create([
            'employee_id' => $this->employee1->id,
            'type' => 'permission',
            'start_date' => $date,
            'end_date' => $date,
            'reason' => 'Urusan keluarga',
            'status' => 'approved',
            'reviewed_by' => $this->ownerUser->id,
        ]);

        $report = $this->reportService->generateAttendanceReport([
            'start_date' => $date,
            'end_date' => $date,
            'employee_id' => $this->employee1->id,
        ]);

        $this->assertEquals(1, $report['global_summary']['permission_count']);
        $this->assertEquals(0, $report['global_summary']['absent_count']);
    }

    public function test_approved_sick_counted_correctly(): void
    {
        $date = '2026-08-10';
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $date,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        LeaveRequest::create([
            'employee_id' => $this->employee1->id,
            'type' => 'sick',
            'start_date' => $date,
            'end_date' => $date,
            'reason' => 'Demam tinggi',
            'status' => 'approved',
            'reviewed_by' => $this->ownerUser->id,
        ]);

        $report = $this->reportService->generateAttendanceReport([
            'start_date' => $date,
            'end_date' => $date,
            'employee_id' => $this->employee1->id,
        ]);

        $this->assertEquals(1, $report['global_summary']['sick_count']);
        $this->assertEquals(0, $report['global_summary']['absent_count']);
    }

    public function test_approved_leave_counted_correctly(): void
    {
        $date = '2026-08-10';
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $date,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        LeaveRequest::create([
            'employee_id' => $this->employee1->id,
            'type' => 'leave',
            'start_date' => $date,
            'end_date' => $date,
            'reason' => 'Cuti tahunan',
            'status' => 'approved',
            'reviewed_by' => $this->ownerUser->id,
        ]);

        $report = $this->reportService->generateAttendanceReport([
            'start_date' => $date,
            'end_date' => $date,
            'employee_id' => $this->employee1->id,
        ]);

        $this->assertEquals(1, $report['global_summary']['leave_count']);
        $this->assertEquals(0, $report['global_summary']['absent_count']);
    }

    public function test_multi_day_leave_counted_only_on_working_days(): void
    {
        // 3 days period: Day 1 (WORK), Day 2 (OFF), Day 3 (WORK)
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => '2026-08-10',
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => '2026-08-11',
            'schedule_type' => 'off',
        ]);
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => '2026-08-12',
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        LeaveRequest::create([
            'employee_id' => $this->employee1->id,
            'type' => 'leave',
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-12',
            'reason' => 'Cuti 3 hari',
            'status' => 'approved',
            'reviewed_by' => $this->ownerUser->id,
        ]);

        $report = $this->reportService->generateAttendanceReport([
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-12',
            'employee_id' => $this->employee1->id,
        ]);

        // Only 2 scheduled WORK days should count as CUTI (Day 1 & Day 3). Day 2 (OFF) must remain OFF!
        $this->assertEquals(2, $report['global_summary']['leave_count']);
        $this->assertEquals(2, $report['global_summary']['scheduled_work_days']);
    }

    public function test_total_late_minutes_correct(): void
    {
        $d1 = '2026-08-10';
        $d2 = '2026-08-11';
        $sch1 = EmployeeSchedule::create(['employee_id' => $this->employee1->id, 'work_date' => $d1, 'shift_id' => $this->shiftNormal->id, 'schedule_type' => 'work']);
        $sch2 = EmployeeSchedule::create(['employee_id' => $this->employee1->id, 'work_date' => $d2, 'shift_id' => $this->shiftNormal->id, 'schedule_type' => 'work']);

        AttendanceRecord::create(['employee_id' => $this->employee1->id, 'work_schedule_id' => $sch1->id, 'work_date' => $d1, 'status' => 'late', 'late_minutes' => 15, 'worked_minutes' => 465]);
        AttendanceRecord::create(['employee_id' => $this->employee1->id, 'work_schedule_id' => $sch2->id, 'work_date' => $d2, 'status' => 'late', 'late_minutes' => 28, 'worked_minutes' => 452]);

        $report = $this->reportService->generateAttendanceReport([
            'start_date' => $d1,
            'end_date' => $d2,
            'employee_id' => $this->employee1->id,
        ]);

        $this->assertEquals(43, $report['global_summary']['total_late_minutes']);
        $this->assertEquals(2, $report['global_summary']['late_count']);
    }

    public function test_worked_minutes_total_correct(): void
    {
        $d1 = '2026-08-10';
        $d2 = '2026-08-11';
        $sch1 = EmployeeSchedule::create(['employee_id' => $this->employee1->id, 'work_date' => $d1, 'shift_id' => $this->shiftNormal->id, 'schedule_type' => 'work']);
        $sch2 = EmployeeSchedule::create(['employee_id' => $this->employee1->id, 'work_date' => $d2, 'shift_id' => $this->shiftNormal->id, 'schedule_type' => 'work']);

        AttendanceRecord::create(['employee_id' => $this->employee1->id, 'work_schedule_id' => $sch1->id, 'work_date' => $d1, 'status' => 'present', 'worked_minutes' => 480]);
        AttendanceRecord::create(['employee_id' => $this->employee1->id, 'work_schedule_id' => $sch2->id, 'work_date' => $d2, 'status' => 'present', 'worked_minutes' => 450]);

        $report = $this->reportService->generateAttendanceReport([
            'start_date' => $d1,
            'end_date' => $d2,
            'employee_id' => $this->employee1->id,
        ]);

        $this->assertEquals(930, $report['global_summary']['total_worked_minutes']);
    }

    public function test_early_leave_total_correct(): void
    {
        $d1 = '2026-08-10';
        $sch = EmployeeSchedule::create(['employee_id' => $this->employee1->id, 'work_date' => $d1, 'shift_id' => $this->shiftNormal->id, 'schedule_type' => 'work']);

        AttendanceRecord::create(['employee_id' => $this->employee1->id, 'work_schedule_id' => $sch->id, 'work_date' => $d1, 'status' => 'present', 'early_leave_minutes' => 15, 'worked_minutes' => 465]);

        $report = $this->reportService->generateAttendanceReport([
            'start_date' => $d1,
            'end_date' => $d1,
            'employee_id' => $this->employee1->id,
        ]);

        $this->assertEquals(15, $report['global_summary']['total_early_leave_minutes']);
    }

    public function test_approved_overtime_uses_approved_minutes(): void
    {
        $d1 = '2026-08-10';
        $sch = EmployeeSchedule::create(['employee_id' => $this->employee1->id, 'work_date' => $d1, 'shift_id' => $this->shiftNormal->id, 'schedule_type' => 'work']);

        AttendanceRecord::create(['employee_id' => $this->employee1->id, 'work_schedule_id' => $sch->id, 'work_date' => $d1, 'status' => 'present', 'overtime_minutes' => 90]);

        OvertimeRequest::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $d1,
            'requested_minutes' => 60,
            'approved_minutes' => 45,
            'reason' => 'Lembur disetujui 45m',
            'status' => 'approved',
            'reviewed_by' => $this->ownerUser->id,
        ]);

        $report = $this->reportService->generateAttendanceReport([
            'start_date' => $d1,
            'end_date' => $d1,
            'employee_id' => $this->employee1->id,
        ]);

        // Must report 45 approved minutes, NOT candidate 90 minutes!
        $this->assertEquals(45, $report['global_summary']['total_approved_overtime_minutes']);
        $this->assertEquals(45, $report['detail_rows'][0]['approved_overtime_minutes']);
    }

    public function test_overtime_candidate_not_treated_as_approved_overtime(): void
    {
        $d1 = '2026-08-10';
        $sch = EmployeeSchedule::create(['employee_id' => $this->employee1->id, 'work_date' => $d1, 'shift_id' => $this->shiftNormal->id, 'schedule_type' => 'work']);

        // Overtime candidate calculated by attendance = 60m, but NO approved overtime request exists!
        AttendanceRecord::create(['employee_id' => $this->employee1->id, 'work_schedule_id' => $sch->id, 'work_date' => $d1, 'status' => 'present', 'overtime_minutes' => 60]);

        $report = $this->reportService->generateAttendanceReport([
            'start_date' => $d1,
            'end_date' => $d1,
            'employee_id' => $this->employee1->id,
        ]);

        $this->assertEquals(0, $report['global_summary']['total_approved_overtime_minutes']);
        $this->assertEquals(0, $report['detail_rows'][0]['approved_overtime_minutes']);
    }

    public function test_cross_midnight_attendance_stays_on_original_work_date(): void
    {
        $d1 = '2026-08-11';
        $sch = EmployeeSchedule::create(['employee_id' => $this->employee1->id, 'work_date' => $d1, 'shift_id' => $this->shiftCrossMidnight->id, 'schedule_type' => 'work']);

        AttendanceRecord::create([
            'employee_id' => $this->employee1->id,
            'work_schedule_id' => $sch->id,
            'work_date' => $d1,
            'status' => 'present',
            'check_in_at' => "2026-08-11 19:55:00",
            'check_out_at' => "2026-08-12 06:15:00",
            'worked_minutes' => 620,
        ]);

        $report = $this->reportService->generateAttendanceReport([
            'start_date' => '2026-08-11',
            'end_date' => '2026-08-11',
            'employee_id' => $this->employee1->id,
        ]);

        $this->assertCount(1, $report['detail_rows']);
        $this->assertEquals('2026-08-11', $report['detail_rows'][0]['date_str']);
        $this->assertEquals(1, $report['global_summary']['present_count']);
    }

    public function test_employee_filter_works(): void
    {
        $d1 = '2026-08-10';
        EmployeeSchedule::create(['employee_id' => $this->employee1->id, 'work_date' => $d1, 'shift_id' => $this->shiftNormal->id, 'schedule_type' => 'work']);
        EmployeeSchedule::create(['employee_id' => $this->employee2->id, 'work_date' => $d1, 'shift_id' => $this->shiftNormal->id, 'schedule_type' => 'work']);

        $report = $this->reportService->generateAttendanceReport([
            'start_date' => $d1,
            'end_date' => $d1,
            'employee_id' => $this->employee1->id,
        ]);

        $this->assertCount(1, $report['detail_rows']);
        $this->assertEquals($this->employee1->id, $report['detail_rows'][0]['employee']->id);
    }

    public function test_date_range_filter_works(): void
    {
        $d1 = '2026-08-10';
        $d2 = '2026-08-15';
        EmployeeSchedule::create(['employee_id' => $this->employee1->id, 'work_date' => $d1, 'shift_id' => $this->shiftNormal->id, 'schedule_type' => 'work']);
        EmployeeSchedule::create(['employee_id' => $this->employee1->id, 'work_date' => $d2, 'shift_id' => $this->shiftNormal->id, 'schedule_type' => 'work']);

        $report = $this->reportService->generateAttendanceReport([
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-12',
            'employee_id' => $this->employee1->id,
        ]);

        // Date 2026-08-15 is outside range
        $dates = collect($report['detail_rows'])->pluck('date_str')->toArray();
        $this->assertContains('2026-08-10', $dates);
        $this->assertNotContains('2026-08-15', $dates);
    }

    public function test_status_filter_works(): void
    {
        $d1 = '2026-08-10';
        $d2 = '2026-08-11';
        $sch1 = EmployeeSchedule::create(['employee_id' => $this->employee1->id, 'work_date' => $d1, 'shift_id' => $this->shiftNormal->id, 'schedule_type' => 'work']);
        $sch2 = EmployeeSchedule::create(['employee_id' => $this->employee1->id, 'work_date' => $d2, 'shift_id' => $this->shiftNormal->id, 'schedule_type' => 'work']);

        AttendanceRecord::create(['employee_id' => $this->employee1->id, 'work_schedule_id' => $sch1->id, 'work_date' => $d1, 'status' => 'present', 'late_minutes' => 0, 'worked_minutes' => 480]);
        AttendanceRecord::create(['employee_id' => $this->employee1->id, 'work_schedule_id' => $sch2->id, 'work_date' => $d2, 'status' => 'late', 'late_minutes' => 20, 'worked_minutes' => 460]);

        $reportLateOnly = $this->reportService->generateAttendanceReport([
            'start_date' => $d1,
            'end_date' => $d2,
            'employee_id' => $this->employee1->id,
            'status' => 'late',
        ]);

        $this->assertCount(1, $reportLateOnly['detail_rows']);
        $this->assertEquals('late', $reportLateOnly['detail_rows'][0]['status']);
    }

    public function test_csv_export_respects_active_filters(): void
    {
        $d1 = '2026-08-10';
        $sch = EmployeeSchedule::create(['employee_id' => $this->employee1->id, 'work_date' => $d1, 'shift_id' => $this->shiftNormal->id, 'schedule_type' => 'work']);
        AttendanceRecord::create(['employee_id' => $this->employee1->id, 'work_schedule_id' => $sch->id, 'work_date' => $d1, 'status' => 'present', 'worked_minutes' => 480]);

        $response = $this->actingAs($this->ownerUser)->get(route('admin.reports.attendance.export-csv', [
            'start_date' => $d1,
            'end_date' => $d1,
            'employee_id' => $this->employee1->id,
        ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        
        $content = $response->streamedContent();
        $this->assertStringContainsString('Ayu Pratama', $content);
        $this->assertStringContainsString('SB-001', $content);
        $this->assertStringContainsString($d1, $content);
    }

    public function test_employee_cannot_access_admin_reports(): void
    {
        $response = $this->actingAs($this->employeeUser1)->get(route('admin.reports.attendance'));
        $response->assertRedirect(route('employee.dashboard'));
    }

    public function test_report_empty_state_handled_safely(): void
    {
        $response = $this->actingAs($this->ownerUser)->get(route('admin.reports.attendance', [
            'start_date' => '2029-01-01',
            'end_date' => '2029-01-31',
        ]));

        $response->assertOk();
        $response->assertSee('Belum ada data kehadiran pada periode yang dipilih.');
    }

    public function test_report_detail_row_date_is_carbon_instance_and_view_renders_without_error(): void
    {
        $d1 = '2026-08-10'; // A Monday
        $sch = EmployeeSchedule::create(['employee_id' => $this->employee1->id, 'work_date' => $d1, 'shift_id' => $this->shiftNormal->id, 'schedule_type' => 'work']);
        AttendanceRecord::create(['employee_id' => $this->employee1->id, 'work_schedule_id' => $sch->id, 'work_date' => $d1, 'status' => 'present', 'worked_minutes' => 480]);

        $report = $this->reportService->generateAttendanceReport([
            'start_date' => $d1,
            'end_date' => $d1,
            'employee_id' => $this->employee1->id,
        ]);

        $this->assertInstanceOf(\Carbon\CarbonInterface::class, $report['detail_rows'][0]['date']);

        $response = $this->actingAs($this->ownerUser)->get(route('admin.reports.attendance', [
            'start_date' => $d1,
            'end_date' => $d1,
            'employee_id' => $this->employee1->id,
        ]));

        $response->assertOk();
        $response->assertSee('10/08/2026');
        $response->assertSee('Senin');
    }

    public function test_attendance_report_view_handles_string_and_null_date_safely(): void
    {
        $reportData = [
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-10',
            'detail_rows' => [
                [
                    'employee' => $this->employee1,
                    'date' => '2026-08-10', // Raw string instead of Carbon
                    'date_str' => '2026-08-10',
                    'day_name' => 'Senin',
                    'schedule' => null,
                    'shift' => null,
                    'attendance' => null,
                    'leave_request' => null,
                    'overtime_request' => null,
                    'status' => 'absent',
                    'status_key' => 'absent',
                    'status_label' => 'Tidak Hadir',
                    'status_badge_class' => 'bg-rose-50 text-rose-700 border-rose-200',
                    'check_in_at' => null,
                    'check_out_at' => null,
                    'late_minutes' => 0,
                    'worked_minutes' => 0,
                    'early_leave_minutes' => 0,
                    'approved_overtime_minutes' => 0,
                ],
                [
                    'employee' => $this->employee1,
                    'date' => null, // Null date
                    'date_str' => '-',
                    'day_name' => '-',
                    'schedule' => null,
                    'shift' => null,
                    'attendance' => null,
                    'leave_request' => null,
                    'overtime_request' => null,
                    'status' => 'absent',
                    'status_key' => 'absent',
                    'status_label' => 'Tidak Hadir',
                    'status_badge_class' => 'bg-rose-50 text-rose-700 border-rose-200',
                    'check_in_at' => null,
                    'check_out_at' => null,
                    'late_minutes' => 0,
                    'worked_minutes' => 0,
                    'early_leave_minutes' => 0,
                    'approved_overtime_minutes' => 0,
                ],
            ],
            'employee_summaries' => [],
            'global_summary' => [
                'scheduled_work_days' => 1,
                'present_count' => 0,
                'late_count' => 0,
                'absent_count' => 2,
                'permission_count' => 0,
                'sick_count' => 0,
                'leave_count' => 0,
                'total_late_minutes' => 0,
                'total_worked_minutes' => 0,
                'total_early_leave_minutes' => 0,
                'total_approved_overtime_minutes' => 0,
            ],
            'filters' => [
                'start_date' => '2026-08-10',
                'end_date' => '2026-08-10',
                'employee_id' => null,
                'status' => 'all',
                'job_title_id' => null,
            ],
        ];

        $allRows = collect($reportData['detail_rows']);
        $paginatedRows = new \Illuminate\Pagination\LengthAwarePaginator(
            $allRows,
            $allRows->count(),
            25,
            1,
            ['path' => '/admin/reports/attendance']
        );

        $view = (string) $this->actingAs($this->ownerUser)->view('admin.reports.attendance', [
            'reportData' => $reportData,
            'employees' => [$this->employee1],
            'jobTitles' => [],
            'filters' => $reportData['filters'],
            'paginatedRows' => $paginatedRows,
        ]);

        $this->assertStringContainsString('10/08/2026', $view);
        $this->assertStringContainsString('Senin', $view);
    }
}
