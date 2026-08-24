<?php

namespace Tests\Feature\Admin;

use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmployeeOutletTransfer;
use App\Models\EmployeeSchedule;
use App\Models\EmployeeScheduleOverride;
use App\Models\LeaveRequest;
use App\Models\Outlet;
use App\Models\OvertimeRequest;
use App\Models\OvertimeSession;
use App\Models\Shift;
use App\Models\User;
use App\Services\AttendanceMonitoringService;
use App\Services\AttendanceService;
use App\Services\EffectiveScheduleService;
use App\Services\OvertimeSessionService;
use App\Services\ShiftSwapService;
use App\Services\WorkCalendarService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Tests\TestCase;

class TemporaryCrossOutletAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected Outlet $outletPusat;
    protected Outlet $outletCabang;
    protected Shift $shiftPagi;
    protected Employee $employeePusat;
    protected User $userPusat;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-08-25 08:00:00');

        $this->outletPusat = Outlet::firstOrCreate(
            ['code' => 'PUSAT'],
            [
                'name' => 'Kopi Selon Pusat',
                'latitude' => -6.200000,
                'longitude' => 106.816666,
                'radius_meters' => 100,
                'max_accuracy_meters' => 100,
                'is_active' => true,
            ]
        );

        $this->outletCabang = Outlet::firstOrCreate(
            ['code' => 'CABANG'],
            [
                'name' => 'Kopi Selon Cabang',
                'latitude' => -6.300000,
                'longitude' => 106.900000,
                'radius_meters' => 100,
                'max_accuracy_meters' => 100,
                'is_active' => true,
            ]
        );

        $this->shiftPagi = Shift::firstOrCreate(
            ['code' => 'PAGI'],
            [
                'name' => 'Shift Pagi',
                'start_time' => '08:00:00',
                'end_time' => '16:00:00',
                'is_active' => true,
            ]
        );

        $empId = DB::table('employees')->insertGetId([
            'employee_code' => 'SB-100',
            'full_name' => 'Karyawan Pusat',
            'email' => 'karyawan.pusat@selon.com',
            'phone' => '081234567890',
            'outlet_id' => $this->outletPusat->id,
            'status' => 'active',
            'attendance_enabled' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->employeePusat = Employee::find($empId);

        $this->userPusat = User::create([
            'employee_id' => $empId,
            'outlet_id' => $this->outletPusat->id,
            'name' => 'Karyawan Pusat',
            'email' => 'karyawan.pusat@selon.com',
            'password' => Hash::make('password'),
            'role' => 'employee',
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function createAdmin(string $role = 'admin', string $accessMode = 'all', array $assignedOutlets = []): User
    {
        $user = User::create([
            'name' => 'Test Admin ' . rand(100, 999),
            'email' => 'admin.' . rand(1000, 9999) . '@selon.com',
            'password' => Hash::make('password'),
            'role' => $role,
            'outlet_access_mode' => $accessMode,
            'outlet_id' => $this->outletPusat->id,
            'is_active' => true,
        ]);

        if (! empty($assignedOutlets)) {
            $user->assignedOutlets()->sync($assignedOutlets);
        }

        return $user;
    }

    protected function fakeSelfie(): UploadedFile
    {
        return UploadedFile::fake()->image('selfie.jpg');
    }

    public function test_1_admin_pusat_and_cabang_assigns_pusat_employee_to_cabang(): void
    {
        $admin = $this->createAdmin('admin', 'selected', [$this->outletPusat->id, $this->outletCabang->id]);
        $service = app(WorkCalendarService::class);

        $override = $service->saveOverride([
            'employee_id' => $this->employeePusat->id,
            'date' => '2026-08-25',
            'override_type' => 'work',
            'shift_id' => $this->shiftPagi->id,
            'work_outlet_id' => $this->outletCabang->id,
            'reason' => 'Penugasan sementara ke outlet cabang',
        ], $admin);

        $this->assertNotNull($override);
        $this->assertEquals($this->outletCabang->id, $override->work_outlet_id);
    }

    public function test_2_home_outlet_remains_unchanged(): void
    {
        $admin = $this->createAdmin('admin', 'selected', [$this->outletPusat->id, $this->outletCabang->id]);
        $service = app(WorkCalendarService::class);

        $service->saveOverride([
            'employee_id' => $this->employeePusat->id,
            'date' => '2026-08-25',
            'override_type' => 'work',
            'shift_id' => $this->shiftPagi->id,
            'work_outlet_id' => $this->outletCabang->id,
            'reason' => 'Bantu operasional cabang',
        ], $admin);

        $this->employeePusat->refresh();
        $this->assertEquals($this->outletPusat->id, $this->employeePusat->outlet_id);
    }

    public function test_3_effective_work_outlet_becomes_cabang_for_assigned_date(): void
    {
        $admin = $this->createAdmin('admin', 'selected', [$this->outletPusat->id, $this->outletCabang->id]);
        $service = app(WorkCalendarService::class);

        $service->saveOverride([
            'employee_id' => $this->employeePusat->id,
            'date' => '2026-08-25',
            'override_type' => 'work',
            'shift_id' => $this->shiftPagi->id,
            'work_outlet_id' => $this->outletCabang->id,
            'reason' => 'Bantu operasional cabang',
        ], $admin);

        $effectiveService = app(EffectiveScheduleService::class);
        $effective = $effectiveService->resolve($this->employeePusat, '2026-08-25');

        $this->assertEquals($this->outletCabang->id, $effective['work_outlet_id']);
        $this->assertEquals('Kopi Selon Cabang', $effective['work_outlet']->name);
    }

    public function test_4_admin_pusat_only_cannot_assign_to_cabang(): void
    {
        $adminPusatOnly = $this->createAdmin('admin', 'selected', [$this->outletPusat->id]);
        $service = app(WorkCalendarService::class);

        $this->expectException(AccessDeniedHttpException::class);

        $service->saveOverride([
            'employee_id' => $this->employeePusat->id,
            'date' => '2026-08-25',
            'override_type' => 'work',
            'shift_id' => $this->shiftPagi->id,
            'work_outlet_id' => $this->outletCabang->id,
            'reason' => 'Percobaan assign ke cabang tanpa akses',
        ], $adminPusatOnly);
    }

    public function test_5_admin_cabang_only_cannot_assign_pusat_employee(): void
    {
        $adminCabangOnly = $this->createAdmin('admin', 'selected', [$this->outletCabang->id]);
        $service = app(WorkCalendarService::class);

        $this->expectException(AccessDeniedHttpException::class);

        $service->saveOverride([
            'employee_id' => $this->employeePusat->id,
            'date' => '2026-08-25',
            'override_type' => 'work',
            'shift_id' => $this->shiftPagi->id,
            'work_outlet_id' => $this->outletCabang->id,
            'reason' => 'Percobaan ambil karyawan pusat dari cabang',
        ], $adminCabangOnly);
    }

    public function test_6_admin_with_zero_assignments_fails_closed(): void
    {
        $adminZero = $this->createAdmin('admin', 'selected', []);
        $service = app(WorkCalendarService::class);

        $this->expectException(AccessDeniedHttpException::class);

        $service->saveOverride([
            'employee_id' => $this->employeePusat->id,
            'date' => '2026-08-25',
            'override_type' => 'work',
            'shift_id' => $this->shiftPagi->id,
            'work_outlet_id' => $this->outletCabang->id,
            'reason' => 'Admin tanpa outlet terdaftar',
        ], $adminZero);
    }

    public function test_7_admin_all_outlet_succeeds(): void
    {
        $adminAll = $this->createAdmin('admin', 'all', []);
        $service = app(WorkCalendarService::class);

        $override = $service->saveOverride([
            'employee_id' => $this->employeePusat->id,
            'date' => '2026-08-25',
            'override_type' => 'work',
            'shift_id' => $this->shiftPagi->id,
            'work_outlet_id' => $this->outletCabang->id,
            'reason' => 'Penugasan oleh admin all-mode',
        ], $adminAll);

        $this->assertNotNull($override);
    }

    public function test_8_owner_succeeds(): void
    {
        $owner = $this->createAdmin('owner', 'selected', []);
        $service = app(WorkCalendarService::class);

        $override = $service->saveOverride([
            'employee_id' => $this->employeePusat->id,
            'date' => '2026-08-25',
            'override_type' => 'work',
            'shift_id' => $this->shiftPagi->id,
            'work_outlet_id' => $this->outletCabang->id,
            'reason' => 'Penugasan oleh owner',
        ], $owner);

        $this->assertNotNull($override);
    }

    public function test_9_superadmin_succeeds(): void
    {
        $superadmin = $this->createAdmin('superadmin', 'selected', []);
        $service = app(WorkCalendarService::class);

        $override = $service->saveOverride([
            'employee_id' => $this->employeePusat->id,
            'date' => '2026-08-25',
            'override_type' => 'work',
            'shift_id' => $this->shiftPagi->id,
            'work_outlet_id' => $this->outletCabang->id,
            'reason' => 'Penugasan oleh superadmin',
        ], $superadmin);

        $this->assertNotNull($override);
    }

    public function test_10_forged_employee_id_blocked(): void
    {
        $admin = $this->createAdmin('admin', 'all');
        $service = app(WorkCalendarService::class);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $service->saveOverride([
            'employee_id' => 99999,
            'date' => '2026-08-25',
            'override_type' => 'work',
            'shift_id' => $this->shiftPagi->id,
            'work_outlet_id' => $this->outletCabang->id,
            'reason' => 'Employee ID palsu',
        ], $admin);
    }

    public function test_11_forged_work_outlet_id_blocked(): void
    {
        $admin = $this->createAdmin('admin', 'all');
        $service = app(WorkCalendarService::class);

        $this->expectException(\InvalidArgumentException::class);

        $service->saveOverride([
            'employee_id' => $this->employeePusat->id,
            'date' => '2026-08-25',
            'override_type' => 'work',
            'shift_id' => $this->shiftPagi->id,
            'work_outlet_id' => 88888,
            'reason' => 'Work Outlet ID palsu',
        ], $admin);
    }

    public function test_12_inactive_work_outlet_blocked(): void
    {
        $inactiveOutlet = Outlet::create([
            'name' => 'Outlet Tutup',
            'code' => 'TUTUP',
            'latitude' => -6.4,
            'longitude' => 106.9,
            'radius_meters' => 100,
            'max_accuracy_meters' => 100,
            'is_active' => false,
        ]);

        $admin = $this->createAdmin('admin', 'all');
        $service = app(WorkCalendarService::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('aktif');

        $service->saveOverride([
            'employee_id' => $this->employeePusat->id,
            'date' => '2026-08-25',
            'override_type' => 'work',
            'shift_id' => $this->shiftPagi->id,
            'work_outlet_id' => $inactiveOutlet->id,
            'reason' => 'Penugasan ke outlet non-aktif',
        ], $admin);
    }

    public function test_13_soft_deleted_employee_blocked(): void
    {
        $deletedEmpId = DB::table('employees')->insertGetId([
            'employee_code' => 'SB-DEL',
            'full_name' => 'Karyawan Hapus',
            'outlet_id' => $this->outletPusat->id,
            'status' => 'active',
            'attendance_enabled' => 1,
            'deleted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $admin = $this->createAdmin('admin', 'all');
        $service = app(WorkCalendarService::class);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $service->saveOverride([
            'employee_id' => $deletedEmpId,
            'date' => '2026-08-25',
            'override_type' => 'work',
            'shift_id' => $this->shiftPagi->id,
            'work_outlet_id' => $this->outletCabang->id,
            'reason' => 'Penugasan karyawan terhapus',
        ], $admin);
    }

    public function test_14_editing_assignment_rechecks_both_home_and_work_authorization(): void
    {
        $adminFull = $this->createAdmin('admin', 'all');
        $service = app(WorkCalendarService::class);

        $override = $service->saveOverride([
            'employee_id' => $this->employeePusat->id,
            'date' => '2026-08-25',
            'override_type' => 'work',
            'shift_id' => $this->shiftPagi->id,
            'work_outlet_id' => $this->outletCabang->id,
            'reason' => 'Awal penugasan',
        ], $adminFull);

        // Admin Pusat only tries to update override destination to Cabang -> Denied
        $adminPusatOnly = $this->createAdmin('admin', 'selected', [$this->outletPusat->id]);

        $this->expectException(AccessDeniedHttpException::class);

        $service->saveOverride([
            'employee_id' => $this->employeePusat->id,
            'date' => '2026-08-25',
            'override_type' => 'work',
            'shift_id' => $this->shiftPagi->id,
            'work_outlet_id' => $this->outletCabang->id,
            'reason' => 'Update alasan oleh admin tanpa akses cabang',
        ], $adminPusatOnly, $override);
    }

    public function test_15_cancelling_assignment_rechecks_authorization(): void
    {
        $adminFull = $this->createAdmin('admin', 'all');
        $service = app(WorkCalendarService::class);

        $override = $service->saveOverride([
            'employee_id' => $this->employeePusat->id,
            'date' => '2026-08-25',
            'override_type' => 'work',
            'shift_id' => $this->shiftPagi->id,
            'work_outlet_id' => $this->outletCabang->id,
            'reason' => 'Awal penugasan',
        ], $adminFull);

        // Admin Pusat only tries to delete override -> Denied because destination WORK is Cabang
        $adminPusatOnly = $this->createAdmin('admin', 'selected', [$this->outletPusat->id]);

        $this->expectException(AccessDeniedHttpException::class);

        $service->deleteOverride($override, 'Batal oleh admin tanpa akses cabang', $adminPusatOnly);
    }

    public function test_16_no_employee_outlet_transfer_row_is_created(): void
    {
        $admin = $this->createAdmin('admin', 'all');
        $service = app(WorkCalendarService::class);

        $initialTransfersCount = EmployeeOutletTransfer::count();

        $service->saveOverride([
            'employee_id' => $this->employeePusat->id,
            'date' => '2026-08-25',
            'override_type' => 'work',
            'shift_id' => $this->shiftPagi->id,
            'work_outlet_id' => $this->outletCabang->id,
            'reason' => 'Penugasan sementara',
        ], $admin);

        $this->assertEquals($initialTransfersCount, EmployeeOutletTransfer::count());
    }

    public function test_17_checkin_validates_destination_work_outlet_geofence(): void
    {
        $admin = $this->createAdmin('admin', 'all');
        $service = app(WorkCalendarService::class);

        $service->saveOverride([
            'employee_id' => $this->employeePusat->id,
            'date' => '2026-08-25',
            'override_type' => 'work',
            'shift_id' => $this->shiftPagi->id,
            'work_outlet_id' => $this->outletCabang->id,
            'reason' => 'Penugasan ke Cabang',
        ], $admin);

        $attendanceService = app(AttendanceService::class);

        // Employee physically at Pusat (-6.200000, 106.816666) -> should fail for Cabang geofence
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Kopi Selon Cabang');

        $attendanceService->checkIn($this->userPusat, [
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'accuracy' => 10,
            'selfie' => $this->fakeSelfie(),
        ]);
    }

    public function test_18_checkin_succeeds_at_destination_geofence_and_snapshots_outlet(): void
    {
        $admin = $this->createAdmin('admin', 'all');
        $service = app(WorkCalendarService::class);

        $service->saveOverride([
            'employee_id' => $this->employeePusat->id,
            'date' => '2026-08-25',
            'override_type' => 'work',
            'shift_id' => $this->shiftPagi->id,
            'work_outlet_id' => $this->outletCabang->id,
            'reason' => 'Penugasan ke Cabang',
        ], $admin);

        $attendanceService = app(AttendanceService::class);

        // Employee physically at Cabang (-6.300000, 106.900000) -> succeeds!
        $record = $attendanceService->checkIn($this->userPusat, [
            'latitude' => -6.300000,
            'longitude' => 106.900000,
            'accuracy' => 10,
            'selfie' => $this->fakeSelfie(),
        ]);

        $this->assertNotNull($record);
        $this->assertEquals($this->outletCabang->id, $record->outlet_id);
    }

    public function test_19_checkout_uses_attendancerecord_outlet_snapshot(): void
    {
        $admin = $this->createAdmin('admin', 'all');
        $service = app(WorkCalendarService::class);

        $service->saveOverride([
            'employee_id' => $this->employeePusat->id,
            'date' => '2026-08-25',
            'override_type' => 'work',
            'shift_id' => $this->shiftPagi->id,
            'work_outlet_id' => $this->outletCabang->id,
            'reason' => 'Penugasan ke Cabang',
        ], $admin);

        $attendanceService = app(AttendanceService::class);

        // Check in at Cabang
        $record = $attendanceService->checkIn($this->userPusat, [
            'latitude' => -6.300000,
            'longitude' => 106.900000,
            'accuracy' => 10,
            'selfie' => $this->fakeSelfie(),
        ]);

        // Advance time to 16:00
        Carbon::setTestNow('2026-08-25 16:00:00');

        // Check out at Cabang
        $checkedOutRecord = $attendanceService->checkOut($this->userPusat, [
            'latitude' => -6.300000,
            'longitude' => 106.900000,
            'accuracy' => 10,
            'selfie' => $this->fakeSelfie(),
        ]);

        $this->assertNotNull($checkedOutRecord->check_out_at);
        $this->assertEquals($this->outletCabang->id, $checkedOutRecord->outlet_id);
    }

    public function test_20_later_schedule_edit_cannot_rewrite_attendance_outlet_history(): void
    {
        $admin = $this->createAdmin('admin', 'all');
        $service = app(WorkCalendarService::class);

        $service->saveOverride([
            'employee_id' => $this->employeePusat->id,
            'date' => '2026-08-25',
            'override_type' => 'work',
            'shift_id' => $this->shiftPagi->id,
            'work_outlet_id' => $this->outletCabang->id,
            'reason' => 'Penugasan ke Cabang',
        ], $admin);

        $attendanceService = app(AttendanceService::class);

        $record = $attendanceService->checkIn($this->userPusat, [
            'latitude' => -6.300000,
            'longitude' => 106.900000,
            'accuracy' => 10,
            'selfie' => $this->fakeSelfie(),
        ]);

        // Verify AttendanceRecord snapshot is Cabang
        $this->assertEquals($this->outletCabang->id, $record->outlet_id);

        // Verify that trying to update override AFTER check-in is blocked by mutation blocker
        $override = EmployeeScheduleOverride::where('employee_id', $this->employeePusat->id)->whereDate('date', '2026-08-25')->first();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('sudah memiliki absensi');

        $service->saveOverride([
            'employee_id' => $this->employeePusat->id,
            'date' => '2026-08-25',
            'override_type' => 'work',
            'shift_id' => $this->shiftPagi->id,
            'work_outlet_id' => $this->outletPusat->id,
            'reason' => 'Coba ubah override setelah absensi',
        ], $admin, $override);
    }

    public function test_21_overtime_uses_correct_work_or_attendance_outlet(): void
    {
        $admin = $this->createAdmin('admin', 'all');
        $calendarService = app(WorkCalendarService::class);

        $calendarService->saveOverride([
            'employee_id' => $this->employeePusat->id,
            'date' => '2026-08-25',
            'override_type' => 'work',
            'shift_id' => $this->shiftPagi->id,
            'work_outlet_id' => $this->outletCabang->id,
            'reason' => 'Penugasan ke Cabang',
        ], $admin);

        $attendanceService = app(AttendanceService::class);
        $attendanceService->checkIn($this->userPusat, [
            'latitude' => -6.300000,
            'longitude' => 106.900000,
            'accuracy' => 10,
            'selfie' => $this->fakeSelfie(),
        ]);

        Carbon::setTestNow('2026-08-25 16:00:00');
        $attendanceService->checkOut($this->userPusat, [
            'latitude' => -6.300000,
            'longitude' => 106.900000,
            'accuracy' => 10,
            'selfie' => $this->fakeSelfie(),
        ]);

        // Create approved overtime request
        $otReq = OvertimeRequest::create([
            'employee_id' => $this->employeePusat->id,
            'work_date' => '2026-08-25',
            'requested_minutes' => 60,
            'approved_minutes' => 60,
            'reason' => 'Lembur closing cabang',
            'status' => 'approved',
        ]);

        $otService = app(OvertimeSessionService::class);

        // Start overtime session at Cabang location
        $session = $otService->start($this->userPusat, $otReq->id, [
            'latitude' => -6.300000,
            'longitude' => 106.900000,
            'accuracy' => 10,
            'selfie' => $this->fakeSelfie(),
        ]);

        $this->assertNotNull($session);
        $this->assertEquals('active', $session->status);
    }

    public function test_22_cross_outlet_shift_swap_remains_rejected(): void
    {
        $empCabangId = DB::table('employees')->insertGetId([
            'employee_code' => 'SB-200',
            'full_name' => 'Karyawan Cabang',
            'outlet_id' => $this->outletCabang->id,
            'status' => 'active',
            'attendance_enabled' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $empCabang = Employee::find($empCabangId);

        $swapService = app(ShiftSwapService::class);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $swapService->validateEligibilityAndConflicts(
            $this->employeePusat,
            $empCabang,
            '2026-08-25',
            '2026-08-25'
        );
    }

    public function test_23_active_attendance_prevents_unsafe_assignment_mutation(): void
    {
        $admin = $this->createAdmin('admin', 'all');
        $service = app(WorkCalendarService::class);

        $override = $service->saveOverride([
            'employee_id' => $this->employeePusat->id,
            'date' => '2026-08-25',
            'override_type' => 'work',
            'shift_id' => $this->shiftPagi->id,
            'work_outlet_id' => $this->outletCabang->id,
            'reason' => 'Penugasan sementara',
        ], $admin);

        // Employee checks in
        $attendanceService = app(AttendanceService::class);
        $attendanceService->checkIn($this->userPusat, [
            'latitude' => -6.300000,
            'longitude' => 106.900000,
            'accuracy' => 10,
            'selfie' => $this->fakeSelfie(),
        ]);

        // Attempting to delete override while attendance exists must fail
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('sudah memiliki absensi');

        $service->deleteOverride($override, 'Mencoba hapus saat absensi aktif', $admin);
    }

    public function test_24_audit_events_contain_no_sensitive_credentials(): void
    {
        $admin = $this->createAdmin('admin', 'all');
        $service = app(WorkCalendarService::class);

        $service->saveOverride([
            'employee_id' => $this->employeePusat->id,
            'date' => '2026-08-25',
            'override_type' => 'work',
            'shift_id' => $this->shiftPagi->id,
            'work_outlet_id' => $this->outletCabang->id,
            'reason' => 'Penugasan sementara',
        ], $admin);

        $audit = AuditLog::where('action', 'schedule_override.created')->first();

        $this->assertNotNull($audit);
        $this->assertArrayNotHasKey('password', $audit->metadata ?? []);
        $this->assertArrayNotHasKey('remember_token', $audit->metadata ?? []);
    }

    public function test_25_dashboard_headcount_remains_home_based(): void
    {
        $admin = $this->createAdmin('admin', 'all');
        $calendarService = app(WorkCalendarService::class);

        // Assign Pusat employee to Cabang for today
        $calendarService->saveOverride([
            'employee_id' => $this->employeePusat->id,
            'date' => '2026-08-25',
            'override_type' => 'work',
            'shift_id' => $this->shiftPagi->id,
            'work_outlet_id' => $this->outletCabang->id,
            'reason' => 'Penugasan sementara ke cabang',
        ], $admin);

        $monitoringService = app(AttendanceMonitoringService::class);

        // Pusat organizational headcount includes employeePusat
        $metricsPusat = $monitoringService->getSummaryMetrics('2026-08-25', $admin, null, $this->outletPusat->id);
        $this->assertEquals(1, $metricsPusat['total_employees']);

        // Cabang organizational headcount does NOT gain employeePusat permanently
        $metricsCabang = $monitoringService->getSummaryMetrics('2026-08-25', $admin, null, $this->outletCabang->id);
        $this->assertEquals(0, $metricsCabang['total_employees']);
    }

    public function test_26_same_outlet_schedule_shows_reguler_badge(): void
    {
        EmployeeSchedule::create([
            'employee_id' => $this->employeePusat->id,
            'work_date' => '2026-08-25',
            'schedule_type' => 'work',
            'shift_id' => $this->shiftPagi->id,
            'work_outlet_id' => $this->outletPusat->id,
        ]);

        $response = $this->actingAs($this->userPusat->fresh())->get(route('employee.schedules.index', ['start_date' => '2026-08-25']));
        $response->assertStatus(200);
        $response->assertSee('REGULER');
        $response->assertDontSee('PENUGASAN');
    }

    public function test_27_cross_outlet_assignment_shows_penugasan_badge_and_not_reguler(): void
    {
        $admin = $this->createAdmin('admin', 'all');
        $calendarService = app(WorkCalendarService::class);

        $override = $calendarService->saveOverride([
            'employee_id' => $this->employeePusat->id,
            'date' => '2026-08-25',
            'override_type' => 'work',
            'shift_id' => $this->shiftPagi->id,
            'work_outlet_id' => $this->outletCabang->id,
            'reason' => 'Penugasan sementara ke cabang',
        ], $admin);

        $this->assertNotNull($override);

        $response = $this->actingAs($this->userPusat)->get(route('employee.schedules.index', ['start_date' => '2026-08-25']));
        $response->assertStatus(200);
        $response->assertSee('PENUGASAN');
        $response->assertSee('Outlet Kerja:');
        $response->assertSee('Kopi Selon Cabang');
        $response->assertSee('Home Outlet Anda tetap');
    }

    public function test_28_admin_work_calendar_shows_penugasan_sementara_badge(): void
    {
        $admin = $this->createAdmin('admin', 'all');
        $calendarService = app(WorkCalendarService::class);

        $calendarService->saveOverride([
            'employee_id' => $this->employeePusat->id,
            'date' => '2026-08-25',
            'override_type' => 'work',
            'shift_id' => $this->shiftPagi->id,
            'work_outlet_id' => $this->outletCabang->id,
            'reason' => 'Penugasan sementara ke cabang',
        ], $admin);

        $response = $this->actingAs($admin)->get(route('admin.work-calendar.index'));
        $response->assertStatus(200);
        $response->assertSee('PENUGASAN SEMENTARA');
    }
}
