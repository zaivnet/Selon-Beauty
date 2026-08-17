<?php

namespace Tests\Feature\MultiOutlet;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Outlet;
use App\Models\Shift;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\GeofenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OutletSingleSourceOfTruthTest extends TestCase
{
    use RefreshDatabase;

    protected Outlet $outletA;
    protected Outlet $outletB;
    protected Shift $shiftPagi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outletA = Outlet::create([
            'name' => 'Selon Beauty Outlet Jakarta',
            'code' => 'JKT01',
            'latitude' => -6.2000000,
            'longitude' => 106.8166660,
            'radius_meters' => 50,
            'max_accuracy_meters' => 30,
            'is_active' => true,
        ]);

        $this->outletB = Outlet::create([
            'name' => 'Selon Beauty Outlet Bandung',
            'code' => 'BDG01',
            'latitude' => -6.9147440,
            'longitude' => 107.6098100,
            'radius_meters' => 200,
            'max_accuracy_meters' => 100,
            'is_active' => true,
        ]);

        $this->shiftPagi = Shift::create([
            'name' => 'Shift Pagi',
            'code' => 'PAGI',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'check_in_open_minutes_before' => 60,
            'check_in_close_minutes_after' => 120,
        ]);
    }

    /** 1. Employee Outlet A check-in using Outlet A coordinates and radius succeeds */
    public function test_employee_outlet_a_uses_outlet_a_geofence(): void
    {
        $empA = Employee::create([
            'employee_code' => 'EMP-SINGLE-A',
            'full_name' => 'Karyawan Outlet A',
            'email' => 'single.a@test.com',
            'status' => 'active',
            'attendance_enabled' => true,
            'outlet_id' => $this->outletA->id,
        ]);
        $userA = User::factory()->create(['employee_id' => $empA->id, 'role' => 'employee', 'is_active' => true]);

        $attendanceService = app(AttendanceService::class);
        $result = $attendanceService->validateGeofence(-6.2000100, 106.8166660, 15.0, $empA);

        $this->assertEquals('Selon Beauty Outlet Jakarta', $result['location']->name);
        $this->assertLessThanOrEqual(50, $result['distance']);
    }

    /** 2. Employee Outlet B check-in using Outlet B coordinates and radius succeeds */
    public function test_employee_outlet_b_uses_outlet_b_geofence(): void
    {
        $empB = Employee::create([
            'employee_code' => 'EMP-SINGLE-B',
            'full_name' => 'Karyawan Outlet B',
            'email' => 'single.b@test.com',
            'status' => 'active',
            'attendance_enabled' => true,
            'outlet_id' => $this->outletB->id,
        ]);

        $attendanceService = app(AttendanceService::class);
        $result = $attendanceService->validateGeofence(-6.9147500, 107.6098100, 50.0, $empB);

        $this->assertEquals('Selon Beauty Outlet Bandung', $result['location']->name);
    }

    /** 3. Employee Outlet A attempting check-in at Outlet B coordinates is rejected */
    public function test_employee_outlet_a_rejected_at_outlet_b_coordinates(): void
    {
        $empA = Employee::create([
            'employee_code' => 'EMP-CROSS-A',
            'full_name' => 'Karyawan Outlet A Cross',
            'email' => 'cross.a@test.com',
            'status' => 'active',
            'attendance_enabled' => true,
            'outlet_id' => $this->outletA->id,
        ]);

        $attendanceService = app(AttendanceService::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('di luar area absensi');

        // Testing Employee A at Outlet B (Bandung) coordinates
        $attendanceService->validateGeofence(-6.9147440, 107.6098100, 15.0, $empA);
    }

    /** 4 & 5. Independent radius and max GPS accuracy per outlet */
    public function test_outlets_have_independent_radius_and_accuracy(): void
    {
        $empA = Employee::create([
            'employee_code' => 'EMP-RAD-A',
            'full_name' => 'Karyawan Radius A',
            'status' => 'active',
            'attendance_enabled' => true,
            'outlet_id' => $this->outletA->id, // Radius 50m, Max Acc 30m
        ]);

        $empB = Employee::create([
            'employee_code' => 'EMP-RAD-B',
            'full_name' => 'Karyawan Radius B',
            'status' => 'active',
            'attendance_enabled' => true,
            'outlet_id' => $this->outletB->id, // Radius 200m, Max Acc 100m
        ]);

        $attendanceService = app(AttendanceService::class);

        // Accuracy 60m is REJECTED for EmpA (Outlet A max acc is 30m)
        try {
            $attendanceService->validateGeofence(-6.2000000, 106.8166660, 60.0, $empA);
            $this->fail('Expected InvalidArgumentException for Outlet A GPS accuracy exceeding limit');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('Akurasi lokasi belum cukup baik', $e->getMessage());
        }

        // Accuracy 60m is ACCEPTED for EmpB (Outlet B max acc is 100m)
        $resB = $attendanceService->validateGeofence(-6.9147440, 107.6098100, 60.0, $empB);
        $this->assertEquals('Selon Beauty Outlet Bandung', $resB['location']->name);
    }

    /** 6. Employee without outlet_id fails closed */
    public function test_employee_without_outlet_fails_closed(): void
    {
        $noOutletEmp = Employee::create([
            'employee_code' => 'EMP-NO-OUTLET',
            'full_name' => 'Karyawan Tanpa Outlet',
            'status' => 'active',
            'attendance_enabled' => true,
            'outlet_id' => null,
        ]);

        $attendanceService = app(AttendanceService::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Lokasi outlet Anda belum dikonfigurasi. Hubungi Admin/Owner.');

        $attendanceService->validateGeofence(-6.2, 106.8, 10.0, $noOutletEmp);
    }

    /** 7. Employee with inactive outlet fails closed */
    public function test_employee_with_inactive_outlet_fails_closed(): void
    {
        $inactiveOutlet = Outlet::create([
            'name' => 'Selon Outlet Nonaktif',
            'code' => 'OFF01',
            'latitude' => -6.2,
            'longitude' => 106.8,
            'radius_meters' => 100,
            'is_active' => false,
        ]);

        $empInactiveOutlet = Employee::create([
            'employee_code' => 'EMP-INACTIVE-OUTLET',
            'full_name' => 'Karyawan Outlet Nonaktif',
            'status' => 'active',
            'attendance_enabled' => true,
            'outlet_id' => $inactiveOutlet->id,
        ]);

        $attendanceService = app(AttendanceService::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Outlet tempat Anda bertugas saat ini sedang tidak aktif. Hubungi Admin/Owner.');

        $attendanceService->validateGeofence(-6.2, 106.8, 10.0, $empInactiveOutlet);
    }

    /** 8. Employee with invalid outlet coordinates fails closed */
    public function test_employee_with_invalid_outlet_coordinates_fails_closed(): void
    {
        $invalidCoordOutlet = Outlet::create([
            'name' => 'Selon Outlet Invalid GPS',
            'code' => 'INVGPS',
            'latitude' => 999.0, // Invalid lat
            'longitude' => 106.8,
            'radius_meters' => 100,
            'is_active' => true,
        ]);

        $empInvalidCoord = Employee::create([
            'employee_code' => 'EMP-INV-GPS',
            'full_name' => 'Karyawan GPS Invalid',
            'status' => 'active',
            'attendance_enabled' => true,
            'outlet_id' => $invalidCoordOutlet->id,
        ]);

        $attendanceService = app(AttendanceService::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Koordinat lokasi outlet Anda belum dikonfigurasi dengan benar.');

        $attendanceService->validateGeofence(-6.2, 106.8, 10.0, $empInvalidCoord);
    }

    /** 9. Admin scope isolation remains enforced */
    public function test_admin_scope_isolation_remains_enforced(): void
    {
        $adminA = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'outlet_id' => $this->outletA->id,
        ]);

        $empB = Employee::create([
            'employee_code' => 'EMP-ADMIN-ISOLATED',
            'full_name' => 'Karyawan Outlet B Isolated',
            'status' => 'active',
            'outlet_id' => $this->outletB->id,
        ]);

        $response = $this->actingAs($adminA)->get(route('admin.employees.show', $empB));
        $response->assertStatus(403);
    }

    /** 10. Attendance check-in / check-out flow succeeds using Outlet geofence */
    public function test_full_attendance_checkin_checkout_flow_with_outlet(): void
    {
        DB::table('app_settings')->updateOrInsert(
            ['key' => 'attendance_require_selfie'],
            ['value' => '0', 'type' => 'boolean', 'is_public' => true]
        );

        $emp = Employee::create([
            'employee_code' => 'EMP-FLOW-01',
            'full_name' => 'Karyawan Flow Full Test',
            'status' => 'active',
            'attendance_enabled' => true,
            'outlet_id' => $this->outletA->id,
        ]);
        $user = User::factory()->create(['employee_id' => $emp->id, 'role' => 'employee', 'is_active' => true]);

        $shiftFlex = Shift::create([
            'name' => 'Shift Flex',
            'code' => 'FLEX',
            'start_time' => '00:00:00',
            'end_time' => '23:59:00',
            'check_in_open_minutes_before' => 1440,
            'check_in_close_minutes_after' => 1440,
            'check_out_open_minutes_before' => 1440,
            'check_out_close_minutes_after' => 1440,
        ]);

        $schedule = EmployeeSchedule::create([
            'employee_id' => $emp->id,
            'work_date' => now('Asia/Jakarta')->toDateString(),
            'shift_id' => $shiftFlex->id,
            'schedule_type' => 'work',
        ]);

        $attendanceService = app(AttendanceService::class);

        // Perform Check In
        $record = $attendanceService->checkIn($user, [
            'latitude' => -6.2000050,
            'longitude' => 106.8166660,
            'accuracy' => 10.0,
            'notes' => 'Hadir tepat waktu',
        ]);

        $this->assertEquals($emp->id, $record->employee_id);
        $this->assertEquals($this->outletA->id, $record->outlet_id);
        $this->assertNotNull($record->check_in_at);

        // Perform Check Out
        $recordOut = $attendanceService->checkOut($user, [
            'latitude' => -6.2000050,
            'longitude' => 106.8166660,
            'accuracy' => 10.0,
        ]);
        $this->assertNotNull($recordOut->check_out_at);
    }

    /** 11. Global attendance settings (require_checkout_geofence, require_selfie, timezone) work */
    public function test_global_attendance_settings_function_properly(): void
    {
        $superadmin = User::factory()->create(['role' => 'superadmin', 'is_active' => true]);

        $response = $this->actingAs($superadmin)->post(route('admin.settings.attendance.update'), [
            'timezone' => 'Asia/Makassar',
            'require_checkout_geofence' => 0,
            'require_selfie' => 1,
        ]);

        $response->assertRedirect(route('admin.settings.attendance'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('app_settings', [
            'key' => 'timezone',
            'value' => 'Asia/Makassar',
        ]);
        $this->assertDatabaseHas('app_settings', [
            'key' => 'attendance_require_checkout_geofence',
            'value' => '0',
        ]);
    }

    /** 12. Comprehensive 12-point Employee Attendance location data flow isolation test */
    public function test_employee_dashboard_and_attendance_dataflow_isolation_for_two_outlets(): void
    {
        $empA = Employee::create([
            'employee_code' => 'EMP-DASH-A',
            'full_name' => 'Ade Zaiv',
            'status' => 'active',
            'attendance_enabled' => true,
            'outlet_id' => $this->outletA->id, // Selon Beauty (lat -6.2000000, lon 106.8166660, radius 50)
        ]);
        $userA = User::factory()->create(['employee_id' => $empA->id, 'role' => 'employee', 'is_active' => true]);

        $empB = Employee::create([
            'employee_code' => 'EMP-DASH-B',
            'full_name' => 'Dulhaq',
            'status' => 'active',
            'attendance_enabled' => true,
            'outlet_id' => $this->outletB->id, // Kopi Selon 1 (lat -6.9147440, lon 107.6098100, radius 200)
        ]);
        $userB = User::factory()->create(['employee_id' => $empB->id, 'role' => 'employee', 'is_active' => true]);

        // 1 & 3: Employee A dashboard renders Outlet A name & coordinates
        $responseA = $this->actingAs($userA)->get(route('employee.dashboard'));
        $responseA->assertOk();
        $responseA->assertSee('Selon Beauty Outlet Jakarta');
        $responseA->assertSee('-6.2');
        $responseA->assertSee('106.816666');

        // 2, 4, 5: Employee B dashboard renders Outlet B name & coordinates, NOT Outlet A coordinates
        $responseB = $this->actingAs($userB)->get(route('employee.dashboard'));
        $responseB->assertOk();
        $responseB->assertSee('Selon Beauty Outlet Bandung');
        $responseB->assertSee('-6.914744');
        $responseB->assertSee('107.60981');
        $responseB->assertDontSee('-6.2000000');

        // 6 & 7: Geofence evaluation using Haversine
        $attendanceService = app(AttendanceService::class);

        // Emp A at Outlet A coords -> inside radius
        $resA = $attendanceService->validateGeofence(-6.2000050, 106.8166660, 10.0, $empA);
        $this->assertEquals($this->outletA->id, $resA['location']->id);
        $this->assertLessThanOrEqual(50, $resA['distance']);

        // Emp B at Outlet A coords -> outside Outlet B radius (evaluates against Outlet B)
        try {
            $attendanceService->validateGeofence(-6.2000000, 106.8166660, 10.0, $empB);
            $this->fail('Expected InvalidArgumentException for Emp B at Outlet A location');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('Selon Beauty Outlet Bandung', $e->getMessage());
            $this->assertStringContainsString('luar area absensi', $e->getMessage());
        }

        // 8, 9, 10: Check-in rejection at wrong location & acceptance at correct location
        $shiftFlex = Shift::create([
            'name' => 'Shift Flex Dual',
            'code' => 'FLEXDUAL',
            'start_time' => '00:00:00',
            'end_time' => '23:59:00',
            'check_in_open_minutes_before' => 1440,
            'check_in_close_minutes_after' => 1440,
            'check_out_open_minutes_before' => 1440,
            'check_out_close_minutes_after' => 1440,
        ]);
        EmployeeSchedule::create([
            'employee_id' => $empB->id,
            'work_date' => now('Asia/Jakarta')->toDateString(),
            'shift_id' => $shiftFlex->id,
            'schedule_type' => 'work',
        ]);

        DB::table('app_settings')->updateOrInsert(
            ['key' => 'attendance_require_selfie'],
            ['value' => '0', 'type' => 'boolean', 'is_public' => true]
        );

        // Check-in request for Emp B at Outlet A location -> REJECTED
        $checkInWrongResp = $this->actingAs($userB)->post(route('employee.attendance.check-in'), [
            'latitude' => -6.2000000,
            'longitude' => 106.8166660,
            'accuracy' => 10.0,
        ]);
        $checkInWrongResp->assertSessionHas('error');

        // Check-in request for Emp B at Outlet B location -> ACCEPTED
        $checkInRightResp = $this->actingAs($userB)->post(route('employee.attendance.check-in'), [
            'latitude' => -6.9147440,
            'longitude' => 107.6098100,
            'accuracy' => 10.0,
        ]);
        $checkInRightResp->assertSessionHas('success');

        // 11. Check-out uses Employee B outlet
        $checkOutResp = $this->actingAs($userB)->post(route('employee.attendance.check-out'), [
            'latitude' => -6.9147440,
            'longitude' => 107.6098100,
            'accuracy' => 10.0,
        ]);
        $checkOutResp->assertSessionHas('success');

        // 12. Overtime geofence uses Employee B outlet
        $overtimeSessionService = app(\App\Services\OvertimeSessionService::class);
        $overtimeRequest = \App\Models\OvertimeRequest::create([
            'employee_id' => $empB->id,
            'work_date' => now('Asia/Jakarta')->toDateString(),
            'requested_minutes' => 60,
            'approved_minutes' => 60,
            'status' => 'approved',
            'reason' => 'Testing Overtime Geofence',
        ]);

        // Attempt starting overtime at Outlet A coordinates -> REJECTED
        try {
            $overtimeSessionService->start($userB, $overtimeRequest->id, [
                'latitude' => -6.2000000,
                'longitude' => 106.8166660,
                'accuracy' => 10.0,
            ]);
            $this->fail('Expected Exception for overtime start at Outlet A coordinates');
        } catch (\InvalidArgumentException|\Illuminate\Validation\ValidationException $e) {
            $this->assertTrue(true);
        }
    }
}
