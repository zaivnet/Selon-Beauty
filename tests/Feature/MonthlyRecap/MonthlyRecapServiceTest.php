<?php

namespace Tests\Feature\MonthlyRecap;

use App\Models\AttendanceCorrection;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\EmployeeScheduleOverride;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\OvertimeSession;
use App\Models\Shift;
use App\Models\User;
use App\Services\MonthlyAttendanceRecapService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MonthlyRecapServiceTest extends TestCase
{
    use RefreshDatabase;

    private Employee $employee;

    private Shift $dayShift;

    private Shift $nightShift;

    private MonthlyAttendanceRecapService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-08-14 10:00:00', config('app.timezone')));
        $this->employee = Employee::create(['employee_code' => 'REC-001', 'full_name' => 'Ayu Recap', 'status' => 'active']);
        $this->dayShift = $this->shift('Recap Pagi', 'REC-P', '08:00', '17:00');
        $this->nightShift = $this->shift('Recap Malam', 'REC-N', '22:00', '06:00', true);
        $this->service = app(MonthlyAttendanceRecapService::class);
    }

    public function test_monthly_recap_aggregates_schedule_attendance_leave_and_overtime(): void
    {
        $day1 = $this->schedule('2026-07-01');
        $day2 = $this->schedule('2026-07-02');
        $this->schedule('2026-07-03');
        $this->schedule('2026-07-04');
        $this->schedule('2026-07-05');
        $this->schedule('2026-07-06');
        $this->schedule('2026-07-07');
        $this->schedule('2026-07-08', 'off');
        $this->schedule('2026-07-09');
        $this->schedule('2026-07-10', 'off');
        foreach (range(11, 31) as $day) {
            $this->schedule(sprintf('2026-07-%02d', $day), 'off');
        }

        $this->attendance($day1, '2026-07-01 08:00', '2026-07-01 17:00', 480);
        $this->attendance($day2, '2026-07-02 08:15', '2026-07-02 16:45', 450, 15, 15);
        $day10Attendance = AttendanceRecord::create([
            'employee_id' => $this->employee->id, 'work_date' => '2026-07-10', 'status' => 'present',
            'check_in_at' => '2026-07-10 08:00:00', 'check_out_at' => '2026-07-10 17:00:00', 'worked_minutes' => 480,
        ]);
        foreach (['permission' => '2026-07-04', 'sick' => '2026-07-05', 'leave' => '2026-07-06'] as $type => $date) {
            LeaveRequest::create([
                'employee_id' => $this->employee->id, 'type' => $type, 'start_date' => $date,
                'end_date' => $date, 'reason' => 'Keperluan recap', 'status' => 'approved',
            ]);
        }
        Holiday::create(['date' => '2026-07-07', 'type' => 'public_holiday', 'name' => 'Libur Nasional', 'is_working_day' => false]);
        EmployeeScheduleOverride::create([
            'employee_id' => $this->employee->id, 'date' => '2026-07-09',
            'override_type' => 'off', 'reason' => 'Libur override',
        ]);
        EmployeeScheduleOverride::create([
            'employee_id' => $this->employee->id, 'date' => '2026-07-10',
            'override_type' => 'work', 'shift_id' => $this->dayShift->id, 'reason' => 'Masuk override',
        ]);

        $firstRequest = OvertimeRequest::create([
            'employee_id' => $this->employee->id, 'work_date' => '2026-07-01',
            'requested_minutes' => 180, 'approved_minutes' => 120, 'reason' => 'Lembur recap', 'status' => 'approved',
        ]);
        OvertimeSession::create([
            'overtime_request_id' => $firstRequest->id, 'employee_id' => $this->employee->id,
            'work_date' => '2026-07-01', 'status' => 'completed',
            'check_in_at' => '2026-07-01 18:00:00', 'check_out_at' => '2026-07-01 20:30:00',
            'actual_minutes' => 150, 'credited_minutes' => 120,
        ]);
        OvertimeRequest::create([
            'employee_id' => $this->employee->id, 'work_date' => '2026-07-02',
            'requested_minutes' => 60, 'approved_minutes' => 60, 'reason' => 'Approved tanpa sesi', 'status' => 'approved',
        ]);

        $recap = $this->service->forEmployee($this->employee, 2026, 7);
        $summary = $recap['summary'];

        $this->assertSame(31, $summary['calendar_days']);
        $this->assertSame(7, $summary['effective_work_days']);
        $this->assertSame(1, $summary['holiday_days']);
        $this->assertSame(23, $summary['off_days']);
        $this->assertSame(3, $summary['present_days']);
        $this->assertSame(1, $summary['late_days']);
        $this->assertSame(15, $summary['total_late_minutes']);
        $this->assertSame(15, $summary['total_early_leave_minutes']);
        $this->assertSame(1, $summary['absent_days']);
        $this->assertSame(1, $summary['permission_days']);
        $this->assertSame(1, $summary['sick_days']);
        $this->assertSame(1, $summary['leave_days']);
        $this->assertSame(1410, $summary['regular_worked_minutes']);
        $this->assertSame(240, $summary['overtime_requested_minutes']);
        $this->assertSame(180, $summary['overtime_approved_minutes']);
        $this->assertSame(150, $summary['overtime_actual_minutes']);
        $this->assertSame(120, $summary['overtime_credited_minutes']);
        $this->assertSame(42.86, $summary['attendance_rate']);
        $this->assertSame('READY', $summary['readiness_status']);
        $this->assertCount(31, $recap['daily']);
        $this->assertSame('employee_override', collect($recap['daily'])->firstWhere('date_string', '2026-07-10')['schedule_source']);
        $this->assertSame($day10Attendance->id, collect($recap['daily'])->firstWhere('date_string', '2026-07-10')['attendance']->id);
    }

    public function test_cross_midnight_attendance_and_overtime_use_original_work_date(): void
    {
        $schedule = $this->schedule('2026-07-11', 'work', $this->nightShift);
        $this->attendance($schedule, '2026-07-11 22:00', '2026-07-12 06:00', 480);
        $request = OvertimeRequest::create([
            'employee_id' => $this->employee->id, 'work_date' => '2026-07-11',
            'requested_minutes' => 180, 'approved_minutes' => 120, 'reason' => 'Lembur malam', 'status' => 'approved',
        ]);
        OvertimeSession::create([
            'overtime_request_id' => $request->id, 'employee_id' => $this->employee->id,
            'work_date' => '2026-07-11', 'status' => 'completed',
            'check_in_at' => '2026-07-11 23:00:00', 'check_out_at' => '2026-07-12 02:00:00',
            'actual_minutes' => 180, 'credited_minutes' => 120,
        ]);

        $recap = $this->service->forEmployee($this->employee, 2026, 7);
        $day11 = collect($recap['daily'])->firstWhere('date_string', '2026-07-11');
        $day12 = collect($recap['daily'])->firstWhere('date_string', '2026-07-12');
        $this->assertSame(480, $day11['regular_worked_minutes']);
        $this->assertSame(180, $day11['overtime_actual_minutes']);
        $this->assertSame(120, $day11['overtime_credited_minutes']);
        $this->assertSame(0, $day12['regular_worked_minutes']);
        $this->assertSame(0, $day12['overtime_actual_minutes']);
    }

    public function test_corrected_current_attendance_values_and_indicator_are_used(): void
    {
        $schedule = $this->schedule('2026-07-13');
        $record = $this->attendance($schedule, '2026-07-13 08:30', '2026-07-13 17:00', 450, 30);
        $before = $record->getAttributes();
        $record->update(['check_in_at' => '2026-07-13 08:05:00', 'late_minutes' => 5, 'worked_minutes' => 475, 'is_manually_adjusted' => true, 'corrected_at' => now()]);
        $admin = User::create([
            'name' => 'Admin Recap', 'email' => 'admin-recap@example.test',
            'password' => Hash::make('password'), 'role' => 'admin', 'is_active' => true,
        ]);
        AttendanceCorrection::create([
            'attendance_record_id' => $record->id, 'reason' => 'Koreksi jam masuk',
            'requested_by' => $admin->id, 'approved_by' => $admin->id,
            'before_data' => $before, 'after_data' => $record->fresh()->getAttributes(), 'status' => 'approved',
        ]);

        $recap = $this->service->forEmployee($this->employee, 2026, 7);
        $day = collect($recap['daily'])->firstWhere('date_string', '2026-07-13');
        $this->assertSame(5, $recap['summary']['total_late_minutes']);
        $this->assertSame(475, $recap['summary']['regular_worked_minutes']);
        $this->assertSame(1, $recap['summary']['corrected_attendance_count']);
        $this->assertTrue($day['is_corrected']);
    }

    public function test_missing_checkout_and_active_overtime_require_review(): void
    {
        foreach (array_diff(range(1, 31), [14]) as $day) {
            $this->schedule(sprintf('2026-07-%02d', $day), 'off');
        }
        $schedule = $this->schedule('2026-07-14');
        $this->attendance($schedule, '2026-07-14 08:00', null, 0);
        $request = OvertimeRequest::create([
            'employee_id' => $this->employee->id, 'work_date' => '2026-07-14',
            'requested_minutes' => 60, 'approved_minutes' => 60, 'reason' => 'Lembur aktif', 'status' => 'approved',
        ]);
        OvertimeSession::create([
            'overtime_request_id' => $request->id, 'employee_id' => $this->employee->id,
            'work_date' => '2026-07-14', 'status' => 'active', 'check_in_at' => '2026-07-14 18:00:00',
        ]);

        $recap = $this->service->forEmployee($this->employee, 2026, 7);
        $codes = collect($recap['daily'])->firstWhere('date_string', '2026-07-14')['review_issues'];
        $this->assertSame('NEEDS_REVIEW', $recap['summary']['readiness_status']);
        $this->assertSame(1, $recap['summary']['missing_checkout_count']);
        $this->assertSame(1, $recap['summary']['review_required_count']);
        $this->assertEqualsCanonicalizing(['missing_checkout', 'active_overtime'], collect($codes)->pluck('code')->all());
    }

    public function test_work_override_without_shift_requires_review(): void
    {
        EmployeeScheduleOverride::create([
            'employee_id' => $this->employee->id,
            'date' => '2026-07-15',
            'override_type' => 'work',
            'shift_id' => null,
            'reason' => 'Shift belum ditentukan',
        ]);

        $recap = $this->service->forEmployee($this->employee, 2026, 7);
        $day = collect($recap['daily'])->firstWhere('date_string', '2026-07-15');

        $this->assertSame('NEEDS_REVIEW', $recap['summary']['readiness_status']);
        $this->assertTrue($day['needs_review']);
        $this->assertContains('incomplete_schedule', collect($day['review_issues'])->pluck('code')->all());
    }

    public function test_zero_work_days_are_safe_and_ready(): void
    {
        foreach (range(1, 31) as $day) {
            $this->schedule(sprintf('2026-07-%02d', $day), 'off');
        }

        $recap = $this->service->forEmployee($this->employee, 2026, 7);
        $this->assertSame(0, $recap['summary']['effective_work_days']);
        $this->assertSame(31, $recap['summary']['off_days']);
        $this->assertSame(0.0, $recap['summary']['attendance_rate']);
        $this->assertSame('READY', $recap['summary']['readiness_status']);
        $this->assertCount(31, $recap['daily']);
    }

    public function test_missing_historical_schedule_requires_review(): void
    {
        $recap = $this->service->forEmployee($this->employee, 2026, 7);

        $this->assertSame('NEEDS_REVIEW', $recap['summary']['readiness_status']);
        $this->assertSame(31, $recap['summary']['review_required_count']);
        $this->assertContains(
            'missing_schedule',
            collect($recap['daily'][0]['review_issues'])->pluck('code')->all(),
        );
    }

    public function test_query_count_is_batched_not_employee_date_n_plus_one(): void
    {
        $other = Employee::create(['employee_code' => 'REC-002', 'full_name' => 'Budi Recap', 'status' => 'active']);
        foreach ([$this->employee, $other] as $employee) {
            EmployeeSchedule::create([
                'employee_id' => $employee->id, 'work_date' => '2026-07-01',
                'shift_id' => $this->dayShift->id, 'schedule_type' => 'work',
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $data = $this->service->generate(2026, 7);
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertCount(2, $data['recaps']);
        // Work Outlet eager loading adds one bounded relation query for regular
        // schedules and one for overrides; the total must remain independent of
        // employee and date cardinality.
        $this->assertLessThanOrEqual(12, $queryCount);
    }

    private function shift(string $name, string $code, string $start, string $end, bool $crossesMidnight = false): Shift
    {
        return Shift::create([
            'name' => $name, 'code' => $code, 'start_time' => $start, 'end_time' => $end,
            'crosses_midnight' => $crossesMidnight, 'is_active' => true,
        ]);
    }

    private function schedule(string $date, string $type = 'work', ?Shift $shift = null): EmployeeSchedule
    {
        return EmployeeSchedule::create([
            'employee_id' => $this->employee->id, 'work_date' => $date,
            'schedule_type' => $type, 'shift_id' => $type === 'work' ? ($shift ?? $this->dayShift)->id : null,
        ]);
    }

    private function attendance(
        EmployeeSchedule $schedule,
        string $checkIn,
        ?string $checkOut,
        int $workedMinutes,
        int $lateMinutes = 0,
        int $earlyLeaveMinutes = 0,
    ): AttendanceRecord {
        return AttendanceRecord::create([
            'employee_id' => $this->employee->id, 'work_schedule_id' => $schedule->id,
            'work_date' => $schedule->work_date, 'status' => $lateMinutes > 0 ? 'late' : 'present',
            'check_in_at' => $checkIn, 'check_out_at' => $checkOut,
            'worked_minutes' => $workedMinutes, 'late_minutes' => $lateMinutes,
            'early_leave_minutes' => $earlyLeaveMinutes,
        ]);
    }
}
