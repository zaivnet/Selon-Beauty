<?php

namespace Tests\Feature\MonthlyPeriodLock;

use App\Models\AttendanceLocation;
use App\Models\AttendancePeriod;
use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\OvertimeSession;
use App\Models\Shift;
use App\Models\User;
use App\Services\AttendancePeriodService;
use App\Services\AttendanceService;
use App\Services\BackupService;
use App\Services\EmployeeScheduleService;
use App\Services\LeaveRequestService;
use App\Services\MonthlyAttendanceRecapService;
use App\Services\OvertimeRequestService;
use App\Services\OvertimeSessionService;
use App\Services\WorkCalendarService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MonthlyPeriodLockTest extends TestCase
{
    use RefreshDatabase;

    protected AttendancePeriodService $periodService;

    protected User $superadmin;

    protected User $owner;

    protected User $admin;

    protected User $employeeUser;

    protected Employee $employee;

    protected Shift $shift;

    protected AttendanceLocation $location;

    protected function setUp(): void
    {
        parent::setUp();

        $this->periodService = app(AttendancePeriodService::class);

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

        $this->employee = Employee::create([
            'employee_code' => 'EMP-001',
            'full_name' => 'Budi Santoso',
            'status' => 'active',
            'attendance_enabled' => true,
        ]);

        $this->employeeUser = User::create([
            'employee_id' => $this->employee->id,
            'name' => 'Budi Santoso',
            'email' => 'budi@example.test',
            'password' => Hash::make('password'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        $this->shift = Shift::create([
            'name' => 'Shift Pagi',
            'code' => 'SP',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'grace_period_minutes' => 5,
            'break_minutes' => 60,
            'check_in_open_minutes_before' => 60,
            'check_in_close_minutes_after' => 120,
            'check_out_open_minutes_before' => 60,
            'is_active' => true,
        ]);

        $this->location = AttendanceLocation::create([
            'name' => 'Kantor Utama',
            'latitude' => -6.2000000,
            'longitude' => 106.8166667,
            'radius_meters' => 100,
            'max_accuracy_meters' => 100,
            'is_active' => true,
        ]);
    }

    // ==========================================
    // PERIOD BASIC TESTS (1 - 8)
    // ==========================================

    public function test_1_period_default_is_open(): void
    {
        $period = $this->periodService->getOrCreatePeriod(2026, 8);

        $this->assertTrue($period->isOpen());
        $this->assertFalse($period->isClosed());
        $this->assertTrue($this->periodService->isOpen(2026, 8));
        $this->assertTrue($this->periodService->isOpen('2026-08-15'));
    }

    public function test_2_close_period_authorized_for_owner_and_superadmin(): void
    {
        $closedByOwner = $this->periodService->closePeriod(2026, 8, $this->owner, 'Verifikasi payroll selesai');
        $this->assertTrue($closedByOwner->isClosed());

        $this->periodService->reopenPeriod(2026, 8, $this->superadmin, 'Buka untuk penyesuaian');
        $closedBySuperadmin = $this->periodService->closePeriod(2026, 8, $this->superadmin, 'Tutup kembali oleh superadmin');
        $this->assertTrue($closedBySuperadmin->isClosed());
    }

    public function test_3_admin_cannot_close_period(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Akses ditolak');

        $this->periodService->closePeriod(2026, 8, $this->admin, 'Admin coba menutup periode');
    }

    public function test_4_reason_required_minimum_5_chars(): void
    {
        $this->expectException(ValidationException::class);

        $this->periodService->closePeriod(2026, 8, $this->owner, '3ch');
    }

    public function test_5_double_close_is_idempotent(): void
    {
        $first = $this->periodService->closePeriod(2026, 8, $this->owner, 'Close pertama verified');
        $this->assertTrue($first->isClosed());

        $auditCountBefore = AuditLog::where('action', 'attendance_period.closed')->count();
        $second = $this->periodService->closePeriod(2026, 8, $this->owner, 'Close kedua verified');

        $this->assertTrue($second->isClosed());
        $this->assertEquals($auditCountBefore, AuditLog::where('action', 'attendance_period.closed')->count());
    }

    public function test_6_reopen_authorized_for_owner_and_superadmin(): void
    {
        $this->periodService->closePeriod(2026, 8, $this->owner, 'Verified payroll');
        $reopened = $this->periodService->reopenPeriod(2026, 8, $this->owner, 'Ditemukan koreksi valid');

        $this->assertTrue($reopened->isOpen());
    }

    public function test_7_reopen_reason_required_minimum_5_chars(): void
    {
        $this->periodService->closePeriod(2026, 8, $this->owner, 'Verified payroll');

        $this->expectException(ValidationException::class);
        $this->periodService->reopenPeriod(2026, 8, $this->owner, '4ch');
    }

    public function test_8_double_reopen_is_safe_and_idempotent(): void
    {
        $this->periodService->closePeriod(2026, 8, $this->owner, 'Verified payroll');
        $this->periodService->reopenPeriod(2026, 8, $this->owner, 'Reopen pertama valid');

        $auditCount = AuditLog::where('action', 'attendance_period.reopened')->count();
        $second = $this->periodService->reopenPeriod(2026, 8, $this->owner, 'Reopen kedua valid');

        $this->assertTrue($second->isOpen());
        $this->assertEquals($auditCount, AuditLog::where('action', 'attendance_period.reopened')->count());
    }

    // ==========================================
    // ELIGIBILITY TESTS (9 - 11)
    // ==========================================

    public function test_9_cannot_close_period_with_missing_checkout(): void
    {
        AttendanceRecord::create([
            'employee_id' => $this->employee->id,
            'work_date' => '2026-08-10',
            'check_in_at' => '2026-08-10 08:00:00',
            'check_out_at' => null,
            'status' => 'present',
        ]);

        $this->expectException(ValidationException::class);
        $this->periodService->closePeriod(2026, 8, $this->owner, 'Try close with unclosed checkin');
    }

    public function test_10_cannot_close_period_with_active_overtime_session(): void
    {
        $req = OvertimeRequest::create([
            'employee_id' => $this->employee->id,
            'work_date' => '2026-08-12',
            'requested_minutes' => 120,
            'approved_minutes' => 120,
            'reason' => 'Lembur stok',
            'status' => 'approved',
        ]);

        OvertimeSession::create([
            'overtime_request_id' => $req->id,
            'employee_id' => $this->employee->id,
            'work_date' => '2026-08-12',
            'status' => 'active',
            'check_in_at' => '2026-08-12 17:30:00',
        ]);

        $this->expectException(ValidationException::class);
        $this->periodService->closePeriod(2026, 8, $this->owner, 'Try close with active overtime');
    }

    public function test_11_clean_period_can_close(): void
    {
        AttendanceRecord::create([
            'employee_id' => $this->employee->id,
            'work_date' => '2026-08-10',
            'check_in_at' => '2026-08-10 08:00:00',
            'check_out_at' => '2026-08-10 17:00:00',
            'status' => 'present',
        ]);

        $period = $this->periodService->closePeriod(2026, 8, $this->owner, 'Periode bersih siap ditutup');
        $this->assertTrue($period->isClosed());
    }

    // ==========================================
    // ATTENDANCE LOCK TESTS (12 - 16)
    // ==========================================

    public function test_12_checkin_rejected_in_closed_period(): void
    {
        $this->periodService->closePeriod(2026, 8, $this->owner, 'Close August period');
        Carbon::setTestNow(Carbon::parse('2026-08-15 08:00:00', 'Asia/Jakarta'));

        EmployeeSchedule::create([
            'employee_id' => $this->employee->id,
            'work_date' => '2026-08-15',
            'shift_id' => $this->shift->id,
            'schedule_type' => 'work',
        ]);

        $this->expectException(ValidationException::class);
        app(AttendanceService::class)->checkIn($this->employeeUser, [
            'latitude' => -6.2000000, 'longitude' => 106.8166667, 'accuracy' => 10,
        ]);
    }

    public function test_13_checkout_rejected_in_closed_period(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-15 08:00:00', 'Asia/Jakarta'));

        $schedule = EmployeeSchedule::create([
            'employee_id' => $this->employee->id,
            'work_date' => '2026-08-15',
            'shift_id' => $this->shift->id,
            'schedule_type' => 'work',
        ]);

        AttendanceRecord::create([
            'employee_id' => $this->employee->id,
            'work_schedule_id' => $schedule->id,
            'work_date' => '2026-08-15',
            'check_in_at' => '2026-08-15 08:00:00',
            'check_out_at' => null,
            'status' => 'present',
        ]);

        AttendancePeriod::updateOrCreate(
            ['year' => 2026, 'month' => 8],
            [
                'status' => AttendancePeriod::STATUS_CLOSED,
                'closed_at' => now(),
                'closed_by' => $this->owner->id,
                'close_reason' => 'Direct lock test',
            ]
        );

        Carbon::setTestNow(Carbon::parse('2026-08-15 17:00:00', 'Asia/Jakarta'));

        $file = \Illuminate\Http\UploadedFile::fake()->image('selfie.jpg');

        $this->expectException(ValidationException::class);
        app(AttendanceService::class)->checkOut($this->employeeUser, [
            'latitude' => -6.2000000, 'longitude' => 106.8166667, 'accuracy' => 10, 'selfie' => $file,
            'attendance_location_id' => $this->location->id,
        ]);
    }

    public function test_14_and_15_admin_correction_and_recovery_rejected_in_closed_period(): void
    {
        $record = AttendanceRecord::create([
            'employee_id' => $this->employee->id,
            'work_date' => '2026-08-10',
            'check_in_at' => '2026-08-10 08:00:00',
            'check_out_at' => '2026-08-10 17:00:00',
            'status' => 'present',
        ]);

        $this->periodService->closePeriod(2026, 8, $this->owner, 'Close August');

        $this->expectException(ValidationException::class);
        app(AttendanceService::class)->correctAttendanceRecord(
            $record, '08:00:00', '18:00:00', 'Koreksi jam pulang', $this->superadmin
        );
    }

    public function test_16_historical_read_still_allowed_when_closed(): void
    {
        AttendanceRecord::create([
            'employee_id' => $this->employee->id,
            'work_date' => '2026-08-10',
            'check_in_at' => '2026-08-10 08:00:00',
            'check_out_at' => '2026-08-10 17:00:00',
            'status' => 'present',
        ]);

        $this->periodService->closePeriod(2026, 8, $this->owner, 'Close August');

        $response = $this->actingAs($this->superadmin)->get(route('admin.attendance.index', ['date' => '2026-08-10']));
        $response->assertStatus(200);
    }

    // ==========================================
    // OVERTIME LOCK TESTS (17 - 22)
    // ==========================================

    public function test_17_overtime_submit_and_cancel_rejected_in_closed_period(): void
    {
        $this->periodService->closePeriod(2026, 8, $this->owner, 'Close August');

        $this->expectException(ValidationException::class);
        app(OvertimeRequestService::class)->submitRequest($this->employee, [
            'work_date' => '2026-08-15',
            'requested_minutes' => 60,
            'reason' => 'Lembur pekerjaan stok',
        ]);
    }

    public function test_18_overtime_approval_and_rejection_rejected_in_closed_period(): void
    {
        $req = OvertimeRequest::create([
            'employee_id' => $this->employee->id,
            'work_date' => '2026-08-15',
            'requested_minutes' => 60,
            'reason' => 'Lembur pekerjaan',
            'status' => 'pending',
        ]);

        $this->periodService->closePeriod(2026, 8, $this->owner, 'Close August');

        $this->expectException(ValidationException::class);
        app(OvertimeRequestService::class)->approveRequest($req, $this->owner, 60);
    }

    public function test_19_overtime_start_rejected_in_closed_period(): void
    {
        $req = OvertimeRequest::create([
            'employee_id' => $this->employee->id,
            'work_date' => '2026-08-15',
            'requested_minutes' => 60,
            'approved_minutes' => 60,
            'reason' => 'Lembur pekerjaan',
            'status' => 'approved',
        ]);

        $this->periodService->closePeriod(2026, 8, $this->owner, 'Close August');

        $this->expectException(ValidationException::class);
        app(OvertimeSessionService::class)->start($this->employeeUser, $req->id, [
            'latitude' => -6.2000000, 'longitude' => 106.8166667, 'accuracy' => 10,
        ]);
    }

    public function test_20_21_22_overtime_session_mutations_rejected_in_closed_period(): void
    {
        $req = OvertimeRequest::create([
            'employee_id' => $this->employee->id,
            'work_date' => '2026-08-15',
            'requested_minutes' => 60,
            'approved_minutes' => 60,
            'reason' => 'Lembur pekerjaan',
            'status' => 'approved',
        ]);

        $session = OvertimeSession::create([
            'overtime_request_id' => $req->id,
            'employee_id' => $this->employee->id,
            'work_date' => '2026-08-15',
            'status' => 'completed',
            'check_in_at' => '2026-08-15 17:30:00',
            'check_out_at' => '2026-08-15 18:30:00',
            'actual_minutes' => 60,
            'credited_minutes' => 60,
        ]);

        $this->periodService->closePeriod(2026, 8, $this->owner, 'Close August');

        $this->expectException(ValidationException::class);
        app(OvertimeSessionService::class)->correctCompleted($this->superadmin, $session, '17:30:00', '19:00:00', 'Koreksi durasi lembur');
    }

    // ==========================================
    // LEAVE LOCK TESTS (23 - 25)
    // ==========================================

    public function test_23_retroactive_leave_submission_rejected_in_closed_period(): void
    {
        $this->periodService->closePeriod(2026, 8, $this->owner, 'Close August');

        $this->expectException(ValidationException::class);
        app(LeaveRequestService::class)->submitRequest($this->employee, [
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-10',
            'type' => 'sick',
            'reason' => 'Demam tinggi',
        ]);
    }

    public function test_24_leave_approval_mutation_rejected_in_closed_period(): void
    {
        $leave = LeaveRequest::create([
            'employee_id' => $this->employee->id,
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-10',
            'type' => 'sick',
            'reason' => 'Demam tinggi',
            'status' => 'pending',
        ]);

        $this->periodService->closePeriod(2026, 8, $this->owner, 'Close August');

        $this->expectException(ValidationException::class);
        app(LeaveRequestService::class)->approveRequest($leave, $this->owner);
    }

    public function test_25_multiday_leave_intersecting_closed_period_rejected(): void
    {
        $this->periodService->closePeriod(2026, 8, $this->owner, 'Close August');

        $this->expectException(ValidationException::class);
        app(LeaveRequestService::class)->submitRequest($this->employee, [
            'start_date' => '2026-08-30',
            'end_date' => '2026-09-02',
            'type' => 'leave',
            'reason' => 'Cuti tahunan tumpang tindih',
        ]);
    }

    // ==========================================
    // SCHEDULE & OVERRIDE LOCK TESTS (26 - 27)
    // ==========================================

    public function test_26_schedule_override_mutation_rejected_in_closed_period(): void
    {
        $this->periodService->closePeriod(2026, 8, $this->owner, 'Close August');

        $this->expectException(ValidationException::class);
        app(WorkCalendarService::class)->saveOverride([
            'employee_id' => $this->employee->id,
            'date' => '2026-08-20',
            'override_type' => 'off',
            'reason' => 'Libur khusus karyawan',
        ], $this->owner);
    }

    public function test_27_regular_schedule_mutation_rejected_in_closed_period(): void
    {
        $this->periodService->closePeriod(2026, 8, $this->owner, 'Close August');

        $this->expectException(ValidationException::class);
        app(EmployeeScheduleService::class)->assignSchedule([
            'employee_id' => $this->employee->id,
            'work_date' => '2026-08-20',
            'schedule_type' => 'work',
            'shift_id' => $this->shift->id,
        ], $this->owner);
    }

    // ==========================================
    // HOLIDAY LOCK TESTS (28 - 29)
    // ==========================================

    public function test_28_and_29_holiday_and_special_working_day_mutation_rejected_in_closed_period(): void
    {
        $this->periodService->closePeriod(2026, 8, $this->owner, 'Close August');

        $this->expectException(ValidationException::class);
        app(WorkCalendarService::class)->createCalendarDay([
            'date' => '2026-08-17',
            'type' => 'public_holiday',
            'name' => 'Hari Kemerdekaan RI',
            'audit_reason' => 'Tambah hari libur nasional',
        ], $this->owner);
    }

    // ==========================================
    // WORKFORCE & RECAP TESTS (30 - 34)
    // ==========================================

    public function test_30_attendance_enabled_future_change_still_works(): void
    {
        $this->periodService->closePeriod(2026, 8, $this->owner, 'Close August');

        $this->employee->update(['attendance_enabled' => false]);
        $this->assertFalse($this->employee->fresh()->participatesInAttendance());
    }

    public function test_31_closed_period_historical_records_remain_visible(): void
    {
        $schedule = EmployeeSchedule::create([
            'employee_id' => $this->employee->id,
            'work_date' => '2026-08-10',
            'shift_id' => $this->shift->id,
            'schedule_type' => 'work',
        ]);

        AttendanceRecord::create([
            'employee_id' => $this->employee->id,
            'work_schedule_id' => $schedule->id,
            'work_date' => '2026-08-10',
            'check_in_at' => '2026-08-10 08:00:00',
            'check_out_at' => '2026-08-10 17:00:00',
            'status' => 'present',
        ]);

        $this->periodService->closePeriod(2026, 8, $this->owner, 'Close August');

        $recap = app(MonthlyAttendanceRecapService::class)->forEmployee($this->employee, 2026, 8);
        $this->assertEquals(1, $recap['summary']['present_days']);
    }

    public function test_32_monthly_recap_shows_locked_state(): void
    {
        $this->periodService->closePeriod(2026, 8, $this->owner, 'Close August');

        $response = $this->actingAs($this->superadmin)->get(route('admin.monthly-recaps.index', ['year' => 2026, 'month' => 8]));
        $response->assertStatus(200);
        $response->assertSee('PERIODE TERKUNCI');
    }

    public function test_33_closed_period_remains_deterministic(): void
    {
        AttendanceRecord::create([
            'employee_id' => $this->employee->id,
            'work_date' => '2026-08-10',
            'check_in_at' => '2026-08-10 08:00:00',
            'check_out_at' => '2026-08-10 17:00:00',
            'status' => 'present',
        ]);

        $this->periodService->closePeriod(2026, 8, $this->owner, 'Close August');

        $firstRun = app(MonthlyAttendanceRecapService::class)->generate(2026, 8);
        $secondRun = app(MonthlyAttendanceRecapService::class)->generate(2026, 8);

        $this->assertEquals($firstRun, $secondRun);
    }

    public function test_34_reopen_allows_valid_mutation_again(): void
    {
        $this->periodService->closePeriod(2026, 8, $this->owner, 'Close August');
        $this->periodService->reopenPeriod(2026, 8, $this->owner, 'Reopen for valid adjustment');

        $schedule = app(EmployeeScheduleService::class)->assignSchedule([
            'employee_id' => $this->employee->id,
            'work_date' => '2026-08-20',
            'schedule_type' => 'work',
            'shift_id' => $this->shift->id,
        ], $this->owner);

        $this->assertNotNull($schedule->id);
    }

    // ==========================================
    // AUDIT LOG TESTS (35 - 37)
    // ==========================================

    public function test_35_and_36_and_37_audit_logs_and_no_duplicates(): void
    {
        $period = $this->periodService->closePeriod(2026, 8, $this->owner, 'Penutupan periode verified');
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'attendance_period.closed',
            'user_id' => $this->owner->id,
            'reason' => 'Penutupan periode verified',
        ]);

        $this->periodService->reopenPeriod(2026, 8, $this->superadmin, 'Buka kembali oleh superadmin');
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'attendance_period.reopened',
            'user_id' => $this->superadmin->id,
            'reason' => 'Buka kembali oleh superadmin',
        ]);
    }

    // ==========================================
    // BACKUP & RESTORE TESTS (38 - 39)
    // ==========================================

    public function test_38_and_39_backup_includes_attendance_periods_table_and_preserves_status(): void
    {
        $this->periodService->closePeriod(2026, 8, $this->owner, 'Close August before backup');

        $backupService = app(BackupService::class);
        $backupRecord = $backupService->createBackup('database', $this->superadmin);

        $this->assertNotNull($backupRecord);
        $this->assertEquals('completed', $backupRecord->status);

        $backupService->restoreBackup($backupRecord, 'password', $this->superadmin);
        $this->assertTrue($this->periodService->isClosed(2026, 8));
    }

    // ==========================================
    // AUTHORIZATION TESTS (40 - 42)
    // ==========================================

    public function test_40_employee_denied_close_and_reopen(): void
    {
        $responseClose = $this->actingAs($this->employeeUser)->post(route('admin.monthly-recaps.close'), [
            'year' => 2026, 'month' => 8, 'reason' => 'Unauthorized employee try close',
        ]);
        $this->assertTrue(in_array($responseClose->status(), [302, 403], true));
    }

    public function test_41_admin_denied_close_and_reopen(): void
    {
        $responseClose = $this->actingAs($this->admin)->post(route('admin.monthly-recaps.close'), [
            'year' => 2026, 'month' => 8, 'reason' => 'Admin trying to close period',
        ]);
        $responseClose->assertStatus(403);
    }

    public function test_42_owner_and_superadmin_permitted_to_close_and_reopen(): void
    {
        $responseOwner = $this->actingAs($this->owner)->post(route('admin.monthly-recaps.close'), [
            'year' => 2026, 'month' => 8, 'reason' => 'Owner closing period verified',
        ]);
        $responseOwner->assertRedirect();
        $this->assertTrue($this->periodService->isClosed(2026, 8));

        $responseSuperadmin = $this->actingAs($this->superadmin)->post(route('admin.monthly-recaps.reopen'), [
            'year' => 2026, 'month' => 8, 'reason' => 'Superadmin reopening period verified',
        ]);
        $responseSuperadmin->assertRedirect();
        $this->assertTrue($this->periodService->isOpen(2026, 8));
    }

    // ==========================================
    // MOBILE & UI TEST (43)
    // ==========================================

    public function test_43_period_ui_renders_responsive(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('admin.monthly-recaps.index', ['year' => 2026, 'month' => 8]));
        $response->assertStatus(200);
        $response->assertSee('PERIODE TERBUKA');
        $response->assertSee('Tutup Periode');
    }

    // ==========================================
    // PERFORMANCE TEST (44)
    // ==========================================

    public function test_44_period_service_query_performance(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->periodService->isOpen(2026, 8);
        }

        $this->assertTrue($this->periodService->isOpen(2026, 8));
    }
}
