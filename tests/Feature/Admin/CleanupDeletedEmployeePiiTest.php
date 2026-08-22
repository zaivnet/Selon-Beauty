<?php

namespace Tests\Feature\Admin;

use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmployeeOutletTransfer;
use App\Models\EmployeeSchedule;
use App\Models\LeaveRequest;
use App\Models\Outlet;
use App\Models\OvertimeRequest;
use App\Models\OvertimeSession;
use App\Models\Shift;
use App\Models\User;
use App\Services\EmployeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CleanupDeletedEmployeePiiTest extends TestCase
{
    use RefreshDatabase;

    protected Outlet $outlet;
    protected Shift $shift;
    protected Employee $legacyDeletedEmployee;
    protected User $linkedLegacyUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outlet = Outlet::firstOrCreate(
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

        $this->shift = Shift::firstOrCreate(
            ['code' => 'PAGI'],
            [
                'name' => 'Shift Pagi',
                'start_time' => '08:00',
                'end_time' => '16:00',
                'is_active' => true,
            ]
        );

        // Create legacy soft-deleted employee using direct DB insert
        $empId = DB::table('employees')->insertGetId([
            'employee_code' => 'SB-001',
            'full_name' => 'Ade Zaiv Legacy',
            'email' => 'adezaiv@gmail.com',
            'phone' => '082338464846',
            'outlet_id' => $this->outlet->id,
            'status' => 'active',
            'attendance_enabled' => 1,
            'deleted_at' => '2026-08-17 15:50:03',
            'created_at' => '2026-08-17 15:00:00',
            'updated_at' => '2026-08-17 15:50:03',
        ]);
        $this->legacyDeletedEmployee = Employee::withTrashed()->find($empId);

        $this->linkedLegacyUser = User::create([
            'employee_id' => $empId,
            'outlet_id' => $this->outlet->id,
            'name' => 'Ade Zaiv Legacy',
            'email' => 'adezaiv@gmail.com',
            'phone' => '082338464846',
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'is_active' => true,
            'remember_token' => 'legacy_token_123',
        ]);
    }

    public function test_1_legacy_soft_deleted_employee_with_pii_is_detected(): void
    {
        $exitCode = Artisan::call('app:cleanup-deleted-employee-pii', ['--dry-run' => true]);
        $output = Artisan::output();

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('SB-001', $output);
        $this->assertStringContainsString('Ade Zaiv Legacy', $output);
        $this->assertStringContainsString('Would Clean: 1', $output);
    }

    public function test_2_active_employee_with_pii_is_not_touched(): void
    {
        $activeEmp = Employee::create([
            'employee_code' => 'SB-999',
            'full_name' => 'Active Employee',
            'email' => 'active@company.com',
            'phone' => '08999888777',
            'outlet_id' => $this->outlet->id,
            'status' => 'active',
        ]);

        $exitCode = Artisan::call('app:cleanup-deleted-employee-pii --force');
        $this->assertEquals(0, $exitCode);

        $activeEmp->refresh();
        $this->assertEquals('active@company.com', $activeEmp->email);
        $this->assertEquals('08999888777', $activeEmp->phone);
    }

    public function test_3_already_clean_soft_deleted_employee_is_ignored(): void
    {
        // First cleanup legacyDeletedEmployee
        Artisan::call('app:cleanup-deleted-employee-pii --force');

        // Run dry-run again; clean employee is ignored
        $exitCode = Artisan::call('app:cleanup-deleted-employee-pii', ['--dry-run' => true]);
        $output = Artisan::output();

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('No legacy soft-deleted employees with PII found.', $output);
    }

    public function test_4_dry_run_makes_zero_database_changes(): void
    {
        $exitCode = Artisan::call('app:cleanup-deleted-employee-pii', ['--dry-run' => true]);
        $this->assertEquals(0, $exitCode);

        $emp = Employee::withTrashed()->find($this->legacyDeletedEmployee->id);
        $user = User::find($this->linkedLegacyUser->id);

        $this->assertEquals('adezaiv@gmail.com', $emp->email);
        $this->assertEquals('082338464846', $emp->phone);
        $this->assertEquals('adezaiv@gmail.com', $user->email);
        $this->assertTrue((bool) $user->is_active);
    }

    public function test_5_force_clears_employee_email(): void
    {
        $exitCode = Artisan::call('app:cleanup-deleted-employee-pii --force');
        $this->assertEquals(0, $exitCode);

        $emp = Employee::withTrashed()->find($this->legacyDeletedEmployee->id);
        $this->assertNull($emp->email);
    }

    public function test_6_force_clears_employee_phone(): void
    {
        $exitCode = Artisan::call('app:cleanup-deleted-employee-pii --force');
        $this->assertEquals(0, $exitCode);

        $emp = Employee::withTrashed()->find($this->legacyDeletedEmployee->id);
        $this->assertNull($emp->phone);
    }

    public function test_7_linked_employee_user_is_revoked(): void
    {
        $exitCode = Artisan::call('app:cleanup-deleted-employee-pii --force');
        $this->assertEquals(0, $exitCode);

        $user = User::find($this->linkedLegacyUser->id);
        $this->assertFalse((bool) $user->is_active);
    }

    public function test_8_linked_user_email_released_anonymized(): void
    {
        $exitCode = Artisan::call('app:cleanup-deleted-employee-pii --force');
        $this->assertEquals(0, $exitCode);

        $user = User::find($this->linkedLegacyUser->id);
        $this->assertNull($user->email);
        $this->assertNull($user->phone);
    }

    public function test_9_linked_user_sessions_are_deleted(): void
    {
        DB::table('sessions')->insert([
            'id' => 'legacy_session_abc',
            'user_id' => $this->linkedLegacyUser->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => 'dummy',
            'last_activity' => time(),
        ]);

        $this->assertDatabaseHas('sessions', ['id' => 'legacy_session_abc']);

        $exitCode = Artisan::call('app:cleanup-deleted-employee-pii --force');
        $this->assertEquals(0, $exitCode);

        $this->assertDatabaseMissing('sessions', ['id' => 'legacy_session_abc']);
    }

    public function test_10_remember_token_invalidated(): void
    {
        $exitCode = Artisan::call('app:cleanup-deleted-employee-pii --force');
        $this->assertEquals(0, $exitCode);

        $user = User::find($this->linkedLegacyUser->id);
        $this->assertNull($user->remember_token);
    }

    public function test_11_old_email_can_be_reused_after_cleanup(): void
    {
        $oldEmail = $this->legacyDeletedEmployee->email;

        $exitCode = Artisan::call('app:cleanup-deleted-employee-pii --force');
        $this->assertEquals(0, $exitCode);

        $newEmp = Employee::create([
            'employee_code' => 'SB-002',
            'full_name' => 'Ade Zaiv Reborn',
            'email' => $oldEmail,
            'phone' => '08999000111',
            'outlet_id' => $this->outlet->id,
            'status' => 'active',
        ]);

        $newUser = User::create([
            'employee_id' => $newEmp->id,
            'outlet_id' => $this->outlet->id,
            'name' => 'Ade Zaiv Reborn',
            'email' => $oldEmail,
            'phone' => '08999000111',
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        $this->assertEquals($oldEmail, $newEmp->email);
        $this->assertEquals($oldEmail, $newUser->email);
    }

    public function test_12_old_phone_can_be_reused_after_cleanup(): void
    {
        $oldPhone = $this->legacyDeletedEmployee->phone;

        $exitCode = Artisan::call('app:cleanup-deleted-employee-pii --force');
        $this->assertEquals(0, $exitCode);

        $newEmp = Employee::create([
            'employee_code' => 'SB-003',
            'full_name' => 'Ade Zaiv New Phone',
            'email' => 'newemail@company.com',
            'phone' => $oldPhone,
            'outlet_id' => $this->outlet->id,
            'status' => 'active',
        ]);

        $this->assertEquals($oldPhone, $newEmp->phone);
    }

    public function test_13_attendance_history_remains(): void
    {
        $rec = AttendanceRecord::create([
            'employee_id' => $this->legacyDeletedEmployee->id,
            'work_date' => '2026-08-15',
            'status' => 'present',
            'check_in_at' => '2026-08-15 08:00:00',
            'check_out_at' => '2026-08-15 16:00:00',
            'outlet_id' => $this->outlet->id,
        ]);

        $exitCode = Artisan::call('app:cleanup-deleted-employee-pii --force');
        $this->assertEquals(0, $exitCode);

        $this->assertDatabaseHas('attendance_records', ['id' => $rec->id]);
        $fetched = AttendanceRecord::find($rec->id);
        $this->assertNotNull($fetched->employee);
        $this->assertEquals('Ade Zaiv Legacy', $fetched->employee->full_name);
    }

    public function test_14_leave_history_remains(): void
    {
        $leave = LeaveRequest::create([
            'employee_id' => $this->legacyDeletedEmployee->id,
            'type' => 'sick',
            'start_date' => '2026-08-14',
            'end_date' => '2026-08-14',
            'reason' => 'Demam',
            'status' => 'approved',
        ]);

        $exitCode = Artisan::call('app:cleanup-deleted-employee-pii --force');
        $this->assertEquals(0, $exitCode);

        $this->assertDatabaseHas('leave_requests', ['id' => $leave->id]);
        $fetched = LeaveRequest::find($leave->id);
        $this->assertNotNull($fetched->employee);
        $this->assertEquals('SB-001', $fetched->employee->employee_code);
    }

    public function test_15_overtime_history_remains(): void
    {
        $otReq = OvertimeRequest::create([
            'employee_id' => $this->legacyDeletedEmployee->id,
            'work_date' => '2026-08-10',
            'requested_minutes' => 60,
            'approved_minutes' => 60,
            'reason' => 'Closing event',
            'status' => 'approved',
        ]);

        $otSess = OvertimeSession::create([
            'overtime_request_id' => $otReq->id,
            'employee_id' => $this->legacyDeletedEmployee->id,
            'work_date' => '2026-08-10',
            'status' => 'completed',
            'actual_minutes' => 60,
            'credited_minutes' => 60,
        ]);

        $exitCode = Artisan::call('app:cleanup-deleted-employee-pii --force');
        $this->assertEquals(0, $exitCode);

        $this->assertDatabaseHas('overtime_requests', ['id' => $otReq->id]);
        $this->assertDatabaseHas('overtime_sessions', ['id' => $otSess->id]);

        $fetched = OvertimeSession::find($otSess->id);
        $this->assertNotNull($fetched->employee);
    }

    public function test_16_transfer_history_remains(): void
    {
        $cabang = Outlet::create([
            'name' => 'Outlet Cabang 2',
            'code' => 'CABANG2',
            'latitude' => -6.3,
            'longitude' => 106.9,
            'radius_meters' => 100,
            'max_accuracy_meters' => 100,
            'is_active' => true,
        ]);

        $transfer = EmployeeOutletTransfer::create([
            'employee_id' => $this->legacyDeletedEmployee->id,
            'from_outlet_id' => $cabang->id,
            'to_outlet_id' => $this->outlet->id,
            'effective_date' => '2026-08-01',
            'transferred_by_user_id' => $this->linkedLegacyUser->id,
        ]);

        $exitCode = Artisan::call('app:cleanup-deleted-employee-pii --force');
        $this->assertEquals(0, $exitCode);

        $this->assertDatabaseHas('employee_outlet_transfers', ['id' => $transfer->id]);
        $fetched = EmployeeOutletTransfer::find($transfer->id);
        $this->assertNotNull($fetched->employee);
    }

    public function test_17_schedule_history_remains(): void
    {
        $sched = EmployeeSchedule::create([
            'employee_id' => $this->legacyDeletedEmployee->id,
            'work_date' => '2026-08-10',
            'shift_id' => $this->shift->id,
            'work_outlet_id' => $this->outlet->id,
            'schedule_type' => 'regular',
        ]);

        $exitCode = Artisan::call('app:cleanup-deleted-employee-pii --force');
        $this->assertEquals(0, $exitCode);

        $this->assertDatabaseHas('work_schedules', ['id' => $sched->id]);
        $fetched = EmployeeSchedule::find($sched->id);
        $this->assertNotNull($fetched->employee);
    }

    public function test_18_unrelated_user_with_same_email_is_not_touched(): void
    {
        // First cleanup linkedLegacyUser
        Artisan::call('app:cleanup-deleted-employee-pii --force');

        // Now create an unrelated active User with the released email
        $unrelatedUser = User::create([
            'employee_id' => null,
            'outlet_id' => $this->outlet->id,
            'name' => 'Unrelated User Shared Email',
            'email' => 'adezaiv@gmail.com',
            'phone' => '08999111222',
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        $exitCode = Artisan::call('app:cleanup-deleted-employee-pii --force');
        $this->assertEquals(0, $exitCode);

        $unrelatedUser->refresh();
        $this->assertEquals('adezaiv@gmail.com', $unrelatedUser->email);
        $this->assertTrue((bool) $unrelatedUser->is_active);
    }

    public function test_19_unrelated_admin_with_same_email_is_not_touched(): void
    {
        Artisan::call('app:cleanup-deleted-employee-pii --force');

        $unrelatedAdmin = User::create([
            'employee_id' => null,
            'outlet_id' => $this->outlet->id,
            'name' => 'Unrelated Admin',
            'email' => 'adezaiv@gmail.com',
            'phone' => '08999111333',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $exitCode = Artisan::call('app:cleanup-deleted-employee-pii --force');
        $this->assertEquals(0, $exitCode);

        $unrelatedAdmin->refresh();
        $this->assertEquals('adezaiv@gmail.com', $unrelatedAdmin->email);
        $this->assertTrue((bool) $unrelatedAdmin->is_active);
    }

    public function test_20_unrelated_owner_superadmin_is_not_touched(): void
    {
        $owner = User::create([
            'name' => 'Owner Utama',
            'email' => 'owner@selonbeauty.com',
            'phone' => '08999888111',
            'password' => Hash::make('password123'),
            'role' => 'owner',
            'outlet_id' => $this->outlet->id,
            'is_active' => true,
        ]);

        $exitCode = Artisan::call('app:cleanup-deleted-employee-pii --force');
        $this->assertEquals(0, $exitCode);

        $owner->refresh();
        $this->assertEquals('owner@selonbeauty.com', $owner->email);
        $this->assertTrue((bool) $owner->is_active);
    }

    public function test_21_genuine_linked_privileged_user_reported_as_conflict(): void
    {
        // Legacy employee genuinely linked to an Admin User
        $empId = DB::table('employees')->insertGetId([
            'employee_code' => 'SB-PRIV',
            'full_name' => 'Privileged Admin Employee',
            'email' => 'admin.priv@company.com',
            'phone' => '08555444333',
            'outlet_id' => $this->outlet->id,
            'status' => 'active',
            'attendance_enabled' => 1,
            'deleted_at' => '2026-08-17 15:50:03',
            'created_at' => '2026-08-17 15:00:00',
            'updated_at' => '2026-08-17 15:50:03',
        ]);
        $privEmp = Employee::withTrashed()->find($empId);

        $privUser = User::create([
            'employee_id' => $empId,
            'outlet_id' => $this->outlet->id,
            'name' => 'Privileged Admin Employee',
            'email' => 'admin.priv@company.com',
            'phone' => '08555444333',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $exitCode = Artisan::call('app:cleanup-deleted-employee-pii', ['--dry-run' => true]);
        $output = Artisan::output();

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('CONFLICT', $output);
        $this->assertStringContainsString('Conflicts: 1', $output);

        // On force mode, conflict candidate is skipped from mutation
        $exitCodeForce = Artisan::call('app:cleanup-deleted-employee-pii --force');
        $outputForce = Artisan::output();

        $this->assertEquals(0, $exitCodeForce);
        $this->assertStringContainsString('Conflicts: 1', $outputForce);

        $privEmp = Employee::withTrashed()->find($empId);
        $privUser->refresh();
        $this->assertEquals('admin.priv@company.com', $privEmp->email);
        $this->assertEquals('admin.priv@company.com', $privUser->email);
        $this->assertTrue((bool) $privUser->is_active);
    }

    public function test_22_command_is_idempotent(): void
    {
        // First run cleans candidate
        $exitCode1 = Artisan::call('app:cleanup-deleted-employee-pii --force');
        $output1 = Artisan::output();

        $this->assertEquals(0, $exitCode1);
        $this->assertStringContainsString('Cleaned: 1', $output1);

        // Second run cleans 0 candidates
        $exitCode2 = Artisan::call('app:cleanup-deleted-employee-pii --force');
        $output2 = Artisan::output();

        $this->assertEquals(0, $exitCode2);
        $this->assertStringContainsString('Candidates: 0', $output2);
        $this->assertStringContainsString('Cleaned: 0', $output2);
    }

    public function test_23_second_force_run_cleans_zero_records(): void
    {
        Artisan::call('app:cleanup-deleted-employee-pii --force');

        $exitCode = Artisan::call('app:cleanup-deleted-employee-pii --force');
        $output = Artisan::output();

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('No legacy soft-deleted employees with PII found.', $output);
    }

    public function test_24_audit_log_contains_no_old_email_phone_password_hash(): void
    {
        Artisan::call('app:cleanup-deleted-employee-pii --force');

        $audit = AuditLog::where('action', 'employee.deleted_pii.cleaned')->first();
        $this->assertNotNull($audit);
        $this->assertEquals('legacy_backfill', $audit->metadata['source'] ?? null);
        $this->assertArrayNotHasKey('email', $audit->metadata);
        $this->assertArrayNotHasKey('phone', $audit->metadata);
        $this->assertArrayNotHasKey('password', $audit->metadata);
        $this->assertArrayNotHasKey('password_hash', $audit->metadata);
    }

    public function test_25_transaction_rollback_prevents_partial_state_on_simulated_failure(): void
    {
        $this->expectException(\RuntimeException::class);

        DB::transaction(function () {
            $this->linkedLegacyUser->update(['email' => null]);
            throw new \RuntimeException('Simulated failure during cleanup');
        });

        $this->linkedLegacyUser->refresh();
        $this->assertEquals('adezaiv@gmail.com', $this->linkedLegacyUser->email);
    }

    public function test_26_deleted_employee_name_and_code_remain_intact(): void
    {
        Artisan::call('app:cleanup-deleted-employee-pii --force');

        $emp = Employee::withTrashed()->find($this->legacyDeletedEmployee->id);
        $this->assertEquals('SB-001', $emp->employee_code);
        $this->assertEquals('Ade Zaiv Legacy', $emp->full_name);
    }

    public function test_27_deleted_at_remains_unchanged(): void
    {
        $originalDeletedAt = $this->legacyDeletedEmployee->deleted_at->toDateTimeString();

        Artisan::call('app:cleanup-deleted-employee-pii --force');

        $emp = Employee::withTrashed()->find($this->legacyDeletedEmployee->id);
        $this->assertEquals($originalDeletedAt, $emp->deleted_at->toDateTimeString());
    }
}
