<?php

namespace Tests\Feature\WorkCalendar;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\EmployeeScheduleOverride;
use App\Models\Holiday;
use App\Models\Outlet;
use App\Models\Shift;
use App\Services\AttendanceStatusResolver;
use App\Services\EffectiveScheduleService;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EffectiveScheduleTest extends TestCase
{
    use RefreshDatabase;

    private Employee $employee;

    private Shift $dayShift;

    private Shift $nightShift;

    private EffectiveScheduleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-08-17 20:00:00', config('app.timezone')));
        $this->employee = Employee::create(['employee_code' => 'CAL-001', 'full_name' => 'Ayu Kalender', 'status' => 'active']);
        $this->dayShift = Shift::create([
            'name' => 'Pagi', 'code' => 'CAL-P', 'start_time' => '08:00', 'end_time' => '17:00',
            'check_in_open_minutes_before' => 60, 'check_in_close_minutes_after' => 60,
            'check_out_open_minutes_before' => 60, 'grace_period_minutes' => 5,
            'break_minutes' => 60, 'crosses_midnight' => false, 'is_active' => true,
        ]);
        $this->nightShift = Shift::create([
            'name' => 'Malam', 'code' => 'CAL-N', 'start_time' => '22:00', 'end_time' => '06:00',
            'check_in_open_minutes_before' => 60, 'check_in_close_minutes_after' => 60,
            'check_out_open_minutes_before' => 60, 'grace_period_minutes' => 5,
            'break_minutes' => 0, 'crosses_midnight' => true, 'is_active' => true,
        ]);
        $this->service = app(EffectiveScheduleService::class);
    }

    public function test_regular_work_day_is_unchanged(): void
    {
        $schedule = $this->schedule('2026-08-17', 'work', $this->dayShift);
        $effective = $this->service->resolve($this->employee, '2026-08-17');

        $this->assertTrue($effective['is_working_day']);
        $this->assertSame('regular_schedule', $effective['source']);
        $this->assertTrue($effective['regular_schedule']->is($schedule));
        $this->assertTrue($effective['shift']->is($this->dayShift));
    }

    public function test_legacy_schedule_without_work_outlet_falls_back_to_home_outlet(): void
    {
        $this->schedule('2026-08-17', 'work', $this->dayShift);

        $effective = $this->service->resolve($this->employee, '2026-08-17');

        $this->assertSame($this->employee->outlet_id, $effective['work_outlet_id']);
        $this->assertTrue($effective['uses_home_outlet_fallback']);
    }

    public function test_regular_schedule_can_resolve_a_work_outlet_different_from_home_outlet(): void
    {
        $cabang = Outlet::create([
            'name' => 'Cabang Kalender', 'code' => 'CAL-CABANG', 'latitude' => -6.21, 'longitude' => 106.81,
            'radius_meters' => 100, 'is_active' => true,
        ]);
        $this->schedule('2026-08-17', 'work', $this->dayShift, $cabang);

        $effective = $this->service->resolve($this->employee, '2026-08-17');

        $this->assertSame($cabang->id, $effective['work_outlet_id']);
        $this->assertTrue($effective['work_outlet']->is($cabang));
        $this->assertFalse($effective['uses_home_outlet_fallback']);
    }

    public function test_work_override_outlet_has_priority_over_regular_schedule_outlet(): void
    {
        $regularOutlet = Outlet::create([
            'name' => 'Regular Outlet', 'code' => 'CAL-REG', 'latitude' => -6.21, 'longitude' => 106.81,
            'radius_meters' => 100, 'is_active' => true,
        ]);
        $overrideOutlet = Outlet::create([
            'name' => 'Override Outlet', 'code' => 'CAL-OVR', 'latitude' => -6.22, 'longitude' => 106.82,
            'radius_meters' => 100, 'is_active' => true,
        ]);
        $this->schedule('2026-08-17', 'work', $this->dayShift, $regularOutlet);
        EmployeeScheduleOverride::create([
            'employee_id' => $this->employee->id, 'date' => '2026-08-17', 'override_type' => 'work',
            'shift_id' => $this->dayShift->id, 'work_outlet_id' => $overrideOutlet->id, 'reason' => 'Bantuan cabang',
        ]);

        $effective = $this->service->resolve($this->employee, '2026-08-17');

        $this->assertSame('employee_override', $effective['source']);
        $this->assertSame($overrideOutlet->id, $effective['work_outlet_id']);
    }

    public function test_company_and_public_holidays_resolve_to_libur_not_absent(): void
    {
        foreach (['company_holiday', 'public_holiday'] as $index => $type) {
            $date = '2026-08-'.(17 + $index);
            $this->schedule($date, 'work', $this->dayShift);
            Holiday::create(['date' => $date, 'type' => $type, 'name' => 'Hari Libur', 'is_working_day' => false]);
            $effective = $this->service->resolve($this->employee, $date);
            $status = app(AttendanceStatusResolver::class)->resolveEffective($effective, null, null, Carbon::parse($date.' 23:00', config('app.timezone')));

            $this->assertFalse($effective['is_working_day']);
            $this->assertSame($type, $effective['source']);
            $this->assertSame('holiday', $status['key']);
            $this->assertSame('LIBUR', $status['label']);
        }
    }

    public function test_employee_off_override_wins_over_regular_schedule(): void
    {
        $this->schedule('2026-08-17', 'work', $this->dayShift);
        EmployeeScheduleOverride::create([
            'employee_id' => $this->employee->id, 'date' => '2026-08-17',
            'override_type' => 'off', 'reason' => 'Libur khusus karyawan',
        ]);

        $effective = $this->service->resolve($this->employee, '2026-08-17');
        $this->assertFalse($effective['is_working_day']);
        $this->assertSame('employee_override', $effective['source']);
        $this->assertSame('LIBUR KHUSUS', $effective['label']);
    }

    public function test_employee_work_override_wins_over_global_holiday(): void
    {
        Holiday::create(['date' => '2026-08-17', 'type' => 'company_holiday', 'name' => 'Libur Toko', 'is_working_day' => false]);
        EmployeeScheduleOverride::create([
            'employee_id' => $this->employee->id, 'date' => '2026-08-17',
            'override_type' => 'work', 'shift_id' => $this->dayShift->id, 'reason' => 'Tetap masuk operasional',
        ]);

        $effective = $this->service->resolve($this->employee, '2026-08-17');
        $this->assertTrue($effective['is_working_day']);
        $this->assertSame('employee_override', $effective['source']);
        $this->assertTrue($effective['shift']->is($this->dayShift));
    }

    public function test_special_working_day_uses_regular_shift_and_never_guesses_one(): void
    {
        Holiday::create(['date' => '2026-08-17', 'type' => 'special_working_day', 'name' => 'Buka Khusus', 'is_working_day' => true]);
        $withoutShift = $this->service->resolve($this->employee, '2026-08-17');
        $this->assertFalse($withoutShift['is_working_day']);
        $this->assertNull($withoutShift['shift']);

        $this->schedule('2026-08-17', 'work', $this->dayShift);
        $withShift = $this->service->resolve($this->employee, '2026-08-17');
        $this->assertTrue($withShift['is_working_day']);
        $this->assertTrue($withShift['shift']->is($this->dayShift));
    }

    public function test_cross_midnight_resolution_is_anchored_to_original_work_date(): void
    {
        $this->schedule('2026-08-17', 'work', $this->nightShift);
        Holiday::create(['date' => '2026-08-18', 'type' => 'public_holiday', 'name' => 'Libur Besok', 'is_working_day' => false]);

        $effective = $this->service->resolve($this->employee, '2026-08-17');
        $this->assertTrue($effective['is_working_day']);
        $this->assertTrue($effective['shift']->crosses_midnight);
    }

    public function test_report_preserves_historical_attendance_and_excludes_holiday_from_denominator(): void
    {
        $work = $this->schedule('2026-08-17', 'work', $this->dayShift);
        $holidaySchedule = $this->schedule('2026-08-18', 'work', $this->dayShift);
        Holiday::create(['date' => '2026-08-18', 'type' => 'public_holiday', 'name' => 'Hari Nasional', 'is_working_day' => false]);
        AttendanceRecord::create([
            'employee_id' => $this->employee->id, 'work_schedule_id' => $work->id, 'work_date' => '2026-08-17',
            'status' => 'present', 'check_in_at' => '2026-08-17 08:00:00', 'check_out_at' => '2026-08-17 17:00:00', 'worked_minutes' => 480,
        ]);
        $holidayAttendance = AttendanceRecord::create([
            'employee_id' => $this->employee->id, 'work_schedule_id' => $holidaySchedule->id, 'work_date' => '2026-08-18',
            'status' => 'late', 'check_in_at' => '2026-08-18 09:00:00', 'late_minutes' => 60,
        ]);

        $report = app(ReportService::class)->generateAttendanceReport(['start_date' => '2026-08-17', 'end_date' => '2026-08-18']);
        $this->assertSame(1, $report['global_summary']['scheduled_work_days']);
        $this->assertSame(1, $report['global_summary']['holiday_count']);
        $this->assertSame(0, $report['global_summary']['late_count']);
        $this->assertSame(100.0, $report['global_summary']['attendance_rate']);
        $holidayRow = collect($report['detail_rows'])->firstWhere('date_str', '2026-08-18');
        $this->assertSame('holiday', $holidayRow['status_key']);
        $this->assertDatabaseHas('attendance_records', ['id' => $holidayAttendance->id]);
    }

    private function schedule(string $date, string $type, ?Shift $shift = null, ?Outlet $workOutlet = null): EmployeeSchedule
    {
        return EmployeeSchedule::create([
            'employee_id' => $this->employee->id, 'work_date' => $date,
            'schedule_type' => $type, 'shift_id' => $shift?->id, 'work_outlet_id' => $workOutlet?->id,
        ]);
    }
}
