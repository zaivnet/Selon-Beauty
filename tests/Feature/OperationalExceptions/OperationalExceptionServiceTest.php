<?php

namespace Tests\Feature\OperationalExceptions;

use App\Models\AppSetting;
use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\BackupRecord;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\EmployeeScheduleOverride;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\OvertimeSession;
use App\Models\Shift;
use App\Models\User;
use App\Services\OperationalExceptionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OperationalExceptionServiceTest extends TestCase
{
    use RefreshDatabase;

    private Employee $employee;

    private Shift $shift;

    private OperationalExceptionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-08-14 09:00:00', config('app.timezone')));
        $this->employee = Employee::create([
            'employee_code' => 'OPS-001', 'full_name' => 'Ayu Operasional', 'status' => 'active',
        ]);
        $this->shift = Shift::create([
            'name' => 'Pagi', 'code' => 'OPS-P', 'start_time' => '08:00', 'end_time' => '17:00',
            'check_in_open_minutes_before' => 60, 'check_in_close_minutes_after' => 120,
            'check_out_open_minutes_before' => 60, 'grace_period_minutes' => 5,
            'break_minutes' => 60, 'is_active' => true,
        ]);
        $this->service = app(OperationalExceptionService::class);
    }

    public function test_check_in_window_transitions_from_not_started_to_pending_to_absent(): void
    {
        $this->schedule('2026-08-14');

        $before = $this->generate('2026-08-14 06:59:00');
        $open = $this->generate('2026-08-14 07:30:00');
        $closed = $this->generate('2026-08-14 10:01:00');

        $this->assertArrayNotHasKey('pending_check_in', $before['groups']);
        $this->assertArrayNotHasKey('absent', $before['groups']);
        $this->assertSame(1, $open['groups']['pending_check_in']['count']);
        $this->assertSame('warning', $open['groups']['pending_check_in']['severity']);
        $this->assertSame(1, $closed['groups']['absent']['count']);
        $this->assertSame('critical', $closed['groups']['absent']['severity']);
    }

    public function test_leave_holiday_and_off_override_are_excluded_from_attendance_exceptions(): void
    {
        $this->schedule('2026-08-14');
        LeaveRequest::create([
            'employee_id' => $this->employee->id, 'type' => 'leave', 'start_date' => '2026-08-14',
            'end_date' => '2026-08-14', 'reason' => 'Cuti', 'status' => 'approved',
        ]);
        $leave = $this->generate('2026-08-14 10:30:00');
        $this->assertArrayNotHasKey('absent', $leave['groups']);

        LeaveRequest::query()->delete();
        Holiday::create([
            'date' => '2026-08-14', 'type' => 'company_holiday', 'name' => 'Libur Toko', 'is_working_day' => false,
        ]);
        $holiday = $this->generate('2026-08-14 10:30:00');
        $this->assertArrayNotHasKey('absent', $holiday['groups']);

        EmployeeScheduleOverride::create([
            'employee_id' => $this->employee->id, 'date' => '2026-08-14',
            'override_type' => 'off', 'reason' => 'Libur khusus',
        ]);
        $override = $this->generate('2026-08-14 10:30:00');
        $this->assertArrayNotHasKey('absent', $override['groups']);
        $this->assertSame(1, $override['groups']['schedule_override']['count']);
    }

    public function test_work_override_wins_over_holiday_and_becomes_pending_check_in(): void
    {
        Holiday::create([
            'date' => '2026-08-14', 'type' => 'public_holiday', 'name' => 'Libur Umum', 'is_working_day' => false,
        ]);
        EmployeeScheduleOverride::create([
            'employee_id' => $this->employee->id, 'date' => '2026-08-14',
            'override_type' => 'work', 'shift_id' => $this->shift->id, 'reason' => 'Masuk khusus',
        ]);

        $data = $this->generate('2026-08-14 07:30:00');

        $this->assertSame(1, $data['groups']['pending_check_in']['count']);
        $this->assertSame(1, $data['groups']['schedule_override']['count']);
        $this->assertSame('Pagi', $data['groups']['schedule_override']['items'][0]['data']['effective_shift']);
    }

    public function test_work_override_shift_drives_missing_checkout_window(): void
    {
        EmployeeScheduleOverride::create([
            'employee_id' => $this->employee->id, 'date' => '2026-08-14',
            'override_type' => 'work', 'shift_id' => $this->shift->id, 'reason' => 'Masuk khusus',
        ]);
        AttendanceRecord::create([
            'employee_id' => $this->employee->id, 'work_date' => '2026-08-14',
            'status' => 'present', 'check_in_at' => '2026-08-14 08:00:00',
        ]);

        $item = $this->generate('2026-08-14 16:30:00')['groups']['missing_checkout']['items'][0];

        $this->assertSame('Pagi', $item['data']['shift_name']);
        $this->assertSame('2026-08-14', $item['data']['work_date']);
    }

    public function test_late_and_missing_checkout_use_current_attendance_values(): void
    {
        $schedule = $this->schedule('2026-08-14');
        AttendanceRecord::create([
            'employee_id' => $this->employee->id, 'work_schedule_id' => $schedule->id,
            'work_date' => '2026-08-14', 'status' => 'late',
            'check_in_at' => '2026-08-14 08:30:00', 'late_minutes' => 30,
        ]);

        $data = $this->generate('2026-08-14 16:30:00');

        $this->assertSame(30, $data['groups']['late']['items'][0]['data']['late_minutes']);
        $this->assertSame(1, $data['groups']['missing_checkout']['count']);
        $this->assertSame('warning', $data['groups']['missing_checkout']['items'][0]['severity']);
        $this->assertSame(1, $data['groups']['attendance_needs_review']['count']);

        $overdue = $this->generate('2026-08-14 19:01:00');
        $this->assertSame('critical', $overdue['groups']['missing_checkout']['items'][0]['severity']);
    }

    public function test_open_attendance_before_checkout_window_is_not_prematurely_flagged(): void
    {
        $schedule = $this->schedule('2026-08-14');
        AttendanceRecord::create([
            'employee_id' => $this->employee->id, 'work_schedule_id' => $schedule->id,
            'work_date' => '2026-08-14', 'status' => 'present', 'check_in_at' => '2026-08-14 08:00:00',
        ]);

        $data = $this->generate('2026-08-14 09:00:00');

        $this->assertArrayNotHasKey('missing_checkout', $data['groups']);
        $this->assertArrayNotHasKey('attendance_needs_review', $data['groups']);
    }

    public function test_cross_midnight_missing_checkout_is_anchored_to_previous_work_date(): void
    {
        $night = Shift::create([
            'name' => 'Malam', 'code' => 'OPS-N', 'start_time' => '22:00', 'end_time' => '06:00',
            'check_in_open_minutes_before' => 60, 'check_in_close_minutes_after' => 120,
            'check_out_open_minutes_before' => 60, 'crosses_midnight' => true, 'is_active' => true,
        ]);
        $schedule = $this->schedule('2026-08-13', $night);
        AttendanceRecord::create([
            'employee_id' => $this->employee->id, 'work_schedule_id' => $schedule->id,
            'work_date' => '2026-08-13', 'status' => 'present', 'check_in_at' => '2026-08-13 22:00:00',
        ]);

        $data = $this->generate('2026-08-14 05:30:00');
        $item = $data['groups']['missing_checkout']['items'][0];

        $this->assertSame('2026-08-13', $item['data']['work_date']);
        $this->assertSame('Malam', $item['data']['shift_name']);
    }

    public function test_missing_historical_schedule_reuses_monthly_review_rule(): void
    {
        $data = $this->service->generate('2026-08-13', [], Carbon::parse('2026-08-14 09:00:00', config('app.timezone')));

        $issues = $data['groups']['attendance_needs_review']['items'][0]['data']['issues'];
        $this->assertContains('missing_schedule', collect($issues)->pluck('code')->all());
    }

    public function test_active_overtime_becomes_critical_after_approved_duration(): void
    {
        $request = $this->overtimeRequest('approved', 60, 60);
        OvertimeSession::create([
            'overtime_request_id' => $request->id, 'employee_id' => $this->employee->id,
            'work_date' => '2026-08-14', 'status' => 'active', 'check_in_at' => '2026-08-14 07:30:00',
        ]);

        $data = $this->generate('2026-08-14 09:00:00');
        $item = $data['groups']['overtime_active']['items'][0];

        $this->assertSame('critical', $item['severity']);
        $this->assertSame(90, $item['data']['elapsed_minutes']);
        $this->assertSame(0, $item['data']['remaining_minutes']);
    }

    public function test_active_overtime_within_approved_duration_is_informational(): void
    {
        $request = $this->overtimeRequest('approved', 120, 120);
        OvertimeSession::create([
            'overtime_request_id' => $request->id, 'employee_id' => $this->employee->id,
            'work_date' => '2026-08-14', 'status' => 'active', 'check_in_at' => '2026-08-14 08:30:00',
        ]);

        $item = $this->generate('2026-08-14 09:00:00')['groups']['overtime_active']['items'][0];

        $this->assertSame('info', $item['severity']);
        $this->assertSame(30, $item['data']['elapsed_minutes']);
        $this->assertSame(90, $item['data']['remaining_minutes']);
    }

    public function test_approved_overtime_without_session_is_listed_with_attendance_context(): void
    {
        $this->schedule('2026-08-14');
        $request = $this->overtimeRequest('approved', 120, 90);
        $request->update(['reviewed_at' => '2026-08-14 07:00:00']);

        $data = $this->generate('2026-08-14 09:00:00');
        $item = $data['groups']['overtime_approved_not_started']['items'][0];

        $this->assertSame(120, $item['data']['requested_minutes']);
        $this->assertSame(90, $item['data']['approved_minutes']);
        $this->assertSame('BELUM CHECK-IN', $item['data']['attendance_status']);
    }

    public function test_pending_leave_and_overtime_are_prioritized_as_approvals(): void
    {
        LeaveRequest::create([
            'employee_id' => $this->employee->id, 'type' => 'sick', 'start_date' => '2026-08-14',
            'end_date' => '2026-08-14', 'reason' => 'Sakit', 'status' => 'pending',
            'created_at' => '2026-08-13 08:00:00', 'updated_at' => '2026-08-13 08:00:00',
        ]);
        $this->overtimeRequest('pending', 60, null);

        $data = $this->generate('2026-08-14 09:00:00');

        $this->assertSame('warning', $data['groups']['pending_leave']['items'][0]['severity']);
        $this->assertSame('warning', $data['groups']['pending_overtime']['items'][0]['severity']);
        $this->assertSame(2, $data['summary']['pending_approval']);
    }

    public function test_recent_attendance_correction_is_informational_and_links_to_monitoring(): void
    {
        $admin = User::create([
            'name' => 'Admin Ops', 'email' => 'admin-ops@example.test', 'password' => Hash::make('password'),
            'role' => 'admin', 'is_active' => true,
        ]);
        AuditLog::create([
            'user_id' => $admin->id, 'action' => 'attendance.corrected',
            'auditable_type' => AttendanceRecord::class, 'auditable_id' => 99,
            'reason' => 'Jam masuk dikoreksi', 'metadata' => [
                'employee_id' => $this->employee->id, 'changed_fields' => ['check_in_at'],
            ], 'created_at' => '2026-08-14 08:00:00',
        ]);

        $data = $this->generate('2026-08-14 09:00:00');
        $item = $data['groups']['recent_correction']['items'][0];

        $this->assertSame('info', $item['severity']);
        $this->assertSame('Admin Ops', $item['data']['actor']);
        $this->assertStringContainsString('/admin/attendance', $item['action_url']);
    }

    public function test_backup_health_is_healthy_after_recent_scheduled_success_and_critical_after_failure(): void
    {
        AppSetting::set('backup_scheduled_enabled', '1', 'boolean');
        AppSetting::set('backup_scheduled_frequency', 'daily');
        $this->backup('completed', '2026-08-14 02:00:00');

        $healthy = $this->service->generate('2026-08-14', ['include_backup_health' => true], Carbon::parse('2026-08-14 09:00:00', config('app.timezone')));
        $this->assertSame('info', $healthy['backup_health']['severity']);
        $this->assertArrayNotHasKey('backup_scheduler_issue', $healthy['groups']);

        $this->backup('failed', '2026-08-14 08:00:00');
        $failed = $this->service->generate('2026-08-14', ['include_backup_health' => true], Carbon::parse('2026-08-14 09:00:00', config('app.timezone')));
        $this->assertSame('critical', $failed['backup_health']['severity']);
        $this->assertSame(1, $failed['groups']['backup_scheduler_issue']['count']);
    }

    public function test_filters_and_empty_state_are_deterministic(): void
    {
        $this->schedule('2026-08-14');
        $warning = $this->service->generate('2026-08-14', [
            'severity' => 'warning', 'category' => 'pending_check_in',
        ], Carbon::parse('2026-08-14 07:30:00', config('app.timezone')));
        $this->assertSame(['pending_check_in'], array_keys($warning['groups']));
        $this->assertSame(1, $warning['summary']['warning']);

        EmployeeSchedule::query()->update(['schedule_type' => 'off', 'shift_id' => null]);
        $empty = $this->generate('2026-08-14 09:00:00');
        $this->assertSame(0, $empty['summary']['total']);
        $this->assertSame([], $empty['groups']);
    }

    public function test_query_count_is_fixed_not_per_employee_or_category(): void
    {
        $other = Employee::create(['employee_code' => 'OPS-002', 'full_name' => 'Budi Operasional', 'status' => 'active']);
        foreach ([$this->employee, $other] as $employee) {
            EmployeeSchedule::create([
                'employee_id' => $employee->id, 'work_date' => '2026-08-14',
                'shift_id' => $this->shift->id, 'schedule_type' => 'work',
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $data = $this->generate('2026-08-14 07:30:00');
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(2, $data['groups']['pending_check_in']['count']);
        $this->assertLessThanOrEqual(25, $queryCount);
    }

    private function generate(string $now): array
    {
        $carbon = Carbon::parse($now, config('app.timezone'));

        return $this->service->generate($carbon->toDateString(), [], $carbon);
    }

    private function schedule(string $date, ?Shift $shift = null): EmployeeSchedule
    {
        $shift ??= $this->shift;

        return EmployeeSchedule::create([
            'employee_id' => $this->employee->id, 'work_date' => $date,
            'shift_id' => $shift->id, 'schedule_type' => 'work',
        ]);
    }

    private function overtimeRequest(string $status, int $requested, ?int $approved): OvertimeRequest
    {
        return OvertimeRequest::create([
            'employee_id' => $this->employee->id, 'work_date' => '2026-08-14',
            'requested_minutes' => $requested, 'approved_minutes' => $approved,
            'reason' => 'Keperluan operasional', 'status' => $status,
        ]);
    }

    private function backup(string $status, string $createdAt): BackupRecord
    {
        $record = BackupRecord::create([
            'backup_uuid' => uniqid('ops-', true), 'type' => 'full', 'file_path' => 'private/backups/test.zip',
            'file_size' => 100, 'checksum' => 'abc', 'status' => $status, 'created_by' => null,
            'is_pre_restore' => false,
        ]);
        DB::table('backup_records')->where('id', $record->id)->update([
            'created_at' => $createdAt, 'updated_at' => $createdAt,
        ]);

        return $record->fresh();
    }
}
