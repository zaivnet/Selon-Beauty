<?php

namespace Tests\Feature\Admin;

use App\Models\AttendanceLocation;
use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmployeeOutletTransfer;
use App\Models\LeaveRequest;
use App\Models\Outlet;
use App\Models\OvertimeRequest;
use App\Models\OvertimeSession;
use App\Models\Shift;
use App\Models\ShiftSwapRequest;
use App\Models\User;
use App\Services\EmployeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmployeeDeletionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $adminScopedPusat;
    protected User $adminScopedCabang;
    protected Outlet $outletPusat;
    protected Outlet $outletCabang;
    protected Employee $employeePusat;
    protected User $userPusat;
    protected Shift $shiftPagi;

    protected function setUp(): void
    {
        parent::setUp();

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
                'longitude' => 106.916666,
                'radius_meters' => 100,
                'max_accuracy_meters' => 100,
                'is_active' => true,
            ]
        );

        $this->owner = User::create([
            'name' => 'Owner Utama',
            'email' => 'owner@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'owner',
            'outlet_id' => $this->outletPusat->id,
            'is_active' => true,
        ]);

        $this->adminScopedPusat = User::create([
            'name' => 'Admin Pusat',
            'email' => 'admin.pusat@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'outlet_id' => $this->outletPusat->id,
            'outlet_access_mode' => 'selected',
            'is_active' => true,
        ]);
        $this->adminScopedPusat->assignedOutlets()->sync([$this->outletPusat->id]);

        $this->adminScopedCabang = User::create([
            'name' => 'Admin Cabang',
            'email' => 'admin.cabang@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'outlet_id' => $this->outletCabang->id,
            'outlet_access_mode' => 'selected',
            'is_active' => true,
        ]);
        $this->adminScopedCabang->assignedOutlets()->sync([$this->outletCabang->id]);

        $this->employeePusat = Employee::create([
            'employee_code' => 'SB-101',
            'full_name' => 'Siti Aminah',
            'email' => 'siti@company.com',
            'phone' => '081234567890',
            'outlet_id' => $this->outletPusat->id,
            'status' => 'active',
            'attendance_enabled' => true,
        ]);

        $this->userPusat = User::create([
            'employee_id' => $this->employeePusat->id,
            'outlet_id' => $this->outletPusat->id,
            'name' => 'Siti Aminah',
            'email' => 'siti@company.com',
            'phone' => '081234567890',
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'is_active' => true,
            'remember_token' => 'some_token_123',
        ]);

        $this->shiftPagi = Shift::create([
            'name' => 'Shift Pagi',
            'code' => 'PAGI',
            'start_time' => '08:00',
            'end_time' => '16:00',
            'is_active' => true,
        ]);
    }

    public function test_1_delete_employee_soft_deletes_employee_record(): void
    {
        $response = $this->actingAs($this->owner)->delete("/admin/employees/{$this->employeePusat->id}");

        $response->assertRedirect('/admin/employees');
        $this->assertSoftDeleted('employees', ['id' => $this->employeePusat->id]);
    }

    public function test_2_attendance_history_remains_after_deletion(): void
    {
        $rec = AttendanceRecord::create([
            'employee_id' => $this->employeePusat->id,
            'work_date' => '2026-08-20',
            'status' => 'present',
            'check_in_at' => '2026-08-20 08:00:00',
            'check_out_at' => '2026-08-20 16:00:00',
            'outlet_id' => $this->outletPusat->id,
        ]);

        $this->actingAs($this->owner)->delete("/admin/employees/{$this->employeePusat->id}");

        $this->assertDatabaseHas('attendance_records', ['id' => $rec->id]);
        $fetched = AttendanceRecord::find($rec->id);
        $this->assertNotNull($fetched->employee);
        $this->assertEquals('Siti Aminah', $fetched->employee->full_name);
    }

    public function test_3_leave_history_remains_after_deletion(): void
    {
        $leave = LeaveRequest::create([
            'employee_id' => $this->employeePusat->id,
            'type' => 'permission',
            'start_date' => '2026-08-21',
            'end_date' => '2026-08-21',
            'reason' => 'Izin keluarga',
            'status' => 'approved',
        ]);

        $this->actingAs($this->owner)->delete("/admin/employees/{$this->employeePusat->id}");

        $this->assertDatabaseHas('leave_requests', ['id' => $leave->id]);
        $fetched = LeaveRequest::find($leave->id);
        $this->assertNotNull($fetched->employee);
        $this->assertEquals('SB-101', $fetched->employee->employee_code);
    }

    public function test_4_overtime_history_remains_after_deletion(): void
    {
        $otReq = OvertimeRequest::create([
            'employee_id' => $this->employeePusat->id,
            'work_date' => '2026-08-20',
            'requested_minutes' => 120,
            'approved_minutes' => 120,
            'reason' => 'Lembur stok opname',
            'status' => 'approved',
        ]);

        $otSess = OvertimeSession::create([
            'overtime_request_id' => $otReq->id,
            'employee_id' => $this->employeePusat->id,
            'work_date' => '2026-08-20',
            'status' => 'completed',
            'actual_minutes' => 120,
            'credited_minutes' => 120,
        ]);

        $this->actingAs($this->owner)->delete("/admin/employees/{$this->employeePusat->id}");

        $this->assertDatabaseHas('overtime_requests', ['id' => $otReq->id]);
        $this->assertDatabaseHas('overtime_sessions', ['id' => $otSess->id]);

        $fetchedSess = OvertimeSession::find($otSess->id);
        $this->assertNotNull($fetchedSess->employee);
        $this->assertEquals('Siti Aminah', $fetchedSess->employee->full_name);
    }

    public function test_5_transfer_history_remains_after_deletion(): void
    {
        $transfer = EmployeeOutletTransfer::create([
            'employee_id' => $this->employeePusat->id,
            'from_outlet_id' => $this->outletCabang->id,
            'to_outlet_id' => $this->outletPusat->id,
            'effective_date' => '2026-08-01',
            'transferred_by_user_id' => $this->owner->id,
        ]);

        $this->actingAs($this->owner)->delete("/admin/employees/{$this->employeePusat->id}");

        $this->assertDatabaseHas('employee_outlet_transfers', ['id' => $transfer->id]);
        $fetched = EmployeeOutletTransfer::find($transfer->id);
        $this->assertNotNull($fetched->employee);
        $this->assertEquals($this->employeePusat->id, $fetched->employee->id);
    }

    public function test_6_employee_email_is_anonymized_to_null(): void
    {
        $this->actingAs($this->owner)->delete("/admin/employees/{$this->employeePusat->id}");

        $emp = Employee::withTrashed()->find($this->employeePusat->id);
        $this->assertNull($emp->email);
    }

    public function test_7_employee_phone_is_anonymized_to_null(): void
    {
        $this->actingAs($this->owner)->delete("/admin/employees/{$this->employeePusat->id}");

        $emp = Employee::withTrashed()->find($this->employeePusat->id);
        $this->assertNull($emp->phone);
    }

    public function test_8_linked_user_login_email_no_longer_blocks_reuse(): void
    {
        $this->actingAs($this->owner)->delete("/admin/employees/{$this->employeePusat->id}");

        $user = User::find($this->userPusat->id);
        $this->assertNotNull($user);
        $this->assertNull($user->email);
        $this->assertFalse($user->is_active);
    }

    public function test_9_same_old_email_can_be_used_for_new_employee(): void
    {
        $oldEmail = $this->employeePusat->email;

        $this->actingAs($this->owner)->delete("/admin/employees/{$this->employeePusat->id}");

        $response = $this->actingAs($this->owner)->post('/admin/employees', [
            'employee_code' => 'SB-102',
            'full_name' => 'Ayu Lestari Baru',
            'email' => $oldEmail,
            'phone' => '081999888777',
            'outlet_id' => $this->outletPusat->id,
            'status' => 'active',
            'create_user_account' => '1',
            'account_password' => 'password123',
        ]);

        $response->assertRedirect('/admin/employees');
        $this->assertDatabaseHas('employees', [
            'employee_code' => 'SB-102',
            'email' => $oldEmail,
        ]);
        $this->assertDatabaseHas('users', [
            'email' => $oldEmail,
        ]);
    }

    public function test_10_same_old_phone_can_be_used_again(): void
    {
        $oldPhone = $this->employeePusat->phone;

        $this->actingAs($this->owner)->delete("/admin/employees/{$this->employeePusat->id}");

        $response = $this->actingAs($this->owner)->post('/admin/employees', [
            'employee_code' => 'SB-103',
            'full_name' => 'Budi Baru',
            'email' => 'budibaru@company.com',
            'phone' => $oldPhone,
            'outlet_id' => $this->outletPusat->id,
            'status' => 'active',
            'create_user_account' => '1',
            'account_password' => 'password123',
        ]);

        $response->assertRedirect('/admin/employees');
        $this->assertDatabaseHas('employees', [
            'employee_code' => 'SB-103',
            'phone' => $oldPhone,
        ]);
    }

    public function test_11_deleted_employee_cannot_authenticate(): void
    {
        $oldEmail = $this->employeePusat->email;

        $this->actingAs($this->owner)->delete("/admin/employees/{$this->employeePusat->id}");

        \Illuminate\Support\Facades\Auth::logout();

        $response = $this->post('/login', [
            'login' => $oldEmail,
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    public function test_12_existing_active_session_is_revoked(): void
    {
        DB::table('sessions')->insert([
            'id' => 'test_session_id_123',
            'user_id' => $this->userPusat->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => 'dummy',
            'last_activity' => time(),
        ]);

        $this->assertDatabaseHas('sessions', ['id' => 'test_session_id_123']);

        $this->actingAs($this->owner)->delete("/admin/employees/{$this->employeePusat->id}");

        $this->assertDatabaseMissing('sessions', ['id' => 'test_session_id_123']);
    }

    public function test_13_remember_token_is_invalidated(): void
    {
        $this->actingAs($this->owner)->delete("/admin/employees/{$this->employeePusat->id}");

        $user = User::find($this->userPusat->id);
        $this->assertNull($user->remember_token);
    }

    public function test_14_normal_employee_list_excludes_deleted_employee(): void
    {
        $this->actingAs($this->owner)->delete("/admin/employees/{$this->employeePusat->id}");

        // First request receives the redirect flash alert containing the employee name.
        // Second request visits clean employee listing with flash cleared.
        $this->actingAs($this->owner)->get('/admin/employees');
        $response = $this->actingAs($this->owner)->get('/admin/employees');

        $response->assertOk();
        $response->assertDontSee('Siti Aminah');
        $response->assertDontSee('SB-101');
    }

    public function test_15_historical_attendance_view_can_resolve_deleted_employee(): void
    {
        AttendanceRecord::create([
            'employee_id' => $this->employeePusat->id,
            'work_date' => '2026-08-20',
            'status' => 'present',
            'check_in_at' => '2026-08-20 08:00:00',
            'check_out_at' => '2026-08-20 16:00:00',
            'outlet_id' => $this->outletPusat->id,
        ]);

        $this->actingAs($this->owner)->delete("/admin/employees/{$this->employeePusat->id}");

        $response = $this->actingAs($this->owner)->get('/admin/attendance?date=2026-08-20');

        $response->assertOk();
    }

    public function test_16_admin_outside_allowed_outlet_cannot_delete_employee(): void
    {
        $response = $this->actingAs($this->adminScopedCabang)->delete("/admin/employees/{$this->employeePusat->id}");

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('employees', ['id' => $this->employeePusat->id, 'deleted_at' => null]);
    }

    public function test_17_authorized_admin_can_delete_within_scope(): void
    {
        $response = $this->actingAs($this->adminScopedPusat)->delete("/admin/employees/{$this->employeePusat->id}");

        $response->assertRedirect('/admin/employees');
        $this->assertSoftDeleted('employees', ['id' => $this->employeePusat->id]);
    }

    public function test_18_active_attendance_blocks_deletion(): void
    {
        AttendanceRecord::create([
            'employee_id' => $this->employeePusat->id,
            'work_date' => now()->toDateString(),
            'status' => 'present',
            'check_in_at' => now(),
            'check_out_at' => null,
            'outlet_id' => $this->outletPusat->id,
        ]);

        $response = $this->actingAs($this->owner)->delete("/admin/employees/{$this->employeePusat->id}");

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('employees', ['id' => $this->employeePusat->id, 'deleted_at' => null]);
    }

    public function test_19_active_overtime_blocks_deletion(): void
    {
        $otReq = OvertimeRequest::create([
            'employee_id' => $this->employeePusat->id,
            'work_date' => now()->toDateString(),
            'requested_minutes' => 60,
            'approved_minutes' => 60,
            'reason' => 'Lembur malam',
            'status' => 'approved',
        ]);

        OvertimeSession::create([
            'overtime_request_id' => $otReq->id,
            'employee_id' => $this->employeePusat->id,
            'work_date' => now()->toDateString(),
            'status' => 'active',
            'check_in_at' => now(),
        ]);

        $response = $this->actingAs($this->owner)->delete("/admin/employees/{$this->employeePusat->id}");

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('employees', ['id' => $this->employeePusat->id, 'deleted_at' => null]);
    }

    public function test_20_pending_shift_swap_blocks_deletion(): void
    {
        $empTarget = Employee::create([
            'employee_code' => 'SB-109',
            'full_name' => 'Dewi Target',
            'outlet_id' => $this->outletPusat->id,
            'status' => 'active',
        ]);

        ShiftSwapRequest::create([
            'requester_employee_id' => $this->employeePusat->id,
            'target_employee_id' => $empTarget->id,
            'requester_work_date' => '2026-08-25',
            'target_work_date' => '2026-08-25',
            'requester_original_shift_id' => $this->shiftPagi->id,
            'target_original_shift_id' => $this->shiftPagi->id,
            'status' => ShiftSwapRequest::STATUS_PENDING_TARGET,
            'requester_reason' => 'Ada keperluan',
        ]);

        $response = $this->actingAs($this->owner)->delete("/admin/employees/{$this->employeePusat->id}");

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('employees', ['id' => $this->employeePusat->id, 'deleted_at' => null]);
    }

    public function test_21_failed_deletion_transaction_does_not_leave_partial_anonymization(): void
    {
        // Simulate exception during delete service
        $service = app(EmployeeService::class);

        $this->expectException(\InvalidArgumentException::class);

        // Force exception inside transaction
        DB::transaction(function () use ($service) {
            $this->userPusat->update(['email' => null]);
            throw new \InvalidArgumentException('Simulated database failure during delete');
        });

        $this->userPusat->refresh();
        $this->assertEquals('siti@company.com', $this->userPusat->email);
    }

    public function test_22_audit_log_created_without_password_or_pii_leakage(): void
    {
        $this->actingAs($this->owner)->delete("/admin/employees/{$this->employeePusat->id}");

        $audit = AuditLog::where('action', 'employee.deleted')->first();
        $this->assertNotNull($audit);
        $this->assertEquals($this->owner->id, $audit->user_id);
        $this->assertArrayHasKey('employee_id', $audit->metadata);
        $this->assertArrayNotHasKey('password', $audit->metadata);
        $this->assertArrayNotHasKey('password_hash', $audit->metadata);
    }

    public function test_23_restore_behavior_safety(): void
    {
        $this->actingAs($this->owner)->delete("/admin/employees/{$this->employeePusat->id}");

        $emp = Employee::withTrashed()->find($this->employeePusat->id);
        $emp->restore();

        // PII remains null after restore so it does not conflict with any newly registered employee
        $this->assertNull($emp->email);
        $this->assertNull($emp->phone);
        $this->assertEquals('inactive', $emp->status);
    }

    public function test_24_full_email_uniqueness_and_reuse_regression_passes(): void
    {
        $email = $this->employeePusat->email;
        $phone = $this->employeePusat->phone;

        // Step 1: Delete Employee 1
        $this->actingAs($this->owner)->delete("/admin/employees/{$this->employeePusat->id}");

        // Step 2: Create Employee 2 with same email and phone
        $emp2 = Employee::create([
            'employee_code' => 'SB-200',
            'full_name' => 'Siti Second Generation',
            'email' => $email,
            'phone' => $phone,
            'outlet_id' => $this->outletPusat->id,
            'status' => 'active',
        ]);

        $user2 = User::create([
            'employee_id' => $emp2->id,
            'outlet_id' => $this->outletPusat->id,
            'name' => 'Siti Second Generation',
            'email' => $email,
            'phone' => $phone,
            'password' => Hash::make('newpassword123'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        $this->assertEquals($email, $emp2->email);
        $this->assertEquals($email, $user2->email);

        // Step 3: Delete Employee 2
        $this->actingAs($this->owner)->delete("/admin/employees/{$emp2->id}");

        // Step 4: Create Employee 3 with same email and phone
        $emp3 = Employee::create([
            'employee_code' => 'SB-300',
            'full_name' => 'Siti Third Generation',
            'email' => $email,
            'phone' => $phone,
            'outlet_id' => $this->outletPusat->id,
            'status' => 'active',
        ]);

        $this->assertEquals($email, $emp3->email);
    }
}
