<?php

namespace Tests\Feature\Attendance;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\LeaveRequest;
use App\Models\Shift;
use App\Models\User;
use App\Services\AttendanceStatusResolver;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AttendanceStatusResolverTest extends TestCase
{
    use RefreshDatabase;

    protected AttendanceStatusResolver $resolver;
    protected Employee $employee;
    protected Shift $pagiShift;
    protected Shift $nightShift;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new AttendanceStatusResolver();

        $this->employee = Employee::create([
            'employee_code' => 'SB-100',
            'full_name' => 'Karyawan Tes Resolver',
            'status' => 'active',
        ]);

        // Shift PAGI: 08:00 - 14:00, grace 5m, open 10m before (07:50), close 10m after (08:10)
        $this->pagiShift = Shift::create([
            'name' => 'PAGI',
            'code' => 'PG-TEST',
            'start_time' => '08:00:00',
            'end_time' => '14:00:00',
            'grace_period_minutes' => 5,
            'check_in_open_minutes_before' => 10,
            'check_in_close_minutes_after' => 10,
            'is_active' => true,
        ]);

        // Shift MALAM (Cross-midnight): 20:00 - 06:00 (next day), open 10m before (19:50), close 10m after (20:10)
        $this->nightShift = Shift::create([
            'name' => 'MALAM',
            'code' => 'MLM-TEST',
            'start_time' => '20:00:00',
            'end_time' => '06:00:00',
            'grace_period_minutes' => 5,
            'check_in_open_minutes_before' => 10,
            'check_in_close_minutes_after' => 10,
            'is_active' => true,
        ]);
    }

    public function test_work_employee_before_check_in_window_is_schedule_not_started(): void
    {
        $workDate = '2026-08-12';
        $schedule = EmployeeSchedule::create([
            'employee_id' => $this->employee->id,
            'shift_id' => $this->pagiShift->id,
            'work_date' => $workDate,
            'schedule_type' => 'work',
        ]);

        // 07:30 is before check-in open (07:50)
        $serverTime = Carbon::parse('2026-08-12 07:30:00', 'Asia/Jakarta');
        $resolved = $this->resolver->resolve($schedule, null, null, $serverTime);

        $this->assertEquals('not_started', $resolved['key']);
        $this->assertEquals('JADWAL BELUM DIMULAI', $resolved['label']);
    }

    public function test_work_employee_inside_check_in_window_is_not_checked_in(): void
    {
        $workDate = '2026-08-12';
        $schedule = EmployeeSchedule::create([
            'employee_id' => $this->employee->id,
            'shift_id' => $this->pagiShift->id,
            'work_date' => $workDate,
            'schedule_type' => 'work',
        ]);

        // 07:55 is inside check-in window (07:50 - 08:10)
        $serverTime = Carbon::parse('2026-08-12 07:55:00', 'Asia/Jakarta');
        $resolved = $this->resolver->resolve($schedule, null, null, $serverTime);

        $this->assertEquals('pending', $resolved['key']);
        $this->assertEquals('BELUM CHECK-IN', $resolved['label']);
    }

    public function test_work_employee_inside_grace_period_can_still_be_on_time(): void
    {
        $workDate = '2026-08-12';
        $schedule = EmployeeSchedule::create([
            'employee_id' => $this->employee->id,
            'shift_id' => $this->pagiShift->id,
            'work_date' => $workDate,
            'schedule_type' => 'work',
        ]);

        // 08:04 is inside grace period (08:00 + 5m)
        $serverTime = Carbon::parse('2026-08-12 08:04:00', 'Asia/Jakarta');
        $resolved = $this->resolver->resolve($schedule, null, null, $serverTime);

        $this->assertEquals('pending', $resolved['key']);
    }

    public function test_work_employee_after_grace_but_before_check_in_close_can_still_be_late(): void
    {
        $workDate = '2026-08-12';
        $schedule = EmployeeSchedule::create([
            'employee_id' => $this->employee->id,
            'shift_id' => $this->pagiShift->id,
            'work_date' => $workDate,
            'schedule_type' => 'work',
        ]);

        // 08:08 is after grace period (08:05) but before check-in close (08:10)
        $serverTime = Carbon::parse('2026-08-12 08:08:00', 'Asia/Jakarta');
        $resolved = $this->resolver->resolve($schedule, null, null, $serverTime);

        $this->assertEquals('pending', $resolved['key']);
    }

    public function test_work_employee_after_check_in_close_without_attendance_is_absent(): void
    {
        $workDate = '2026-08-12';
        $schedule = EmployeeSchedule::create([
            'employee_id' => $this->employee->id,
            'shift_id' => $this->pagiShift->id,
            'work_date' => $workDate,
            'schedule_type' => 'work',
        ]);

        // 08:15 or 18:20 is after check-in close (08:10)
        $serverTime = Carbon::parse('2026-08-12 18:20:00', 'Asia/Jakarta');
        $resolved = $this->resolver->resolve($schedule, null, null, $serverTime);

        $this->assertEquals('absent', $resolved['key']);
        $this->assertEquals('TIDAK HADIR', $resolved['label']);
    }

    public function test_work_employee_after_shift_end_without_attendance_is_absent(): void
    {
        $workDate = '2026-08-12';
        $schedule = EmployeeSchedule::create([
            'employee_id' => $this->employee->id,
            'shift_id' => $this->pagiShift->id,
            'work_date' => $workDate,
            'schedule_type' => 'work',
        ]);

        // 14:30 is after shift end (14:00)
        $serverTime = Carbon::parse('2026-08-12 14:30:00', 'Asia/Jakarta');
        $resolved = $this->resolver->resolve($schedule, null, null, $serverTime);

        $this->assertEquals('absent', $resolved['key']);
        $this->assertEquals('TIDAK HADIR', $resolved['label']);
    }

    public function test_approved_permission_is_not_absent(): void
    {
        $workDate = '2026-08-12';
        $schedule = EmployeeSchedule::create([
            'employee_id' => $this->employee->id,
            'shift_id' => $this->pagiShift->id,
            'work_date' => $workDate,
            'schedule_type' => 'work',
        ]);

        $leave = LeaveRequest::create([
            'employee_id' => $this->employee->id,
            'type' => 'permission',
            'start_date' => $workDate,
            'end_date' => $workDate,
            'reason' => 'Keperluan Keluarga',
            'status' => 'approved',
        ]);

        $serverTime = Carbon::parse('2026-08-12 18:20:00', 'Asia/Jakarta');
        $resolved = $this->resolver->resolve($schedule, null, $leave, $serverTime);

        $this->assertEquals('permission', $resolved['key']);
        $this->assertEquals('IZIN', $resolved['label']);
    }

    public function test_approved_sick_is_not_absent(): void
    {
        $workDate = '2026-08-12';
        $schedule = EmployeeSchedule::create([
            'employee_id' => $this->employee->id,
            'shift_id' => $this->pagiShift->id,
            'work_date' => $workDate,
            'schedule_type' => 'work',
        ]);

        $leave = LeaveRequest::create([
            'employee_id' => $this->employee->id,
            'type' => 'sick',
            'start_date' => $workDate,
            'end_date' => $workDate,
            'reason' => 'Demam Tinggi',
            'status' => 'approved',
        ]);

        $serverTime = Carbon::parse('2026-08-12 18:20:00', 'Asia/Jakarta');
        $resolved = $this->resolver->resolve($schedule, null, $leave, $serverTime);

        $this->assertEquals('sick', $resolved['key']);
        $this->assertEquals('SAKIT', $resolved['label']);
    }

    public function test_approved_leave_is_not_absent(): void
    {
        $workDate = '2026-08-12';
        $schedule = EmployeeSchedule::create([
            'employee_id' => $this->employee->id,
            'shift_id' => $this->pagiShift->id,
            'work_date' => $workDate,
            'schedule_type' => 'work',
        ]);

        $leave = LeaveRequest::create([
            'employee_id' => $this->employee->id,
            'type' => 'leave',
            'start_date' => $workDate,
            'end_date' => $workDate,
            'reason' => 'Cuti Tahunan',
            'status' => 'approved',
        ]);

        $serverTime = Carbon::parse('2026-08-12 18:20:00', 'Asia/Jakarta');
        $resolved = $this->resolver->resolve($schedule, null, $leave, $serverTime);

        $this->assertEquals('leave', $resolved['key']);
        $this->assertEquals('CUTI', $resolved['label']);
    }

    public function test_off_employee_is_not_absent(): void
    {
        $workDate = '2026-08-12';
        $schedule = EmployeeSchedule::create([
            'employee_id' => $this->employee->id,
            'work_date' => $workDate,
            'schedule_type' => 'off',
        ]);

        $serverTime = Carbon::parse('2026-08-12 18:20:00', 'Asia/Jakarta');
        $resolved = $this->resolver->resolve($schedule, null, null, $serverTime);

        $this->assertEquals('off', $resolved['key']);
        $this->assertEquals('OFF', $resolved['label']);
    }

    public function test_holiday_employee_is_not_absent(): void
    {
        $workDate = '2026-08-12';
        $schedule = EmployeeSchedule::create([
            'employee_id' => $this->employee->id,
            'work_date' => $workDate,
            'schedule_type' => 'holiday',
        ]);

        $serverTime = Carbon::parse('2026-08-12 18:20:00', 'Asia/Jakarta');
        $resolved = $this->resolver->resolve($schedule, null, null, $serverTime);

        $this->assertEquals('holiday', $resolved['key']);
        $this->assertEquals('LIBUR', $resolved['label']);
    }

    public function test_employee_without_schedule_is_not_absent(): void
    {
        $serverTime = Carbon::parse('2026-08-12 18:20:00', 'Asia/Jakarta');
        $resolved = $this->resolver->resolve(null, null, null, $serverTime);

        $this->assertEquals('unknown', $resolved['key']);
        $this->assertEquals('BELUM DITETAPKAN', $resolved['label']);
    }

    public function test_attendance_present_is_not_absent(): void
    {
        $workDate = '2026-08-12';
        $schedule = EmployeeSchedule::create([
            'employee_id' => $this->employee->id,
            'shift_id' => $this->pagiShift->id,
            'work_date' => $workDate,
            'schedule_type' => 'work',
        ]);

        $record = AttendanceRecord::create([
            'employee_id' => $this->employee->id,
            'work_date' => $workDate,
            'check_in_at' => '2026-08-12 07:58:00',
            'status' => 'present',
        ]);

        $serverTime = Carbon::parse('2026-08-12 18:20:00', 'Asia/Jakarta');
        $resolved = $this->resolver->resolve($schedule, $record, null, $serverTime);

        $this->assertEquals('present', $resolved['key']);
        $this->assertEquals('HADIR', $resolved['label']);
    }

    public function test_late_attendance_is_not_absent(): void
    {
        $workDate = '2026-08-12';
        $schedule = EmployeeSchedule::create([
            'employee_id' => $this->employee->id,
            'shift_id' => $this->pagiShift->id,
            'work_date' => $workDate,
            'schedule_type' => 'work',
        ]);

        $record = AttendanceRecord::create([
            'employee_id' => $this->employee->id,
            'work_date' => $workDate,
            'check_in_at' => '2026-08-12 08:08:00',
            'late_minutes' => 3,
            'status' => 'late',
        ]);

        $serverTime = Carbon::parse('2026-08-12 18:20:00', 'Asia/Jakarta');
        $resolved = $this->resolver->resolve($schedule, $record, null, $serverTime);

        $this->assertEquals('late', $resolved['key']);
        $this->assertEquals('TERLAMBAT', $resolved['label']);
    }

    public function test_cross_midnight_shift_does_not_become_absent_incorrectly_before_valid_check_in_window_ends(): void
    {
        $workDate = '2026-08-11';
        $schedule = EmployeeSchedule::create([
            'employee_id' => $this->employee->id,
            'shift_id' => $this->nightShift->id,
            'work_date' => $workDate,
            'schedule_type' => 'work',
        ]);

        // Shift MALAM 20:00 - 06:00. Check-in open 19:50, close 20:10.
        // At 19:40 -> JADWAL BELUM DIMULAI
        $timeBefore = Carbon::parse('2026-08-11 19:40:00', 'Asia/Jakarta');
        $resolvedBefore = $this->resolver->resolve($schedule, null, null, $timeBefore);
        $this->assertEquals('not_started', $resolvedBefore['key']);

        // At 20:05 -> BELUM CHECK-IN
        $timeInside = Carbon::parse('2026-08-11 20:05:00', 'Asia/Jakarta');
        $resolvedInside = $this->resolver->resolve($schedule, null, null, $timeInside);
        $this->assertEquals('pending', $resolvedInside['key']);

        // At 20:20 -> TIDAK HADIR
        $timeAfter = Carbon::parse('2026-08-11 20:20:00', 'Asia/Jakarta');
        $resolvedAfter = $this->resolver->resolve($schedule, null, null, $timeAfter);
        $this->assertEquals('absent', $resolvedAfter['key']);
    }

    public function test_asia_jakarta_timezone_is_used(): void
    {
        $shiftWindow = $this->resolver->calculateCheckInWindow('2026-08-12', $this->pagiShift);
        $this->assertEquals('Asia/Jakarta', $shiftWindow['open_time']->timezoneName);
        $this->assertEquals('Asia/Jakarta', $shiftWindow['close_time']->timezoneName);
        $this->assertEquals('2026-08-12 07:50:00', $shiftWindow['open_time']->format('Y-m-d H:i:s'));
        $this->assertEquals('2026-08-12 08:10:00', $shiftWindow['close_time']->format('Y-m-d H:i:s'));
    }
}
