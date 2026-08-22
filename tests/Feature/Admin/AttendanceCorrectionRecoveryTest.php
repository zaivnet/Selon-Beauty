<?php

namespace Tests\Feature\Admin;

use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Shift;
use App\Models\User;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AttendanceCorrectionRecoveryTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $employeeUser;

    protected AttendanceRecord $attendance;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-08-14 18:00:00', config('app.timezone')));
        $employee = Employee::create(['employee_code' => 'COR-01', 'full_name' => 'Nadia', 'status' => 'active']);
        $this->employeeUser = User::create(['employee_id' => $employee->id, 'name' => 'Nadia', 'email' => 'nadia@example.test', 'password' => Hash::make('password'), 'role' => 'employee', 'is_active' => true]);
        $this->admin = User::create(['name' => 'Admin', 'email' => 'admin-cor@example.test', 'password' => Hash::make('password'), 'role' => 'admin', 'is_active' => true]);
        $this->admin->assignedOutlets()->sync([$this->admin->outlet_id]);
        $shift = Shift::create([
            'name' => 'Pagi', 'code' => 'COR-PAGI', 'start_time' => '08:00', 'end_time' => '17:00',
            'grace_period_minutes' => 5, 'break_minutes' => 60, 'check_in_open_minutes_before' => 60,
            'check_in_close_minutes_after' => 120, 'check_out_open_minutes_before' => 60, 'is_active' => true,
        ]);
        $schedule = EmployeeSchedule::create(['employee_id' => $employee->id, 'work_date' => '2026-08-14', 'shift_id' => $shift->id, 'schedule_type' => 'work']);
        $this->attendance = AttendanceRecord::create([
            'employee_id' => $employee->id, 'work_schedule_id' => $schedule->id, 'work_date' => '2026-08-14',
            'status' => 'present', 'check_in_at' => '2026-08-14 08:00:00', 'check_out_at' => '2026-08-14 17:00:00',
            'worked_minutes' => 480, 'check_in_selfie_path' => 'attendance/original-in.jpg',
        ]);
    }

    public function test_admin_correction_recalculates_status_late_and_worked_minutes_without_changing_evidence(): void
    {
        $this->actingAs($this->admin)->post(route('admin.attendance.correct', $this->attendance), [
            'check_in_at' => '2026-08-14T08:30', 'check_out_at' => '2026-08-14T17:00', 'reason' => 'Jam masuk pada mesin salah',
        ])->assertSessionHas('success');

        $record = $this->attendance->fresh();
        $this->assertSame('late', $record->status);
        $this->assertSame(25, $record->late_minutes);
        $this->assertSame(450, $record->worked_minutes);
        $this->assertTrue($record->is_manually_adjusted);
        $this->assertSame($this->admin->id, $record->corrected_by);
        $this->assertSame('attendance/original-in.jpg', $record->check_in_selfie_path);
    }

    public function test_admin_recovers_missing_checkout_with_specific_audit_before_after_and_notification(): void
    {
        $this->attendance->update(['check_out_at' => null, 'worked_minutes' => 0]);
        $this->actingAs($this->admin)->post(route('admin.attendance.correct', $this->attendance), [
            'check_in_at' => '2026-08-14T08:00', 'check_out_at' => '2026-08-14T17:15', 'reason' => 'Lupa absen pulang',
        ])->assertSessionHas('success');

        $log = AuditLog::where('action', 'attendance.checkout_recovered')->firstOrFail();
        $this->assertNull($log->before_data['check_out_at']);
        $this->assertNotNull($log->after_data['check_out_at']);
        $this->assertSame('Lupa absen pulang', $log->reason);
        $this->assertSame($this->attendance->employee_id, $log->metadata['employee_id']);
        $notification = $this->employeeUser->notifications()->firstOrFail();
        $this->assertSame('Absensi Anda dikoreksi', $notification->data['title']);
        $this->actingAs($this->employeeUser)->get($notification->data['target_url'])
            ->assertOk()
            ->assertSee('Dikoreksi Admin');
    }

    public function test_employee_cannot_correct_and_reason_is_required(): void
    {
        $this->actingAs($this->employeeUser)->post(route('admin.attendance.correct', $this->attendance), [
            'check_in_at' => '2026-08-14T09:00', 'reason' => 'Koreksi ilegal',
        ])->assertRedirect(route('employee.dashboard'));
        $this->actingAs($this->admin)->post(route('admin.attendance.correct', $this->attendance), [
            'check_in_at' => '2026-08-14T09:00', 'reason' => '',
        ])->assertSessionHasErrors('reason');
        $this->assertSame('08:00', $this->attendance->fresh()->check_in_at->format('H:i'));
    }

    public function test_checkout_before_checkin_is_rejected(): void
    {
        $this->actingAs($this->admin)->from(route('admin.attendance.index'))->post(route('admin.attendance.correct', $this->attendance), [
            'check_in_at' => '2026-08-14T10:00', 'check_out_at' => '2026-08-14T09:00', 'reason' => 'Perbaikan waktu salah',
        ])->assertSessionHas('error');
        $this->assertSame('08:00', $this->attendance->fresh()->check_in_at->format('H:i'));
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_report_and_resolver_use_corrected_current_state(): void
    {
        $this->actingAs($this->admin)->post(route('admin.attendance.correct', $this->attendance), [
            'check_in_at' => '2026-08-14T08:20', 'check_out_at' => '2026-08-14T16:30', 'reason' => 'Disesuaikan dengan bukti mesin',
        ]);
        $report = app(ReportService::class)->generateAttendanceReport([
            'start_date' => '2026-08-14', 'end_date' => '2026-08-14', 'employee_id' => $this->attendance->employee_id,
        ]);
        $this->assertSame('late', $report['detail_rows'][0]['status_key']);
        $this->assertSame(15, $report['detail_rows'][0]['late_minutes']);
        $this->assertSame(430, $report['detail_rows'][0]['worked_minutes']);
    }

    public function test_identical_double_submit_does_not_duplicate_audit_or_notification(): void
    {
        $payload = ['check_in_at' => '2026-08-14T08:15', 'check_out_at' => '2026-08-14T17:00', 'reason' => 'Koreksi berdasarkan CCTV'];
        $this->actingAs($this->admin)->post(route('admin.attendance.correct', $this->attendance), $payload);
        $this->actingAs($this->admin)->post(route('admin.attendance.correct', $this->attendance), $payload);
        $this->assertSame(1, AuditLog::where('action', 'attendance.corrected')->count());
        $this->assertSame(1, $this->employeeUser->notifications()->count());
        $this->assertDatabaseCount('attendance_records', 1);
    }
}
