<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\AppSetting;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeOutletTransfer;
use App\Models\EmployeeSchedule;
use App\Models\EmployeeScheduleOverride;
use App\Models\JobTitle;
use App\Models\Outlet;
use App\Models\Shift;
use App\Models\User;
use App\Services\OutletModeService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutletModeTest extends TestCase
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

    protected function createSuperadmin(): User
    {
        return User::factory()->create([
            'role' => UserRole::SUPERADMIN->value,
            'is_active' => true,
        ]);
    }

    protected function createAdmin(Outlet $outlet): User
    {
        $user = User::factory()->create([
            'role' => UserRole::ADMIN->value,
            'outlet_id' => $outlet->id,
            'is_active' => true,
        ]);
        $user->assignedOutlets()->sync([$outlet->id]);

        return $user;
    }

    protected function createOutlet(array $attributes = []): Outlet
    {
        return Outlet::create(array_merge([
            'name' => 'Outlet ' . uniqid(),
            'code' => strtoupper('OUT' . rand(100, 999)),
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'radius_meters' => 100,
            'max_accuracy_meters' => 50,
            'is_active' => true,
        ], $attributes));
    }

    protected function createEmployee(array $attributes = []): Employee
    {
        $jobTitle = JobTitle::first() ?? JobTitle::create(['name' => 'Staff', 'is_active' => true]);
        $outlet = $attributes['outlet_id'] ?? ($this->createOutlet()->id);

        return Employee::create(array_merge([
            'employee_code' => 'EMP' . rand(1000, 9999),
            'full_name' => 'Karyawan ' . uniqid(),
            'email' => 'emp' . uniqid() . '@example.com',
            'phone' => '0812' . rand(10000000, 99999999),
            'job_title_id' => $jobTitle->id,
            'outlet_id' => $outlet,
            'join_date' => '2026-01-01',
            'status' => 'active',
            'attendance_enabled' => true,
        ], $attributes));
    }

    protected function createShift(array $attributes = []): Shift
    {
        return Shift::create(array_merge([
            'name' => 'Shift Pagi',
            'code' => 'SPG' . rand(100, 999),
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'is_active' => true,
        ], $attributes));
    }

    // =========================================================================
    // 1. Resolution & Safety Rules (Fix 1 & Fix 2)
    // =========================================================================

    public function test_exactly_one_active_outlet_resolves_as_single_operational_outlet(): void
    {
        $outlet = $this->createOutlet(['name' => 'Cabang Tunggal', 'code' => 'CBGTGL', 'is_active' => true]);

        $service = app(OutletModeService::class);
        $resolved = $service->getSingleOperationalOutlet();

        $this->assertNotNull($resolved);
        $this->assertEquals($outlet->id, $resolved->id);
        $this->assertEquals('CBGTGL', $resolved->code);
    }

    public function test_two_active_outlets_never_return_arbitrary_single_operational_outlet(): void
    {
        $this->createOutlet(['name' => 'Outlet Alpha', 'code' => 'ALPHA', 'is_active' => true]);
        $this->createOutlet(['name' => 'Outlet Beta', 'code' => 'BETA', 'is_active' => true]);

        $service = app(OutletModeService::class);
        $resolved = $service->getSingleOperationalOutlet();

        $this->assertNull($resolved);
    }

    public function test_no_active_outlets_returns_null_for_single_operational_outlet(): void
    {
        $this->createOutlet(['name' => 'Nonaktif', 'code' => 'OFF', 'is_active' => false]);

        $service = app(OutletModeService::class);
        $this->assertNull($service->getSingleOperationalOutlet());
    }

    // =========================================================================
    // 2. Initialization & Zero Active Outlets (Fix 3, 4, 5)
    // =========================================================================

    public function test_initialization_with_two_active_outlets_persists_multi(): void
    {
        $this->createOutlet(['name' => 'Pusat', 'code' => 'PUSAT', 'is_active' => true]);
        $this->createOutlet(['name' => 'Cabang', 'code' => 'KSC', 'is_active' => true]);

        $service = app(OutletModeService::class);
        $details = [];
        $mode = $service->initializeIfMissing($details);

        $this->assertEquals(OutletModeService::MODE_MULTI, $mode);
        $this->assertEquals('initialized', $details['status']);
        $this->assertEquals(OutletModeService::MODE_MULTI, AppSetting::get('outlet_mode'));
    }

    public function test_initialization_with_one_active_outlet_persists_single(): void
    {
        $this->createOutlet(['name' => 'Pusat', 'code' => 'PUSAT', 'is_active' => true]);

        $service = app(OutletModeService::class);
        $details = [];
        $mode = $service->initializeIfMissing($details);

        $this->assertEquals(OutletModeService::MODE_SINGLE, $mode);
        $this->assertEquals('initialized', $details['status']);
        $this->assertEquals(OutletModeService::MODE_SINGLE, AppSetting::get('outlet_mode'));
    }

    public function test_initialization_with_zero_active_outlets_does_not_persist_mode(): void
    {
        $this->createOutlet(['name' => 'Nonaktif', 'code' => 'OFF', 'is_active' => false]);

        $service = app(OutletModeService::class);
        $details = [];
        $mode = $service->initializeIfMissing($details);

        $this->assertNull($mode);
        $this->assertEquals('no_active_outlets', $details['status']);
        $this->assertNull(AppSetting::get('outlet_mode'));
    }

    public function test_existing_explicit_mode_remains_unchanged_on_initialization(): void
    {
        AppSetting::set('outlet_mode', OutletModeService::MODE_SINGLE, 'string', false);
        $this->createOutlet(['name' => 'Pusat', 'code' => 'PUSAT', 'is_active' => true]);
        $this->createOutlet(['name' => 'Cabang', 'code' => 'KSC', 'is_active' => true]);

        $service = app(OutletModeService::class);
        $details = [];
        $mode = $service->initializeIfMissing($details);

        $this->assertEquals(OutletModeService::MODE_SINGLE, $mode);
        $this->assertEquals('already_configured', $details['status']);
        $this->assertEquals(OutletModeService::MODE_SINGLE, AppSetting::get('outlet_mode'));
    }

    public function test_artisan_command_is_idempotent_and_reports_clear_output(): void
    {
        $this->createOutlet(['name' => 'Pusat', 'code' => 'PUSAT', 'is_active' => true]);
        $this->createOutlet(['name' => 'Cabang', 'code' => 'KSC', 'is_active' => true]);

        // First run initializes
        $this->artisan('app:init-outlet-mode')
            ->assertSuccessful()
            ->expectsOutput('Outlet mode initialized: multi (2 active outlets detected)');

        $this->assertEquals(OutletModeService::MODE_MULTI, AppSetting::get('outlet_mode'));

        // Second run is idempotent and reports already configured
        $this->artisan('app:init-outlet-mode')
            ->assertSuccessful()
            ->expectsOutput('Outlet mode is already configured: [multi]. No changes made.');
    }

    public function test_artisan_command_fails_safely_when_zero_active_outlets_exist(): void
    {
        $this->artisan('app:init-outlet-mode')
            ->assertFailed()
            ->expectsOutput('Cannot initialize outlet mode: No active outlets detected. Please configure at least one active outlet first.');

        $this->assertNull(AppSetting::get('outlet_mode'));
    }

    // =========================================================================
    // 3. Single Mode Guardrails & Blocker Checks
    // =========================================================================

    public function test_single_mode_blocks_second_outlet_creation_server_side(): void
    {
        AppSetting::set('outlet_mode', OutletModeService::MODE_SINGLE, 'string', false);
        $this->createOutlet(['name' => 'Pusat', 'code' => 'PUSAT', 'is_active' => true]);

        $superadmin = $this->createSuperadmin();

        $response = $this->actingAs($superadmin)->post(route('admin.outlets.store'), [
            'name' => 'Cabang Baru',
            'code' => 'NEWCAB',
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'radius_meters' => 100,
            'max_accuracy_meters' => 50,
        ]);

        $response->assertRedirect(route('admin.outlets.index'));
        $response->assertSessionHas('error', 'Aplikasi berada dalam Mode Single Outlet. Tidak dapat menambah outlet baru.');
        $this->assertEquals(1, Outlet::count());
    }

    public function test_single_mode_new_employee_binds_to_single_operational_outlet(): void
    {
        AppSetting::set('outlet_mode', OutletModeService::MODE_SINGLE, 'string', false);
        $outlet = $this->createOutlet(['name' => 'Pusat', 'code' => 'PUSAT', 'is_active' => true]);
        $jobTitle = JobTitle::create(['name' => 'Staff', 'is_active' => true]);
        $superadmin = $this->createSuperadmin();

        $response = $this->actingAs($superadmin)->post(route('admin.employees.store'), [
            'employee_code' => 'EMP101',
            'full_name' => 'Budi Santoso',
            'job_title_id' => $jobTitle->id,
            'phone' => '081234567890',
            'email' => 'budi@example.com',
            'join_date' => '2026-01-01',
            'status' => 'active',
            'outlet_id' => 9999, // Should be auto-bound to $outlet->id in single mode
        ]);

        $response->assertRedirect(route('admin.employees.index'));
        $employee = Employee::where('email', 'budi@example.com')->firstOrFail();
        $this->assertEquals($outlet->id, $employee->outlet_id);
    }

    public function test_single_mode_blocks_permanent_transfer(): void
    {
        AppSetting::set('outlet_mode', OutletModeService::MODE_SINGLE, 'string', false);
        $outlet1 = $this->createOutlet(['name' => 'Pusat', 'code' => 'PUSAT', 'is_active' => true]);
        $outlet2 = $this->createOutlet(['name' => 'Cabang', 'code' => 'KSC', 'is_active' => false]);
        $employee = $this->createEmployee(['outlet_id' => $outlet1->id]);
        $superadmin = $this->createSuperadmin();

        $response = $this->actingAs($superadmin)->post(route('admin.employees.transfer', $employee), [
            'destination_outlet_id' => $outlet2->id,
            'notes' => 'Coba transfer di single mode',
        ]);

        $response->assertSessionHas('error', 'Aplikasi berada dalam Mode Single Outlet. Fitur pemindahan outlet tidak tersedia.');
        $this->assertEquals(0, EmployeeOutletTransfer::count());
        $this->assertEquals($outlet1->id, $employee->fresh()->outlet_id);
    }

    public function test_multi_to_single_blocked_with_more_than_one_active_outlet(): void
    {
        AppSetting::set('outlet_mode', OutletModeService::MODE_MULTI, 'string', false);
        $this->createOutlet(['name' => 'Pusat', 'code' => 'PUSAT', 'is_active' => true]);
        $this->createOutlet(['name' => 'Cabang', 'code' => 'KSC', 'is_active' => true]);

        $superadmin = $this->createSuperadmin();

        $response = $this->actingAs($superadmin)->post(route('admin.settings.attendance.update'), [
            'timezone' => 'Asia/Jakarta',
            'require_checkout_geofence' => 1,
            'require_selfie' => 1,
            'outlet_mode' => 'single',
        ]);

        $response->assertRedirect(route('admin.settings.attendance'));
        $response->assertSessionHas('error');
        $this->assertEquals(OutletModeService::MODE_MULTI, AppSetting::get('outlet_mode'));
    }

    public function test_multi_to_single_blocked_when_active_employees_are_in_other_outlets(): void
    {
        AppSetting::set('outlet_mode', OutletModeService::MODE_MULTI, 'string', false);
        $outlet1 = $this->createOutlet(['name' => 'Pusat', 'code' => 'PUSAT', 'is_active' => true]);
        $outlet2 = $this->createOutlet(['name' => 'Cabang', 'code' => 'KSC', 'is_active' => false]); // Inactive
        $this->createEmployee(['outlet_id' => $outlet2->id, 'status' => 'active']);

        $service = app(OutletModeService::class);
        $blockerReason = null;
        $canSwitch = $service->canSwitchToSingleOutlet($blockerReason);

        $this->assertFalse($canSwitch);
        $this->assertStringContainsString('karyawan aktif yang terikat ke outlet selain', $blockerReason);
    }

    public function test_multi_to_single_blocked_when_future_cross_outlet_schedules_exist(): void
    {
        AppSetting::set('outlet_mode', OutletModeService::MODE_MULTI, 'string', false);
        $outlet1 = $this->createOutlet(['name' => 'Pusat', 'code' => 'PUSAT', 'is_active' => true]);
        $outlet2 = $this->createOutlet(['name' => 'Cabang', 'code' => 'KSC', 'is_active' => false]);
        $shift = $this->createShift();
        $employee = $this->createEmployee(['outlet_id' => $outlet1->id, 'status' => 'active']);

        EmployeeSchedule::create([
            'employee_id' => $employee->id,
            'work_date' => '2026-09-05',
            'shift_id' => $shift->id,
            'work_outlet_id' => $outlet2->id,
            'schedule_type' => 'work',
        ]);

        $service = app(OutletModeService::class);
        $blockerReason = null;
        $canSwitch = $service->canSwitchToSingleOutlet($blockerReason);

        $this->assertFalse($canSwitch);
        $this->assertStringContainsString('jadwal kerja mendatang yang ditugaskan ke outlet selain', $blockerReason);
    }

    public function test_safe_multi_to_single_succeeds_and_preserves_historical_records(): void
    {
        AppSetting::set('outlet_mode', OutletModeService::MODE_MULTI, 'string', false);
        $outlet1 = $this->createOutlet(['name' => 'Pusat', 'code' => 'PUSAT', 'is_active' => true]);
        $outlet2 = $this->createOutlet(['name' => 'Cabang', 'code' => 'KSC', 'is_active' => false]);
        $shift = $this->createShift();
        $employee = $this->createEmployee(['outlet_id' => $outlet1->id, 'status' => 'active']);

        // Historical attendance snapshot at outlet2
        $historicalRecord = AttendanceRecord::create([
            'employee_id' => $employee->id,
            'work_date' => '2026-08-01',
            'shift_id' => $shift->id,
            'outlet_id' => $outlet2->id,
            'check_in_at' => '2026-08-01 08:00:00',
            'check_in_latitude' => -6.2,
            'check_in_longitude' => 106.8,
            'check_in_distance_meters' => 5,
            'status' => 'present',
        ]);

        $service = app(OutletModeService::class);
        $this->assertTrue($service->canSwitchToSingleOutlet());

        $superadmin = $this->createSuperadmin();
        $service->setMode(OutletModeService::MODE_SINGLE, $superadmin);

        $this->assertEquals(OutletModeService::MODE_SINGLE, AppSetting::get('outlet_mode'));
        // Historical attendance snapshot remains intact at outlet2!
        $this->assertEquals($outlet2->id, $historicalRecord->fresh()->outlet_id);
    }

    // =========================================================================
    // 4. UI Gating & Authorization
    // =========================================================================

    public function test_ui_gating_in_single_outlet_mode(): void
    {
        AppSetting::set('outlet_mode', OutletModeService::MODE_SINGLE, 'string', false);
        $this->createOutlet(['name' => 'Pusat', 'code' => 'PUSAT', 'is_active' => true]);
        $superadmin = $this->createSuperadmin();

        // 1. Outlet list should NOT display "Tambah Outlet Baru" button
        $response = $this->actingAs($superadmin)->get(route('admin.outlets.index'));
        $response->assertOk();
        $response->assertDontSee('Tambah Outlet Baru');

        // 2. Attendance report should NOT display outlet select dropdown
        $responseReport = $this->actingAs($superadmin)->get(route('admin.reports.attendance'));
        $responseReport->assertOk();
        $responseReport->assertDontSee('name="outlet_id"', false);

        // 3. Employee create should show automatic outlet note
        $responseEmp = $this->actingAs($superadmin)->get(route('admin.employees.create'));
        $responseEmp->assertOk();
        $responseEmp->assertSee('(Otomatis)');
    }

    public function test_ui_gating_in_multi_outlet_mode(): void
    {
        AppSetting::set('outlet_mode', OutletModeService::MODE_MULTI, 'string', false);
        $this->createOutlet(['name' => 'Pusat', 'code' => 'PUSAT', 'is_active' => true]);
        $this->createOutlet(['name' => 'Cabang', 'code' => 'KSC', 'is_active' => true]);
        $superadmin = $this->createSuperadmin();

        // 1. Outlet list should display "Tambah Outlet Baru" button
        $response = $this->actingAs($superadmin)->get(route('admin.outlets.index'));
        $response->assertOk();
        $response->assertSee('Tambah Outlet Baru');

        // 2. Attendance report should display outlet select dropdown
        $responseReport = $this->actingAs($superadmin)->get(route('admin.reports.attendance'));
        $responseReport->assertOk();
        $responseReport->assertSee('name="outlet_id"', false);
    }

    public function test_admin_cannot_change_outlet_mode(): void
    {
        AppSetting::set('outlet_mode', OutletModeService::MODE_MULTI, 'string', false);
        $outlet = $this->createOutlet(['name' => 'Pusat', 'code' => 'PUSAT', 'is_active' => true]);
        $admin = $this->createAdmin($outlet);

        $response = $this->actingAs($admin)->post(route('admin.settings.attendance.update'), [
            'timezone' => 'Asia/Jakarta',
            'outlet_mode' => 'single',
        ]);

        $response->assertRedirect(route('admin.settings.attendance'));
        $this->assertEquals(OutletModeService::MODE_MULTI, AppSetting::get('outlet_mode'));
    }

    // =========================================================================
    // 5. Zero Mutation Contract
    // =========================================================================

    public function test_data_integrity_assertions_prove_zero_mutation_across_sprint(): void
    {
        $outlet1 = $this->createOutlet(['name' => 'Pusat', 'code' => 'PUSAT', 'is_active' => true, 'latitude' => -6.1234567, 'longitude' => 106.1234567, 'radius_meters' => 100, 'max_accuracy_meters' => 50]);
        $outlet2 = $this->createOutlet(['name' => 'Cabang', 'code' => 'KSC', 'is_active' => true, 'latitude' => -6.7654321, 'longitude' => 106.7654321, 'radius_meters' => 150, 'max_accuracy_meters' => 60]);
        $employee1 = $this->createEmployee(['outlet_id' => $outlet1->id]);
        $employee2 = $this->createEmployee(['outlet_id' => $outlet2->id]);
        $shift = $this->createShift();

        $att1 = AttendanceRecord::create([
            'employee_id' => $employee1->id, 'work_date' => '2026-08-10', 'shift_id' => $shift->id, 'outlet_id' => $outlet1->id, 'status' => 'present',
        ]);
        $att2 = AttendanceRecord::create([
            'employee_id' => $employee2->id, 'work_date' => '2026-08-10', 'shift_id' => $shift->id, 'outlet_id' => $outlet2->id, 'status' => 'present',
        ]);

        $superadmin = $this->createSuperadmin();
        $transfer = EmployeeOutletTransfer::create([
            'employee_id' => $employee1->id,
            'from_outlet_id' => $outlet1->id,
            'to_outlet_id' => $outlet2->id,
            'transferred_by_user_id' => $superadmin->id,
            'effective_date' => '2026-08-15',
        ]);

        // Explicitly initialize mode via command
        $this->artisan('app:init-outlet-mode')->assertSuccessful();

        // Verify all baseline fields remain untouched
        $this->assertEquals(2, Outlet::count());
        $this->assertEquals(2, Employee::count());
        $this->assertEquals(2, AttendanceRecord::count());
        $this->assertEquals(1, EmployeeOutletTransfer::count());

        $this->assertEquals(-6.1234567, $outlet1->fresh()->latitude);
        $this->assertEquals(-6.7654321, $outlet2->fresh()->latitude);
        $this->assertEquals(100, $outlet1->fresh()->radius_meters);
        $this->assertEquals(150, $outlet2->fresh()->radius_meters);

        $this->assertEquals($outlet1->id, $employee1->fresh()->outlet_id);
        $this->assertEquals($outlet2->id, $employee2->fresh()->outlet_id);
        $this->assertEquals($outlet1->id, $att1->fresh()->outlet_id);
        $this->assertEquals($outlet2->id, $att2->fresh()->outlet_id);
        $this->assertEquals($outlet1->id, $transfer->fresh()->from_outlet_id);
        $this->assertEquals($outlet2->id, $transfer->fresh()->to_outlet_id);
    }
}
