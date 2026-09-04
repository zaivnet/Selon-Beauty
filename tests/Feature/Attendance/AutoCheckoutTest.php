<?php

namespace Tests\Feature\Attendance;

use App\Models\AttendanceLocation;
use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\EmployeeScheduleOverride;
use App\Models\JobTitle;
use App\Models\LeaveRequest;
use App\Models\Outlet;
use App\Models\OvertimeRequest;
use App\Models\OvertimeSession;
use App\Models\Shift;
use App\Models\User;
use App\Notifications\AutoCheckoutNotification;
use App\Services\AutoCheckoutService;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AutoCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected Outlet $outlet;
    protected Outlet $workOutlet;
    protected AttendanceLocation $location;
    protected JobTitle $jobTitle;
    protected Shift $morningShift;
    protected Shift $nightShift;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(null);

        $this->outlet = Outlet::create([
            'name' => 'Outlet Pusat',
            'code' => 'OUT-001',
            'address' => 'Jl. Pusat No. 1',
            'latitude' => -6.2000000,
            'longitude' => 106.8166660,
            'radius_meters' => 100,
            'max_accuracy_meters' => 50,
            'is_active' => true,
        ]);

        $this->workOutlet = Outlet::create([
            'name' => 'Outlet Cabang',
            'code' => 'OUT-002',
            'address' => 'Jl. Cabang No. 2',
            'latitude' => -6.2100000,
            'longitude' => 106.8200000,
            'radius_meters' => 100,
            'max_accuracy_meters' => 50,
            'is_active' => true,
        ]);

        $this->location = AttendanceLocation::create([
            'name' => 'Lokasi Utama',
            'address' => 'Jl. Pusat No. 1',
            'latitude' => -6.2000000,
            'longitude' => 106.8166660,
            'radius_meters' => 100,
            'max_accuracy_meters' => 50,
            'is_active' => true,
        ]);

        $this->jobTitle = JobTitle::create([
            'name' => 'Stylist',
            'is_active' => true,
        ]);

        $this->morningShift = Shift::create([
            'name' => 'Shift Pagi',
            'code' => 'PAGI',
            'start_time' => '06:00:00',
            'end_time' => '14:00:00',
            'check_in_open_minutes_before' => 60,
            'check_in_close_minutes_after' => 120,
            'check_out_open_minutes_before' => 60,
            'grace_period_minutes' => 5,
            'break_minutes' => 60,
            'crosses_midnight' => false,
            'auto_checkout_enabled' => true,
            'auto_checkout_grace_minutes' => 10,
            'is_active' => true,
        ]);

        $this->nightShift = Shift::create([
            'name' => 'Shift Malam',
            'code' => 'NIGHT',
            'start_time' => '22:00:00',
            'end_time' => '06:00:00',
            'check_in_open_minutes_before' => 60,
            'check_in_close_minutes_after' => 120,
            'check_out_open_minutes_before' => 60,
            'grace_period_minutes' => 5,
            'break_minutes' => 60,
            'crosses_midnight' => true,
            'auto_checkout_enabled' => true,
            'auto_checkout_grace_minutes' => 10,
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(null);
        parent::tearDown();
    }

    protected function createEmployeeUser(string $name = 'Budi Stylist', ?Outlet $outlet = null): array
    {
        $targetOutlet = $outlet ?? $this->outlet;

        $user = User::factory()->create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).rand(100, 999).'@salon.test',
            'role' => 'employee',
            'is_active' => true,
        ]);

        $employee = Employee::create([
            'outlet_id' => $targetOutlet->id,
            'job_title_id' => $this->jobTitle->id,
            'employee_code' => 'EMP-'.rand(1000, 9999),
            'full_name' => $name,
            'email' => $user->email,
            'status' => 'active',
            'attendance_enabled' => true,
            'hire_date' => '2025-01-01',
        ]);

        $user->employee_id = $employee->id;
        $user->save();

        return [$user, $employee];
    }

    protected function createSnapshottedRecord(array $attributes): AttendanceRecord
    {
        $workDate = $attributes['work_date'] ?? '2026-09-04';
        $defaults = [
            'status' => 'present',
            'scheduled_shift_end_at' => Carbon::parse($workDate.' 14:00:00', 'Asia/Jakarta'),
            'break_minutes_snapshot' => 60,
        ];

        return AttendanceRecord::create(array_merge($defaults, $attributes));
    }

    /**
     * Scenario A: Normal auto checkout at boundary.
     * Shift 06:00-14:00, Grace 10. Boundary snapshot: 14:10:00.
     * At 14:09 -> no checkout. At 14:10 -> checkout = 14:10, source = auto_shift_end.
     */
    public function test_normal_auto_checkout_at_boundary(): void
    {
        Notification::fake();
        [$user, $employee] = $this->createEmployeeUser('Andi Pagi');
        $workDate = '2026-09-04';

        $schedule = EmployeeSchedule::create([
            'employee_id' => $employee->id,
            'work_date' => $workDate,
            'shift_id' => $this->morningShift->id,
            'work_outlet_id' => $this->outlet->id,
            'schedule_type' => 'work',
        ]);

        $record = $this->createSnapshottedRecord([
            'employee_id' => $employee->id,
            'work_schedule_id' => $schedule->id,
            'work_date' => $workDate,
            'attendance_location_id' => $this->location->id,
            'outlet_id' => $this->outlet->id,
            'check_in_at' => Carbon::parse('2026-09-04 06:00:00', 'Asia/Jakarta'),
            'auto_checkout_boundary' => Carbon::parse('2026-09-04 14:10:00', 'Asia/Jakarta'),
            'check_out_at' => null,
            'late_minutes' => 0,
        ]);

        $service = app(AutoCheckoutService::class);

        // At 14:09:59 (before boundary 14:10:00) -> query filters it out
        $timeBefore = Carbon::parse('2026-09-04 14:09:59', 'Asia/Jakarta');
        $resultBefore = $service->process($timeBefore);

        $this->assertEquals(0, $resultBefore['processed']);
        $this->assertEquals(0, $resultBefore['checked_out']);
        $this->assertNull($record->fresh()->check_out_at);

        // At 14:10:00 (exact boundary) -> should auto checkout
        $timeBoundary = Carbon::parse('2026-09-04 14:10:00', 'Asia/Jakarta');
        $resultAt = $service->process($timeBoundary);

        $this->assertEquals(1, $resultAt['processed']);
        $this->assertEquals(1, $resultAt['checked_out']);

        $fresh = $record->fresh();
        $this->assertNotNull($fresh->check_out_at);
        $this->assertEquals('2026-09-04 14:10:00', $fresh->check_out_at->format('Y-m-d H:i:s'));
        $this->assertEquals('auto_shift_end', $fresh->checkout_source);
        // Worked minutes: 06:00 to 14:10 = 490 min - 60 min break = 430 min
        $this->assertEquals(430, $fresh->worked_minutes);
        $this->assertEquals(0, $fresh->early_leave_minutes);
        $this->assertEquals(10, $fresh->overtime_minutes);

        Notification::assertSentTo($user, AutoCheckoutNotification::class);
    }

    /**
     * Legacy open attendance with NULL boundary is skipped and untouched.
     */
    public function test_legacy_open_attendance_with_null_boundary_is_skipped(): void
    {
        [$user, $employee] = $this->createEmployeeUser('Legacy Employee');
        $oldWorkDate = '2026-08-01';

        $schedule = EmployeeSchedule::create([
            'employee_id' => $employee->id,
            'work_date' => $oldWorkDate,
            'shift_id' => $this->morningShift->id,
            'work_outlet_id' => $this->outlet->id,
            'schedule_type' => 'work',
        ]);

        $record = AttendanceRecord::create([
            'employee_id' => $employee->id,
            'work_schedule_id' => $schedule->id,
            'work_date' => $oldWorkDate,
            'attendance_location_id' => $this->location->id,
            'outlet_id' => $this->outlet->id,
            'status' => 'present',
            'check_in_at' => Carbon::parse('2026-08-01 06:00:00', 'Asia/Jakarta'),
            'auto_checkout_boundary' => null, // Legacy record without snapshot
            'check_out_at' => null,
            'late_minutes' => 0,
        ]);

        $service = app(AutoCheckoutService::class);
        $now = Carbon::parse('2026-09-04 15:00:00', 'Asia/Jakarta');
        $result = $service->process($now);

        // Filtered out by candidate query because auto_checkout_boundary is NULL
        $this->assertEquals(0, $result['processed']);
        $this->assertEquals(0, $result['checked_out']);
        $this->assertNull($record->fresh()->check_out_at);

        // Direct record check returns explicit skipped status
        $directStatus = $service->processRecord($record, $now, 'Asia/Jakarta');
        $this->assertEquals('skipped', $directStatus['action']);
        $this->assertEquals('Auto checkout boundary snapshot is unavailable', $directStatus['reason']);
    }

    /**
     * Stale open attendance older than 7 days with valid snapshot is successfully processed and closed.
     */
    public function test_stale_open_attendance_older_than_7_days_with_snapshot_is_processed(): void
    {
        [$user, $employee] = $this->createEmployeeUser('Stale Employee');
        $oldWorkDate = '2026-08-01'; // Over a month ago

        $schedule = EmployeeSchedule::create([
            'employee_id' => $employee->id,
            'work_date' => $oldWorkDate,
            'shift_id' => $this->morningShift->id,
            'work_outlet_id' => $this->outlet->id,
            'schedule_type' => 'work',
        ]);

        $record = $this->createSnapshottedRecord([
            'employee_id' => $employee->id,
            'work_schedule_id' => $schedule->id,
            'work_date' => $oldWorkDate,
            'attendance_location_id' => $this->location->id,
            'outlet_id' => $this->outlet->id,
            'check_in_at' => Carbon::parse('2026-08-01 06:00:00', 'Asia/Jakarta'),
            'scheduled_shift_end_at' => Carbon::parse('2026-08-01 14:00:00', 'Asia/Jakarta'),
            'break_minutes_snapshot' => 60,
            'auto_checkout_boundary' => Carbon::parse('2026-08-01 14:10:00', 'Asia/Jakarta'),
            'check_out_at' => null,
            'late_minutes' => 0,
        ]);

        $service = app(AutoCheckoutService::class);
        $now = Carbon::parse('2026-09-04 15:00:00', 'Asia/Jakarta');
        $result = $service->process($now);

        $this->assertEquals(1, $result['checked_out']);

        $fresh = $record->fresh();
        $this->assertNotNull($fresh->check_out_at);
        $this->assertEquals('2026-08-01 14:10:00', $fresh->check_out_at->format('Y-m-d H:i:s'));
        $this->assertEquals('auto_shift_end', $fresh->checkout_source);
        $this->assertEquals(430, $fresh->worked_minutes);
    }

    /**
     * Missing boundary snapshot is safely skipped without crashing processing.
     */
    public function test_missing_or_invalid_shift_is_safely_skipped(): void
    {
        [$user, $employee] = $this->createEmployeeUser('NoShift Employee');
        $workDate = '2026-09-04';

        // Record exists without boundary snapshot
        $record = AttendanceRecord::create([
            'employee_id' => $employee->id,
            'work_schedule_id' => null,
            'work_date' => $workDate,
            'attendance_location_id' => $this->location->id,
            'outlet_id' => $this->outlet->id,
            'status' => 'present',
            'check_in_at' => Carbon::parse('2026-09-04 06:00:00', 'Asia/Jakarta'),
            'auto_checkout_boundary' => null,
            'check_out_at' => null,
        ]);

        $service = app(AutoCheckoutService::class);
        $result = $service->process(Carbon::parse('2026-09-04 15:00:00', 'Asia/Jakarta'));

        $this->assertEquals(0, $result['processed']);
        $this->assertEquals(0, $result['checked_out']);
        $this->assertNull($record->fresh()->check_out_at);
    }

    /**
     * Admin correction properly sets checkout_source to admin_correction when checkout is added or updated.
     */
    public function test_admin_correction_checkout_source_semantics(): void
    {
        [$user, $employee] = $this->createEmployeeUser('Koreksi Employee');
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $workDate = '2026-09-04';

        $schedule = EmployeeSchedule::create([
            'employee_id' => $employee->id,
            'work_date' => $workDate,
            'shift_id' => $this->morningShift->id,
            'work_outlet_id' => $this->outlet->id,
            'schedule_type' => 'work',
        ]);

        $record = AttendanceRecord::create([
            'employee_id' => $employee->id,
            'work_schedule_id' => $schedule->id,
            'work_date' => $workDate,
            'attendance_location_id' => $this->location->id,
            'outlet_id' => $this->outlet->id,
            'status' => 'present',
            'check_in_at' => Carbon::parse('2026-09-04 06:00:00', 'Asia/Jakarta'),
            'check_out_at' => null,
        ]);

        $attendanceService = app(\App\Services\AttendanceService::class);

        // Admin supplies missing checkout
        $corrected = $attendanceService->correctAttendanceRecord(
            record: $record,
            checkInStr: '06:00',
            checkOutStr: '14:00',
            reason: 'Karyawan lupa checkout manual',
            actor: $admin
        );

        $this->assertEquals('admin_correction', $corrected->checkout_source);
        $this->assertEquals('2026-09-04 14:00:00', $corrected->check_out_at->format('Y-m-d H:i:s'));
    }

    /**
     * Scenario B: Scheduler delayed execution (e.g. runs at 14:25).
     * Stored checkout timestamp must remain deterministic (14:10:00, not 14:25:00).
     */
    public function test_delayed_scheduler_preserves_deterministic_timestamp(): void
    {
        [$user, $employee] = $this->createEmployeeUser('Cici Delay');
        $workDate = '2026-09-04';

        $schedule = EmployeeSchedule::create([
            'employee_id' => $employee->id,
            'work_date' => $workDate,
            'shift_id' => $this->morningShift->id,
            'work_outlet_id' => $this->outlet->id,
            'schedule_type' => 'work',
        ]);

        $record = $this->createSnapshottedRecord([
            'employee_id' => $employee->id,
            'work_schedule_id' => $schedule->id,
            'work_date' => $workDate,
            'attendance_location_id' => $this->location->id,
            'outlet_id' => $this->outlet->id,
            'check_in_at' => Carbon::parse('2026-09-04 06:00:00', 'Asia/Jakarta'),
            'auto_checkout_boundary' => Carbon::parse('2026-09-04 14:10:00', 'Asia/Jakarta'),
            'check_out_at' => null,
        ]);

        $service = app(AutoCheckoutService::class);

        // Cron delayed, running at 14:25:00
        $delayedNow = Carbon::parse('2026-09-04 14:25:00', 'Asia/Jakarta');
        $result = $service->process($delayedNow);

        $this->assertEquals(1, $result['checked_out']);

        $fresh = $record->fresh();
        $this->assertEquals('2026-09-04 14:10:00', $fresh->check_out_at->format('Y-m-d H:i:s'));
        $this->assertEquals('auto_shift_end', $fresh->checkout_source);
        $this->assertEquals(430, $fresh->worked_minutes);
    }

    /**
     * Scenario C: Manual checkout before boundary (e.g. at 14:07).
     * Scheduler must not overwrite manual checkout.
     */
    public function test_manual_checkout_wins_and_is_not_overwritten(): void
    {
        [$user, $employee] = $this->createEmployeeUser('Dedi Manual');
        $workDate = '2026-09-04';

        $schedule = EmployeeSchedule::create([
            'employee_id' => $employee->id,
            'work_date' => $workDate,
            'shift_id' => $this->morningShift->id,
            'work_outlet_id' => $this->outlet->id,
            'schedule_type' => 'work',
        ]);

        $record = $this->createSnapshottedRecord([
            'employee_id' => $employee->id,
            'work_schedule_id' => $schedule->id,
            'work_date' => $workDate,
            'attendance_location_id' => $this->location->id,
            'outlet_id' => $this->outlet->id,
            'check_in_at' => Carbon::parse('2026-09-04 06:00:00', 'Asia/Jakarta'),
            'auto_checkout_boundary' => Carbon::parse('2026-09-04 14:10:00', 'Asia/Jakarta'),
            'check_out_at' => Carbon::parse('2026-09-04 14:07:00', 'Asia/Jakarta'),
            'checkout_source' => 'manual',
            'worked_minutes' => 427,
        ]);

        $service = app(AutoCheckoutService::class);
        $result = $service->process(Carbon::parse('2026-09-04 14:15:00', 'Asia/Jakarta'));

        $this->assertEquals(0, $result['checked_out']);
        $this->assertEquals(0, $result['processed']); // check_out_at is not null, so not a candidate

        $fresh = $record->fresh();
        $this->assertEquals('2026-09-04 14:07:00', $fresh->check_out_at->format('Y-m-d H:i:s'));
        $this->assertEquals('manual', $fresh->checkout_source);
    }

    /**
     * Scenario D: Already checked out record is skipped.
     */
    public function test_already_checked_out_record_is_skipped(): void
    {
        [$user, $employee] = $this->createEmployeeUser('Eka Done');
        $workDate = '2026-09-04';

        $this->createSnapshottedRecord([
            'employee_id' => $employee->id,
            'work_date' => $workDate,
            'check_in_at' => Carbon::parse('2026-09-04 06:00:00', 'Asia/Jakarta'),
            'auto_checkout_boundary' => Carbon::parse('2026-09-04 14:10:00', 'Asia/Jakarta'),
            'check_out_at' => Carbon::parse('2026-09-04 14:00:00', 'Asia/Jakarta'),
            'checkout_source' => 'manual',
        ]);

        $service = app(AutoCheckoutService::class);
        $result = $service->process(Carbon::parse('2026-09-04 14:30:00', 'Asia/Jakarta'));

        $this->assertEquals(0, $result['processed']);
        $this->assertEquals(0, $result['checked_out']);
    }

    /**
     * Scenario E: No check-in -> no attendance auto checkout created.
     */
    public function test_no_check_in_does_not_create_auto_checkout(): void
    {
        [$user, $employee] = $this->createEmployeeUser('Fani Absent');
        $workDate = '2026-09-04';

        EmployeeSchedule::create([
            'employee_id' => $employee->id,
            'work_date' => $workDate,
            'shift_id' => $this->morningShift->id,
            'work_outlet_id' => $this->outlet->id,
            'schedule_type' => 'work',
        ]);

        $service = app(AutoCheckoutService::class);
        $result = $service->process(Carbon::parse('2026-09-04 15:00:00', 'Asia/Jakarta'));

        $this->assertEquals(0, $result['processed']);
        $this->assertEquals(0, $result['checked_out']);
        $this->assertDatabaseMissing('attendance_records', [
            'employee_id' => $employee->id,
            'work_date' => $workDate,
        ]);
    }

    /**
     * Scenario F: Auto checkout disabled on shift -> boundary snapshot is null, record remains open.
     */
    public function test_auto_checkout_disabled_shift_remains_open(): void
    {
        $noAutoShift = Shift::create([
            'name' => 'Shift Khusus Manual',
            'code' => 'MANUAL',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'check_in_open_minutes_before' => 60,
            'check_in_close_minutes_after' => 120,
            'check_out_open_minutes_before' => 60,
            'grace_period_minutes' => 5,
            'break_minutes' => 60,
            'crosses_midnight' => false,
            'auto_checkout_enabled' => false,
            'auto_checkout_grace_minutes' => 10,
            'is_active' => true,
        ]);

        [$user, $employee] = $this->createEmployeeUser('Gita Manual');
        $workDate = '2026-09-04';

        $schedule = EmployeeSchedule::create([
            'employee_id' => $employee->id,
            'work_date' => $workDate,
            'shift_id' => $noAutoShift->id,
            'work_outlet_id' => $this->outlet->id,
            'schedule_type' => 'work',
        ]);

        $record = AttendanceRecord::create([
            'employee_id' => $employee->id,
            'work_schedule_id' => $schedule->id,
            'work_date' => $workDate,
            'attendance_location_id' => $this->location->id,
            'outlet_id' => $this->outlet->id,
            'status' => 'present',
            'check_in_at' => Carbon::parse('2026-09-04 08:00:00', 'Asia/Jakarta'),
            'auto_checkout_boundary' => null, // Disabled shift -> boundary is null
            'check_out_at' => null,
        ]);

        $service = app(AutoCheckoutService::class);
        $result = $service->process(Carbon::parse('2026-09-04 17:00:00', 'Asia/Jakarta'));

        $this->assertEquals(0, $result['processed']);
        $this->assertEquals(0, $result['checked_out']);
        $this->assertNull($record->fresh()->check_out_at);
    }

    /**
     * Scenario G: Grace = 0 -> checkout exactly at shift end.
     */
    public function test_grace_zero_checks_out_at_exact_shift_end(): void
    {
        $zeroGraceShift = Shift::create([
            'name' => 'Shift Zero Grace',
            'code' => 'ZEROGRACE',
            'start_time' => '07:00:00',
            'end_time' => '15:00:00',
            'check_in_open_minutes_before' => 60,
            'check_in_close_minutes_after' => 120,
            'check_out_open_minutes_before' => 60,
            'grace_period_minutes' => 5,
            'break_minutes' => 60,
            'crosses_midnight' => false,
            'auto_checkout_enabled' => true,
            'auto_checkout_grace_minutes' => 0,
            'is_active' => true,
        ]);

        [$user, $employee] = $this->createEmployeeUser('Hadi Zero');
        $workDate = '2026-09-04';

        $schedule = EmployeeSchedule::create([
            'employee_id' => $employee->id,
            'work_date' => $workDate,
            'shift_id' => $zeroGraceShift->id,
            'work_outlet_id' => $this->outlet->id,
            'schedule_type' => 'work',
        ]);

        $record = $this->createSnapshottedRecord([
            'employee_id' => $employee->id,
            'work_schedule_id' => $schedule->id,
            'work_date' => $workDate,
            'attendance_location_id' => $this->location->id,
            'outlet_id' => $this->outlet->id,
            'check_in_at' => Carbon::parse('2026-09-04 07:00:00', 'Asia/Jakarta'),
            'scheduled_shift_end_at' => Carbon::parse('2026-09-04 15:00:00', 'Asia/Jakarta'),
            'break_minutes_snapshot' => 60,
            'auto_checkout_boundary' => Carbon::parse('2026-09-04 15:00:00', 'Asia/Jakarta'),
            'check_out_at' => null,
        ]);

        $service = app(AutoCheckoutService::class);
        $result = $service->process(Carbon::parse('2026-09-04 15:00:00', 'Asia/Jakarta'));

        $this->assertEquals(1, $result['checked_out']);
        $fresh = $record->fresh();
        $this->assertEquals('2026-09-04 15:00:00', $fresh->check_out_at->format('Y-m-d H:i:s'));
        $this->assertEquals(420, $fresh->worked_minutes); // 8 hours - 60 min break = 420
    }

    /**
     * Scenario H: Cross-midnight shift (22:00-06:00).
     * work_date: 2026-09-04, Grace: 10 min -> Auto checkout on next day: 2026-09-05 06:10:00.
     */
    public function test_cross_midnight_auto_checkout(): void
    {
        [$user, $employee] = $this->createEmployeeUser('Indra Malam');
        $workDate = '2026-09-04';

        $schedule = EmployeeSchedule::create([
            'employee_id' => $employee->id,
            'work_date' => $workDate,
            'shift_id' => $this->nightShift->id,
            'work_outlet_id' => $this->outlet->id,
            'schedule_type' => 'work',
        ]);

        $record = $this->createSnapshottedRecord([
            'employee_id' => $employee->id,
            'work_schedule_id' => $schedule->id,
            'work_date' => $workDate,
            'attendance_location_id' => $this->location->id,
            'outlet_id' => $this->outlet->id,
            'check_in_at' => Carbon::parse('2026-09-04 22:00:00', 'Asia/Jakarta'),
            'scheduled_shift_end_at' => Carbon::parse('2026-09-05 06:00:00', 'Asia/Jakarta'),
            'break_minutes_snapshot' => 60,
            'auto_checkout_boundary' => Carbon::parse('2026-09-05 06:10:00', 'Asia/Jakarta'),
            'check_out_at' => null,
        ]);

        $service = app(AutoCheckoutService::class);

        // At 06:05:00 on 2026-09-05 (boundary is 06:10:00) -> not yet due
        $beforeResult = $service->process(Carbon::parse('2026-09-05 06:05:00', 'Asia/Jakarta'));
        $this->assertEquals(0, $beforeResult['processed']);
        $this->assertEquals(0, $beforeResult['checked_out']);
        $this->assertNull($record->fresh()->check_out_at);

        // At 06:10:00 on 2026-09-05 -> auto checkout
        $atResult = $service->process(Carbon::parse('2026-09-05 06:10:00', 'Asia/Jakarta'));
        $this->assertEquals(1, $atResult['processed']);
        $this->assertEquals(1, $atResult['checked_out']);

        $fresh = $record->fresh();
        $this->assertEquals('2026-09-05 06:10:00', $fresh->check_out_at->format('Y-m-d H:i:s'));
        $this->assertEquals('auto_shift_end', $fresh->checkout_source);
        // Worked minutes: 22:00 to 06:10 = 490 min - 60 min break = 430 min
        $this->assertEquals(430, $fresh->worked_minutes);
    }

    /**
     * Scenario I: Temporary WORK outlet does not change HOME outlet or attendance outlet snapshot.
     */
    public function test_temporary_work_outlet_is_preserved_during_auto_checkout(): void
    {
        [$user, $employee] = $this->createEmployeeUser('Joko Mutasi', $this->outlet);
        $workDate = '2026-09-04';

        // Override schedule at workOutlet
        EmployeeScheduleOverride::create([
            'employee_id' => $employee->id,
            'date' => $workDate,
            'override_type' => 'work',
            'shift_id' => $this->morningShift->id,
            'work_outlet_id' => $this->workOutlet->id,
            'reason' => 'Bantuan Cabang',
        ]);

        $record = $this->createSnapshottedRecord([
            'employee_id' => $employee->id,
            'work_schedule_id' => null,
            'work_date' => $workDate,
            'attendance_location_id' => $this->location->id,
            'outlet_id' => $this->workOutlet->id, // Branch outlet
            'check_in_at' => Carbon::parse('2026-09-04 06:00:00', 'Asia/Jakarta'),
            'auto_checkout_boundary' => Carbon::parse('2026-09-04 14:10:00', 'Asia/Jakarta'),
            'check_out_at' => null,
        ]);

        $service = app(AutoCheckoutService::class);
        $result = $service->process(Carbon::parse('2026-09-04 14:10:00', 'Asia/Jakarta'));

        $this->assertEquals(1, $result['checked_out']);

        $fresh = $record->fresh();
        $this->assertEquals('2026-09-04 14:10:00', $fresh->check_out_at->format('Y-m-d H:i:s'));
        $this->assertEquals($this->workOutlet->id, $fresh->outlet_id); // Attendance outlet snapshot preserved
        $this->assertEquals($this->outlet->id, $employee->fresh()->outlet_id); // HOME outlet untouched
    }

    /**
     * Scenario J: Approved leave without check-in -> no auto checkout.
     */
    public function test_approved_leave_without_check_in_is_safe(): void
    {
        [$user, $employee] = $this->createEmployeeUser('Kartika Cuti');
        $workDate = '2026-09-04';

        LeaveRequest::create([
            'employee_id' => $employee->id,
            'type' => 'leave',
            'start_date' => $workDate,
            'end_date' => $workDate,
            'reason' => 'Cuti tahunan',
            'status' => 'approved',
        ]);

        $service = app(AutoCheckoutService::class);
        $result = $service->process(Carbon::parse('2026-09-04 15:00:00', 'Asia/Jakarta'));

        $this->assertEquals(0, $result['processed']);
        $this->assertEquals(0, $result['checked_out']);
    }

    /**
     * Scenario K: Overtime safety. Regular auto checkout does not auto-finish active overtime session.
     */
    public function test_overtime_session_remains_independent_and_unmodified(): void
    {
        [$user, $employee] = $this->createEmployeeUser('Lina Lembur');
        $workDate = '2026-09-04';

        $schedule = EmployeeSchedule::create([
            'employee_id' => $employee->id,
            'work_date' => $workDate,
            'shift_id' => $this->morningShift->id,
            'work_outlet_id' => $this->outlet->id,
            'schedule_type' => 'work',
        ]);

        $record = $this->createSnapshottedRecord([
            'employee_id' => $employee->id,
            'work_schedule_id' => $schedule->id,
            'work_date' => $workDate,
            'attendance_location_id' => $this->location->id,
            'outlet_id' => $this->outlet->id,
            'check_in_at' => Carbon::parse('2026-09-04 06:00:00', 'Asia/Jakarta'),
            'auto_checkout_boundary' => Carbon::parse('2026-09-04 14:10:00', 'Asia/Jakarta'),
            'check_out_at' => null,
        ]);

        // Approved overtime request & active session
        $otRequest = OvertimeRequest::create([
            'employee_id' => $employee->id,
            'work_date' => $workDate,
            'requested_minutes' => 120,
            'approved_minutes' => 120,
            'reason' => 'Peak customer demand',
            'status' => 'approved',
        ]);

        $otSession = OvertimeSession::create([
            'overtime_request_id' => $otRequest->id,
            'employee_id' => $employee->id,
            'work_date' => $workDate,
            'status' => 'active',
            'check_in_at' => Carbon::parse('2026-09-04 14:15:00', 'Asia/Jakarta'),
            'started_at' => Carbon::parse('2026-09-04 14:15:00', 'Asia/Jakarta'),
            'check_in_latitude' => -6.2000000,
            'check_in_longitude' => 106.8166660,
            'check_in_accuracy_meters' => 10,
            'check_in_distance_meters' => 5,
        ]);

        $service = app(AutoCheckoutService::class);
        $result = $service->process(Carbon::parse('2026-09-04 14:20:00', 'Asia/Jakarta'));

        $this->assertEquals(1, $result['checked_out']);

        // Regular attendance checked out
        $freshRecord = $record->fresh();
        $this->assertEquals('2026-09-04 14:10:00', $freshRecord->check_out_at->format('Y-m-d H:i:s'));
        $this->assertEquals('auto_shift_end', $freshRecord->checkout_source);

        // Overtime session remains active and separate!
        $freshOt = $otSession->fresh();
        $this->assertEquals('active', $freshOt->status);
        $this->assertNull($freshOt->check_out_at);
        $this->assertNull($freshOt->completed_at);
    }

    /**
     * Scenario L: Idempotency. Running scheduler multiple times yields only 1 checkout, 1 audit, 1 notification.
     */
    public function test_scheduler_idempotency(): void
    {
        Notification::fake();
        [$user, $employee] = $this->createEmployeeUser('Maya Idempotent');
        $workDate = '2026-09-04';

        $schedule = EmployeeSchedule::create([
            'employee_id' => $employee->id,
            'work_date' => $workDate,
            'shift_id' => $this->morningShift->id,
            'work_outlet_id' => $this->outlet->id,
            'schedule_type' => 'work',
        ]);

        $record = $this->createSnapshottedRecord([
            'employee_id' => $employee->id,
            'work_schedule_id' => $schedule->id,
            'work_date' => $workDate,
            'attendance_location_id' => $this->location->id,
            'outlet_id' => $this->outlet->id,
            'check_in_at' => Carbon::parse('2026-09-04 06:00:00', 'Asia/Jakarta'),
            'auto_checkout_boundary' => Carbon::parse('2026-09-04 14:10:00', 'Asia/Jakarta'),
            'check_out_at' => null,
        ]);

        $service = app(AutoCheckoutService::class);
        $runTime = Carbon::parse('2026-09-04 14:12:00', 'Asia/Jakarta');

        // First run
        $res1 = $service->process($runTime);
        $this->assertEquals(1, $res1['checked_out']);

        // Second run
        $res2 = $service->process($runTime->copy()->addMinutes(1));
        $this->assertEquals(0, $res2['checked_out']);

        // Third run
        $res3 = $service->process($runTime->copy()->addMinutes(2));
        $this->assertEquals(0, $res3['checked_out']);

        // Audit Log count
        $auditCount = AuditLog::where('action', 'attendance.auto_checkout')
            ->where('auditable_id', $record->id)
            ->count();
        $this->assertEquals(1, $auditCount);

        // Notification count
        Notification::assertSentToTimes($user, AutoCheckoutNotification::class, 1);
    }

    /**
     * Scenario M: Artisan command execution output and status code.
     */
    public function test_artisan_command_output_and_success(): void
    {
        [$user, $employee] = $this->createEmployeeUser('Nando Artisan');
        $workDate = '2026-09-04';

        EmployeeSchedule::create([
            'employee_id' => $employee->id,
            'work_date' => $workDate,
            'shift_id' => $this->morningShift->id,
            'work_outlet_id' => $this->outlet->id,
            'schedule_type' => 'work',
        ]);

        $this->createSnapshottedRecord([
            'employee_id' => $employee->id,
            'work_date' => $workDate,
            'check_in_at' => Carbon::parse('2026-09-04 06:00:00', 'Asia/Jakarta'),
            'auto_checkout_boundary' => Carbon::parse('2026-09-04 14:10:00', 'Asia/Jakarta'),
            'check_out_at' => null,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-09-04 14:15:00', 'Asia/Jakarta'));

        $this->artisan('attendance:auto-checkout')
            ->expectsOutput('Auto checkout completed.')
            ->expectsOutput('Processed: 1')
            ->expectsOutput('Checked out: 1')
            ->expectsOutput('Skipped: 0')
            ->expectsOutput('Errors: 0')
            ->assertExitCode(0);
    }

    /**
     * Scenario N: Shift settings authorization (employees cannot update shifts, admins can).
     */
    public function test_shift_settings_authorization_and_validation(): void
    {
        [$empUser, $employee] = $this->createEmployeeUser('Oki Regular');
        $adminUser = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        // Employee cannot update shift (redirected or forbidden)
        $responseEmp = $this->actingAs($empUser)->put(route('admin.shifts.update', $this->morningShift), [
            'name' => 'Shift Pagi Updated',
            'code' => 'PAGI',
            'start_time' => '06:00',
            'end_time' => '14:00',
            'grace_period_minutes' => 5,
            'check_in_open_minutes_before' => 60,
            'check_in_close_minutes_after' => 120,
            'check_out_open_minutes_before' => 60,
            'break_minutes' => 60,
            'auto_checkout_enabled' => 1,
            'auto_checkout_grace_minutes' => 15,
        ]);
        $responseEmp->assertRedirect(route('employee.dashboard'));

        // Admin can update shift
        $responseAdmin = $this->actingAs($adminUser)->put(route('admin.shifts.update', $this->morningShift), [
            'name' => 'Shift Pagi Updated',
            'code' => 'PAGI',
            'start_time' => '06:00',
            'end_time' => '14:00',
            'grace_period_minutes' => 5,
            'check_in_open_minutes_before' => 60,
            'check_in_close_minutes_after' => 120,
            'check_out_open_minutes_before' => 60,
            'break_minutes' => 60,
            'auto_checkout_enabled' => 1,
            'auto_checkout_grace_minutes' => 15,
        ]);
        $responseAdmin->assertRedirect(route('admin.shifts.index'));

        $this->assertEquals(15, $this->morningShift->fresh()->auto_checkout_grace_minutes);
    }

    /**
     * Scenario P: Report compatibility. Auto checkout record appears seamlessly in attendance report.
     */
    public function test_auto_checkout_records_in_attendance_report(): void
    {
        [$user, $employee] = $this->createEmployeeUser('Putri Report');
        $workDate = '2026-09-04';

        $schedule = EmployeeSchedule::create([
            'employee_id' => $employee->id,
            'work_date' => $workDate,
            'shift_id' => $this->morningShift->id,
            'work_outlet_id' => $this->outlet->id,
            'schedule_type' => 'work',
        ]);

        $record = $this->createSnapshottedRecord([
            'employee_id' => $employee->id,
            'work_schedule_id' => $schedule->id,
            'work_date' => $workDate,
            'outlet_id' => $this->outlet->id,
            'check_in_at' => Carbon::parse('2026-09-04 06:00:00', 'Asia/Jakarta'),
            'auto_checkout_boundary' => Carbon::parse('2026-09-04 14:10:00', 'Asia/Jakarta'),
            'check_out_at' => null,
        ]);

        $service = app(AutoCheckoutService::class);
        $service->process(Carbon::parse('2026-09-04 14:15:00', 'Asia/Jakarta'));

        $reportService = app(ReportService::class);
        $report = $reportService->generateAttendanceReport([
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'outlet_id' => $this->outlet->id,
            'employee_id' => $employee->id,
            'status' => 'all',
        ]);

        $this->assertNotEmpty($report['detail_rows']);
        $row = collect($report['detail_rows'])->firstWhere('date_str', $workDate);
        $this->assertNotNull($row);
        $this->assertEquals('present', $row['status']);
        $this->assertEquals(430, $row['worked_minutes']);
        $this->assertEquals('auto_shift_end', $row['checkout_source']);
        $this->assertEquals('14:10', $row['check_out_at']->format('H:i'));
    }

    /**
     * Historical shift edited after check-in preserves immutable snapshot on AttendanceRecord.
     */
    public function test_historical_shift_edited_after_checkin_preserves_immutable_snapshot(): void
    {
        [$user, $employee] = $this->createEmployeeUser('Snapshot Edit Employee');
        $workDate = '2026-09-04';

        $schedule = EmployeeSchedule::create([
            'employee_id' => $employee->id,
            'work_date' => $workDate,
            'shift_id' => $this->morningShift->id,
            'work_outlet_id' => $this->outlet->id,
            'schedule_type' => 'work',
        ]);

        $record = $this->createSnapshottedRecord([
            'employee_id' => $employee->id,
            'work_schedule_id' => $schedule->id,
            'work_date' => $workDate,
            'attendance_location_id' => $this->location->id,
            'outlet_id' => $this->outlet->id,
            'check_in_at' => Carbon::parse('2026-09-04 06:00:00', 'Asia/Jakarta'),
            'auto_checkout_boundary' => Carbon::parse('2026-09-04 14:10:00', 'Asia/Jakarta'),
            'check_out_at' => null,
        ]);

        // Shift is edited later by admin to end at 18:00 instead of 14:00
        $this->morningShift->update([
            'end_time' => '18:00:00',
            'auto_checkout_grace_minutes' => 30,
        ]);

        $service = app(AutoCheckoutService::class);
        $result = $service->process(Carbon::parse('2026-09-04 14:10:00', 'Asia/Jakarta'));

        $this->assertEquals(1, $result['checked_out']);

        $fresh = $record->fresh();
        // Preserves original 14:10:00 boundary from snapshot rather than mutated 18:30:00!
        $this->assertEquals('2026-09-04 14:10:00', $fresh->check_out_at->format('Y-m-d H:i:s'));
        $this->assertEquals('auto_shift_end', $fresh->checkout_source);
    }

    /**
     * Historical shift deleted/missing after check-in safely closes if snapshot exists.
     */
    public function test_historical_shift_deleted_after_checkin_preserves_immutable_snapshot(): void
    {
        [$user, $employee] = $this->createEmployeeUser('Snapshot Deleted Shift Employee');
        $workDate = '2026-09-04';

        $record = $this->createSnapshottedRecord([
            'employee_id' => $employee->id,
            'work_schedule_id' => null, // Schedule/shift detached or deleted
            'work_date' => $workDate,
            'attendance_location_id' => $this->location->id,
            'outlet_id' => $this->outlet->id,
            'check_in_at' => Carbon::parse('2026-09-04 06:00:00', 'Asia/Jakarta'),
            'auto_checkout_boundary' => Carbon::parse('2026-09-04 14:10:00', 'Asia/Jakarta'),
            'check_out_at' => null,
        ]);

        $service = app(AutoCheckoutService::class);
        $result = $service->process(Carbon::parse('2026-09-04 14:10:00', 'Asia/Jakarta'));

        $this->assertEquals(1, $result['checked_out']);

        $fresh = $record->fresh();
        $this->assertEquals('2026-09-04 14:10:00', $fresh->check_out_at->format('Y-m-d H:i:s'));
        $this->assertEquals('auto_shift_end', $fresh->checkout_source);
    }

    /**
     * Chunk and batch processing handles multiple candidate records properly.
     */
    public function test_chunk_batch_processing_handles_multiple_records(): void
    {
        $workDate = '2026-09-04';
        $records = [];

        for ($i = 1; $i <= 5; $i++) {
            [$u, $emp] = $this->createEmployeeUser("Batch Employee {$i}");
            $sch = EmployeeSchedule::create([
                'employee_id' => $emp->id,
                'work_date' => $workDate,
                'shift_id' => $this->morningShift->id,
                'work_outlet_id' => $this->outlet->id,
                'schedule_type' => 'work',
            ]);
            $records[] = $this->createSnapshottedRecord([
                'employee_id' => $emp->id,
                'work_schedule_id' => $sch->id,
                'work_date' => $workDate,
                'attendance_location_id' => $this->location->id,
                'outlet_id' => $this->outlet->id,
                'check_in_at' => Carbon::parse('2026-09-04 06:00:00', 'Asia/Jakarta'),
                'auto_checkout_boundary' => Carbon::parse('2026-09-04 14:10:00', 'Asia/Jakarta'),
                'check_out_at' => null,
            ]);
        }

        $service = app(AutoCheckoutService::class);
        $result = $service->process(Carbon::parse('2026-09-04 14:10:00', 'Asia/Jakarta'));

        $this->assertEquals(5, $result['processed']);
        $this->assertEquals(5, $result['checked_out']);
        $this->assertEquals(0, $result['errors']);

        foreach ($records as $r) {
            $this->assertNotNull($r->fresh()->check_out_at);
            $this->assertEquals('auto_shift_end', $r->fresh()->checkout_source);
        }
    }

    /**
     * Legacy attendance linked to a currently enabled shift is still skipped if boundary snapshot is null.
     */
    public function test_legacy_attendance_linked_to_currently_enabled_shift_is_still_skipped(): void
    {
        [$user, $employee] = $this->createEmployeeUser('Legacy Linked Emp');
        $workDate = '2026-08-15';

        // Shift is currently enabled for auto checkout
        $this->assertTrue($this->morningShift->auto_checkout_enabled);

        $schedule = EmployeeSchedule::create([
            'employee_id' => $employee->id,
            'work_date' => $workDate,
            'shift_id' => $this->morningShift->id,
            'work_outlet_id' => $this->outlet->id,
            'schedule_type' => 'work',
        ]);

        $record = AttendanceRecord::create([
            'employee_id' => $employee->id,
            'work_schedule_id' => $schedule->id,
            'work_date' => $workDate,
            'attendance_location_id' => $this->location->id,
            'outlet_id' => $this->outlet->id,
            'status' => 'present',
            'check_in_at' => Carbon::parse('2026-08-15 06:00:00', 'Asia/Jakarta'),
            'auto_checkout_boundary' => null, // Snapshot is null from before feature rollout
            'check_out_at' => null,
        ]);

        $service = app(AutoCheckoutService::class);
        $result = $service->process(Carbon::parse('2026-09-04 15:00:00', 'Asia/Jakarta'));

        $this->assertEquals(0, $result['processed']);
        $this->assertEquals(0, $result['checked_out']);
        $this->assertNull($record->fresh()->check_out_at);
    }

    /**
     * Existing shift enabled after old attendance check-in leaves old attendance untouched.
     */
    public function test_existing_shift_enabled_after_old_attendance_checkin_leaves_old_attendance_untouched(): void
    {
        $existingShift = Shift::create([
            'name' => 'Shift Sore Existing',
            'code' => 'SORE-EX',
            'start_time' => '14:00:00',
            'end_time' => '22:00:00',
            'check_in_open_minutes_before' => 60,
            'check_in_close_minutes_after' => 120,
            'check_out_open_minutes_before' => 60,
            'grace_period_minutes' => 5,
            'break_minutes' => 60,
            'crosses_midnight' => false,
            'auto_checkout_enabled' => false, // Initially disabled on rollout
            'auto_checkout_grace_minutes' => 10,
            'is_active' => true,
        ]);

        [$user, $employee] = $this->createEmployeeUser('Old Attendance Emp');
        $workDate = '2026-09-04';

        $schedule = EmployeeSchedule::create([
            'employee_id' => $employee->id,
            'work_date' => $workDate,
            'shift_id' => $existingShift->id,
            'work_outlet_id' => $this->outlet->id,
            'schedule_type' => 'work',
        ]);

        // Employee checked in while auto_checkout_enabled was false -> boundary is null
        $oldRecord = AttendanceRecord::create([
            'employee_id' => $employee->id,
            'work_schedule_id' => $schedule->id,
            'work_date' => $workDate,
            'attendance_location_id' => $this->location->id,
            'outlet_id' => $this->outlet->id,
            'status' => 'present',
            'check_in_at' => Carbon::parse('2026-09-04 14:00:00', 'Asia/Jakarta'),
            'auto_checkout_boundary' => null,
            'check_out_at' => null,
        ]);

        // Admin later enables auto checkout on this shift
        $existingShift->update([
            'auto_checkout_enabled' => true,
            'auto_checkout_grace_minutes' => 15,
        ]);

        // Scheduler runs after shift end (22:15:00)
        $service = app(AutoCheckoutService::class);
        $result = $service->process(Carbon::parse('2026-09-04 22:30:00', 'Asia/Jakarta'));

        // Old attendance without snapshot is untouched
        $this->assertEquals(0, $result['processed']);
        $this->assertEquals(0, $result['checked_out']);
        $this->assertNull($oldRecord->fresh()->check_out_at);
        $this->assertNull($oldRecord->fresh()->auto_checkout_boundary);
    }

    /**
     * New check-in after shift enabled creates boundary snapshot and auto checkout succeeds.
     */
    public function test_new_checkin_after_shift_enabled_creates_boundary_snapshot_and_succeeds(): void
    {
        $existingShift = Shift::create([
            'name' => 'Shift Sore New',
            'code' => 'SORE-NEW',
            'start_time' => '14:00:00',
            'end_time' => '22:00:00',
            'check_in_open_minutes_before' => 60,
            'check_in_close_minutes_after' => 120,
            'check_out_open_minutes_before' => 60,
            'grace_period_minutes' => 5,
            'break_minutes' => 60,
            'crosses_midnight' => false,
            'auto_checkout_enabled' => false,
            'auto_checkout_grace_minutes' => 15,
            'is_active' => true,
        ]);

        // Admin enables auto checkout
        $existingShift->update([
            'auto_checkout_enabled' => true,
        ]);

        [$user, $employee] = $this->createEmployeeUser('New Checkin Emp');
        $workDate = '2026-09-04';

        $schedule = EmployeeSchedule::create([
            'employee_id' => $employee->id,
            'work_date' => $workDate,
            'shift_id' => $existingShift->id,
            'work_outlet_id' => $this->outlet->id,
            'schedule_type' => 'work',
        ]);

        // Perform Check-in via AttendanceService
        Storage::fake('public');
        Carbon::setTestNow(Carbon::parse('2026-09-04 14:00:00', 'Asia/Jakarta'));
        $attendanceService = app(\App\Services\AttendanceService::class);

        $record = $attendanceService->checkIn($user, [
            'latitude' => $this->outlet->latitude,
            'longitude' => $this->outlet->longitude,
            'accuracy' => 10,
            'selfie' => UploadedFile::fake()->image('selfie.jpg'),
        ]);

        // Boundary is snapshotted: 22:00 + 15 min = 22:15:00
        $this->assertNotNull($record->auto_checkout_boundary);
        $this->assertEquals('2026-09-04 22:15:00', $record->auto_checkout_boundary->format('Y-m-d H:i:s'));

        // Run auto checkout scheduler at 22:15:00
        $service = app(AutoCheckoutService::class);
        $result = $service->process(Carbon::parse('2026-09-04 22:15:00', 'Asia/Jakarta'));

        $this->assertEquals(1, $result['checked_out']);
        $fresh = $record->fresh();
        $this->assertNotNull($fresh->check_out_at);
        $this->assertEquals('2026-09-04 22:15:00', $fresh->check_out_at->format('Y-m-d H:i:s'));
        $this->assertEquals('auto_shift_end', $fresh->checkout_source);
    }

    /**
     * Test A & B: Check-in snapshots scheduled shift end and break minutes.
     */
    public function test_checkin_snapshots_scheduled_shift_end_and_break_minutes(): void
    {
        Storage::fake('public');
        [$user, $employee] = $this->createEmployeeUser('Snapshot Checkin Emp');
        $workDate = '2026-09-04';

        EmployeeSchedule::create([
            'employee_id' => $employee->id,
            'work_date' => $workDate,
            'shift_id' => $this->morningShift->id,
            'work_outlet_id' => $this->outlet->id,
            'schedule_type' => 'work',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-09-04 06:00:00', 'Asia/Jakarta'));
        $service = app(\App\Services\AttendanceService::class);
        $record = $service->checkIn($user, [
            'latitude' => $this->outlet->latitude,
            'longitude' => $this->outlet->longitude,
            'accuracy' => 10,
            'selfie' => UploadedFile::fake()->image('selfie.jpg'),
        ]);

        $this->assertNotNull($record->scheduled_shift_end_at);
        $this->assertEquals('2026-09-04 14:00:00', $record->scheduled_shift_end_at->format('Y-m-d H:i:s'));
        $this->assertEquals(60, $record->break_minutes_snapshot);
        $this->assertEquals('2026-09-04 14:10:00', $record->auto_checkout_boundary->format('Y-m-d H:i:s'));
    }

    /**
     * Test C: Cross-midnight scheduled end snapshot is next day.
     */
    public function test_checkin_cross_midnight_scheduled_end_snapshot_is_next_day(): void
    {
        Storage::fake('public');
        [$user, $employee] = $this->createEmployeeUser('Midnight Snapshot Emp');
        $workDate = '2026-09-04';

        EmployeeSchedule::create([
            'employee_id' => $employee->id,
            'work_date' => $workDate,
            'shift_id' => $this->nightShift->id,
            'work_outlet_id' => $this->outlet->id,
            'schedule_type' => 'work',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-09-04 22:00:00', 'Asia/Jakarta'));
        $service = app(\App\Services\AttendanceService::class);
        $record = $service->checkIn($user, [
            'latitude' => $this->outlet->latitude,
            'longitude' => $this->outlet->longitude,
            'accuracy' => 10,
            'selfie' => UploadedFile::fake()->image('selfie.jpg'),
        ]);

        $this->assertNotNull($record->scheduled_shift_end_at);
        $this->assertEquals('2026-09-05 06:00:00', $record->scheduled_shift_end_at->format('Y-m-d H:i:s'));
        $this->assertEquals(60, $record->break_minutes_snapshot);
        $this->assertEquals('2026-09-05 06:10:00', $record->auto_checkout_boundary->format('Y-m-d H:i:s'));
    }

    /**
     * Test D & E & F: Shift end, break minutes, and grace edited after check-in do not affect auto checkout metrics.
     */
    public function test_shift_end_break_and_grace_edited_after_checkin_does_not_affect_metrics(): void
    {
        [$user, $employee] = $this->createEmployeeUser('Shift Edit Metric Emp');
        $workDate = '2026-09-04';

        $schedule = EmployeeSchedule::create([
            'employee_id' => $employee->id,
            'work_date' => $workDate,
            'shift_id' => $this->morningShift->id,
            'work_outlet_id' => $this->outlet->id,
            'schedule_type' => 'work',
        ]);

        // Record created with snapshot: shift 06:00-14:00, break 60, grace 10 -> boundary 14:10:00
        $record = AttendanceRecord::create([
            'employee_id' => $employee->id,
            'work_schedule_id' => $schedule->id,
            'work_date' => $workDate,
            'attendance_location_id' => $this->location->id,
            'outlet_id' => $this->outlet->id,
            'status' => 'present',
            'check_in_at' => Carbon::parse('2026-09-04 06:00:00', 'Asia/Jakarta'),
            'scheduled_shift_end_at' => Carbon::parse('2026-09-04 14:00:00', 'Asia/Jakarta'),
            'break_minutes_snapshot' => 60,
            'auto_checkout_boundary' => Carbon::parse('2026-09-04 14:10:00', 'Asia/Jakarta'),
            'check_out_at' => null,
        ]);

        // Admin subsequently modifies the shift definition
        $this->morningShift->update([
            'start_time' => '07:00:00',
            'end_time' => '18:00:00',
            'break_minutes' => 30,
            'auto_checkout_grace_minutes' => 20,
        ]);

        $service = app(AutoCheckoutService::class);
        $result = $service->process(Carbon::parse('2026-09-04 14:10:00', 'Asia/Jakarta'));

        $this->assertEquals(1, $result['checked_out']);
        $fresh = $record->fresh();
        $this->assertEquals('2026-09-04 14:10:00', $fresh->check_out_at->format('Y-m-d H:i:s'));
        $this->assertEquals('auto_shift_end', $fresh->checkout_source);
        // Worked: 06:00 to 14:10 = 490 min - 60 min break snapshot = 430 min (NOT modified by break=30!)
        $this->assertEquals(430, $fresh->worked_minutes);
        // Overtime: 14:10 vs 14:00 scheduled end snapshot = 10 min (NOT modified by end_time=18:00!)
        $this->assertEquals(10, $fresh->overtime_minutes);
        $this->assertEquals(0, $fresh->early_leave_minutes);
    }

    /**
     * Test G: Shift/Schedule deleted after check-in still auto checkouts correctly using snapshots.
     */
    public function test_shift_deleted_after_checkin_still_processes_using_snapshots(): void
    {
        [$user, $employee] = $this->createEmployeeUser('Shift Deleted Metric Emp');
        $workDate = '2026-09-04';

        $record = AttendanceRecord::create([
            'employee_id' => $employee->id,
            'work_schedule_id' => null, // Schedule/Shift unlinked or deleted
            'work_date' => $workDate,
            'attendance_location_id' => $this->location->id,
            'outlet_id' => $this->outlet->id,
            'status' => 'present',
            'check_in_at' => Carbon::parse('2026-09-04 06:00:00', 'Asia/Jakarta'),
            'scheduled_shift_end_at' => Carbon::parse('2026-09-04 14:00:00', 'Asia/Jakarta'),
            'break_minutes_snapshot' => 60,
            'auto_checkout_boundary' => Carbon::parse('2026-09-04 14:10:00', 'Asia/Jakarta'),
            'check_out_at' => null,
        ]);

        $service = app(AutoCheckoutService::class);
        $result = $service->process(Carbon::parse('2026-09-04 14:10:00', 'Asia/Jakarta'));

        $this->assertEquals(1, $result['checked_out']);
        $fresh = $record->fresh();
        $this->assertEquals('2026-09-04 14:10:00', $fresh->check_out_at->format('Y-m-d H:i:s'));
        $this->assertEquals('auto_shift_end', $fresh->checkout_source);
        $this->assertEquals(430, $fresh->worked_minutes);
        $this->assertEquals(10, $fresh->overtime_minutes);
    }

    /**
     * Incomplete/partial snapshots (e.g. missing scheduled_shift_end_at or break_minutes_snapshot)
     * cause auto checkout to safely skip without guessing or reading mutable shift values.
     */
    public function test_incomplete_snapshots_cause_auto_checkout_to_safely_skip_without_mutable_fallback(): void
    {
        [$user, $employee] = $this->createEmployeeUser('Partial Snapshot Emp');
        $workDate = '2026-09-04';

        $schedule = EmployeeSchedule::create([
            'employee_id' => $employee->id,
            'work_date' => $workDate,
            'shift_id' => $this->morningShift->id,
            'work_outlet_id' => $this->outlet->id,
            'schedule_type' => 'work',
        ]);

        // Boundary is set, but scheduled_shift_end_at and break_minutes_snapshot are NULL
        $record = AttendanceRecord::create([
            'employee_id' => $employee->id,
            'work_schedule_id' => $schedule->id,
            'work_date' => $workDate,
            'attendance_location_id' => $this->location->id,
            'outlet_id' => $this->outlet->id,
            'status' => 'present',
            'check_in_at' => Carbon::parse('2026-09-04 06:00:00', 'Asia/Jakarta'),
            'auto_checkout_boundary' => Carbon::parse('2026-09-04 14:10:00', 'Asia/Jakarta'),
            'scheduled_shift_end_at' => null, // INCOMPLETE
            'break_minutes_snapshot' => null, // INCOMPLETE
            'check_out_at' => null,
        ]);

        $service = app(AutoCheckoutService::class);
        $result = $service->process(Carbon::parse('2026-09-04 14:10:00', 'Asia/Jakarta'));

        // Safely skipped
        $this->assertEquals(0, $result['checked_out']);
        $this->assertEquals(1, $result['skipped']);
        $this->assertNull($record->fresh()->check_out_at);

        // Direct record check returns explicit skipped status
        $directStatus = $service->processRecord($record, Carbon::parse('2026-09-04 14:10:00', 'Asia/Jakarta'), 'Asia/Jakarta');
        $this->assertEquals('skipped', $directStatus['action']);
        $this->assertStringContainsString('Incomplete metric snapshots', $directStatus['reason']);
    }

    /**
     * Scenario Q: Scheduler Registration Test.
     * Verify attendance:auto-checkout is scheduled every minute in Asia/Jakarta timezone.
     */
    public function test_scheduler_registers_auto_checkout_command_every_minute(): void
    {
        $schedule = app(\Illuminate\Console\Scheduling\Schedule::class);
        $events = collect($schedule->events());

        $autoCheckoutEvent = $events->first(function ($event) {
            return str_contains((string) ($event->command ?? ''), 'attendance:auto-checkout');
        });

        $this->assertNotNull($autoCheckoutEvent, 'attendance:auto-checkout command must be registered in the scheduler');
        $this->assertEquals('* * * * *', $autoCheckoutEvent->expression);
        $this->assertEquals('Asia/Jakarta', $autoCheckoutEvent->timezone);
    }
}


