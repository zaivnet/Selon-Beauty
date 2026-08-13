<?php

namespace Tests\Feature\Overtime;

use App\Models\AppSetting;
use App\Models\AttendanceLocation;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\OvertimeRequest;
use App\Models\OvertimeSession;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OvertimeSessionTest extends TestCase
{
    use RefreshDatabase;

    protected Employee $employee;

    protected User $user;

    protected User $otherUser;

    protected EmployeeSchedule $schedule;

    protected AttendanceRecord $attendance;

    protected OvertimeRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Carbon::setTestNow(Carbon::parse('2026-08-13 18:00:00', 'Asia/Jakarta'));
        AppSetting::set('attendance_require_selfie', false, 'boolean');

        $this->employee = Employee::create(['employee_code' => 'OT-001', 'full_name' => 'Ayu', 'status' => 'active']);
        $this->user = User::create([
            'employee_id' => $this->employee->id, 'name' => 'Ayu', 'email' => 'ayu-ot@example.test',
            'password' => Hash::make('password123'), 'role' => 'employee', 'is_active' => true,
        ]);
        $otherEmployee = Employee::create(['employee_code' => 'OT-002', 'full_name' => 'Budi', 'status' => 'active']);
        $this->otherUser = User::create([
            'employee_id' => $otherEmployee->id, 'name' => 'Budi', 'email' => 'budi-ot@example.test',
            'password' => Hash::make('password123'), 'role' => 'employee', 'is_active' => true,
        ]);
        AttendanceLocation::create([
            'name' => 'Store', 'latitude' => -6.2, 'longitude' => 106.816666,
            'radius_meters' => 100, 'max_accuracy_meters' => 100, 'is_active' => true,
        ]);
        $shift = Shift::create([
            'name' => 'Pagi', 'code' => 'PAGI-OT', 'start_time' => '08:00', 'end_time' => '17:00',
            'check_in_open_minutes_before' => 60, 'check_in_close_minutes_after' => 120,
            'check_out_open_minutes_before' => 60, 'grace_period_minutes' => 5,
            'break_minutes' => 60, 'is_active' => true,
        ]);
        $this->schedule = EmployeeSchedule::create([
            'employee_id' => $this->employee->id, 'work_date' => '2026-08-13',
            'shift_id' => $shift->id, 'schedule_type' => 'work',
        ]);
        $this->attendance = AttendanceRecord::create([
            'employee_id' => $this->employee->id, 'work_schedule_id' => $this->schedule->id,
            'work_date' => '2026-08-13', 'status' => 'present',
            'check_in_at' => Carbon::parse('2026-08-13 08:00:00', 'Asia/Jakarta'),
            'check_out_at' => Carbon::parse('2026-08-13 17:00:00', 'Asia/Jakarta'),
        ]);
        $this->request = OvertimeRequest::create([
            'employee_id' => $this->employee->id, 'work_date' => '2026-08-13',
            'requested_minutes' => 360, 'approved_minutes' => 360,
            'reason' => 'Stock opname', 'status' => 'approved',
        ]);
    }

    protected function gps(): array
    {
        return ['latitude' => -6.2, 'longitude' => 106.816666, 'accuracy' => 10];
    }

    public function test_model_relations_and_approved_request_can_start_after_regular_checkout(): void
    {
        $this->actingAs($this->user)->post(route('employee.overtime-requests.start', $this->request), $this->gps());

        $session = OvertimeSession::firstOrFail();
        $this->assertTrue($session->isActive());
        $this->assertTrue($session->overtimeRequest->is($this->request));
        $this->assertTrue($session->employee->is($this->employee));
        $this->assertTrue($session->schedule->is($this->schedule));
        $this->assertTrue($this->request->fresh()->session->is($session));
        $this->assertTrue($this->employee->overtimeSessions->first()->is($session));
        $this->assertNull($session->check_in_selfie_path);
        $this->assertSame(1, AttendanceRecord::count());
    }

    public function test_regular_attendance_must_be_checked_out_before_start(): void
    {
        $this->attendance->update(['check_out_at' => null]);

        $this->actingAs($this->user)->post(route('employee.overtime-requests.start', $this->request), $this->gps())
            ->assertSessionHasErrors('overtime');

        $this->assertDatabaseCount('overtime_sessions', 0);
    }

    public function test_pending_and_rejected_requests_cannot_start(): void
    {
        foreach (['pending', 'rejected'] as $status) {
            $this->request->update(['status' => $status]);
            $this->actingAs($this->user)->post(route('employee.overtime-requests.start', $this->request), $this->gps())
                ->assertSessionHasErrors('overtime');
            $this->assertDatabaseCount('overtime_sessions', 0);
        }
    }

    public function test_actual_below_approved_is_fully_credited(): void
    {
        $this->actingAs($this->user)->post(route('employee.overtime-requests.start', $this->request), $this->gps());
        Carbon::setTestNow(Carbon::parse('2026-08-13 22:00:00', 'Asia/Jakarta'));
        $session = OvertimeSession::firstOrFail();

        $this->actingAs($this->user)->post(route('employee.overtime-sessions.finish', $session), $this->gps());

        $session->refresh();
        $this->assertTrue($session->isCompleted());
        $this->assertSame(240, $session->actual_minutes);
        $this->assertSame(240, $session->credited_minutes);
    }

    public function test_actual_above_approved_is_capped_only_for_credit(): void
    {
        $this->actingAs($this->user)->post(route('employee.overtime-requests.start', $this->request), $this->gps());
        Carbon::setTestNow(Carbon::parse('2026-08-14 01:00:00', 'Asia/Jakarta'));
        $session = OvertimeSession::firstOrFail();

        $this->actingAs($this->user)->post(route('employee.overtime-sessions.finish', $session), $this->gps());

        $session->refresh();
        $this->assertSame(420, $session->actual_minutes);
        $this->assertSame(360, $session->credited_minutes);
        $this->assertSame('2026-08-13', $session->work_date->format('Y-m-d'));
    }

    public function test_cross_midnight_session_keeps_original_work_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-13 23:00:00', 'Asia/Jakarta'));
        $this->actingAs($this->user)->post(route('employee.overtime-requests.start', $this->request), $this->gps());
        Carbon::setTestNow(Carbon::parse('2026-08-14 02:00:00', 'Asia/Jakarta'));
        $session = OvertimeSession::firstOrFail();
        $this->actingAs($this->user)->post(route('employee.overtime-sessions.finish', $session), $this->gps());

        $session->refresh();
        $this->assertSame(180, $session->actual_minutes);
        $this->assertSame('2026-08-13', $session->work_date->format('Y-m-d'));
    }

    public function test_double_start_and_double_finish_are_idempotently_rejected(): void
    {
        $this->actingAs($this->user)->post(route('employee.overtime-requests.start', $this->request), $this->gps());
        $this->actingAs($this->user)->post(route('employee.overtime-requests.start', $this->request), $this->gps())
            ->assertSessionHasErrors('overtime');
        $this->assertDatabaseCount('overtime_sessions', 1);

        $session = OvertimeSession::firstOrFail();
        Carbon::setTestNow(Carbon::parse('2026-08-13 19:00:00', 'Asia/Jakarta'));
        $this->actingAs($this->user)->post(route('employee.overtime-sessions.finish', $session), $this->gps());
        $this->actingAs($this->user)->post(route('employee.overtime-sessions.finish', $session), $this->gps())
            ->assertSessionHasErrors('overtime');
        $this->assertSame(60, $session->fresh()->actual_minutes);
    }

    public function test_other_employee_cannot_start_or_finish_session(): void
    {
        $this->actingAs($this->otherUser)->post(route('employee.overtime-requests.start', $this->request), $this->gps())
            ->assertSessionHasErrors('overtime');
        $this->actingAs($this->user)->post(route('employee.overtime-requests.start', $this->request), $this->gps());
        $session = OvertimeSession::firstOrFail();

        $this->actingAs($this->otherUser)->post(route('employee.overtime-sessions.finish', $session), $this->gps())
            ->assertSessionHasErrors('overtime');
        $this->assertTrue($session->fresh()->isActive());
    }

    public function test_geofence_and_selfie_setting_are_enforced_without_orphans(): void
    {
        $this->actingAs($this->user)->post(route('employee.overtime-requests.start', $this->request), [
            'latitude' => -6.3, 'longitude' => 106.816666, 'accuracy' => 10,
        ])->assertSessionHas('error');
        $this->assertSame([], Storage::disk('local')->allFiles());

        AppSetting::set('attendance_require_selfie', true, 'boolean');
        $this->actingAs($this->user)->post(route('employee.overtime-requests.start', $this->request), $this->gps())
            ->assertSessionHas('error');
        $this->actingAs($this->user)->post(route('employee.overtime-requests.start', $this->request), array_merge($this->gps(), [
            'selfie' => UploadedFile::fake()->image('overtime-start.jpg'),
        ]));

        $session = OvertimeSession::firstOrFail();
        $this->assertStringStartsWith("overtime/{$this->employee->id}/", $session->check_in_selfie_path);
        Storage::disk('local')->assertExists($session->check_in_selfie_path);

        Carbon::setTestNow(Carbon::parse('2026-08-13 19:00:00', 'Asia/Jakarta'));
        $this->actingAs($this->user)->post(route('employee.overtime-sessions.finish', $session), array_merge($this->gps(), [
            'selfie' => UploadedFile::fake()->image('overtime-finish.jpg'),
        ]));
        $session->refresh();
        Storage::disk('local')->assertExists($session->check_out_selfie_path);
    }

    public function test_overtime_evidence_is_private_and_role_authorized(): void
    {
        AppSetting::set('attendance_require_selfie', true, 'boolean');
        $this->actingAs($this->user)->post(route('employee.overtime-requests.start', $this->request), array_merge($this->gps(), [
            'selfie' => UploadedFile::fake()->image('private-overtime.jpg'),
        ]));
        $session = OvertimeSession::firstOrFail();
        $superadmin = User::create([
            'name' => 'Superadmin', 'email' => 'super-ot@example.test',
            'password' => Hash::make('password123'), 'role' => 'superadmin', 'is_active' => true,
        ]);

        $this->actingAs($this->user)->get(route('overtime-sessions.selfie', [$session, 'check_in']))->assertOk();
        $this->actingAs($this->otherUser)->get(route('overtime-sessions.selfie', [$session, 'check_in']))->assertForbidden();
        $this->actingAs($superadmin)->get(route('overtime-sessions.selfie', [$session, 'check_in']))->assertOk();
    }

    public function test_new_selfie_is_removed_when_database_transaction_fails(): void
    {
        $this->withoutExceptionHandling();
        AppSetting::set('attendance_require_selfie', true, 'boolean');
        OvertimeSession::created(function (): void {
            throw new \RuntimeException('Simulated database workflow failure');
        });

        try {
            $this->actingAs($this->user)->post(route('employee.overtime-requests.start', $this->request), array_merge($this->gps(), [
                'selfie' => UploadedFile::fake()->image('orphan-overtime.jpg'),
            ]));
            $this->fail('Expected the simulated failure to be thrown.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated database workflow failure', $exception->getMessage());
        }

        $this->assertDatabaseCount('overtime_sessions', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('overtime'));
    }
}
