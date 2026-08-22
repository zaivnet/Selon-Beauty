<?php

namespace Tests\Feature\MultiOutlet;

use App\Enums\OutletAccessMode;
use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Outlet;
use App\Models\OvertimeRequest;
use App\Models\User;
use App\Services\AdminOutletAccessService;
use App\Services\OutletScopeService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminAssignedOutletsTest extends TestCase
{
    use RefreshDatabase;

    private Outlet $outletA;

    private Outlet $outletB;

    private Outlet $outletC;

    private User $admin;

    private User $owner;

    private User $superadmin;

    private Employee $employeeA;

    private Employee $employeeB;

    private Employee $employeeC;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['A', 'B', 'C'] as $suffix) {
            $this->{'outlet'.$suffix} = Outlet::create([
                'name' => "Outlet {$suffix}",
                'code' => "OA{$suffix}",
                'latitude' => -7.1,
                'longitude' => 113.4,
                'radius_meters' => 100,
                'is_active' => true,
            ]);
        }

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'outlet_id' => $this->outletA->id,
            'outlet_access_mode' => OutletAccessMode::SELECTED->value,
        ]);
        $this->admin->assignedOutlets()->sync([$this->outletA->id, $this->outletB->id]);

        $this->owner = User::factory()->create(['role' => 'owner', 'is_active' => true, 'outlet_id' => null]);
        $this->superadmin = User::factory()->create(['role' => 'superadmin', 'is_active' => true, 'outlet_id' => null]);

        foreach (['A', 'B', 'C'] as $suffix) {
            $outlet = $this->{'outlet'.$suffix};
            $this->{'employee'.$suffix} = Employee::create([
                'employee_code' => "AO-EMP-{$suffix}",
                'full_name' => "Employee {$suffix}",
                'email' => strtolower("employee{$suffix}@assigned.test"),
                'status' => 'active',
                'outlet_id' => $outlet->id,
            ]);
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_selected_all_global_and_fail_closed_semantics(): void
    {
        $scope = app(OutletScopeService::class);

        $this->assertSame([$this->outletA->id, $this->outletB->id], $scope->allowedOutletIds($this->admin));
        $this->assertTrue($scope->isGlobalScope($this->owner));
        $this->assertTrue($scope->isGlobalScope($this->superadmin));

        $allAdmin = User::factory()->create([
            'role' => 'admin',
            'outlet_access_mode' => OutletAccessMode::ALL->value,
            'is_active' => true,
        ]);
        $this->assertFalse($scope->isGlobalScope($allAdmin));
        $this->assertTrue($scope->hasAllOutletAccess($allAdmin));
        $this->assertTrue($scope->canManageEmployee($allAdmin, $this->employeeC));

        $emptyAdmin = User::factory()->create([
            'role' => 'admin',
            'outlet_access_mode' => OutletAccessMode::SELECTED->value,
            'outlet_id' => $this->outletA->id,
            'is_active' => true,
        ]);
        $this->assertSame([], $scope->allowedOutletIds($emptyAdmin));
        $this->assertFalse($scope->canManageEmployee($emptyAdmin, $this->employeeA));
    }

    public function test_migration_backfills_legacy_scalar_admin_without_granting_null_admin(): void
    {
        $legacy = User::factory()->create([
            'role' => 'admin', 'is_active' => true, 'outlet_id' => $this->outletC->id,
        ]);
        $legacyWithoutOutlet = User::factory()->create([
            'role' => 'admin', 'is_active' => true, 'outlet_id' => null,
        ]);

        $migration = require database_path('migrations/2026_08_22_000001_add_admin_assigned_outlets.php');
        $migration->down();
        $migration->up();

        $this->assertDatabaseHas('users', [
            'id' => $legacy->id,
            'outlet_access_mode' => OutletAccessMode::SELECTED->value,
        ]);
        $this->assertDatabaseHas('admin_outlet_assignments', [
            'user_id' => $legacy->id,
            'outlet_id' => $this->outletC->id,
        ]);
        $this->assertDatabaseMissing('admin_outlet_assignments', [
            'user_id' => $legacyWithoutOutlet->id,
        ]);
    }

    public function test_all_mode_gets_future_outlet_but_selected_mode_does_not(): void
    {
        $allAdmin = User::factory()->create([
            'role' => 'admin',
            'outlet_access_mode' => OutletAccessMode::ALL->value,
            'is_active' => true,
        ]);
        $future = Outlet::create([
            'name' => 'Future Outlet', 'code' => 'FUT', 'latitude' => -7.2,
            'longitude' => 113.5, 'radius_meters' => 100, 'is_active' => true,
        ]);

        $scope = new OutletScopeService;
        $this->assertTrue($scope->canAccessOutlet($allAdmin, $future));
        $this->assertFalse($scope->canAccessOutlet($this->admin, $future));
    }

    public function test_selected_admin_scopes_both_assigned_outlets_and_rejects_forged_employee(): void
    {
        $scope = app(OutletScopeService::class);
        $ids = $scope->scopeEmployeesFor($this->admin, Employee::query())->pluck('id')->all();

        $this->assertContains($this->employeeA->id, $ids);
        $this->assertContains($this->employeeB->id, $ids);
        $this->assertNotContains($this->employeeC->id, $ids);

        $this->actingAs($this->admin)
            ->get(route('admin.employees.show', $this->employeeC))
            ->assertForbidden();
    }

    public function test_multi_outlet_admin_must_choose_authorized_outlet_when_creating_employee(): void
    {
        $payload = [
            'employee_code' => 'AO-NEW',
            'full_name' => 'Employee New',
            'email' => 'employee.new@assigned.test',
            'status' => 'active',
            'create_user_account' => 0,
        ];

        $this->actingAs($this->admin)->post(route('admin.employees.store'), $payload)
            ->assertSessionHasErrors('outlet_id');

        $this->actingAs($this->admin)->post(route('admin.employees.store'), [
            ...$payload,
            'outlet_id' => $this->outletC->id,
        ])->assertForbidden();

        $this->actingAs($this->admin)->post(route('admin.employees.store'), [
            ...$payload,
            'outlet_id' => $this->outletB->id,
        ])->assertRedirect(route('admin.employees.index'));

        $this->assertDatabaseHas('employees', ['employee_code' => 'AO-NEW', 'outlet_id' => $this->outletB->id]);
    }

    public function test_employee_edit_rejects_unauthorized_home_outlet_and_preserves_home(): void
    {
        $this->actingAs($this->admin)->put(route('admin.employees.update', $this->employeeA), [
            'employee_code' => $this->employeeA->employee_code,
            'full_name' => $this->employeeA->full_name,
            'email' => $this->employeeA->email,
            'status' => 'active',
            'outlet_id' => $this->outletC->id,
            'role' => 'employee',
        ])->assertSessionHasErrors('outlet_id');

        $this->assertSame($this->outletA->id, $this->employeeA->fresh()->outlet_id);
    }

    public function test_attendance_leave_and_overtime_authorization_uses_allowed_home_outlets(): void
    {
        Carbon::setTestNow('2026-08-22 10:00:00');
        $attendanceB = AttendanceRecord::create([
            'employee_id' => $this->employeeB->id,
            'outlet_id' => $this->outletB->id,
            'work_date' => '2026-08-22',
            'status' => 'present',
        ]);
        $leaveB = LeaveRequest::create([
            'employee_id' => $this->employeeB->id,
            'type' => 'permission',
            'start_date' => '2026-08-22',
            'end_date' => '2026-08-22',
            'reason' => 'Keperluan keluarga',
            'status' => 'pending',
        ]);
        $overtimeC = OvertimeRequest::create([
            'employee_id' => $this->employeeC->id,
            'work_date' => '2026-08-22',
            'requested_minutes' => 60,
            'reason' => 'Pekerjaan tambahan',
            'status' => 'pending',
        ]);

        $scope = app(OutletScopeService::class);
        $this->assertTrue($scope->canManageAttendance($this->admin, $attendanceB));
        $this->assertTrue($scope->canManageLeave($this->admin, $leaveB));
        $this->assertFalse($scope->canManageOvertime($this->admin, $overtimeC));
    }

    public function test_outlet_filter_only_lists_authorized_outlets_and_tampering_is_sanitized(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.employees.index', ['outlet_id' => $this->outletC->id]));

        $response->assertOk()
            ->assertSee('Outlet A')
            ->assertSee('Outlet B')
            ->assertDontSee('AO-EMP-C');
        $this->assertSame($this->outletA->id, session('active_outlet_id'));
    }

    public function test_access_change_is_audited_revokes_sessions_and_applies_immediately(): void
    {
        DB::table('sessions')->insert([
            'id' => 'assigned-outlet-session',
            'user_id' => $this->admin->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => '',
            'last_activity' => now()->timestamp,
        ]);

        app(AdminOutletAccessService::class)->update(
            $this->admin,
            OutletAccessMode::SELECTED->value,
            [$this->outletB->id],
            $this->owner,
        );

        $fresh = $this->admin->fresh();
        $scope = new OutletScopeService;
        $this->assertFalse($scope->canAccessOutlet($fresh, $this->outletA));
        $this->assertTrue($scope->canAccessOutlet($fresh, $this->outletB));
        $this->assertDatabaseMissing('sessions', ['id' => 'assigned-outlet-session']);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.outlet_access_changed',
            'user_id' => $this->owner->id,
            'auditable_id' => $this->admin->id,
        ]);

        $audit = AuditLog::where('action', 'user.outlet_access_changed')->latest('id')->firstOrFail();
        $this->assertSame([$this->outletA->id, $this->outletB->id], $audit->before_data['assigned_outlet_ids']);
        $this->assertSame([$this->outletB->id], $audit->after_data['assigned_outlet_ids']);
    }

    public function test_notification_recipient_scope_prevents_cross_outlet_admin_leakage(): void
    {
        $adminC = User::factory()->create([
            'role' => 'admin', 'is_active' => true, 'outlet_access_mode' => 'selected',
        ]);
        $adminC->assignedOutlets()->sync([$this->outletC->id]);

        $ids = app(OutletScopeService::class)
            ->scopeNotificationRecipientsForOutlet(User::query(), $this->outletB->id, ['owner'])
            ->pluck('id')
            ->all();

        $this->assertContains($this->admin->id, $ids);
        $this->assertContains($this->owner->id, $ids);
        $this->assertNotContains($adminC->id, $ids);
    }

    public function test_allowed_outlet_resolution_is_reused_inside_record_loop(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $scope = app(OutletScopeService::class);
        foreach ([$this->employeeA, $this->employeeB, $this->employeeC] as $employee) {
            $scope->canManageEmployee($this->admin, $employee);
        }

        $assignmentQueries = collect(DB::getQueryLog())
            ->filter(fn (array $query) => str_contains($query['query'], 'admin_outlet_assignments'));
        $this->assertCount(1, $assignmentQueries);
    }
}
