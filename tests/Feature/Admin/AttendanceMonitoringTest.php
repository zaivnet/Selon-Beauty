<?php

namespace Tests\Feature\Admin;

use App\Models\AttendanceLocation;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Shift;
use App\Models\User;
use App\Services\AttendanceMonitoringService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttendanceMonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected User $ownerUser;
    protected User $adminUser;
    protected User $employeeUser1;
    protected Employee $employee1;
    protected User $employeeUser2;
    protected Employee $employee2;
    protected User $employeeUser3;
    protected Employee $employee3;
    protected Shift $shiftNormal;
    protected Shift $shiftNight;
    protected AttendanceLocation $location;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

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
            'full_name' => 'Ayu Lestari',
            'status' => 'active',
        ]);
        $this->employeeUser1 = User::create([
            'employee_id' => $this->employee1->id,
            'name' => 'Ayu Lestari',
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

        $this->employee3 = Employee::create([
            'employee_code' => 'SB-003',
            'full_name' => 'Citra Dewi',
            'status' => 'active',
        ]);
        $this->employeeUser3 = User::create([
            'employee_id' => $this->employee3->id,
            'name' => 'Citra Dewi',
            'email' => 'citra@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        $this->location = AttendanceLocation::create([
            'name' => 'SELON BEAUTY',
            'address' => 'Jl. Kebon Jeruk No. 12',
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'radius_meters' => 100,
            'max_accuracy_meters' => 100,
            'is_active' => true,
        ]);

        $this->shiftNormal = Shift::create([
            'name' => 'Shift Pagi',
            'code' => 'PAGI',
            'start_time' => '08:00',
            'end_time' => '16:00',
            'grace_period_minutes' => 5,
            'break_minutes' => 60,
            'is_active' => true,
        ]);

        $this->shiftNight = Shift::create([
            'name' => 'Shift Malam',
            'code' => 'NIGHT',
            'start_time' => '20:00',
            'end_time' => '04:00',
            'grace_period_minutes' => 5,
            'break_minutes' => 60,
            'crosses_midnight' => true,
            'is_active' => true,
        ]);
    }

    public function test_dashboard_total_active_employees_correct(): void
    {
        $service = app(AttendanceMonitoringService::class);
        $metrics = $service->getSummaryMetrics();

        $this->assertEquals(3, $metrics['total_employees']);
    }

    public function test_dashboard_present_count_correct(): void
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        // Employee 1 & 2 have schedules and checked in
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);
        AttendanceRecord::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'attendance_location_id' => $this->location->id,
            'status' => 'present',
            'check_in_at' => Carbon::now('Asia/Jakarta'),
            'check_in_latitude' => -6.200000,
            'check_in_longitude' => 106.816666,
            'check_in_accuracy_meters' => 10,
            'check_in_distance_meters' => 5,
        ]);

        EmployeeSchedule::create([
            'employee_id' => $this->employee2->id,
            'work_date' => $today,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);
        AttendanceRecord::create([
            'employee_id' => $this->employee2->id,
            'work_date' => $today,
            'attendance_location_id' => $this->location->id,
            'status' => 'late',
            'check_in_at' => Carbon::now('Asia/Jakarta'),
            'check_in_latitude' => -6.200000,
            'check_in_longitude' => 106.816666,
            'check_in_accuracy_meters' => 10,
            'check_in_distance_meters' => 5,
        ]);

        $service = app(AttendanceMonitoringService::class);
        $metrics = $service->getSummaryMetrics($today);

        $this->assertEquals(2, $metrics['present_today']);
    }

    public function test_dashboard_late_count_correct(): void
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);
        AttendanceRecord::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'attendance_location_id' => $this->location->id,
            'status' => 'late',
            'late_minutes' => 15,
            'check_in_at' => Carbon::now('Asia/Jakarta'),
        ]);

        $service = app(AttendanceMonitoringService::class);
        $metrics = $service->getSummaryMetrics($today);

        $this->assertEquals(1, $metrics['late_today']);
    }

    public function test_work_schedule_without_check_in_counted_as_not_checked_in(): void
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        $activeShift = Shift::create([
            'name' => 'Shift Active Pending Test',
            'code' => 'PND-TEST',
            'start_time' => Carbon::now('Asia/Jakarta')->format('H:i:s'),
            'end_time' => Carbon::now('Asia/Jakarta')->addHours(8)->format('H:i:s'),
            'check_in_open_minutes_before' => 30,
            'check_in_close_minutes_after' => 30,
            'is_active' => true,
        ]);

        // Employee 1 scheduled WORK but hasn't checked in (inside active window)
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => $activeShift->id,
            'schedule_type' => 'work',
        ]);

        $service = app(AttendanceMonitoringService::class);
        $metrics = $service->getSummaryMetrics($today);

        $this->assertEquals(1, $metrics['pending_check_in_today']);
    }

    public function test_off_employee_not_counted_as_absent_or_not_checked_in(): void
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        // Employee 1 scheduled OFF
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => null,
            'schedule_type' => 'off',
        ]);

        $service = app(AttendanceMonitoringService::class);
        $metrics = $service->getSummaryMetrics($today);

        $this->assertEquals(0, $metrics['pending_check_in_today']);
    }

    public function test_unscheduled_employee_not_counted_absent(): void
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();
        // No schedules created for today

        $service = app(AttendanceMonitoringService::class);
        $metrics = $service->getSummaryMetrics($today);

        $this->assertEquals(0, $metrics['pending_check_in_today']);
    }

    public function test_attendance_list_uses_database_data(): void
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        $outlet = \App\Models\Outlet::create([
            'name' => 'Outlet Test',
            'code' => 'OTL-01',
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'radius_meters' => 50,
            'address' => 'Jl. Kebon Jeruk No. 12',
            'is_active' => true,
        ]);
        $this->employee1->update(['outlet_id' => $outlet->id]);

        $activeShift = Shift::create([
            'name' => 'Shift Active Now',
            'code' => 'ACTIVE',
            'start_time' => Carbon::now('Asia/Jakarta')->format('H:i:s'),
            'end_time' => Carbon::now('Asia/Jakarta')->addHours(8)->format('H:i:s'),
            'check_in_open_minutes_before' => 30,
            'check_in_close_minutes_after' => 30,
            'is_active' => true,
        ]);

        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => $activeShift->id,
            'schedule_type' => 'work',
        ]);

        $response = $this->actingAs($this->ownerUser)->get('/admin/dashboard?outlet_id=' . $outlet->id);

        $response->assertOk();
        $response->assertSee('Ayu Lestari');
        $response->assertSee('BELUM CHECK-IN');
    }

    public function test_employee_attendance_detail_accessible_by_owner(): void
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        $record = AttendanceRecord::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'attendance_location_id' => $this->location->id,
            'status' => 'present',
            'check_in_at' => Carbon::now('Asia/Jakarta'),
            'check_in_latitude' => -6.200000,
            'check_in_longitude' => 106.816666,
            'check_in_accuracy_meters' => 8,
            'check_in_distance_meters' => 18,
            'check_in_selfie_path' => 'attendance/1/2026/08/fake.jpg',
        ]);

        $response = $this->actingAs($this->ownerUser)->get("/admin/attendance/{$record->id}", [
            'Accept' => 'application/json',
        ]);

        $response->assertOk();
        $this->assertEquals(18, (float) $response->json('data.check_in_distance_meters'));
        $this->assertEquals(8, (float) $response->json('data.check_in_accuracy_meters'));
    }

    public function test_employee_cannot_access_admin_attendance_monitoring(): void
    {
        $response1 = $this->actingAs($this->employeeUser1)->get('/admin/dashboard');
        $response1->assertRedirect(route('employee.dashboard'));

        $response2 = $this->actingAs($this->employeeUser1)->get('/admin/attendance');
        $response2->assertRedirect(route('employee.dashboard'));

        $jsonResponse = $this->actingAs($this->employeeUser1)->get('/admin/attendance', [
            'Accept' => 'application/json',
        ]);
        $jsonResponse->assertStatus(403);
    }

    public function test_attendance_selfie_requires_authorization(): void
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        $selfieFile = UploadedFile::fake()->image('selfie.jpg');
        $path = Storage::disk('local')->putFile('attendance/1/2026/08', $selfieFile);

        $record = AttendanceRecord::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'attendance_location_id' => $this->location->id,
            'status' => 'present',
            'check_in_at' => Carbon::now('Asia/Jakarta'),
            'check_in_selfie_path' => $path,
        ]);

        // Employee 2 attempting to view Employee 1's selfie returns 403
        $response = $this->actingAs($this->employeeUser2)->get("/attendance/selfie/{$record->id}/check_in");
        $response->assertStatus(403);

        // Owner viewing selfie returns 200
        $ownerResponse = $this->actingAs($this->ownerUser)->get("/attendance/selfie/{$record->id}/check_in");
        $ownerResponse->assertOk();
    }

    public function test_location_evidence_displayed_from_attendance_record(): void
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        $record = AttendanceRecord::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'attendance_location_id' => $this->location->id,
            'status' => 'present',
            'check_in_at' => Carbon::now('Asia/Jakarta'),
            'check_in_latitude' => -6.200000,
            'check_in_longitude' => 106.816666,
            'check_in_accuracy_meters' => 9,
            'check_in_distance_meters' => 18,
        ]);

        $response = $this->actingAs($this->ownerUser)->get("/admin/attendance/{$record->id}", [
            'Accept' => 'application/json',
        ]);

        $response->assertOk();
        $this->assertEquals(18, (float) $response->json('data.check_in_distance_meters'));
        $this->assertEquals(9, (float) $response->json('data.check_in_accuracy_meters'));
        $response->assertJsonPath('data.location.name', 'SELON BEAUTY');
    }

    public function test_cross_midnight_attendance_appears_under_correct_work_date(): void
    {
        $workDate = '2026-08-11';
        $sched = EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $workDate,
            'shift_id' => $this->shiftNight->id,
            'schedule_type' => 'work',
        ]);

        // Checked in at 20:00 on Aug 11, checked out at 04:00 on Aug 12
        $record = AttendanceRecord::create([
            'employee_id' => $this->employee1->id,
            'work_schedule_id' => $sched->id,
            'work_date' => $workDate,
            'attendance_location_id' => $this->location->id,
            'status' => 'present',
            'check_in_at' => Carbon::parse('2026-08-11 20:00:00', 'Asia/Jakarta'),
            'check_out_at' => Carbon::parse('2026-08-12 04:00:00', 'Asia/Jakarta'),
            'worked_minutes' => 420,
        ]);

        $service = app(AttendanceMonitoringService::class);
        $items = $service->getAttendanceMonitoringList(['date' => '2026-08-11']);

        $empItem = collect($items)->firstWhere('employee.id', $this->employee1->id);
        $this->assertNotNull($empItem);
        $this->assertEquals('2026-08-11', $empItem['record']->work_date->format('Y-m-d'));
        $this->assertEquals('HADIR', $empItem['status_label']);
    }

    public function test_filters_work_correctly(): void
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        $activeShift = Shift::create([
            'name' => 'Shift Active Filter',
            'code' => 'ACT-FLT',
            'start_time' => Carbon::now('Asia/Jakarta')->format('H:i:s'),
            'end_time' => Carbon::now('Asia/Jakarta')->addHours(8)->format('H:i:s'),
            'check_in_open_minutes_before' => 30,
            'check_in_close_minutes_after' => 30,
            'is_active' => true,
        ]);

        // Employee 1 Present
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => $activeShift->id,
            'schedule_type' => 'work',
        ]);
        AttendanceRecord::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'status' => 'present',
            'check_in_at' => Carbon::now('Asia/Jakarta'),
        ]);

        // Employee 2 Pending (inside check-in window)
        EmployeeSchedule::create([
            'employee_id' => $this->employee2->id,
            'work_date' => $today,
            'shift_id' => $activeShift->id,
            'schedule_type' => 'work',
        ]);

        $service = app(AttendanceMonitoringService::class);

        // Filter by status = pending
        $pendingItems = $service->getAttendanceMonitoringList([
            'date' => $today,
            'status' => 'pending',
        ]);
        $this->assertCount(1, $pendingItems);
        $this->assertEquals($this->employee2->id, $pendingItems[0]['employee']->id);

        // Filter by employee_id = employee1->id
        $emp1Items = $service->getAttendanceMonitoringList([
            'date' => $today,
            'employee_id' => $this->employee1->id,
        ]);
        $this->assertCount(1, $emp1Items);
        $this->assertEquals($this->employee1->id, $emp1Items[0]['employee']->id);
    }
}
