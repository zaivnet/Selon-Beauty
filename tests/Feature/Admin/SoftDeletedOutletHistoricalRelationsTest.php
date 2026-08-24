<?php

namespace Tests\Feature\Admin;

use App\Models\AttendanceLocation;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeOutletTransfer;
use App\Models\EmployeeSchedule;
use App\Models\EmployeeScheduleOverride;
use App\Models\JobTitle;
use App\Models\Outlet;
use App\Models\Shift;
use App\Models\User;
use App\Services\EmployeeTransferService;
use App\Services\WorkCalendarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SoftDeletedOutletHistoricalRelationsTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;
    protected Outlet $outletA;
    protected Outlet $outletB;
    protected JobTitle $jobTitle;
    protected Shift $shift;
    protected AttendanceLocation $location;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outletA = Outlet::create([
            'name' => 'Kopi Selon Outlet A',
            'code' => 'KSA',
            'address' => 'Jl. A No. 1',
            'latitude' => -7.250445,
            'longitude' => 112.768845,
            'radius_meters' => 100,
            'max_accuracy_meters' => 50,
            'is_active' => true,
        ]);

        $this->outletB = Outlet::create([
            'name' => 'Kopi Selon Outlet B',
            'code' => 'KSB',
            'address' => 'Jl. B No. 2',
            'latitude' => -7.260000,
            'longitude' => 112.770000,
            'radius_meters' => 100,
            'max_accuracy_meters' => 50,
            'is_active' => true,
        ]);

        $this->jobTitle = JobTitle::create([
            'name' => 'Barista Test',
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

        $this->location = AttendanceLocation::create([
            'name' => 'Lokasi Utama A',
            'address' => 'Jl. A No. 1',
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

    public function test_attendance_record_preserves_soft_deleted_outlet_relation(): void
    {
        $employee = Employee::create([
            'employee_code' => 'SB-101',
            'full_name' => 'Employee Test A',
            'outlet_id' => $this->outletA->id,
            'job_title_id' => $this->jobTitle->id,
            'status' => 'active',
            'attendance_enabled' => true,
        ]);

        $record = AttendanceRecord::create([
            'employee_id' => $employee->id,
            'work_date' => '2026-08-20',
            'attendance_location_id' => $this->location->id,
            'outlet_id' => $this->outletA->id,
            'status' => 'present',
            'check_in_at' => '2026-08-20 08:00:00',
            'check_in_latitude' => -7.250445,
            'check_in_longitude' => 112.768845,
            'check_in_accuracy_meters' => 10,
            'check_in_distance_meters' => 5,
        ]);

        // Soft delete Outlet A
        $this->outletA->delete();
        $this->assertSoftDeleted('outlets', ['id' => $this->outletA->id]);

        // Verify AttendanceRecord->outlet still resolves Outlet A
        $freshRecord = $record->fresh('outlet');
        $this->assertNotNull($freshRecord->outlet);
        $this->assertEquals($this->outletA->id, $freshRecord->outlet->id);
        $this->assertEquals('Kopi Selon Outlet A', $freshRecord->outlet->name);
    }

    public function test_regular_employee_schedule_preserves_soft_deleted_work_outlet_relation(): void
    {
        $employee = Employee::create([
            'employee_code' => 'SB-102',
            'full_name' => 'Employee Test B',
            'outlet_id' => $this->outletA->id,
            'job_title_id' => $this->jobTitle->id,
            'status' => 'active',
            'attendance_enabled' => true,
        ]);

        $schedule = EmployeeSchedule::create([
            'employee_id' => $employee->id,
            'work_date' => '2026-08-21',
            'shift_id' => $this->shift->id,
            'work_outlet_id' => $this->outletA->id,
            'schedule_type' => 'work',
        ]);

        // Soft delete Outlet A
        $this->outletA->delete();

        // Verify EmployeeSchedule->workOutlet resolves soft-deleted outlet
        $freshSchedule = $schedule->fresh('workOutlet');
        $this->assertNotNull($freshSchedule->workOutlet);
        $this->assertEquals($this->outletA->id, $freshSchedule->workOutlet->id);
        $this->assertEquals('Kopi Selon Outlet A', $freshSchedule->workOutlet->name);
    }

    public function test_employee_schedule_override_preserves_soft_deleted_work_outlet_relation(): void
    {
        $employee = Employee::create([
            'employee_code' => 'SB-103',
            'full_name' => 'Employee Test C',
            'outlet_id' => $this->outletA->id,
            'job_title_id' => $this->jobTitle->id,
            'status' => 'active',
            'attendance_enabled' => true,
        ]);

        $override = EmployeeScheduleOverride::create([
            'employee_id' => $employee->id,
            'date' => '2026-08-25',
            'override_type' => 'work',
            'shift_id' => $this->shift->id,
            'work_outlet_id' => $this->outletA->id,
            'reason' => 'Penugasan sementara ke Outlet A',
            'created_by' => $this->superadmin->id,
        ]);

        // Soft delete Outlet A
        $this->outletA->delete();

        // Verify EmployeeScheduleOverride->workOutlet resolves soft-deleted outlet
        $freshOverride = $override->fresh('workOutlet');
        $this->assertNotNull($freshOverride->workOutlet);
        $this->assertEquals($this->outletA->id, $freshOverride->workOutlet->id);
        $this->assertEquals('Kopi Selon Outlet A', $freshOverride->workOutlet->name);
    }

    public function test_employee_outlet_transfer_preserves_soft_deleted_from_and_to_outlets(): void
    {
        $employee = Employee::create([
            'employee_code' => 'SB-104',
            'full_name' => 'Employee Test D',
            'outlet_id' => $this->outletB->id,
            'job_title_id' => $this->jobTitle->id,
            'status' => 'active',
            'attendance_enabled' => true,
        ]);

        $transfer = EmployeeOutletTransfer::create([
            'employee_id' => $employee->id,
            'from_outlet_id' => $this->outletA->id,
            'to_outlet_id' => $this->outletB->id,
            'effective_date' => '2026-08-01',
            'notes' => 'Mutasi kerja dari A ke B',
            'transferred_by_user_id' => $this->superadmin->id,
        ]);

        // Soft delete Outlet A
        $this->outletA->delete();
        $freshTransfer = $transfer->fresh(['fromOutlet', 'toOutlet']);
        $this->assertNotNull($freshTransfer->fromOutlet);
        $this->assertEquals($this->outletA->id, $freshTransfer->fromOutlet->id);
        $this->assertNotNull($freshTransfer->toOutlet);

        // Soft delete Outlet B
        $this->outletB->delete();
        $freshTransfer2 = $transfer->fresh(['fromOutlet', 'toOutlet']);
        $this->assertNotNull($freshTransfer2->fromOutlet);
        $this->assertNotNull($freshTransfer2->toOutlet);
        $this->assertEquals($this->outletB->id, $freshTransfer2->toOutlet->id);
    }

    public function test_active_operational_actions_reject_deleted_or_inactive_outlets(): void
    {
        $employee = Employee::create([
            'employee_code' => 'SB-105',
            'full_name' => 'Employee Test E',
            'outlet_id' => $this->outletA->id,
            'job_title_id' => $this->jobTitle->id,
            'status' => 'active',
            'attendance_enabled' => true,
        ]);

        // Soft delete Outlet B
        $this->outletB->delete();

        // 1. Temporary assignment to deleted outlet B must be rejected
        $calendarService = app(WorkCalendarService::class);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Outlet Kerja harus berupa outlet aktif yang valid.');

        $calendarService->saveOverride([
            'employee_id' => $employee->id,
            'date' => '2026-08-28',
            'override_type' => 'work',
            'shift_id' => $this->shift->id,
            'work_outlet_id' => $this->outletB->id,
            'reason' => 'Mencoba penugasan ke outlet yang sudah dihapus',
        ], $this->superadmin);
    }

    public function test_permanent_transfer_rejects_soft_deleted_destination_outlet(): void
    {
        $employee = Employee::create([
            'employee_code' => 'SB-106',
            'full_name' => 'Employee Test F',
            'outlet_id' => $this->outletA->id,
            'job_title_id' => $this->jobTitle->id,
            'status' => 'active',
            'attendance_enabled' => true,
        ]);

        // Soft delete Outlet B
        $this->outletB->delete();

        // 2. Permanent transfer to soft-deleted outlet B must throw ValidationException
        $transferService = app(EmployeeTransferService::class);

        $this->expectException(ValidationException::class);
        $transferService->transferOutlet($employee, $this->outletB, $this->superadmin, 'Transfer ke outlet terhapus');
    }
}
