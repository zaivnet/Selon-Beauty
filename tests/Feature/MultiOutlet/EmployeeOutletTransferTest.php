<?php

namespace Tests\Feature\MultiOutlet;

use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmployeeOutletTransfer;
use App\Models\JobTitle;
use App\Models\Outlet;
use App\Models\OvertimeSession;
use App\Models\Shift;
use App\Models\ShiftSwapRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeOutletTransferTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $superadmin;
    protected User $adminOutlet1;
    protected User $adminOutlet2;
    protected Outlet $outlet1;
    protected Outlet $outlet2;
    protected Outlet $inactiveOutlet;
    protected Employee $employee;
    protected JobTitle $jobTitle;
    protected Shift $shift;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outlet1 = Outlet::create([
            'name' => 'Selon Beauty Pusat',
            'code' => 'OUT-001',
            'latitude' => -6.175110,
            'longitude' => 106.827220,
            'radius_meters' => 100,
            'is_active' => true,
        ]);

        $this->outlet2 = Outlet::create([
            'name' => 'Kopi Selon Cabang 2',
            'code' => 'OUT-002',
            'latitude' => -6.200000,
            'longitude' => 106.810000,
            'radius_meters' => 150,
            'is_active' => true,
        ]);

        $this->inactiveOutlet = Outlet::create([
            'name' => 'Outlet Nonaktif',
            'code' => 'OUT-999',
            'latitude' => -6.300000,
            'longitude' => 106.850000,
            'radius_meters' => 50,
            'is_active' => false,
        ]);

        $this->jobTitle = JobTitle::create([
            'name' => 'Hairstylist',
            'is_active' => true,
        ]);

        $this->shift = Shift::create([
            'name' => 'Shift Pagi',
            'code' => 'PAGI',
            'start_time' => '08:00',
            'end_time' => '17:00',
            'is_active' => true,
        ]);

        $this->owner = User::create([
            'name' => 'Owner System',
            'email' => 'owner@selonbeauty.com',
            'password' => bcrypt('password'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->superadmin = User::create([
            'name' => 'Super Administrator',
            'email' => 'superadmin@selonbeauty.com',
            'password' => bcrypt('password'),
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $this->adminOutlet1 = User::create([
            'name' => 'Admin Outlet 1',
            'email' => 'admin1@selonbeauty.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'outlet_id' => $this->outlet1->id,
            'is_active' => true,
        ]);

        $this->adminOutlet2 = User::create([
            'name' => 'Admin Outlet 2',
            'email' => 'admin2@selonbeauty.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'outlet_id' => $this->outlet2->id,
            'is_active' => true,
        ]);

        $this->employee = Employee::create([
            'employee_code' => 'EMP-100',
            'full_name' => 'Dulhaq Transferred',
            'email' => 'dulhaq@selonbeauty.com',
            'status' => 'active',
            'outlet_id' => $this->outlet1->id,
            'job_title_id' => $this->jobTitle->id,
        ]);
    }

    public function test_owner_can_transfer_employee_outlet(): void
    {
        $response = $this->actingAs($this->owner)->post(route('admin.employees.transfer', $this->employee), [
            'destination_outlet_id' => $this->outlet2->id,
            'notes' => 'Rotasi cabang harian',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals($this->outlet2->id, $this->employee->fresh()->outlet_id);

        $this->assertDatabaseHas('employee_outlet_transfers', [
            'employee_id' => $this->employee->id,
            'from_outlet_id' => $this->outlet1->id,
            'to_outlet_id' => $this->outlet2->id,
            'transferred_by_user_id' => $this->owner->id,
            'notes' => 'Rotasi cabang harian',
        ]);
    }

    public function test_superadmin_can_transfer_employee_outlet(): void
    {
        $response = $this->actingAs($this->superadmin)->post(route('admin.employees.transfer', $this->employee), [
            'destination_outlet_id' => $this->outlet2->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals($this->outlet2->id, $this->employee->fresh()->outlet_id);
    }

    public function test_admin_cannot_transfer_employee_outlet_and_returns_403(): void
    {
        $response = $this->actingAs($this->adminOutlet1)->post(route('admin.employees.transfer', $this->employee), [
            'destination_outlet_id' => $this->outlet2->id,
        ]);

        $response->assertStatus(403);
        $this->assertEquals($this->outlet1->id, $this->employee->fresh()->outlet_id);
    }

    public function test_transfer_to_same_outlet_is_rejected(): void
    {
        $response = $this->actingAs($this->owner)->post(route('admin.employees.transfer', $this->employee), [
            'destination_outlet_id' => $this->outlet1->id,
        ]);

        $response->assertSessionHasErrors(['destination_outlet_id']);
        $this->assertEquals($this->outlet1->id, $this->employee->fresh()->outlet_id);
    }

    public function test_transfer_to_inactive_outlet_is_rejected(): void
    {
        $response = $this->actingAs($this->owner)->post(route('admin.employees.transfer', $this->employee), [
            'destination_outlet_id' => $this->inactiveOutlet->id,
        ]);

        $response->assertSessionHasErrors(['destination_outlet_id']);
        $this->assertEquals($this->outlet1->id, $this->employee->fresh()->outlet_id);
    }

    public function test_historical_attendance_record_outlet_id_remains_unchanged_after_transfer(): void
    {
        $yesterday = Carbon::yesterday(config('app.timezone'))->toDateString();

        $historicalAttendance = AttendanceRecord::create([
            'employee_id' => $this->employee->id,
            'outlet_id' => $this->outlet1->id,
            'work_date' => $yesterday,
            'check_in_at' => Carbon::yesterday(config('app.timezone'))->setHour(8),
            'check_out_at' => Carbon::yesterday(config('app.timezone'))->setHour(17),
            'status' => 'present',
        ]);

        // Transfer employee to Outlet 2
        $this->actingAs($this->owner)->post(route('admin.employees.transfer', $this->employee), [
            'destination_outlet_id' => $this->outlet2->id,
        ]);

        $this->assertEquals($this->outlet2->id, $this->employee->fresh()->outlet_id);

        // Historical record must strictly retain Outlet 1
        $this->assertEquals($this->outlet1->id, $historicalAttendance->fresh()->outlet_id);
    }

    public function test_old_admin_loses_access_and_new_admin_gains_access_post_transfer(): void
    {
        // Before transfer: Admin 1 has access to employee show
        $respBefore1 = $this->actingAs($this->adminOutlet1)->get(route('admin.employees.show', $this->employee));
        $respBefore1->assertOk();

        // Admin 2 gets 403 before transfer
        $respBefore2 = $this->actingAs($this->adminOutlet2)->get(route('admin.employees.show', $this->employee));
        $respBefore2->assertStatus(403);

        // Transfer employee to Outlet 2
        $this->actingAs($this->owner)->post(route('admin.employees.transfer', $this->employee), [
            'destination_outlet_id' => $this->outlet2->id,
        ]);

        // Post transfer: Admin 1 gets 403, Admin 2 gets 200 OK
        $respAfter1 = $this->actingAs($this->adminOutlet1)->get(route('admin.employees.show', $this->employee));
        $respAfter1->assertStatus(403);

        $respAfter2 = $this->actingAs($this->adminOutlet2)->get(route('admin.employees.show', $this->employee));
        $respAfter2->assertOk();
    }

    public function test_owner_outlet_filter_reflects_employee_new_outlet_post_transfer(): void
    {
        $targetOutletId = $this->outlet2->id;
        $sourceOutletId = $this->outlet1->id;

        // Transfer to Outlet 2
        $this->actingAs($this->owner)->post(route('admin.employees.transfer', $this->employee), [
            'destination_outlet_id' => $targetOutletId,
        ]);

        $this->employee->refresh();
        $this->assertEquals($targetOutletId, $this->employee->outlet_id);

        // Flush session flash messages so success message containing full_name does not pollute HTML assertDontSee
        session()->forget(['_flash', 'flash']);

        // Filter Outlet 1 -> Employee code/name not in table
        $respOut1 = $this->actingAs($this->owner)->get(route('admin.employees.index').'?outlet_id='.$sourceOutletId);
        $respOut1->assertOk();
        $respOut1->assertDontSee($this->employee->employee_code);

        // Filter Outlet 2 -> Employee code/name visible
        $respOut2 = $this->actingAs($this->owner)->get(route('admin.employees.index').'?outlet_id='.$targetOutletId);
        $respOut2->assertOk();
        $respOut2->assertSee($this->employee->employee_code);
    }

    public function test_active_attendance_check_in_blocks_transfer(): void
    {
        $today = Carbon::now(config('app.timezone'))->toDateString();

        // Create open attendance (checked in, not checked out)
        AttendanceRecord::create([
            'employee_id' => $this->employee->id,
            'outlet_id' => $this->outlet1->id,
            'work_date' => $today,
            'check_in_at' => Carbon::now(config('app.timezone'))->subHours(1),
            'status' => 'present',
        ]);

        $response = $this->actingAs($this->owner)->post(route('admin.employees.transfer', $this->employee), [
            'destination_outlet_id' => $this->outlet2->id,
        ]);

        $response->assertSessionHasErrors(['employee_id']);
        $this->assertEquals($this->outlet1->id, $this->employee->fresh()->outlet_id);
    }

    public function test_active_overtime_session_blocks_transfer(): void
    {
        $ovtReq = \App\Models\OvertimeRequest::create([
            'employee_id' => $this->employee->id,
            'work_date' => Carbon::now(config('app.timezone'))->toDateString(),
            'requested_minutes' => 60,
            'reason' => 'Lembur operasional',
            'status' => 'approved',
        ]);

        OvertimeSession::create([
            'overtime_request_id' => $ovtReq->id,
            'employee_id' => $this->employee->id,
            'work_date' => Carbon::now(config('app.timezone'))->toDateString(),
            'status' => 'in_progress',
            'check_in_at' => Carbon::now(config('app.timezone'))->subMinutes(30),
        ]);

        $response = $this->actingAs($this->owner)->post(route('admin.employees.transfer', $this->employee), [
            'destination_outlet_id' => $this->outlet2->id,
        ]);

        $response->assertSessionHasErrors(['employee_id']);
        $this->assertEquals($this->outlet1->id, $this->employee->fresh()->outlet_id);
    }

    public function test_pending_shift_swap_request_blocks_transfer(): void
    {
        $emp2 = Employee::create([
            'employee_code' => 'EMP-200',
            'full_name' => 'Partner Employee',
            'status' => 'active',
            'outlet_id' => $this->outlet1->id,
            'job_title_id' => $this->jobTitle->id,
        ]);

        ShiftSwapRequest::create([
            'requester_employee_id' => $this->employee->id,
            'target_employee_id' => $emp2->id,
            'requester_work_date' => now()->addDay()->toDateString(),
            'target_work_date' => now()->addDay()->toDateString(),
            'requester_shift_id' => $this->shift->id,
            'target_shift_id' => $this->shift->id,
            'requester_original_shift_id' => $this->shift->id,
            'target_original_shift_id' => $this->shift->id,
            'status' => ShiftSwapRequest::STATUS_PENDING_TARGET,
        ]);

        $response = $this->actingAs($this->owner)->post(route('admin.employees.transfer', $this->employee), [
            'destination_outlet_id' => $this->outlet2->id,
        ]);

        $response->assertSessionHasErrors(['employee_id']);
        $this->assertEquals($this->outlet1->id, $this->employee->fresh()->outlet_id);
    }

    public function test_audit_log_recorded_on_transfer(): void
    {
        $this->actingAs($this->owner)->post(route('admin.employees.transfer', $this->employee), [
            'destination_outlet_id' => $this->outlet2->id,
            'notes' => 'Audit log verify',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->owner->id,
            'action' => 'employee.outlet.transferred',
        ]);
    }
}
