<?php

namespace Tests\Feature\MultiOutlet;

use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Outlet;
use App\Models\Shift;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\ShiftSwapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OutletGeofenceTest extends TestCase
{
    use RefreshDatabase;

    protected Outlet $outletA;
    protected Outlet $outletB;
    protected Shift $shiftPagi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outletA = Outlet::create([
            'name' => 'Selon Outlet Jakarta',
            'code' => 'JKT01',
            'latitude' => -6.2000000,
            'longitude' => 106.8166660,
            'radius_meters' => 100,
            'is_active' => true,
        ]);

        $this->outletB = Outlet::create([
            'name' => 'Selon Outlet Bandung',
            'code' => 'BDG01',
            'latitude' => -6.9147440,
            'longitude' => 107.6098100,
            'radius_meters' => 100,
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

    public function test_cross_outlet_shift_swap_is_rejected(): void
    {
        $employeeA = Employee::create([
            'employee_code' => 'EMP-SWAP-A',
            'full_name' => 'Pemohon Swap Outlet A',
            'email' => 'swap.a@test.com',
            'status' => 'active',
            'attendance_enabled' => true,
            'outlet_id' => $this->outletA->id,
        ]);

        $employeeB = Employee::create([
            'employee_code' => 'EMP-SWAP-B',
            'full_name' => 'Target Swap Outlet B',
            'email' => 'swap.b@test.com',
            'status' => 'active',
            'attendance_enabled' => true,
            'outlet_id' => $this->outletB->id,
        ]);

        User::factory()->create(['employee_id' => $employeeA->id, 'role' => 'employee', 'is_active' => true]);
        User::factory()->create(['employee_id' => $employeeB->id, 'role' => 'employee', 'is_active' => true]);

        EmployeeSchedule::create([
            'employee_id' => $employeeA->id,
            'work_date' => now()->addDays(2)->toDateString(),
            'shift_id' => $this->shiftPagi->id,
            'schedule_type' => 'work',
        ]);
        EmployeeSchedule::create([
            'employee_id' => $employeeB->id,
            'work_date' => now()->addDays(2)->toDateString(),
            'shift_id' => $this->shiftPagi->id,
            'schedule_type' => 'work',
        ]);

        $swapService = app(ShiftSwapService::class);

        $this->expectException(ValidationException::class);

        $swapService->submitRequest($employeeA, [
            'target_employee_id' => $employeeB->id,
            'requester_work_date' => now()->addDays(2)->toDateString(),
            'target_work_date' => now()->addDays(2)->toDateString(),
            'reason' => 'Tukar jadwal antar kota',
        ]);
    }

    public function test_employee_geofence_resolves_from_their_assigned_outlet(): void
    {
        $employeeB = Employee::create([
            'employee_code' => 'EMP-GEO-B',
            'full_name' => 'Employee Geofence Bandung',
            'email' => 'geo.b@test.com',
            'status' => 'active',
            'attendance_enabled' => true,
            'outlet_id' => $this->outletB->id,
        ]);
        User::factory()->create(['employee_id' => $employeeB->id, 'role' => 'employee', 'is_active' => true]);

        $attendanceService = app(AttendanceService::class);

        // Position near Outlet B (Bandung: -6.9147440, 107.6098100) -> Valid
        $resultValid = $attendanceService->validateGeofence(-6.9147440, 107.6098100, 20.0, $employeeB);
        $this->assertEquals('Selon Outlet Bandung', $resultValid['location']->name);

        // Position near Outlet A (Jakarta: -6.2000000) -> Out of radius for Employee B
        $this->expectException(\InvalidArgumentException::class);
        $attendanceService->validateGeofence(-6.2000000, 106.8166660, 20.0, $employeeB);
    }
}
