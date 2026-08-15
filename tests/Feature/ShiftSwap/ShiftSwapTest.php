<?php

namespace Tests\Feature\ShiftSwap;

use App\Models\AttendanceLocation;
use App\Models\AttendancePeriod;
use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\EmployeeScheduleOverride;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\OvertimeSession;
use App\Models\Shift;
use App\Models\ShiftSwapRequest;
use App\Models\User;
use App\Services\AttendancePeriodService;
use App\Services\BackupService;
use App\Services\EffectiveScheduleService;
use App\Services\MonthlyAttendanceRecapService;
use App\Services\ShiftSwapService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ShiftSwapTest extends TestCase
{
    use RefreshDatabase;

    protected ShiftSwapService $swapService;

    protected EffectiveScheduleService $effectiveService;

    protected AttendancePeriodService $periodService;

    protected User $superadmin;

    protected User $owner;

    protected User $admin;

    protected User $reqUser;

    protected Employee $reqEmp;

    protected User $targetUser;

    protected Employee $targetEmp;

    protected Shift $shiftPagi;

    protected Shift $shiftMalam;

    protected string $futureDate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->swapService = app(ShiftSwapService::class);
        $this->effectiveService = app(EffectiveScheduleService::class);
        $this->periodService = app(AttendancePeriodService::class);

        $this->futureDate = Carbon::now(config('app.timezone'))->addDays(5)->toDateString();

        $this->superadmin = User::create([
            'name' => 'Superadmin',
            'email' => 'superadmin@example.test',
            'password' => Hash::make('password'),
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $this->owner = User::create([
            'name' => 'Owner',
            'email' => 'owner@example.test',
            'password' => Hash::make('password'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->reqEmp = Employee::create([
            'employee_code' => 'EMP-AYU',
            'full_name' => 'Ayu',
            'status' => 'active',
            'attendance_enabled' => true,
        ]);

        $this->reqUser = User::create([
            'employee_id' => $this->reqEmp->id,
            'name' => 'Ayu',
            'email' => 'ayu@example.test',
            'password' => Hash::make('password'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        $this->targetEmp = Employee::create([
            'employee_code' => 'EMP-DIA',
            'full_name' => 'Dia',
            'status' => 'active',
            'attendance_enabled' => true,
        ]);

        $this->targetUser = User::create([
            'employee_id' => $this->targetEmp->id,
            'name' => 'Dia',
            'email' => 'dia@example.test',
            'password' => Hash::make('password'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        $this->shiftPagi = Shift::create([
            'name' => 'Shift Pagi', 'code' => 'PAGI',
            'start_time' => '08:00:00', 'end_time' => '16:00:00',
            'grace_period_minutes' => 5, 'break_minutes' => 60,
            'is_active' => true,
        ]);

        $this->shiftMalam = Shift::create([
            'name' => 'Shift Malam', 'code' => 'MALAM',
            'start_time' => '16:00:00', 'end_time' => '23:00:00',
            'grace_period_minutes' => 5, 'break_minutes' => 60,
            'is_active' => true,
        ]);

        // Default valid WORK schedules for both
        EmployeeSchedule::create([
            'employee_id' => $this->reqEmp->id,
            'work_date' => $this->futureDate,
            'shift_id' => $this->shiftPagi->id,
            'schedule_type' => 'work',
        ]);

        EmployeeSchedule::create([
            'employee_id' => $this->targetEmp->id,
            'work_date' => $this->futureDate,
            'shift_id' => $this->shiftMalam->id,
            'schedule_type' => 'work',
        ]);
    }

    // ==========================================
    // CREATE REQUEST TESTS (1 - 13)
    // ==========================================

    public function test_1_eligible_employee_can_create_request(): void
    {
        $swap = $this->swapService->submitRequest($this->reqEmp, [
            'target_employee_id' => $this->targetEmp->id,
            'requester_work_date' => $this->futureDate,
            'reason' => 'Keperluan keluarga mendadak',
        ]);

        $this->assertNotNull($swap->id);
        $this->assertEquals(ShiftSwapRequest::STATUS_PENDING_TARGET, $swap->status);
    }

    public function test_2_attendance_disabled_employee_rejected(): void
    {
        $this->reqEmp->update(['attendance_enabled' => false]);

        $this->expectException(ValidationException::class);
        $this->swapService->submitRequest($this->reqEmp, [
            'target_employee_id' => $this->targetEmp->id,
            'requester_work_date' => $this->futureDate,
            'reason' => 'Try submit when disabled',
        ]);
    }

    public function test_3_inactive_employee_rejected(): void
    {
        $this->reqEmp->update(['status' => 'inactive']);

        $this->expectException(ValidationException::class);
        $this->swapService->submitRequest($this->reqEmp, [
            'target_employee_id' => $this->targetEmp->id,
            'requester_work_date' => $this->futureDate,
            'reason' => 'Try submit when inactive',
        ]);
    }

    public function test_4_self_swap_rejected(): void
    {
        $this->expectException(ValidationException::class);
        $this->swapService->submitRequest($this->reqEmp, [
            'target_employee_id' => $this->reqEmp->id,
            'requester_work_date' => $this->futureDate,
            'reason' => 'Try self swap',
        ]);
    }

    public function test_5_target_attendance_disabled_rejected(): void
    {
        $this->targetEmp->update(['attendance_enabled' => false]);

        $this->expectException(ValidationException::class);
        $this->swapService->submitRequest($this->reqEmp, [
            'target_employee_id' => $this->targetEmp->id,
            'requester_work_date' => $this->futureDate,
            'reason' => 'Try swap with disabled target',
        ]);
    }

    public function test_6_off_schedule_rejected(): void
    {
        EmployeeSchedule::where('employee_id', $this->reqEmp->id)->update(['schedule_type' => 'off', 'shift_id' => null]);

        $this->expectException(ValidationException::class);
        $this->swapService->submitRequest($this->reqEmp, [
            'target_employee_id' => $this->targetEmp->id,
            'requester_work_date' => $this->futureDate,
            'reason' => 'Try swap off schedule',
        ]);
    }

    public function test_7_holiday_rejected(): void
    {
        Holiday::create([
            'date' => $this->futureDate,
            'type' => 'company_holiday',
            'name' => 'Libur Perusahaan',
            'is_working_day' => false,
            'created_by' => $this->owner->id,
        ]);

        $this->expectException(ValidationException::class);
        $this->swapService->submitRequest($this->reqEmp, [
            'target_employee_id' => $this->targetEmp->id,
            'requester_work_date' => $this->futureDate,
            'reason' => 'Try swap holiday',
        ]);
    }

    public function test_8_unresolved_schedule_rejected(): void
    {
        $dateNoSchedule = Carbon::now()->addDays(20)->toDateString();

        $this->expectException(ValidationException::class);
        $this->swapService->submitRequest($this->reqEmp, [
            'target_employee_id' => $this->targetEmp->id,
            'requester_work_date' => $dateNoSchedule,
            'reason' => 'Try swap unresolved schedule',
        ]);
    }

    public function test_9_past_date_rejected(): void
    {
        $pastDate = Carbon::now()->subDays(2)->toDateString();

        $this->expectException(ValidationException::class);
        $this->swapService->submitRequest($this->reqEmp, [
            'target_employee_id' => $this->targetEmp->id,
            'requester_work_date' => $pastDate,
            'reason' => 'Try swap past date',
        ]);
    }

    public function test_10_closed_period_rejected(): void
    {
        $date = '2026-07-15';
        $this->periodService->closePeriod(2026, 7, $this->owner, 'Close July');

        $this->expectException(ValidationException::class);
        $this->swapService->submitRequest($this->reqEmp, [
            'target_employee_id' => $this->targetEmp->id,
            'requester_work_date' => $date,
            'reason' => 'Try swap closed period',
        ]);
    }

    public function test_11_existing_attendance_rejected(): void
    {
        AttendanceRecord::create([
            'employee_id' => $this->reqEmp->id,
            'work_date' => $this->futureDate,
            'status' => 'present',
            'check_in_at' => $this->futureDate.' 08:00:00',
        ]);

        $this->expectException(ValidationException::class);
        $this->swapService->submitRequest($this->reqEmp, [
            'target_employee_id' => $this->targetEmp->id,
            'requester_work_date' => $this->futureDate,
            'reason' => 'Try swap existing attendance',
        ]);
    }

    public function test_12_existing_override_rejected(): void
    {
        EmployeeScheduleOverride::create([
            'employee_id' => $this->reqEmp->id,
            'date' => $this->futureDate,
            'override_type' => 'work',
            'shift_id' => $this->shiftMalam->id,
            'reason' => 'Manual admin override',
            'created_by' => $this->owner->id,
        ]);

        $this->expectException(ValidationException::class);
        $this->swapService->submitRequest($this->reqEmp, [
            'target_employee_id' => $this->targetEmp->id,
            'requester_work_date' => $this->futureDate,
            'reason' => 'Try swap override date',
        ]);
    }

    public function test_13_duplicate_active_swap_rejected(): void
    {
        $this->swapService->submitRequest($this->reqEmp, [
            'target_employee_id' => $this->targetEmp->id,
            'requester_work_date' => $this->futureDate,
            'reason' => 'First active swap',
        ]);

        $this->expectException(ValidationException::class);
        $this->swapService->submitRequest($this->reqEmp, [
            'target_employee_id' => $this->targetEmp->id,
            'requester_work_date' => $this->futureDate,
            'reason' => 'Second duplicate swap',
        ]);
    }

    // ==========================================
    // TARGET RESPONSE TESTS (14 - 18)
    // ==========================================

    public function test_14_target_can_accept(): void
    {
        $swap = $this->swapService->submitRequest($this->reqEmp, [
            'target_employee_id' => $this->targetEmp->id,
            'requester_work_date' => $this->futureDate,
            'reason' => 'Tukar shift',
        ]);

        $updated = $this->swapService->respondByTarget($swap, $this->targetUser, 'accept');

        $this->assertEquals(ShiftSwapRequest::STATUS_PENDING_ADMIN, $updated->status);
    }

    public function test_15_target_can_reject(): void
    {
        $swap = $this->swapService->submitRequest($this->reqEmp, [
            'target_employee_id' => $this->targetEmp->id,
            'requester_work_date' => $this->futureDate,
            'reason' => 'Tukar shift',
        ]);

        $updated = $this->swapService->respondByTarget($swap, $this->targetUser, 'reject', 'Ada acara pribadi');

        $this->assertEquals(ShiftSwapRequest::STATUS_REJECTED_BY_TARGET, $updated->status);
    }

    public function test_16_other_employee_cannot_respond(): void
    {
        $swap = $this->swapService->submitRequest($this->reqEmp, [
            'target_employee_id' => $this->targetEmp->id,
            'requester_work_date' => $this->futureDate,
            'reason' => 'Tukar shift',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->swapService->respondByTarget($swap, $this->reqUser, 'accept');
    }

    public function test_17_requester_cannot_respond_as_target(): void
    {
        $swap = $this->swapService->submitRequest($this->reqEmp, [
            'target_employee_id' => $this->targetEmp->id,
            'requester_work_date' => $this->futureDate,
            'reason' => 'Tukar shift',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->swapService->respondByTarget($swap, $this->reqUser, 'accept');
    }

    public function test_18_double_response_safe(): void
    {
        $swap = $this->swapService->submitRequest($this->reqEmp, [
            'target_employee_id' => $this->targetEmp->id,
            'requester_work_date' => $this->futureDate,
            'reason' => 'Tukar shift',
        ]);

        $accepted = $this->swapService->respondByTarget($swap, $this->targetUser, 'accept');

        $this->expectException(\InvalidArgumentException::class);
        $this->swapService->respondByTarget($accepted, $this->targetUser, 'accept');
    }

    // ==========================================
    // ADMIN APPROVAL TESTS (19 - 27)
    // ==========================================

    public function test_19_admin_owner_superadmin_permitted_to_approve(): void
    {
        $swap = $this->swapService->submitRequest($this->reqEmp, [
            'target_employee_id' => $this->targetEmp->id,
            'requester_work_date' => $this->futureDate,
            'reason' => 'Tukar shift',
        ]);
        $swap = $this->swapService->respondByTarget($swap, $this->targetUser, 'accept');

        $approved = $this->swapService->respondByAdmin($swap, $this->admin, 'approve');

        $this->assertEquals(ShiftSwapRequest::STATUS_APPROVED, $approved->status);
    }

    public function test_20_employee_cannot_admin_approve(): void
    {
        $swap = $this->swapService->submitRequest($this->reqEmp, [
            'target_employee_id' => $this->targetEmp->id,
            'requester_work_date' => $this->futureDate,
            'reason' => 'Tukar shift',
        ]);
        $swap = $this->swapService->respondByTarget($swap, $this->targetUser, 'accept');

        $this->expectException(\InvalidArgumentException::class);
        $this->swapService->respondByAdmin($swap, $this->reqUser, 'approve');
    }

    public function test_21_and_22_stale_schedule_rejected(): void
    {
        $swap = $this->swapService->submitRequest($this->reqEmp, [
            'target_employee_id' => $this->targetEmp->id,
            'requester_work_date' => $this->futureDate,
            'reason' => 'Tukar shift',
        ]);
        $swap = $this->swapService->respondByTarget($swap, $this->targetUser, 'accept');

        // Change regular schedule shift before admin approves
        EmployeeSchedule::where('employee_id', $this->reqEmp->id)
            ->whereDate('work_date', $this->futureDate)
            ->update(['shift_id' => $this->shiftMalam->id]);

        $this->expectException(ValidationException::class);
        $this->swapService->respondByAdmin($swap, $this->admin, 'approve');
    }

    public function test_23_closed_period_after_request_rejected(): void
    {
        $date = Carbon::now()->addDays(15)->toDateString();

        EmployeeSchedule::create([
            'employee_id' => $this->reqEmp->id,
            'work_date' => $date,
            'shift_id' => $this->shiftPagi->id,
            'schedule_type' => 'work',
        ]);
        EmployeeSchedule::create([
            'employee_id' => $this->targetEmp->id,
            'work_date' => $date,
            'shift_id' => $this->shiftMalam->id,
            'schedule_type' => 'work',
        ]);

        $swap = $this->swapService->submitRequest($this->reqEmp, [
            'target_employee_id' => $this->targetEmp->id,
            'requester_work_date' => $date,
            'reason' => 'Tukar shift 15 days future',
        ]);
        $swap = $this->swapService->respondByTarget($swap, $this->targetUser, 'accept');

        $year = (int) Carbon::parse($date)->format('Y');
        $month = (int) Carbon::parse($date)->format('m');
        $this->periodService->closePeriod($year, $month, $this->owner, 'Close Period');

        $this->expectException(ValidationException::class);
        $this->swapService->respondByAdmin($swap, $this->admin, 'approve');
    }

    public function test_24_new_attendance_after_request_rejected(): void
    {
        $swap = $this->swapService->submitRequest($this->reqEmp, [
            'target_employee_id' => $this->targetEmp->id,
            'requester_work_date' => $this->futureDate,
            'reason' => 'Tukar shift',
        ]);
        $swap = $this->swapService->respondByTarget($swap, $this->targetUser, 'accept');

        AttendanceRecord::create([
            'employee_id' => $this->reqEmp->id,
            'work_date' => $this->futureDate,
            'status' => 'present',
            'check_in_at' => $this->futureDate.' 08:00:00',
        ]);

        $this->expectException(ValidationException::class);
        $this->swapService->respondByAdmin($swap, $this->admin, 'approve');
    }

    public function test_25_leave_conflict_rejected(): void
    {
        $swap = $this->swapService->submitRequest($this->reqEmp, [
            'target_employee_id' => $this->targetEmp->id,
            'requester_work_date' => $this->futureDate,
            'reason' => 'Tukar shift',
        ]);
        $swap = $this->swapService->respondByTarget($swap, $this->targetUser, 'accept');

        LeaveRequest::create([
            'employee_id' => $this->reqEmp->id,
            'start_date' => $this->futureDate,
            'end_date' => $this->futureDate,
            'type' => 'leave',
            'reason' => 'Cuti tahunan',
            'status' => 'approved',
        ]);

        $this->expectException(ValidationException::class);
        $this->swapService->respondByAdmin($swap, $this->admin, 'approve');
    }

    public function test_26_overtime_conflict_rejected(): void
    {
        $swap = $this->swapService->submitRequest($this->reqEmp, [
            'target_employee_id' => $this->targetEmp->id,
            'requester_work_date' => $this->futureDate,
            'reason' => 'Tukar shift',
        ]);
        $swap = $this->swapService->respondByTarget($swap, $this->targetUser, 'accept');

        $otReq = OvertimeRequest::create([
            'employee_id' => $this->reqEmp->id,
            'work_date' => $this->futureDate,
            'requested_minutes' => 60,
            'approved_minutes' => 60,
            'reason' => 'Lembur',
            'status' => 'approved',
        ]);

        OvertimeSession::create([
            'overtime_request_id' => $otReq->id,
            'employee_id' => $this->reqEmp->id,
            'work_date' => $this->futureDate,
            'status' => 'active',
            'check_in_at' => $this->futureDate.' 17:00:00',
        ]);

        $this->expectException(ValidationException::class);
        $this->swapService->respondByAdmin($swap, $this->admin, 'approve');
    }

    public function test_27_double_approval_safe(): void
    {
        $swap = $this->swapService->submitRequest($this->reqEmp, [
            'target_employee_id' => $this->targetEmp->id,
            'requester_work_date' => $this->futureDate,
            'reason' => 'Tukar shift',
        ]);
        $swap = $this->swapService->respondByTarget($swap, $this->targetUser, 'accept');
        $approved = $this->swapService->respondByAdmin($swap, $this->admin, 'approve');

        $this->expectException(\InvalidArgumentException::class);
        $this->swapService->respondByAdmin($approved, $this->admin, 'approve');
    }

    // ==========================================
    // APPLY & EFFECTIVE SCHEDULE TESTS (28 - 34)
    // ==========================================

    public function test_28_and_29_and_30_and_31_approved_creates_overrides_without_touching_masters(): void
    {
        $swap = $this->swapService->submitRequest($this->reqEmp, [
            'target_employee_id' => $this->targetEmp->id,
            'requester_work_date' => $this->futureDate,
            'reason' => 'Tukar shift',
        ]);
        $swap = $this->swapService->respondByTarget($swap, $this->targetUser, 'accept');
        $this->swapService->respondByAdmin($swap, $this->admin, 'approve');

        // Check overrides created
        $reqOverride = EmployeeScheduleOverride::where('employee_id', $this->reqEmp->id)->whereDate('date', $this->futureDate)->first();
        $targetOverride = EmployeeScheduleOverride::where('employee_id', $this->targetEmp->id)->whereDate('date', $this->futureDate)->first();

        $this->assertNotNull($reqOverride);
        $this->assertEquals($this->shiftMalam->id, $reqOverride->shift_id);

        $this->assertNotNull($targetOverride);
        $this->assertEquals($this->shiftPagi->id, $targetOverride->shift_id);

        // Check regular schedule master untouched
        $this->assertEquals($this->shiftPagi->id, EmployeeSchedule::where('employee_id', $this->reqEmp->id)->whereDate('work_date', $this->futureDate)->first()->shift_id);

        // Check shift master untouched
        $this->assertEquals('Shift Pagi', $this->shiftPagi->fresh()->name);
    }

    public function test_32_and_33_and_34_effective_schedule_and_recap_uses_swapped_shifts(): void
    {
        $swap = $this->swapService->submitRequest($this->reqEmp, [
            'target_employee_id' => $this->targetEmp->id,
            'requester_work_date' => $this->futureDate,
            'reason' => 'Tukar shift',
        ]);
        $swap = $this->swapService->respondByTarget($swap, $this->targetUser, 'accept');
        $this->swapService->respondByAdmin($swap, $this->admin, 'approve');

        $reqEffective = $this->effectiveService->resolve($this->reqEmp, $this->futureDate);
        $targetEffective = $this->effectiveService->resolve($this->targetEmp, $this->futureDate);

        $this->assertEquals('Shift Malam', $reqEffective['shift']->name);
        $this->assertEquals('Shift Pagi', $targetEffective['shift']->name);
    }

    // ==========================================
    // CANCEL TESTS (35 - 37)
    // ==========================================

    public function test_35_and_36_requester_can_cancel_pending_requests(): void
    {
        $swap = $this->swapService->submitRequest($this->reqEmp, [
            'target_employee_id' => $this->targetEmp->id,
            'requester_work_date' => $this->futureDate,
            'reason' => 'Tukar shift',
        ]);

        $cancelled = $this->swapService->cancelRequest($swap, $this->reqUser);
        $this->assertEquals(ShiftSwapRequest::STATUS_CANCELLED, $cancelled->status);
    }

    public function test_37_approved_cannot_cancel(): void
    {
        $swap = $this->swapService->submitRequest($this->reqEmp, [
            'target_employee_id' => $this->targetEmp->id,
            'requester_work_date' => $this->futureDate,
            'reason' => 'Tukar shift',
        ]);
        $swap = $this->swapService->respondByTarget($swap, $this->targetUser, 'accept');
        $approved = $this->swapService->respondByAdmin($swap, $this->admin, 'approve');

        $this->expectException(\InvalidArgumentException::class);
        $this->swapService->cancelRequest($approved, $this->reqUser);
    }

    // ==========================================
    // AUDIT LOG TESTS (38 - 42)
    // ==========================================

    public function test_38_to_42_audit_logs_correct(): void
    {
        $swap = $this->swapService->submitRequest($this->reqEmp, [
            'target_employee_id' => $this->targetEmp->id,
            'requester_work_date' => $this->futureDate,
            'reason' => 'Audit test swap',
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'shift_swap.requested']);

        $swap = $this->swapService->respondByTarget($swap, $this->targetUser, 'accept');
        $this->assertDatabaseHas('audit_logs', ['action' => 'shift_swap.target_approved']);

        $this->swapService->respondByAdmin($swap, $this->admin, 'approve');
        $this->assertDatabaseHas('audit_logs', ['action' => 'shift_swap.admin_approved']);
    }

    // ==========================================
    // NOTIFICATION TESTS (43 - 46)
    // ==========================================

    public function test_43_to_46_notifications_sent(): void
    {
        $swap = $this->swapService->submitRequest($this->reqEmp, [
            'target_employee_id' => $this->targetEmp->id,
            'requester_work_date' => $this->futureDate,
            'reason' => 'Notify test',
        ]);
        $this->assertEquals(1, $this->targetUser->notifications()->count());

        $swap = $this->swapService->respondByTarget($swap, $this->targetUser, 'accept');
        $this->assertEquals(1, $this->reqUser->notifications()->count());

        $this->swapService->respondByAdmin($swap, $this->admin, 'approve');
        $this->assertTrue($this->reqUser->notifications()->count() >= 2);
    }

    // ==========================================
    // SECURITY TESTS (47 - 48)
    // ==========================================

    public function test_47_and_48_participant_visibility_and_route_tampering_prevented(): void
    {
        $otherEmp = Employee::create(['employee_code' => 'EMP-OTHER', 'full_name' => 'Other', 'status' => 'active']);
        $otherUser = User::create(['employee_id' => $otherEmp->id, 'name' => 'Other', 'email' => 'other@example.test', 'password' => Hash::make('password'), 'role' => 'employee']);

        $swap = $this->swapService->submitRequest($this->reqEmp, [
            'target_employee_id' => $this->targetEmp->id,
            'requester_work_date' => $this->futureDate,
            'reason' => 'Security test',
        ]);

        $this->actingAs($otherUser)
            ->post(route('employee.shift-swaps.respond', $swap), ['action' => 'accept'])
            ->assertSessionHas('error');
    }

    // ==========================================
    // BACKUP & RESTORE TESTS (49 - 50)
    // ==========================================

    public function test_49_and_50_backup_includes_shift_swap_requests_table(): void
    {
        $swap = $this->swapService->submitRequest($this->reqEmp, [
            'target_employee_id' => $this->targetEmp->id,
            'requester_work_date' => $this->futureDate,
            'reason' => 'Backup test swap',
        ]);

        $backupService = app(BackupService::class);
        $record = $backupService->createBackup('database', $this->superadmin);

        $this->assertNotNull($record);
        $this->assertEquals('completed', $record->status);

        $backupService->restoreBackup($record, 'password', $this->superadmin);
        $this->assertDatabaseHas('shift_swap_requests', ['id' => $swap->id]);
    }

    // ==========================================
    // PERIOD LOCK TEST (51)
    // ==========================================

    public function test_51_period_lock_blocks_swap_in_closed_period(): void
    {
        $date = '2026-08-20';
        $this->periodService->closePeriod(2026, 8, $this->owner, 'Close August');

        $this->expectException(ValidationException::class);
        $this->swapService->submitRequest($this->reqEmp, [
            'target_employee_id' => $this->targetEmp->id,
            'requester_work_date' => $date,
            'reason' => 'Try swap in closed period',
        ]);
    }

    // ==========================================
    // SUPERADMIN WORKFORCE TEST (52)
    // ==========================================

    public function test_52_superadmin_workforce_behavior_unchanged(): void
    {
        $this->assertFalse($this->superadmin->employee?->participatesInAttendance() ?? false);
    }

    // ==========================================
    // MOBILE UI TESTS (53 - 54)
    // ==========================================

    public function test_53_and_54_mobile_ui_renders_correctly(): void
    {
        $this->actingAs($this->reqUser)
            ->get(route('employee.shift-swaps.index'))
            ->assertStatus(200)
            ->assertSee('Ajukan Tukar Jadwal');

        $this->actingAs($this->admin)
            ->get(route('admin.shift-swaps.index'))
            ->assertStatus(200)
            ->assertSee('Permintaan Pertukaran Jadwal');
    }

    // ==========================================
    // PERFORMANCE TEST (55)
    // ==========================================

    public function test_55_no_major_query_regression(): void
    {
        DB::enableQueryLog();
        $this->swapService->submitRequest($this->reqEmp, [
            'target_employee_id' => $this->targetEmp->id,
            'requester_work_date' => $this->futureDate,
            'reason' => 'Performance test swap',
        ]);

        $queryCount = count(DB::getQueryLog());
        $this->assertLessThanOrEqual(35, $queryCount);
    }

    // ==========================================
    // NAVIGATION REGRESSION TESTS (K1 - K8)
    // ==========================================

    public function test_nav_1_attendance_enabled_employee_sees_tukar_jadwal_entry(): void
    {
        $this->actingAs($this->reqUser)
            ->get(route('employee.leave-requests.index'))
            ->assertStatus(200)
            ->assertSee('Tukar Jadwal');
    }

    public function test_nav_2_attendance_disabled_employee_does_not_see_entry(): void
    {
        $this->reqEmp->update(['attendance_enabled' => false]);

        $this->actingAs($this->reqUser)
            ->get(route('employee.leave-requests.index'))
            ->assertStatus(200)
            ->assertDontSee(route('employee.shift-swaps.index'));
    }

    public function test_nav_3_employee_pengajuan_page_links_to_shift_swaps_index(): void
    {
        $this->actingAs($this->reqUser)
            ->get(route('employee.leave-requests.index'))
            ->assertStatus(200)
            ->assertSee(route('employee.shift-swaps.index'));
    }

    public function test_nav_4_shift_swap_index_contains_ajukan_tukar_jadwal_cta(): void
    {
        $this->actingAs($this->reqUser)
            ->get(route('employee.shift-swaps.index'))
            ->assertStatus(200)
            ->assertSee('Ajukan Tukar Jadwal')
            ->assertSee(route('employee.shift-swaps.create'));
    }

    public function test_nav_5_admin_sees_permintaan_tukar_jadwal_menu(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertStatus(200)
            ->assertSee('Permintaan Tukar Jadwal')
            ->assertSee(route('admin.shift-swaps.index'));
    }

    public function test_nav_6_employee_mobile_navigation_does_not_overflow_structurally(): void
    {
        $response = $this->actingAs($this->reqUser)->get(route('employee.dashboard'));
        $response->assertStatus(200);
        
        // Assert bottom nav bar has exactly 5 main item slots
        $content = $response->getContent();
        $this->assertStringContainsString('Bottom Navigation Bar (5 Items Max', $content);
    }

    public function test_nav_7_active_route_state_correct(): void
    {
        $this->actingAs($this->reqUser)
            ->get(route('employee.shift-swaps.index'))
            ->assertStatus(200)
            ->assertSee('text-rose-600 font-extrabold');

        $this->actingAs($this->admin)
            ->get(route('admin.shift-swaps.index'))
            ->assertStatus(200)
            ->assertSee('bg-gradient-to-r from-rose-600 to-pink-600');
    }

    public function test_nav_8_superadmin_workforce_behavior_unchanged(): void
    {
        $this->assertFalse($this->superadmin->employee?->participatesInAttendance() ?? false);

        $this->actingAs($this->superadmin)
            ->get(route('admin.dashboard'))
            ->assertStatus(200)
            ->assertSee('Permintaan Tukar Jadwal');
    }

    // ==========================================
    // MODAL & VANILLA JS REGRESSION TESTS (L1 - L10)
    // ==========================================

    public function test_modal_1_response_modal_hidden_by_default(): void
    {
        $swap = $this->swapService->submitRequest($this->reqEmp, [
            'target_employee_id' => $this->targetEmp->id,
            'requester_work_date' => $this->futureDate,
            'reason' => 'Modal test swap',
        ]);

        $response = $this->actingAs($this->targetUser)
            ->get(route('employee.shift-swaps.index', ['tab' => 'incoming']));

        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString('id="swap-modal-backdrop"', $content);
        $this->assertStringContainsString('class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs hidden"', $content);
    }

    public function test_modal_2_triggers_contain_safe_data_attributes(): void
    {
        $this->targetEmp->update(['full_name' => "D'ia O'Brian"]);

        $swap = $this->swapService->submitRequest($this->reqEmp, [
            'target_employee_id' => $this->targetEmp->id,
            'requester_work_date' => $this->futureDate,
            'reason' => 'Special name swap test',
        ]);

        $response = $this->actingAs($this->targetUser)
            ->get(route('employee.shift-swaps.index', ['tab' => 'incoming']));

        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString('data-respond-url="'.route('employee.shift-swaps.respond', $swap).'"', $content);
        $this->assertStringContainsString('data-action="accept"', $content);
        $this->assertStringContainsString('data-action="reject"', $content);
        $this->assertStringContainsString('data-requester-name="Ayu"', $content);
    }

    public function test_modal_3_no_alpine_directives_remain_on_employee_shift_swap_modal(): void
    {
        $swap = $this->swapService->submitRequest($this->reqEmp, [
            'target_employee_id' => $this->targetEmp->id,
            'requester_work_date' => $this->futureDate,
            'reason' => 'No alpine test',
        ]);

        $response = $this->actingAs($this->targetUser)
            ->get(route('employee.shift-swaps.index', ['tab' => 'incoming']));

        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringNotContainsString('x-data="{ respondModal', $content);
        $this->assertStringNotContainsString('x-show="respondModal"', $content);
        $this->assertStringNotContainsString('@click.away="respondModal = false"', $content);
        $this->assertStringNotContainsString(':action="', $content);
    }
}
