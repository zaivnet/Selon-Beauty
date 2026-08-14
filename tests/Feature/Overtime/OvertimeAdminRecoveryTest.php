<?php

namespace Tests\Feature\Overtime;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\OvertimeRequest;
use App\Models\OvertimeSession;
use App\Models\User;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OvertimeAdminRecoveryTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $employeeUser;

    protected OvertimeRequest $request;

    protected OvertimeSession $session;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-08-14 21:00:00', config('app.timezone')));
        $employee = Employee::create(['employee_code' => 'OTR-01', 'full_name' => 'Rani', 'status' => 'active']);
        $this->employeeUser = User::create(['employee_id' => $employee->id, 'name' => 'Rani', 'email' => 'rani@example.test', 'password' => Hash::make('password'), 'role' => 'employee', 'is_active' => true]);
        $this->admin = User::create(['name' => 'Owner', 'email' => 'owner-otr@example.test', 'password' => Hash::make('password'), 'role' => 'owner', 'is_active' => true]);
        $this->request = OvertimeRequest::create(['employee_id' => $employee->id, 'work_date' => '2026-08-14', 'requested_minutes' => 180, 'approved_minutes' => 120, 'reason' => 'Stock opname', 'status' => 'approved']);
        $this->session = OvertimeSession::create([
            'overtime_request_id' => $this->request->id, 'employee_id' => $employee->id, 'work_date' => '2026-08-14',
            'status' => 'active', 'check_in_at' => '2026-08-14 18:00:00', 'started_at' => '2026-08-14 18:00:00',
            'check_in_selfie_path' => 'overtime/original.jpg',
        ]);
    }

    public function test_force_finish_recalculates_actual_and_capped_credit_with_audit_notification_and_report(): void
    {
        $this->actingAs($this->admin)->post(route('admin.overtime-sessions.force-finish', $this->session), [
            'finish_at' => '2026-08-14T21:00', 'reason' => 'Karyawan lupa selesai lembur',
        ])->assertSessionHas('success');
        $session = $this->session->fresh();
        $this->assertTrue($session->isCompleted());
        $this->assertSame(180, $session->actual_minutes);
        $this->assertSame(120, $session->credited_minutes);
        $this->assertSame('admin_force_finish', $session->completion_source);
        $this->assertSame('overtime/original.jpg', $session->check_in_selfie_path);
        $log = AuditLog::where('action', 'overtime.force_finished')->firstOrFail();
        $this->assertSame('active', $log->before_data['status']);
        $this->assertSame('completed', $log->after_data['status']);
        $notification = $this->employeeUser->notifications()->firstOrFail();
        $this->actingAs($this->employeeUser)->get($notification->data['target_url'])->assertOk();
        $report = app(ReportService::class)->generateAttendanceReport(['start_date' => '2026-08-14', 'end_date' => '2026-08-14', 'employee_id' => $session->employee_id]);
        $this->assertSame(180, $report['detail_rows'][0]['actual_overtime_minutes']);
        $this->assertSame(120, $report['detail_rows'][0]['credited_overtime_minutes']);
    }

    public function test_cross_midnight_force_finish_keeps_work_date(): void
    {
        $this->session->update(['check_in_at' => '2026-08-14 23:00:00', 'started_at' => '2026-08-14 23:00:00']);
        $this->actingAs($this->admin)->post(route('admin.overtime-sessions.force-finish', $this->session), [
            'finish_at' => '2026-08-15T01:00', 'reason' => 'Lupa menutup sesi malam',
        ]);
        $this->assertSame(120, $this->session->fresh()->actual_minutes);
        $this->assertSame('2026-08-14', $this->session->fresh()->work_date->format('Y-m-d'));
    }

    public function test_cancel_active_session_keeps_record_and_sets_actual_and_credit_to_zero(): void
    {
        $this->actingAs($this->admin)->post(route('admin.overtime-sessions.cancel', $this->session), ['reason' => 'Sesi tidak valid'])->assertSessionHas('success');
        $session = $this->session->fresh();
        $this->assertTrue($session->isCancelled());
        $this->assertSame(0, $session->actual_minutes);
        $this->assertSame(0, $session->credited_minutes);
        $this->assertDatabaseCount('overtime_sessions', 1);
        $this->assertDatabaseHas('audit_logs', ['action' => 'overtime.cancelled', 'auditable_id' => $session->id]);
    }

    public function test_completed_session_correction_recalculates_minutes_without_changing_approval(): void
    {
        $this->session->update(['status' => 'completed', 'check_out_at' => '2026-08-14 19:00:00', 'completed_at' => '2026-08-14 19:00:00', 'actual_minutes' => 60, 'credited_minutes' => 60]);
        $this->actingAs($this->admin)->post(route('admin.overtime-sessions.correct', $this->session), [
            'check_in_at' => '2026-08-14T18:00', 'check_out_at' => '2026-08-14T22:00', 'reason' => 'Bukti waktu menunjukkan empat jam',
        ])->assertSessionHas('success');
        $this->assertSame(240, $this->session->fresh()->actual_minutes);
        $this->assertSame(120, $this->session->fresh()->credited_minutes);
        $this->assertSame(120, $this->request->fresh()->approved_minutes);
        $this->assertDatabaseHas('audit_logs', ['action' => 'overtime.corrected']);
    }

    public function test_employee_cannot_use_admin_recovery_routes_and_reason_is_required(): void
    {
        $this->actingAs($this->employeeUser)->post(route('admin.overtime-sessions.force-finish', $this->session), ['finish_at' => '2026-08-14T21:00', 'reason' => 'Tidak berwenang'])->assertRedirect(route('employee.dashboard'));
        $this->actingAs($this->admin)->post(route('admin.overtime-sessions.force-finish', $this->session), ['finish_at' => '2026-08-14T21:00', 'reason' => ''])->assertSessionHasErrors('reason');
        $this->assertTrue($this->session->fresh()->isActive());
    }

    public function test_double_force_finish_is_safe_and_has_one_audit(): void
    {
        $payload = ['finish_at' => '2026-08-14T21:00', 'reason' => 'Karyawan lupa selesai lembur'];
        $this->actingAs($this->admin)->post(route('admin.overtime-sessions.force-finish', $this->session), $payload);
        $this->actingAs($this->admin)->post(route('admin.overtime-sessions.force-finish', $this->session), $payload)->assertSessionHasErrors('session');
        $this->assertSame(1, AuditLog::where('action', 'overtime.force_finished')->count());
        $this->assertDatabaseCount('overtime_sessions', 1);
    }
}
