<?php

namespace Tests\Feature\MultiOutlet;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Outlet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAssignedOutletPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private Outlet $pusat;

    private Outlet $cabang;

    private User $admin;

    private User $owner;

    private User $superadmin;

    private Employee $employeePusat;

    private Employee $employeeCabang;

    private User $userPusat;

    private User $userCabang;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pusat = $this->createOutlet('Pusat', 'HOTFIX-PUSAT');
        $this->cabang = $this->createOutlet('Cabang', 'HOTFIX-CABANG');

        $this->admin = User::create([
            'name' => 'Admin Pusat',
            'email' => 'admin.password@hotfix.test',
            'password' => Hash::make('admin-password'),
            'role' => 'admin',
            'outlet_id' => $this->pusat->id,
            'outlet_access_mode' => 'selected',
            'is_active' => true,
        ]);
        $this->admin->assignedOutlets()->sync([$this->pusat->id]);

        $this->owner = User::create([
            'name' => 'Owner',
            'email' => 'owner.password@hotfix.test',
            'password' => Hash::make('owner-password'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->superadmin = User::create([
            'name' => 'Superadmin',
            'email' => 'super.password@hotfix.test',
            'password' => Hash::make('super-password'),
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        [$this->employeePusat, $this->userPusat] = $this->createEmployeeWithUser(
            'EMP-PUSAT',
            'Employee Pusat',
            $this->pusat,
            'employee.pusat@hotfix.test',
            // A stale user default must not override Employee HOME outlet authorization.
            $this->cabang,
        );
        [$this->employeeCabang, $this->userCabang] = $this->createEmployeeWithUser(
            'EMP-CABANG',
            'Employee Cabang',
            $this->cabang,
            'employee.cabang@hotfix.test',
            $this->pusat,
        );
    }

    public function test_selected_admin_can_reset_employee_in_assigned_home_outlet(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.employees.reset-password', $this->employeePusat), $this->passwordPayload('new-pusat-password'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $user = $this->userPusat->fresh();
        $this->assertFalse(Hash::check('old-password', $user->password));
        $this->assertTrue(Hash::check('new-pusat-password', $user->password));
    }

    public function test_selected_admin_cannot_reset_employee_outside_assigned_home_outlet(): void
    {
        $oldHash = $this->userCabang->password;

        $this->actingAs($this->admin)
            ->post(route('admin.employees.reset-password', $this->employeeCabang), $this->passwordPayload())
            ->assertForbidden();

        $this->assertSame($oldHash, $this->userCabang->fresh()->password);
    }

    public function test_multi_outlet_admin_can_reset_employees_in_each_assigned_home_outlet(): void
    {
        $this->admin->assignedOutlets()->sync([$this->pusat->id, $this->cabang->id]);

        $this->actingAs($this->admin)
            ->post(route('admin.employees.reset-password', $this->employeePusat), $this->passwordPayload('multi-pusat-password'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->actingAs($this->admin)
            ->post(route('admin.employees.reset-password', $this->employeeCabang), $this->passwordPayload('multi-cabang-password'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('multi-pusat-password', $this->userPusat->fresh()->password));
        $this->assertTrue(Hash::check('multi-cabang-password', $this->userCabang->fresh()->password));
    }

    public function test_all_outlet_admin_can_reset_employee_password(): void
    {
        $this->admin->assignedOutlets()->sync([]);
        $this->admin->forceFill(['outlet_access_mode' => 'all'])->save();

        $this->actingAs($this->admin)
            ->post(route('admin.employees.reset-password', $this->employeeCabang), $this->passwordPayload())
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('new-password-123', $this->userCabang->fresh()->password));
    }

    public function test_selected_admin_without_assignments_fails_closed(): void
    {
        $this->admin->assignedOutlets()->sync([]);
        $oldHash = $this->userPusat->password;

        $this->actingAs($this->admin)
            ->post(route('admin.employees.reset-password', $this->employeePusat), $this->passwordPayload())
            ->assertForbidden();

        $this->assertSame($oldHash, $this->userPusat->fresh()->password);
    }

    public function test_forged_employee_id_outside_scope_is_forbidden_and_preserves_hash(): void
    {
        $oldHash = $this->userCabang->password;

        $this->actingAs($this->admin)
            ->post('/admin/employees/'.$this->employeeCabang->id.'/reset-password', $this->passwordPayload('forged-password'))
            ->assertForbidden();

        $this->assertSame($oldHash, $this->userCabang->fresh()->password);
    }

    public function test_query_and_session_outlet_tampering_cannot_bypass_scope(): void
    {
        $oldHash = $this->userCabang->password;

        $this->actingAs($this->admin)
            ->withSession([
                'active_outlet_id' => $this->cabang->id,
                'active_outlet_user_id' => $this->admin->id,
            ])
            ->post(
                route('admin.employees.reset-password', $this->employeeCabang).'?outlet_id='.$this->cabang->id,
                $this->passwordPayload('tampered-password'),
            )
            ->assertForbidden();

        $this->assertSame($oldHash, $this->userCabang->fresh()->password);
    }

    public function test_owner_and_superadmin_can_reset_employee_password_globally(): void
    {
        $this->actingAs($this->owner)
            ->post(route('admin.employees.reset-password', $this->employeeCabang), $this->passwordPayload('owner-new-password'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->actingAs($this->superadmin)
            ->post(route('admin.employees.reset-password', $this->employeePusat), [
                ...$this->passwordPayload('super-new-password'),
                'superadmin_password' => 'super-password',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('owner-new-password', $this->userCabang->fresh()->password));
        $this->assertTrue(Hash::check('super-new-password', $this->userPusat->fresh()->password));
    }

    public function test_admin_cannot_reset_privileged_linked_accounts(): void
    {
        foreach (['owner', 'superadmin'] as $role) {
            [$employee, $user] = $this->createEmployeeWithUser(
                'EMP-'.strtoupper($role),
                ucfirst($role).' Linked Employee',
                $this->pusat,
                $role.'.linked@hotfix.test',
                $this->pusat,
                $role,
            );
            $oldHash = $user->password;

            $this->actingAs($this->admin)
                ->post(route('admin.employees.reset-password', $employee), $this->passwordPayload())
                ->assertForbidden();

            $this->assertSame($oldHash, $user->fresh()->password);
        }
    }

    public function test_employee_without_linked_user_is_handled_without_creating_account(): void
    {
        $employee = Employee::create([
            'employee_code' => 'EMP-NO-LOGIN',
            'full_name' => 'Employee No Login',
            'status' => 'active',
            'outlet_id' => $this->pusat->id,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.employees.reset-password', $employee), $this->passwordPayload())
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertNull($employee->fresh()->user);

        $this->actingAs($this->admin)
            ->get(route('admin.employees.edit', $employee))
            ->assertOk()
            ->assertDontSee('Reset Password Akun Login Employee');
    }

    public function test_successful_reset_revokes_sessions_rotates_remember_token_and_writes_safe_audit(): void
    {
        $this->userPusat->forceFill(['remember_token' => 'old-remember-token'])->save();
        DB::table('sessions')->insert([
            'id' => 'password-reset-session',
            'user_id' => $this->userPusat->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => 'payload',
            'last_activity' => time(),
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.employees.reset-password', $this->employeePusat), $this->passwordPayload('safe-new-password'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $user = $this->userPusat->fresh();
        $this->assertFalse(Hash::check('old-password', $user->password));
        $this->assertTrue(Hash::check('safe-new-password', $user->password));
        $this->assertFalse(Auth::validate(['email' => $user->email, 'password' => 'old-password']));
        $this->assertTrue(Auth::validate(['email' => $user->email, 'password' => 'safe-new-password']));
        $this->assertNotSame('old-remember-token', $user->remember_token);
        $this->assertDatabaseMissing('sessions', ['id' => 'password-reset-session']);

        $audit = AuditLog::query()->where('action', 'password_reset.admin_completed')->latest('id')->firstOrFail();
        $this->assertSame($this->admin->id, $audit->user_id);
        $this->assertSame($this->userPusat->id, $audit->auditable_id);
        $auditJson = $audit->toJson();
        $this->assertStringNotContainsString('old-password', $auditJson);
        $this->assertStringNotContainsString('safe-new-password', $auditJson);
        $this->assertStringNotContainsString($user->password, $auditJson);
    }

    public function test_reset_ui_matches_record_authorization(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.employees.edit', $this->employeePusat))
            ->assertOk()
            ->assertSee('Reset Password Akun Login Employee');

        $this->actingAs($this->admin)
            ->get(route('admin.employees.edit', $this->employeeCabang))
            ->assertRedirect(route('admin.employees.index'))
            ->assertDontSee('Reset Password Akun Login Employee');
    }

    private function createOutlet(string $name, string $code): Outlet
    {
        return Outlet::create([
            'name' => $name,
            'code' => $code,
            'latitude' => -6.2,
            'longitude' => 106.8,
            'radius_meters' => 100,
            'is_active' => true,
        ]);
    }

    /**
     * @return array{Employee, User}
     */
    private function createEmployeeWithUser(
        string $code,
        string $name,
        Outlet $homeOutlet,
        string $email,
        Outlet $userDefaultOutlet,
        string $role = 'employee',
    ): array {
        $employee = Employee::create([
            'employee_code' => $code,
            'full_name' => $name,
            'email' => $email,
            'status' => 'active',
            'outlet_id' => $homeOutlet->id,
        ]);
        $user = User::create([
            'employee_id' => $employee->id,
            'outlet_id' => $userDefaultOutlet->id,
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('old-password'),
            'role' => $role,
            'is_active' => true,
        ]);

        return [$employee, $user];
    }

    /**
     * @return array<string, string>
     */
    private function passwordPayload(string $password = 'new-password-123'): array
    {
        return [
            'new_password' => $password,
            'new_password_confirmation' => $password,
        ];
    }
}
