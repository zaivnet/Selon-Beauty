<?php

namespace Tests\Feature\WorkforceParticipation;

use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\OvertimeRequest;
use App\Models\OvertimeSession;
use App\Models\Shift;
use App\Models\User;
use App\Services\AttendanceMonitoringService;
use App\Services\AttendanceParticipationService;
use App\Services\AttendanceService;
use App\Services\EffectiveScheduleService;
use App\Services\EmployeeScheduleService;
use App\Services\LeaveRequestService;
use App\Services\MonthlyAttendanceRecapService;
use App\Services\OperationalExceptionService;
use App\Services\OvertimeRequestService;
use App\Services\OvertimeSessionService;
use App\Services\ReportService;
use App\Services\WorkCalendarService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AttendanceParticipationTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    private Shift $shift;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-08-14 11:00:00', config('app.timezone')));
        $this->actor = $this->user('Owner Pengelola', 'owner-manager@example.test', 'owner');
        $this->shift = Shift::create([
            'name' => 'Partisipasi Pagi', 'code' => 'PART-P', 'start_time' => '08:00', 'end_time' => '17:00',
            'check_in_open_minutes_before' => 60, 'check_in_close_minutes_after' => 120,
            'check_out_open_minutes_before' => 60, 'grace_period_minutes' => 5,
            'break_minutes' => 60, 'is_active' => true,
        ]);
    }

    public function test_defaults_role_independence_and_superadmin_workforce_safety(): void
    {
        $default = Employee::create(['employee_code' => 'PART-DEF', 'full_name' => 'Default On', 'status' => 'active']);
        $ownerOff = $this->employee('PART-OWN-OFF', 'Owner Off', 'owner', false);
        $ownerOn = $this->employee('PART-OWN-ON', 'Owner On', 'owner', true);
        $adminOff = $this->employee('PART-ADM-OFF', 'Admin Off', 'admin', false);
        $adminOn = $this->employee('PART-ADM-ON', 'Admin On', 'admin', true);
        $employeeOff = $this->employee('PART-EMP-OFF', 'Employee Off', 'employee', false);
        $employeeOn = $this->employee('PART-EMP-ON', 'Employee On', 'employee', true);
        $superadmin = $this->employee('PART-SUPER', 'Superadmin Outside', 'superadmin', true);

        $this->assertTrue($default->fresh()->attendance_enabled);
        $this->assertFalse($ownerOff->participatesInAttendance());
        $this->assertTrue($ownerOn->participatesInAttendance());
        $this->assertFalse($adminOff->participatesInAttendance());
        $this->assertTrue($adminOn->participatesInAttendance());
        $this->assertFalse($employeeOff->participatesInAttendance());
        $this->assertTrue($employeeOn->participatesInAttendance());

        $workforceIds = Employee::currentAttendanceWorkforce()->pluck('id');
        $this->assertTrue($workforceIds->contains($ownerOn->id));
        $this->assertTrue($workforceIds->contains($adminOn->id));
        $this->assertTrue($workforceIds->contains($employeeOn->id));
        $this->assertFalse($workforceIds->contains($superadmin->id));
        $this->actingAs($superadmin->user)->get(route('admin.dashboard'))->assertOk();
    }

    public function test_schedule_monitoring_and_summary_use_only_current_workforce(): void
    {
        $ownerOff = $this->employee('PART-S1', 'Owner Administratif', 'owner', false);
        $ownerOn = $this->employee('PART-S2', 'Owner Operasional', 'owner', true);
        $adminOff = $this->employee('PART-S3', 'Admin Administratif', 'admin', false);
        $adminOn = $this->employee('PART-S4', 'Admin Operasional', 'admin', true);
        $employeeOff = $this->employee('PART-S5', 'Employee Non Workforce', 'employee', false);
        $employeeOn = $this->employee('PART-S6', 'Employee Workforce', 'employee', true);
        $superadmin = $this->employee('PART-S7', 'Superadmin Hidden', 'superadmin', true);

        $schedulePage = $this->actingAs($this->actor)->get(route('admin.schedules.index', ['start_date' => '2026-08-10']));
        $attendancePage = $this->actingAs($this->actor)->get(route('admin.attendance.index', ['date' => '2026-08-14']));

        foreach ([$ownerOn, $adminOn, $employeeOn] as $included) {
            $schedulePage->assertSee($included->full_name);
            $attendancePage->assertSee($included->full_name);
        }
        foreach ([$ownerOff, $adminOff, $employeeOff, $superadmin] as $excluded) {
            $schedulePage->assertDontSee($excluded->full_name);
            $attendancePage->assertDontSee($excluded->full_name);
        }
        $metrics = app(AttendanceMonitoringService::class)->getSummaryMetrics('2026-08-14');
        $this->assertSame(3, $metrics['total_employees']);
    }

    public function test_disabled_employee_effective_schedule_is_explicit_and_existing_schedule_returns_after_reenable(): void
    {
        $employee = $this->employee('PART-EFF', 'Effective Disabled', 'employee', false);
        $schedule = $this->schedule($employee, '2026-08-14');
        $service = app(EffectiveScheduleService::class);

        $disabled = $service->resolve($employee, '2026-08-14');
        $this->assertFalse($disabled['participates_in_attendance']);
        $this->assertFalse($disabled['is_working_day']);
        $this->assertSame('attendance_disabled', $disabled['source']);
        $this->assertDatabaseHas('work_schedules', ['id' => $schedule->id]);

        $employee->update(['attendance_enabled' => true]);
        $enabled = $service->resolve($employee->fresh(), '2026-08-14');
        $this->assertTrue($enabled['is_working_day']);
        $this->assertSame('regular_schedule', $enabled['source']);
    }

    public function test_disabled_employee_is_rejected_by_new_attendance_leave_and_overtime_actions(): void
    {
        $employee = $this->employee('PART-BLOCK', 'Blocked Workforce', 'employee', false);
        $message = 'Akun Anda tidak terdaftar sebagai peserta sistem kehadiran.';

        try {
            app(AttendanceService::class)->checkIn($employee->user, []);
            $this->fail('Check-in should be rejected.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertSame($message, $exception->getMessage());
        }
        try {
            app(LeaveRequestService::class)->submitRequest($employee, [
                'type' => 'leave', 'start_date' => '2026-08-15', 'end_date' => '2026-08-15', 'reason' => 'Cuti pribadi',
            ]);
            $this->fail('Leave should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertSame($message, $exception->errors()['attendance'][0]);
        }
        try {
            app(OvertimeRequestService::class)->submitRequest($employee, [
                'work_date' => '2026-08-14', 'requested_minutes' => 60, 'reason' => 'Lembur tugas',
            ]);
            $this->fail('Overtime request should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertSame($message, $exception->errors()['attendance'][0]);
        }

        $request = OvertimeRequest::create([
            'employee_id' => $employee->id, 'work_date' => '2026-08-14', 'requested_minutes' => 60,
            'approved_minutes' => 60, 'reason' => 'Existing approved request', 'status' => 'approved',
        ]);
        try {
            app(OvertimeSessionService::class)->start($employee->user, $request->id, []);
            $this->fail('Overtime start should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertSame($message, $exception->errors()['overtime'][0]);
        }
    }

    public function test_schedule_and_override_creation_are_rejected_for_disabled_employee(): void
    {
        $employee = $this->employee('PART-ASSIGN', 'Disabled Assignment', 'employee', false);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Karyawan tidak terdaftar sebagai peserta sistem kehadiran.');
        app(EmployeeScheduleService::class)->assignSchedule([
            'employee_id' => $employee->id, 'work_date' => '2026-08-15',
            'schedule_type' => 'work', 'shift_id' => $this->shift->id,
        ], $this->actor);
    }

    public function test_override_creation_is_rejected_for_disabled_employee(): void
    {
        $employee = $this->employee('PART-OVR', 'Disabled Override', 'employee', false);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Karyawan tidak terdaftar sebagai peserta sistem kehadiran.');
        app(WorkCalendarService::class)->saveOverride([
            'employee_id' => $employee->id, 'date' => '2026-08-15',
            'override_type' => 'work', 'shift_id' => $this->shift->id, 'reason' => 'Masuk khusus',
        ], $this->actor);
    }

    public function test_participation_change_requires_reason_audits_notifies_and_ignores_noop(): void
    {
        $employee = $this->employee('PART-CHANGE', 'Change Participation', 'employee', true);
        $service = app(AttendanceParticipationService::class);

        try {
            $service->update($employee, false, '', $this->actor);
            $this->fail('Reason should be required.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('attendance_participation_reason', $exception->errors());
        }

        $service->update($employee, false, 'Akun hanya untuk administrasi.', $this->actor);
        $this->assertFalse($employee->fresh()->attendance_enabled);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'employee.attendance_disabled', 'auditable_id' => $employee->id,
            'reason' => 'Akun hanya untuk administrasi.',
        ]);
        $this->assertSame('attendance_participation_disabled', $employee->user->notifications()->latest()->first()->data['type']);

        $auditCount = AuditLog::where('auditable_type', Employee::class)->where('auditable_id', $employee->id)->count();
        $notificationCount = $employee->user->notifications()->count();
        $service->update($employee->fresh(), false, null, $this->actor);
        $this->assertSame($auditCount, AuditLog::where('auditable_type', Employee::class)->where('auditable_id', $employee->id)->count());
        $this->assertSame($notificationCount, $employee->user->notifications()->count());

        $service->update($employee->fresh(), true, 'Kembali bertugas operasional.', $this->actor);
        $this->assertDatabaseHas('audit_logs', ['action' => 'employee.attendance_enabled', 'auditable_id' => $employee->id]);
        $this->assertTrue($employee->user->notifications()->get()->contains(
            fn ($notification) => $notification->data['type'] === 'attendance_participation_enabled'
        ));
    }

    public function test_cannot_disable_with_open_attendance_or_active_overtime(): void
    {
        $employee = $this->employee('PART-ACTIVE', 'Active Workforce', 'employee', true);
        AttendanceRecord::create([
            'employee_id' => $employee->id, 'work_date' => '2026-08-14', 'status' => 'present',
            'check_in_at' => '2026-08-14 08:00:00',
        ]);
        $service = app(AttendanceParticipationService::class);

        try {
            $service->update($employee, false, 'Pindah ke administrasi.', $this->actor);
            $this->fail('Open attendance should block disabling.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('Selesaikan absensi/lembur aktif', $exception->errors()['attendance_enabled'][0]);
        }

        AttendanceRecord::query()->update(['check_out_at' => '2026-08-14 17:00:00']);
        $request = OvertimeRequest::create([
            'employee_id' => $employee->id, 'work_date' => '2026-08-14', 'requested_minutes' => 60,
            'approved_minutes' => 60, 'reason' => 'Lembur aktif', 'status' => 'approved',
        ]);
        OvertimeSession::create([
            'overtime_request_id' => $request->id, 'employee_id' => $employee->id,
            'work_date' => '2026-08-14', 'status' => 'active', 'check_in_at' => '2026-08-14 18:00:00',
        ]);

        $this->expectException(ValidationException::class);
        $service->update($employee->fresh(), false, 'Pindah ke administrasi.', $this->actor);
    }

    public function test_operational_exceptions_and_current_recap_report_exclude_disabled_but_explicit_history_remains(): void
    {
        $employee = $this->employee('PART-HIST', 'Historical Disabled', 'employee', false);
        $schedule = $this->schedule($employee, '2026-08-14');
        AttendanceRecord::create([
            'employee_id' => $employee->id, 'work_schedule_id' => $schedule->id,
            'work_date' => '2026-08-14', 'status' => 'present',
            'check_in_at' => '2026-08-14 08:00:00', 'check_out_at' => '2026-08-14 17:00:00',
            'worked_minutes' => 480,
        ]);

        $exceptions = app(OperationalExceptionService::class)->generate('2026-08-14', [], Carbon::now());
        $this->assertNotContains($employee->id, collect($exceptions['items'])->pluck('employee_id'));

        $monthly = app(MonthlyAttendanceRecapService::class);
        $this->assertSame([], collect($monthly->generate(2026, 8)['recaps'])->pluck('employee.id')->all());
        $historicalRecap = $monthly->generate(2026, 8, ['employee_id' => $employee->id]);
        $this->assertSame(1, $historicalRecap['recaps'][0]['summary']['present_days']);

        $report = app(ReportService::class);
        $this->assertSame(0, $report->generateAttendanceReport([
            'start_date' => '2026-08-14', 'end_date' => '2026-08-14',
        ])['global_summary']['present_count']);
        $this->assertSame(1, $report->generateAttendanceReport([
            'start_date' => '2026-08-14', 'end_date' => '2026-08-14', 'employee_id' => $employee->id,
        ])['global_summary']['present_count']);

        $this->actingAs($this->actor)->get(route('admin.monthly-recaps.index'))->assertDontSee($employee->full_name);
        $this->actingAs($this->actor)->get(route('admin.reports.attendance'))->assertDontSee($employee->full_name);
    }

    public function test_historical_attendance_correction_and_overtime_recovery_remain_allowed(): void
    {
        $employee = $this->employee('PART-RECOVERY', 'Historical Recovery', 'employee', false);
        $schedule = $this->schedule($employee, '2026-08-13');
        $attendance = AttendanceRecord::create([
            'employee_id' => $employee->id, 'work_schedule_id' => $schedule->id,
            'work_date' => '2026-08-13', 'status' => 'present', 'check_in_at' => '2026-08-13 08:00:00',
        ]);
        $corrected = app(AttendanceService::class)->correctAttendanceRecord(
            $attendance, '08:00', '17:00', 'Lupa checkout historis', $this->actor,
        );
        $this->assertNotNull($corrected->check_out_at);

        $request = OvertimeRequest::create([
            'employee_id' => $employee->id, 'work_date' => '2026-08-13', 'requested_minutes' => 60,
            'approved_minutes' => 60, 'reason' => 'Lembur historis', 'status' => 'approved',
        ]);
        $session = OvertimeSession::create([
            'overtime_request_id' => $request->id, 'employee_id' => $employee->id,
            'work_date' => '2026-08-13', 'status' => 'active', 'check_in_at' => '2026-08-13 18:00:00',
        ]);
        $finished = app(OvertimeSessionService::class)->forceFinish(
            $this->actor, $session, '2026-08-13 19:00:00', 'Pulihkan sesi historis',
        );
        $this->assertSame('completed', $finished->status);
    }

    public function test_create_edit_detail_and_disabled_employee_mobile_state_render(): void
    {
        $employee = $this->employee('PART-UI', 'UI Disabled', 'employee', false);

        $this->actingAs($this->actor)->get(route('admin.employees.create'))
            ->assertOk()->assertSee('attendance_enabled', false)->assertSee('Sistem Kehadiran');
        $this->actingAs($this->actor)->get(route('admin.employees.edit', $employee))
            ->assertOk()->assertSee('attendance_participation_reason', false)->assertSee('min-h-[44px]', false);
        $this->actingAs($this->actor)->get(route('admin.employees.show', $employee))
            ->assertOk()->assertSee('Tidak Ikut Absensi');
        $this->actingAs($employee->user)->get(route('employee.dashboard'))
            ->assertOk()->assertSee('Akun ini tidak diwajibkan mengikuti sistem kehadiran.')
            ->assertDontSee('Absen Masuk')->assertDontSee('Izin & Cuti');
    }

    private function employee(string $code, string $name, string $role, bool $enabled): Employee
    {
        $employee = Employee::create([
            'employee_code' => $code, 'full_name' => $name, 'status' => 'active',
            'attendance_enabled' => $enabled,
        ]);
        $employee->setRelation('user', $this->user($name, strtolower($code).'@example.test', $role, $employee->id));

        return $employee;
    }

    private function user(string $name, string $email, string $role, ?int $employeeId = null): User
    {
        return User::create([
            'employee_id' => $employeeId, 'name' => $name, 'email' => $email,
            'password' => Hash::make('password'), 'role' => $role, 'is_active' => true,
        ]);
    }

    private function schedule(Employee $employee, string $date): EmployeeSchedule
    {
        return EmployeeSchedule::create([
            'employee_id' => $employee->id, 'work_date' => $date,
            'shift_id' => $this->shift->id, 'schedule_type' => 'work',
        ]);
    }
}
