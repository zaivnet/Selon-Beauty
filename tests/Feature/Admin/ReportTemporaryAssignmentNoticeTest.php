<?php

namespace Tests\Feature\Admin;

use App\Models\AttendanceLocation;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeOutletTransfer;
use App\Models\EmployeeSchedule;
use App\Models\JobTitle;
use App\Models\Outlet;
use App\Models\Shift;
use App\Models\User;
use App\Services\EmployeeTransferService;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTemporaryAssignmentNoticeTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;
    protected Outlet $pusatOutlet;
    protected Outlet $cabangOutlet;
    protected JobTitle $jobTitle;
    protected Shift $shift;
    protected AttendanceLocation $locationPusat;
    protected AttendanceLocation $locationCabang;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pusatOutlet = Outlet::create([
            'name' => 'Kopi Selon Pusat',
            'code' => 'KSP',
            'address' => 'Jl. Pusat No. 1',
            'latitude' => -7.250445,
            'longitude' => 112.768845,
            'radius_meters' => 100,
            'max_accuracy_meters' => 50,
            'is_active' => true,
        ]);

        $this->cabangOutlet = Outlet::create([
            'name' => 'Kopi Selon Cabang',
            'code' => 'KSC',
            'address' => 'Jl. Cabang No. 2',
            'latitude' => -7.260000,
            'longitude' => 112.770000,
            'radius_meters' => 100,
            'max_accuracy_meters' => 50,
            'is_active' => true,
        ]);

        $this->jobTitle = JobTitle::create([
            'name' => 'Barista',
            'is_active' => true,
        ]);

        $this->shift = Shift::create([
            'name' => 'Shift Pagi',
            'code' => 'SP',
            'start_time' => '08:00',
            'end_time' => '16:00',
            'crosses_midnight' => false,
            'is_active' => true,
        ]);

        $this->locationPusat = AttendanceLocation::create([
            'name' => 'Lokasi Pusat',
            'address' => 'Jl. Pusat No. 1',
            'latitude' => -7.250445,
            'longitude' => 112.768845,
            'radius_meters' => 100,
            'max_accuracy_meters' => 50,
            'is_active' => true,
        ]);

        $this->superadmin = User::create([
            'name' => 'Superadmin User',
            'email' => 'superadmin@example.com',
            'password' => bcrypt('password'),
            'role' => 'superadmin',
            'is_active' => true,
        ]);
    }

    public function test_normal_attendance_at_home_outlet_shows_no_penugasan_badge(): void
    {
        $employee = Employee::create([
            'employee_code' => 'SB-201',
            'full_name' => 'Budi Santoso',
            'outlet_id' => $this->pusatOutlet->id,
            'job_title_id' => $this->jobTitle->id,
            'status' => 'active',
            'attendance_enabled' => true,
        ]);

        EmployeeSchedule::create([
            'employee_id' => $employee->id,
            'work_date' => '2026-08-10',
            'shift_id' => $this->shift->id,
            'work_outlet_id' => $this->pusatOutlet->id,
            'schedule_type' => 'work',
        ]);

        AttendanceRecord::create([
            'employee_id' => $employee->id,
            'work_date' => '2026-08-10',
            'attendance_location_id' => $this->locationPusat->id,
            'outlet_id' => $this->pusatOutlet->id,
            'status' => 'present',
            'check_in_at' => '2026-08-10 08:00:00',
            'check_in_latitude' => -7.250445,
            'check_in_longitude' => 112.768845,
            'check_in_accuracy_meters' => 10,
            'check_in_distance_meters' => 5,
        ]);

        $reportService = app(ReportService::class);
        $reportData = $reportService->generateAttendanceReport([
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-10',
            'employee_id' => $employee->id,
        ]);

        $this->assertCount(1, $reportData['detail_rows']);
        $row = $reportData['detail_rows'][0];

        $this->assertFalse($row['is_temporary_assignment']);
        $this->assertEquals($this->pusatOutlet->id, $row['work_outlet']->id);
        $this->assertEquals($this->pusatOutlet->id, $row['historical_home_outlet']->id);
    }

    public function test_temporary_cross_outlet_assignment_shows_penugasan_outlet_notice(): void
    {
        $employee = Employee::create([
            'employee_code' => 'SB-202',
            'full_name' => 'Siti Rahma',
            'outlet_id' => $this->pusatOutlet->id,
            'job_title_id' => $this->jobTitle->id,
            'status' => 'active',
            'attendance_enabled' => true,
        ]);

        EmployeeSchedule::create([
            'employee_id' => $employee->id,
            'work_date' => '2026-08-12',
            'shift_id' => $this->shift->id,
            'work_outlet_id' => $this->cabangOutlet->id,
            'schedule_type' => 'work',
        ]);

        AttendanceRecord::create([
            'employee_id' => $employee->id,
            'work_date' => '2026-08-12',
            'attendance_location_id' => $this->locationCabang->id ?? $this->locationPusat->id,
            'outlet_id' => $this->cabangOutlet->id,
            'status' => 'present',
            'check_in_at' => '2026-08-12 08:00:00',
            'check_in_latitude' => -7.260000,
            'check_in_longitude' => 112.770000,
            'check_in_accuracy_meters' => 10,
            'check_in_distance_meters' => 5,
        ]);

        $reportService = app(ReportService::class);
        $reportData = $reportService->generateAttendanceReport([
            'start_date' => '2026-08-12',
            'end_date' => '2026-08-12',
            'employee_id' => $employee->id,
        ]);

        $this->assertCount(1, $reportData['detail_rows']);
        $row = $reportData['detail_rows'][0];

        $this->assertTrue($row['is_temporary_assignment']);
        $this->assertEquals($this->cabangOutlet->id, $row['work_outlet']->id);
        $this->assertEquals($this->pusatOutlet->id, $row['historical_home_outlet']->id);

        $empSummary = $reportData['employee_summaries'][0];
        $this->assertEquals(1, $empSummary['temporary_assignment_days']);
        $this->assertStringContainsString('Memiliki 1 hari penugasan di outlet lain', $empSummary['notice']);
    }

    public function test_employee_permanently_transferred_later_does_not_falsely_trigger_penugasan_notice_for_old_attendance(): void
    {
        $employee = Employee::create([
            'employee_code' => 'SB-203',
            'full_name' => 'Dewi Lestari',
            'outlet_id' => $this->cabangOutlet->id, // Transferred to Cabang on Aug 15
            'job_title_id' => $this->jobTitle->id,
            'status' => 'active',
            'attendance_enabled' => true,
        ]);

        // Record transfer history: on 2026-08-15 transferred from Pusat to Cabang
        EmployeeOutletTransfer::create([
            'employee_id' => $employee->id,
            'from_outlet_id' => $this->pusatOutlet->id,
            'to_outlet_id' => $this->cabangOutlet->id,
            'effective_date' => '2026-08-15',
            'notes' => 'Mutasi permanen ke Cabang',
            'transferred_by_user_id' => $this->superadmin->id,
        ]);

        // Attendance on 2026-08-10 (Before transfer) at Pusat (Which was her HOME Outlet at that time!)
        AttendanceRecord::create([
            'employee_id' => $employee->id,
            'work_date' => '2026-08-10',
            'attendance_location_id' => $this->locationPusat->id,
            'outlet_id' => $this->pusatOutlet->id,
            'status' => 'present',
            'check_in_at' => '2026-08-10 08:00:00',
            'check_in_latitude' => -7.250445,
            'check_in_longitude' => 112.768845,
            'check_in_accuracy_meters' => 10,
            'check_in_distance_meters' => 5,
        ]);

        $reportService = app(ReportService::class);
        $reportData = $reportService->generateAttendanceReport([
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-10',
            'employee_id' => $employee->id,
        ]);

        $this->assertCount(1, $reportData['detail_rows']);
        $row = $reportData['detail_rows'][0];

        // Must resolve historical HOME Outlet as Pusat on Aug 10, so is_temporary_assignment must be FALSE!
        $this->assertEquals($this->pusatOutlet->id, $row['historical_home_outlet']->id);
        $this->assertEquals($this->pusatOutlet->id, $row['work_outlet']->id);
        $this->assertFalse($row['is_temporary_assignment']);
    }

    public function test_csv_export_contains_home_outlet_and_outlet_kerja_and_assignment_notice(): void
    {
        $employee = Employee::create([
            'employee_code' => 'SB-204',
            'full_name' => 'Rian Hidayat',
            'outlet_id' => $this->pusatOutlet->id,
            'job_title_id' => $this->jobTitle->id,
            'status' => 'active',
            'attendance_enabled' => true,
        ]);

        AttendanceRecord::create([
            'employee_id' => $employee->id,
            'work_date' => '2026-08-14',
            'attendance_location_id' => $this->locationCabang->id ?? $this->locationPusat->id,
            'outlet_id' => $this->cabangOutlet->id,
            'status' => 'present',
            'check_in_at' => '2026-08-14 08:00:00',
            'check_in_latitude' => -7.260000,
            'check_in_longitude' => 112.770000,
            'check_in_accuracy_meters' => 10,
            'check_in_distance_meters' => 5,
        ]);

        $response = $this->actingAs($this->superadmin)
            ->get(route('admin.reports.attendance.export-csv', [
                'start_date' => '2026-08-14',
                'end_date' => '2026-08-14',
                'employee_id' => $employee->id,
            ]));

        $response->assertStatus(200);
        $content = $response->streamedContent();

        $this->assertStringContainsString('Home Outlet', $content);
        $this->assertStringContainsString('Outlet Kerja', $content);
        $this->assertStringContainsString('Outlet Assignment Notice', $content);
        $this->assertStringContainsString('Kopi Selon Pusat', $content);
        $this->assertStringContainsString('Kopi Selon Cabang', $content);
        $this->assertStringContainsString('PENUGASAN OUTLET', $content);
    }

    public function test_print_view_renders_outlet_kerja_and_penugasan_badge(): void
    {
        $employee = Employee::create([
            'employee_code' => 'SB-205',
            'full_name' => 'Agus Pratama',
            'outlet_id' => $this->pusatOutlet->id,
            'job_title_id' => $this->jobTitle->id,
            'status' => 'active',
            'attendance_enabled' => true,
        ]);

        AttendanceRecord::create([
            'employee_id' => $employee->id,
            'work_date' => '2026-08-18',
            'attendance_location_id' => $this->locationCabang->id ?? $this->locationPusat->id,
            'outlet_id' => $this->cabangOutlet->id,
            'status' => 'present',
            'check_in_at' => '2026-08-18 08:00:00',
            'check_in_latitude' => -7.260000,
            'check_in_longitude' => 112.770000,
            'check_in_accuracy_meters' => 10,
            'check_in_distance_meters' => 5,
        ]);

        $response = $this->actingAs($this->superadmin)
            ->get(route('admin.reports.attendance.print', [
                'start_date' => '2026-08-18',
                'end_date' => '2026-08-18',
                'employee_id' => $employee->id,
            ]));

        $response->assertStatus(200);
        $response->assertSee('Outlet Kerja');
        $response->assertSee('Kopi Selon Cabang');
        $response->assertSee('PENUGASAN OUTLET');
        $response->assertSee('Home: Kopi Selon Pusat');
    }
}
