<?php

namespace Tests\Feature\MultiOutlet;

use App\Models\AttendanceLocation;
use App\Models\Employee;
use App\Models\Outlet;
use App\Models\OvertimeRequest;
use App\Models\Shift;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\OvertimeSessionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LegacyLocationIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Outlet $outletA;
    protected Outlet $outletB;
    protected AttendanceLocation $legacyLocationA;
    protected Employee $employeeB;
    protected User $employeeUserB;
    protected Employee $employeeNoOutlet;
    protected User $employeeUserNoOutlet;
    protected Shift $shiftPagi;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        // Outlet A: Selon Beauty Center (-7.1627701, 113.4843582)
        $this->outletA = Outlet::create([
            'name' => 'Selon Beauty Outlet A',
            'code' => 'SB-OUT-A',
            'latitude' => -7.1627701,
            'longitude' => 113.4843582,
            'radius_meters' => 50,
            'max_accuracy_meters' => 100,
            'is_active' => true,
        ]);

        // Outlet B: Kopi Selon (-7.1569777, 113.4895155) - ~800m away from Outlet A
        $this->outletB = Outlet::create([
            'name' => 'Kopi Selon Outlet B',
            'code' => 'KP-OUT-B',
            'latitude' => -7.1569777,
            'longitude' => 113.4895155,
            'radius_meters' => 50,
            'max_accuracy_meters' => 100,
            'is_active' => true,
        ]);

        // Legacy active location matching Outlet A
        $this->legacyLocationA = AttendanceLocation::create([
            'name' => 'Legacy Active Location A',
            'latitude' => -7.1627701,
            'longitude' => 113.4843582,
            'radius_meters' => 50,
            'max_accuracy_meters' => 100,
            'is_active' => true,
        ]);

        // Employee B assigned to Outlet B
        $this->employeeB = Employee::create([
            'employee_code' => 'EMP-OUT-B',
            'full_name' => 'Employee Outlet B',
            'status' => 'active',
            'outlet_id' => $this->outletB->id,
        ]);

        $this->employeeUserB = User::factory()->create([
            'employee_id' => $this->employeeB->id,
            'role' => 'employee',
            'outlet_id' => $this->outletB->id,
            'is_active' => true,
        ]);
        $this->employeeB->update(['user_id' => $this->employeeUserB->id]);

        // Employee without outlet
        $this->employeeNoOutlet = Employee::create([
            'employee_code' => 'EMP-NO-OUT',
            'full_name' => 'Employee Without Outlet',
            'status' => 'active',
            'outlet_id' => null,
        ]);

        $this->employeeUserNoOutlet = User::factory()->create([
            'employee_id' => $this->employeeNoOutlet->id,
            'role' => 'employee',
            'outlet_id' => null,
            'is_active' => true,
        ]);
        $this->employeeNoOutlet->update(['user_id' => $this->employeeUserNoOutlet->id]);

        $this->shiftPagi = Shift::create([
            'code' => 'PGI',
            'name' => 'Shift Pagi',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'is_active' => true,
        ]);
    }

    public function test_employee_b_geofence_uses_outlet_b_and_ignores_legacy_active_location_a(): void
    {
        $attendanceService = app(AttendanceService::class);

        // Employee B attempts check-in at Outlet A coordinates -> MUST be rejected because Employee B belongs to Outlet B
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('di luar area absensi Kopi Selon Outlet B');

        $attendanceService->validateGeofence(
            $this->outletA->latitude,
            $this->outletA->longitude,
            10.0,
            $this->employeeB
        );
    }

    public function test_employee_b_geofence_validates_successfully_at_outlet_b(): void
    {
        $attendanceService = app(AttendanceService::class);

        // Employee B checks in at Outlet B coordinates -> MUST succeed
        $validated = $attendanceService->validateGeofence(
            $this->outletB->latitude,
            $this->outletB->longitude,
            10.0,
            $this->employeeB
        );

        $this::assertEquals($this->outletB->id, $validated['location']->id);
        $this::assertLessThanOrEqual(50.0, $validated['distance']);
    }

    public function test_employee_without_outlet_fails_closed_and_never_falls_back_to_legacy_location(): void
    {
        $attendanceService = app(AttendanceService::class);

        // Employee without outlet -> MUST fail closed immediately
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Lokasi outlet Anda belum dikonfigurasi');

        $attendanceService->validateGeofence(
            $this->legacyLocationA->latitude,
            $this->legacyLocationA->longitude,
            10.0,
            $this->employeeNoOutlet
        );
    }

    public function test_overtime_geofence_uses_assigned_outlet_b(): void
    {
        $overtimeService = app(OvertimeSessionService::class);

        $today = Carbon::now(config('app.timezone'))->toDateString();

        \App\Models\EmployeeSchedule::create([
            'employee_id' => $this->employeeB->id,
            'work_date' => $today,
            'schedule_type' => 'work',
            'shift_id' => $this->shiftPagi->id,
        ]);

        \App\Models\AttendanceRecord::create([
            'employee_id' => $this->employeeB->id,
            'outlet_id' => $this->outletB->id,
            'work_date' => $today,
            'status' => 'present',
            'check_in_at' => $today.' 08:00:00',
            'check_out_at' => $today.' 17:00:00',
        ]);

        $overtimeReq = OvertimeRequest::create([
            'employee_id' => $this->employeeB->id,
            'work_date' => $today,
            'requested_minutes' => 60,
            'approved_minutes' => 60,
            'reason' => 'Lembur toko B',
            'status' => 'approved',
        ]);

        $file = UploadedFile::fake()->image('selfie.jpg');

        // Overtime check-in using Outlet A coordinates -> MUST fail validation
        try {
            $overtimeService->start($this->employeeUserB, $overtimeReq->id, [
                'latitude' => $this->outletA->latitude,
                'longitude' => $this->outletA->longitude,
                'accuracy' => 10.0,
                'selfie' => $file,
            ]);
            $this->fail('Expected exception for invalid outlet overtime geofence was not thrown.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('di luar area absensi Kopi Selon Outlet B', $e->getMessage());
        }
    }
}
