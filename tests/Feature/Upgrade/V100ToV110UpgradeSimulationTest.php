<?php

namespace Tests\Feature\Upgrade;

use App\Enums\UserRole;
use App\Models\AppSetting;
use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmployeeOutletTransfer;
use App\Models\EmployeeSchedule;
use App\Models\EmployeeScheduleOverride;
use App\Models\JobTitle;
use App\Models\LeaveRequest;
use App\Models\Outlet;
use App\Models\OvertimeRequest;
use App\Models\OvertimeSession;
use App\Models\Shift;
use App\Models\User;
use App\Services\BackupService;
use App\Services\OutletModeService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class V100ToV110UpgradeSimulationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-02 08:00:00');
        Outlet::query()->forceDelete();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_full_v100_to_v110_upgrade_simulation_with_production_like_dataset(): void
    {
        // =====================================================================
        // PHASE 1 — SEED PRODUCTION-LIKE v1.0.0 DATASET & CAPTURE BASELINE
        // =====================================================================

        // 1. Two active outlets with specific geofence coordinates
        $outletPusat = Outlet::create([
            'name' => 'Kopi Selon Pusat',
            'code' => 'PUSAT',
            'latitude' => -6.1753924,
            'longitude' => 106.8271528,
            'radius_meters' => 100,
            'max_accuracy_meters' => 50,
            'is_active' => true,
        ]);

        $outletCabang = Outlet::create([
            'name' => 'Kopi Selon Cabang',
            'code' => 'KSC',
            'latitude' => -6.2297465,
            'longitude' => 106.8074872,
            'radius_meters' => 150,
            'max_accuracy_meters' => 80,
            'is_active' => true,
        ]);

        // One soft-deleted/inactive outlet (to test historical relation safety)
        $outletHistorical = Outlet::create([
            'name' => 'Kopi Selon Lama',
            'code' => 'KSL',
            'latitude' => -6.3000000,
            'longitude' => 106.9000000,
            'radius_meters' => 100,
            'max_accuracy_meters' => 50,
            'is_active' => false,
        ]);

        // 2. Job Titles & Shifts
        $jobTitle = JobTitle::create(['name' => 'Barista', 'is_active' => true]);
        $shiftPagi = Shift::create(['name' => 'Shift Pagi', 'code' => 'SPG01', 'start_time' => '08:00:00', 'end_time' => '16:00:00', 'is_active' => true]);
        $shiftSore = Shift::create(['name' => 'Shift Sore', 'code' => 'SSR01', 'start_time' => '14:00:00', 'end_time' => '22:00:00', 'is_active' => true]);

        // 3. Users & Employees across both HOME outlets
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN->value, 'is_active' => true]);
        $owner = User::factory()->create(['role' => UserRole::OWNER->value, 'is_active' => true]);

        $adminPusat = User::factory()->create(['role' => UserRole::ADMIN->value, 'outlet_id' => $outletPusat->id, 'is_active' => true]);
        $adminPusat->assignedOutlets()->sync([$outletPusat->id]);

        $adminCabang = User::factory()->create(['role' => UserRole::ADMIN->value, 'outlet_id' => $outletCabang->id, 'is_active' => true]);
        $adminCabang->assignedOutlets()->sync([$outletCabang->id]);

        $empPusat = Employee::create([
            'employee_code' => 'EMP-PST-01',
            'full_name' => 'Ahmad Pusat',
            'email' => 'ahmad@example.com',
            'phone' => '081211111111',
            'job_title_id' => $jobTitle->id,
            'outlet_id' => $outletPusat->id,
            'status' => 'active',
            'attendance_enabled' => true,
        ]);

        $empCabang = Employee::create([
            'employee_code' => 'EMP-CBG-01',
            'full_name' => 'Budi Cabang',
            'email' => 'budi@example.com',
            'phone' => '081222222222',
            'job_title_id' => $jobTitle->id,
            'outlet_id' => $outletCabang->id,
            'status' => 'active',
            'attendance_enabled' => true,
        ]);

        // 4. Historical Attendance Records across all outlets (including inactive outlet)
        $attPusat = AttendanceRecord::create([
            'employee_id' => $empPusat->id,
            'work_date' => '2026-08-20',
            'shift_id' => $shiftPagi->id,
            'outlet_id' => $outletPusat->id,
            'check_in_at' => '2026-08-20 08:01:00',
            'check_in_latitude' => -6.1753900,
            'check_in_longitude' => 106.8271500,
            'check_in_distance_meters' => 3,
            'status' => 'present',
        ]);

        $attCabang = AttendanceRecord::create([
            'employee_id' => $empCabang->id,
            'work_date' => '2026-08-20',
            'shift_id' => $shiftPagi->id,
            'outlet_id' => $outletCabang->id,
            'check_in_at' => '2026-08-20 08:05:00',
            'check_in_latitude' => -6.2297400,
            'check_in_longitude' => 106.8074800,
            'check_in_distance_meters' => 6,
            'status' => 'present',
        ]);

        $attHistorical = AttendanceRecord::create([
            'employee_id' => $empPusat->id,
            'work_date' => '2026-07-15',
            'shift_id' => $shiftPagi->id,
            'outlet_id' => $outletHistorical->id,
            'check_in_at' => '2026-07-15 08:00:00',
            'check_in_latitude' => -6.3000000,
            'check_in_longitude' => 106.9000000,
            'check_in_distance_meters' => 0,
            'status' => 'present',
        ]);

        // 5. Employee Outlet Transfer History
        $transfer = EmployeeOutletTransfer::create([
            'employee_id' => $empPusat->id,
            'from_outlet_id' => $outletHistorical->id,
            'to_outlet_id' => $outletPusat->id,
            'transferred_by_user_id' => $superadmin->id,
            'effective_date' => '2026-08-01',
            'notes' => 'Relokasi dari outlet lama ke Pusat',
        ]);

        // 6. Regular Schedules and Overrides (Cross-Outlet temporary assignment)
        $schedule = EmployeeSchedule::create([
            'employee_id' => $empPusat->id,
            'work_date' => '2026-09-02',
            'shift_id' => $shiftPagi->id,
            'work_outlet_id' => $outletPusat->id,
            'schedule_type' => 'work',
        ]);

        $override = EmployeeScheduleOverride::create([
            'employee_id' => $empPusat->id,
            'date' => '2026-09-03',
            'override_type' => 'work',
            'shift_id' => $shiftSore->id,
            'work_outlet_id' => $outletCabang->id, // Cross-outlet temporary assignment to Cabang
            'reason' => 'Bantuan shift sore di cabang',
            'created_by_user_id' => $superadmin->id,
        ]);

        // 7. Leave & Overtime
        $leave = LeaveRequest::create([
            'employee_id' => $empCabang->id,
            'type' => 'annual',
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-11',
            'reason' => 'Keperluan keluarga',
            'status' => 'approved',
            'approved_by' => $superadmin->id,
        ]);

        $otRequest = OvertimeRequest::create([
            'employee_id' => $empPusat->id,
            'work_date' => '2026-09-02',
            'requested_minutes' => 120,
            'approved_minutes' => 120,
            'reason' => 'Restock barang',
            'status' => 'approved',
            'reviewed_by' => $superadmin->id,
            'reviewed_at' => '2026-09-02 08:00:00',
        ]);

        $otSession = OvertimeSession::create([
            'overtime_request_id' => $otRequest->id,
            'employee_id' => $empPusat->id,
            'work_date' => '2026-09-02',
            'check_in_at' => '2026-09-02 16:00:00',
            'status' => 'active',
        ]);

        $auditLog = AuditLog::create([
            'user_id' => $superadmin->id,
            'action' => 'employee.created',
            'auditable_type' => Employee::class,
            'auditable_id' => $empPusat->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);

        \Illuminate\Support\Facades\DB::table('notifications')->insert([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => 'App\Notifications\OvertimeApprovedNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $adminPusat->id,
            'data' => json_encode(['message' => 'Lembur Ahmad Pusat telah disetujui.']),
            'created_at' => '2026-09-02 08:00:00',
            'updated_at' => '2026-09-02 08:00:00',
        ]);

        AppSetting::set('timezone', 'Asia/Jakarta', 'string', false);

        // Snapshot Baseline Counts
        $baselineCounts = [
            'outlets' => Outlet::count(),
            'employees' => Employee::count(),
            'users' => User::count(),
            'attendance_records' => AttendanceRecord::count(),
            'employee_outlet_transfers' => EmployeeOutletTransfer::count(),
            'work_schedules' => EmployeeSchedule::count(),
            'employee_schedule_overrides' => EmployeeScheduleOverride::count(),
            'leave_requests' => LeaveRequest::count(),
            'overtime_requests' => OvertimeRequest::count(),
            'overtime_sessions' => OvertimeSession::count(),
            'audit_logs' => AuditLog::count(),
            'notifications' => \Illuminate\Support\Facades\DB::table('notifications')->count(),
        ];

        $this->assertEquals(3, $baselineCounts['outlets']);
        $this->assertEquals(2, $baselineCounts['employees']);
        $this->assertEquals(4, $baselineCounts['users']);
        $this->assertEquals(3, $baselineCounts['attendance_records']);
        $this->assertNull(AppSetting::get('outlet_mode')); // No outlet_mode setting in v1.0.0 baseline

        // =====================================================================
        // PHASE 2 — EXECUTE UPGRADE SEQUENCE TO CANDIDATE v1.1.0
        // =====================================================================

        // Step 1: Run standard migrations (0 new migrations expected)
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();

        // Step 2: Run REQUIRED initialization command
        $this->artisan('app:init-outlet-mode')
            ->assertSuccessful()
            ->expectsOutput('Outlet mode initialized: multi (2 active outlets detected)');

        // Verify outlet_mode persisted as 'multi'
        $this->assertEquals(OutletModeService::MODE_MULTI, AppSetting::get('outlet_mode'));

        // =====================================================================
        // PHASE 3 — DATA INTEGRITY COMPARISON (BEFORE vs AFTER)
        // =====================================================================

        $this->assertEquals($baselineCounts['outlets'], Outlet::count());
        $this->assertEquals($baselineCounts['employees'], Employee::count());
        $this->assertEquals($baselineCounts['users'], User::count());
        $this->assertEquals($baselineCounts['attendance_records'], AttendanceRecord::count());
        $this->assertEquals($baselineCounts['employee_outlet_transfers'], EmployeeOutletTransfer::count());
        $this->assertEquals($baselineCounts['work_schedules'], EmployeeSchedule::count());
        $this->assertEquals($baselineCounts['employee_schedule_overrides'], EmployeeScheduleOverride::count());
        $this->assertEquals($baselineCounts['leave_requests'], LeaveRequest::count());
        $this->assertEquals($baselineCounts['overtime_requests'], OvertimeRequest::count());
        $this->assertEquals($baselineCounts['overtime_sessions'], OvertimeSession::count());
        $this->assertEquals($baselineCounts['audit_logs'], AuditLog::count());
        $this->assertEquals($baselineCounts['notifications'], \Illuminate\Support\Facades\DB::table('notifications')->count());

        // Field value verification
        $this->assertEquals(-6.1753924, $outletPusat->fresh()->latitude);
        $this->assertEquals(106.8271528, $outletPusat->fresh()->longitude);
        $this->assertEquals(100, $outletPusat->fresh()->radius_meters);
        $this->assertEquals(50, $outletPusat->fresh()->max_accuracy_meters);

        $this->assertEquals($outletPusat->id, $empPusat->fresh()->outlet_id);
        $this->assertEquals($outletCabang->id, $empCabang->fresh()->outlet_id);

        $this->assertEquals($outletPusat->id, $attPusat->fresh()->outlet_id);
        $this->assertEquals($outletCabang->id, $attCabang->fresh()->outlet_id);
        $this->assertEquals($outletHistorical->id, $attHistorical->fresh()->outlet_id);

        $this->assertEquals($outletHistorical->id, $transfer->fresh()->from_outlet_id);
        $this->assertEquals($outletPusat->id, $transfer->fresh()->to_outlet_id);

        $this->assertEquals($outletPusat->id, $schedule->fresh()->work_outlet_id);
        $this->assertEquals($outletCabang->id, $override->fresh()->work_outlet_id);

        // =====================================================================
        // PHASE 4 — HISTORICAL READABILITY
        // =====================================================================

        // Historical attendance query resolves relationships smoothly
        $historicalRecords = AttendanceRecord::with(['employee', 'outlet'])->get();
        $this->assertCount(3, $historicalRecords);
        $this->assertEquals('Kopi Selon Lama', $attHistorical->fresh()->outlet?->name);

        // Employee transfer history resolves correctly
        $transferRecords = EmployeeOutletTransfer::with(['employee', 'fromOutlet', 'toOutlet'])->get();
        $this->assertCount(1, $transferRecords);
        $this->assertEquals('Kopi Selon Lama', $transferRecords->first()->fromOutlet->name);
        $this->assertEquals('Kopi Selon Pusat', $transferRecords->first()->toOutlet->name);

        // =====================================================================
        // PHASE 5 — MULTI-MODE OPERATIONAL BEHAVIOR
        // =====================================================================

        // 1. Add Outlet available in UI & endpoint
        $responseOutletList = $this->actingAs($superadmin)->get(route('admin.outlets.index'));
        $responseOutletList->assertOk();
        $responseOutletList->assertSee('Tambah Outlet Baru');

        // 2. Attendance report filters by outlet
        $responseReport = $this->actingAs($superadmin)->get(route('admin.reports.attendance'));
        $responseReport->assertOk();
        $responseReport->assertSee('name="outlet_id"', false);

        // 3. Scoped admin access remains isolated
        $responseAdminScope = $this->actingAs($adminPusat)->get(route('admin.outlets.index'));
        $responseAdminScope->assertOk();
        $responseAdminScope->assertSee('Kopi Selon Pusat');
        $responseAdminScope->assertDontSee('Kopi Selon Cabang');

        // =====================================================================
        // PHASE 8 — MULTI -> SINGLE BLOCKER SAFETY
        // =====================================================================

        $outletModeService = app(OutletModeService::class);
        $blockerReason = null;
        $canSwitch = $outletModeService->canSwitchToSingleOutlet($blockerReason);

        $this->assertFalse($canSwitch);
        $this->assertStringContainsString('Masih terdapat 2 outlet aktif', $blockerReason);

        // Attempting to post single mode from UI is blocked
        $responseBlockSwitch = $this->actingAs($superadmin)->post(route('admin.settings.attendance.update'), [
            'timezone' => 'Asia/Jakarta',
            'outlet_mode' => 'single',
        ]);
        $responseBlockSwitch->assertSessionHas('error');
        $this->assertEquals(OutletModeService::MODE_MULTI, AppSetting::get('outlet_mode'));

        // =====================================================================
        // PHASE 10 — BACKUP SERVICE COMPATIBILITY
        // =====================================================================

        $backupService = app(BackupService::class);
        $reflector = new \ReflectionClass($backupService);
        $property = $reflector->getProperty('applicationTables');
        $property->setAccessible(true);
        $tables = $property->getValue($backupService);

        $this->assertContains('app_settings', $tables);
        $this->assertContains('outlets', $tables);
        $this->assertContains('employee_outlet_transfers', $tables);
    }

    public function test_single_mode_isolated_scenario_simulation(): void
    {
        // =====================================================================
        // PHASE 6 & 7 — SINGLE MODE SCENARIO & REVERSIBILITY
        // =====================================================================

        $singleOutlet = Outlet::create([
            'name' => 'Kopi Selon Standalone',
            'code' => 'KSS',
            'latitude' => -6.1000000,
            'longitude' => 106.7000000,
            'radius_meters' => 100,
            'max_accuracy_meters' => 50,
            'is_active' => true,
        ]);

        $jobTitle = JobTitle::create(['name' => 'Barista', 'is_active' => true]);
        $emp = Employee::create([
            'employee_code' => 'EMP-SGL-01',
            'full_name' => 'Cici Standalone',
            'job_title_id' => $jobTitle->id,
            'outlet_id' => $singleOutlet->id,
            'status' => 'active',
            'attendance_enabled' => true,
        ]);

        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN->value, 'is_active' => true]);

        // Initialize with 1 active outlet -> Persists 'single'
        $this->artisan('app:init-outlet-mode')
            ->assertSuccessful()
            ->expectsOutput('Outlet mode initialized: single (1 active outlet detected)');

        $this->assertEquals(OutletModeService::MODE_SINGLE, AppSetting::get('outlet_mode'));

        // Verify Single Mode Gating
        $responseOutlets = $this->actingAs($superadmin)->get(route('admin.outlets.index'));
        $responseOutlets->assertOk();
        $responseOutlets->assertDontSee('Tambah Outlet Baru');

        // Creating second outlet blocked server-side
        $responseStoreOutlet = $this->actingAs($superadmin)->post(route('admin.outlets.store'), [
            'name' => 'Cabang Baru',
            'code' => 'NEW02',
            'latitude' => -6.2,
            'longitude' => 106.8,
            'radius_meters' => 100,
            'max_accuracy_meters' => 50,
        ]);
        $responseStoreOutlet->assertRedirect(route('admin.outlets.index'));
        $responseStoreOutlet->assertSessionHas('error');
        $this->assertEquals(1, Outlet::count());

        // Transfer blocked
        $responseTransfer = $this->actingAs($superadmin)->post(route('admin.employees.transfer', $emp), [
            'destination_outlet_id' => 99,
        ]);
        $responseTransfer->assertSessionHas('error');

        // PHASE 7: Single -> Multi transition
        $service = app(OutletModeService::class);
        $service->setMode(OutletModeService::MODE_MULTI, $superadmin);

        $this->assertEquals(OutletModeService::MODE_MULTI, AppSetting::get('outlet_mode'));

        // Multi mode UI returns immediately
        $responseOutletsMulti = $this->actingAs($superadmin)->get(route('admin.outlets.index'));
        $responseOutletsMulti->assertSee('Tambah Outlet Baru');
    }
}
