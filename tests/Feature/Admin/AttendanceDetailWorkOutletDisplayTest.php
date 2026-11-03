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
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AttendanceDetailWorkOutletDisplayTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $adminOutletA;
    protected User $adminOutletB;
    protected Outlet $outletA;
    protected Outlet $outletB;
    protected Outlet $outletC;
    protected AttendanceLocation $legacyLocation;
    protected Shift $shift;
    protected JobTitle $jobTitle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outletA = Outlet::create([
            'name' => 'Outlet Pusat Sudirman',
            'code' => 'OPS',
            'address' => 'Jl. Jend. Sudirman No. 1',
            'latitude' => -6.208800,
            'longitude' => 106.845600,
            'radius_meters' => 100,
            'max_accuracy_meters' => 50,
            'is_active' => true,
        ]);

        $this->outletB = Outlet::create([
            'name' => 'Outlet Cabang Melawai',
            'code' => 'OCM',
            'address' => 'Jl. Melawai Raya No. 45',
            'latitude' => -6.244300,
            'longitude' => 106.798100,
            'radius_meters' => 100,
            'max_accuracy_meters' => 50,
            'is_active' => true,
        ]);

        $this->outletC = Outlet::create([
            'name' => 'Outlet Cabang Bandung',
            'code' => 'OCB',
            'address' => 'Jl. Riau No. 10',
            'latitude' => -6.917500,
            'longitude' => 107.619100,
            'radius_meters' => 100,
            'max_accuracy_meters' => 50,
            'is_active' => true,
        ]);

        $this->legacyLocation = AttendanceLocation::create([
            'name' => 'SELON BEAUTY LEGACY',
            'address' => 'Jl. Legacy No. 0',
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'radius_meters' => 100,
            'max_accuracy_meters' => 50,
            'is_active' => true,
        ]);

        $this->jobTitle = JobTitle::create([
            'name' => 'Hair Stylist',
            'is_active' => true,
        ]);

        $this->shift = Shift::create([
            'name' => 'Shift Reguler',
            'code' => 'REG',
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'is_active' => true,
        ]);

        $this->owner = User::create([
            'name' => 'Owner System',
            'email' => 'owner@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->adminOutletA = User::create([
            'name' => 'Admin Sudirman',
            'email' => 'admin.a@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'outlet_id' => $this->outletA->id,
            'outlet_access_mode' => 'selected',
            'is_active' => true,
        ]);
        $this->adminOutletA->assignedOutlets()->sync([$this->outletA->id]);

        $this->adminOutletB = User::create([
            'name' => 'Admin Melawai',
            'email' => 'admin.b@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'outlet_id' => $this->outletB->id,
            'outlet_access_mode' => 'selected',
            'is_active' => true,
        ]);
        $this->adminOutletB->assignedOutlets()->sync([$this->outletB->id]);
    }

    public function test_work_outlet_displayed_from_snapshot_outlet_id(): void
    {
        $employee = Employee::create([
            'employee_code' => 'EMP-001',
            'full_name' => 'Rina Nose',
            'outlet_id' => $this->outletA->id,
            'job_title_id' => $this->jobTitle->id,
            'status' => 'active',
            'attendance_enabled' => true,
        ]);

        $record = AttendanceRecord::create([
            'employee_id' => $employee->id,
            'work_date' => '2026-08-20',
            'outlet_id' => $this->outletB->id,
            'attendance_location_id' => $this->legacyLocation->id,
            'status' => 'present',
            'check_in_at' => '2026-08-20 09:02:00',
            'check_in_latitude' => -6.244310,
            'check_in_longitude' => 106.798120,
            'check_in_distance_meters' => 12.5,
            'check_in_accuracy_meters' => 6.0,
        ]);

        $response = $this->actingAs($this->owner)->getJson(route('admin.attendance.show', $record));

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('work_outlet.name', 'Outlet Cabang Melawai');
        $response->assertJsonPath('work_outlet_name', 'Outlet Cabang Melawai');
        $response->assertJsonPath('data.outlet.name', 'Outlet Cabang Melawai');
        $this->assertEquals('Outlet Cabang Melawai', $record->getWorkOutletName());

        // Check index blade rendering contains the work outlet name
        $indexResponse = $this->actingAs($this->owner)->get(route('admin.attendance.index', ['date' => '2026-08-20']));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Outlet Cabang Melawai');
    }

    public function test_different_home_and_work_outlet_shows_work_outlet_and_temporary_assignment_notice(): void
    {
        $employee = Employee::create([
            'employee_code' => 'EMP-002',
            'full_name' => 'Dewi Persik',
            'outlet_id' => $this->outletA->id,
            'job_title_id' => $this->jobTitle->id,
            'status' => 'active',
            'attendance_enabled' => true,
        ]);

        $record = AttendanceRecord::create([
            'employee_id' => $employee->id,
            'work_date' => '2026-08-21',
            'outlet_id' => $this->outletB->id,
            'status' => 'present',
            'check_in_at' => '2026-08-21 09:00:00',
            'check_in_distance_meters' => 15.0,
            'check_in_accuracy_meters' => 8.0,
        ]);

        $response = $this->actingAs($this->owner)->getJson(route('admin.attendance.show', $record));

        $response->assertOk();
        $response->assertJsonPath('is_temporary_assignment', true);
        $response->assertJsonPath('work_outlet.name', 'Outlet Cabang Melawai');
        $response->assertJsonPath('historical_home_outlet.name', 'Outlet Pusat Sudirman');

        $indexResponse = $this->actingAs($this->owner)->get(route('admin.attendance.index', ['date' => '2026-08-21']));
        $indexResponse->assertOk();
        $indexResponse->assertSee('PENUGASAN OUTLET');
        $indexResponse->assertSee('Home: Outlet Pusat Sudirman');
    }

    public function test_mutated_employee_preserves_historical_home_and_work_outlet(): void
    {
        // Employee originally at Outlet A
        $employee = Employee::create([
            'employee_code' => 'EMP-003',
            'full_name' => 'Siti Badriah',
            'outlet_id' => $this->outletC->id, // Currently at Outlet C after transfer
            'job_title_id' => $this->jobTitle->id,
            'status' => 'active',
            'attendance_enabled' => true,
        ]);

        // Transfer occurred on 2026-08-15 from Outlet A to Outlet C
        EmployeeOutletTransfer::create([
            'employee_id' => $employee->id,
            'from_outlet_id' => $this->outletA->id,
            'to_outlet_id' => $this->outletC->id,
            'effective_date' => '2026-08-15',
            'transferred_by_user_id' => $this->owner->id,
        ]);

        // Historical attendance on 2026-08-10 (before transfer) at WORK Outlet B
        $record = AttendanceRecord::create([
            'employee_id' => $employee->id,
            'work_date' => '2026-08-10',
            'outlet_id' => $this->outletB->id,
            'status' => 'present',
            'check_in_at' => '2026-08-10 09:00:00',
            'check_in_distance_meters' => 20.0,
            'check_in_accuracy_meters' => 5.0,
        ]);

        $response = $this->actingAs($this->owner)->getJson(route('admin.attendance.show', $record));

        $response->assertOk();
        // WORK Outlet remains Outlet B
        $response->assertJsonPath('work_outlet.name', 'Outlet Cabang Melawai');
        // Historical HOME Outlet on 2026-08-10 was Outlet A (NOT current Outlet C)
        $response->assertJsonPath('historical_home_outlet.name', 'Outlet Pusat Sudirman');
        $response->assertJsonPath('is_temporary_assignment', true);
    }

    public function test_legacy_record_without_outlet_id_falls_back_to_legacy_location(): void
    {
        $employee = Employee::create([
            'employee_code' => 'EMP-004',
            'full_name' => 'Legacy Worker',
            'outlet_id' => $this->outletA->id,
            'job_title_id' => $this->jobTitle->id,
            'status' => 'active',
            'attendance_enabled' => true,
        ]);

        // Legacy record without outlet_id, but with attendance_location_id
        $record = AttendanceRecord::create([
            'employee_id' => $employee->id,
            'work_date' => '2026-05-01',
            'outlet_id' => null,
            'attendance_location_id' => $this->legacyLocation->id,
            'status' => 'present',
            'check_in_at' => '2026-05-01 08:55:00',
            'check_in_distance_meters' => 14.0,
            'check_in_accuracy_meters' => 7.0,
        ]);

        $response = $this->actingAs($this->owner)->getJson(route('admin.attendance.show', $record));

        $response->assertOk();
        $response->assertJsonPath('work_outlet', null);
        $response->assertJsonPath('data.location.name', 'SELON BEAUTY LEGACY');
        $response->assertJsonPath('work_outlet_name', 'SELON BEAUTY LEGACY');
        $this->assertEquals('SELON BEAUTY LEGACY', $record->getWorkOutletName());

        $indexResponse = $this->actingAs($this->owner)->get(route('admin.attendance.index', ['date' => '2026-05-01']));
        $indexResponse->assertOk();
        $indexResponse->assertSee('SELON BEAUTY LEGACY');
    }

    public function test_record_with_missing_outlet_data_does_not_fallback_to_legacy_location(): void
    {
        $employee = Employee::create([
            'employee_code' => 'EMP-005',
            'full_name' => 'Orphan Outlet Worker',
            'outlet_id' => $this->outletA->id,
            'job_title_id' => $this->jobTitle->id,
            'status' => 'active',
            'attendance_enabled' => true,
        ]);

        // Record with outlet_id that does not exist in outlets table, plus legacy location
        $record = new AttendanceRecord([
            'employee_id' => $employee->id,
            'work_date' => '2026-08-22',
            'outlet_id' => 999999, // Non-existent outlet
            'attendance_location_id' => $this->legacyLocation->id,
            'status' => 'present',
            'check_in_at' => '2026-08-22 09:00:00',
            'check_in_distance_meters' => 10.0,
            'check_in_accuracy_meters' => 5.0,
        ]);
        $record->id = 999;
        $record->setRelation('employee', $employee);
        $record->setRelation('location', $this->legacyLocation);
        $record->setRelation('outlet', null);

        // Must NOT fallback to legacy location 'SELON BEAUTY LEGACY'
        $this->assertEquals('Outlet Tidak Ditemukan', $record->getWorkOutletName());

        $request = \Illuminate\Http\Request::create('/admin/attendance/999', 'GET', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);
        $request->setUserResolver(fn () => $this->owner);

        $controller = app(\App\Http\Controllers\Admin\AttendanceController::class);
        $response = $controller->show($request, $record);

        $data = $response->getData(true);
        $this->assertNull($data['work_outlet']);
        $this->assertEquals('Outlet Tidak Ditemukan', $data['work_outlet_name']);
        $this->assertNotEquals('SELON BEAUTY LEGACY', $data['work_outlet_name']);
    }

    public function test_outlet_name_with_special_characters_is_safe_from_xss(): void
    {
        $xssOutlet = Outlet::create([
            'name' => '<script>alert("xss")</script> & "Outlet"',
            'code' => 'XSS',
            'address' => 'Jl. Test No. 99',
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'radius_meters' => 100,
            'max_accuracy_meters' => 50,
            'is_active' => true,
        ]);

        $employee = Employee::create([
            'employee_code' => 'EMP-006',
            'full_name' => 'Secure Employee',
            'outlet_id' => $xssOutlet->id,
            'job_title_id' => $this->jobTitle->id,
            'status' => 'active',
            'attendance_enabled' => true,
        ]);

        $record = AttendanceRecord::create([
            'employee_id' => $employee->id,
            'work_date' => '2026-08-23',
            'outlet_id' => $xssOutlet->id,
            'status' => 'present',
            'check_in_at' => '2026-08-23 09:00:00',
            'check_in_distance_meters' => 25.0,
            'check_in_accuracy_meters' => 5.0,
        ]);

        $indexResponse = $this->actingAs($this->owner)->get(route('admin.attendance.index', ['date' => '2026-08-23']));
        $indexResponse->assertOk();
        // Raw script tag must not be rendered without HTML entities
        $indexResponse->assertDontSee('<script>alert("xss")</script>', false);
        $indexResponse->assertSee(e('<script>alert("xss")</script> & "Outlet"'), false);

        $jsonResponse = $this->actingAs($this->owner)->getJson(route('admin.attendance.show', $record));
        $jsonResponse->assertOk();
        $jsonResponse->assertJsonPath('work_outlet_name', '<script>alert("xss")</script> & "Outlet"');
    }

    public function test_gps_distance_and_accuracy_preserved_without_recalculation(): void
    {
        $employee = Employee::create([
            'employee_code' => 'EMP-007',
            'full_name' => 'Distance Preserved',
            'outlet_id' => $this->outletA->id,
            'job_title_id' => $this->jobTitle->id,
            'status' => 'active',
            'attendance_enabled' => true,
        ]);

        $record = AttendanceRecord::create([
            'employee_id' => $employee->id,
            'work_date' => '2026-08-24',
            'outlet_id' => $this->outletA->id,
            'status' => 'present',
            'check_in_at' => '2026-08-24 09:00:00',
            'check_in_latitude' => -6.208800,
            'check_in_longitude' => 106.845600,
            'check_in_distance_meters' => 47.85,
            'check_in_accuracy_meters' => 11.20,
            'check_out_at' => '2026-08-24 17:00:00',
            'check_out_latitude' => -6.208810,
            'check_out_longitude' => 106.845610,
            'check_out_distance_meters' => 52.30,
            'check_out_accuracy_meters' => 9.50,
        ]);

        // Alter outlet coordinates completely to verify distance is NOT recalculated
        $this->outletA->update([
            'latitude' => 0.000000,
            'longitude' => 0.000000,
        ]);

        $response = $this->actingAs($this->owner)->getJson(route('admin.attendance.show', $record));

        $response->assertOk();
        $this->assertEquals(47.85, (float) $response->json('data.check_in_distance_meters'));
        $this->assertEquals(11.20, (float) $response->json('data.check_in_accuracy_meters'));
        $this->assertEquals(52.30, (float) $response->json('data.check_out_distance_meters'));
        $this->assertEquals(9.50, (float) $response->json('data.check_out_accuracy_meters'));
    }

    public function test_no_cross_outlet_data_leakage(): void
    {
        $employeeA = Employee::create([
            'employee_code' => 'EMP-008',
            'full_name' => 'Employee Outlet A',
            'outlet_id' => $this->outletA->id,
            'job_title_id' => $this->jobTitle->id,
            'status' => 'active',
            'attendance_enabled' => true,
        ]);

        $recordA = AttendanceRecord::create([
            'employee_id' => $employeeA->id,
            'work_date' => '2026-08-25',
            'outlet_id' => $this->outletA->id,
            'status' => 'present',
            'check_in_at' => '2026-08-25 09:00:00',
        ]);

        // Admin of Outlet A can access recordA
        $responseA = $this->actingAs($this->adminOutletA)->getJson(route('admin.attendance.show', $recordA));
        $responseA->assertOk();

        // Admin of Outlet B CANNOT access recordA (Forbidden)
        $responseB = $this->actingAs($this->adminOutletB)->getJson(route('admin.attendance.show', $recordA));
        $responseB->assertForbidden();
    }
}
