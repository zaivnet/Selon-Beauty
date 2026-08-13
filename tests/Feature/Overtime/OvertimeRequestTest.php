<?php

namespace Tests\Feature\Overtime;

use App\Models\AttendanceLocation;
use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\OvertimeRequest;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OvertimeRequestTest extends TestCase
{
    use RefreshDatabase;

    protected User $ownerUser;
    protected User $adminUser;
    protected User $employeeUser1;
    protected Employee $employee1;
    protected User $employeeUser2;
    protected Employee $employee2;
    protected Shift $shiftNormal;
    protected Shift $shiftCrossMidnight;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ownerUser = User::create([
            'name' => 'Owner Utama',
            'email' => 'owner@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->adminUser = User::create([
            'name' => 'Admin Toko',
            'email' => 'admin@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->employee1 = Employee::create([
            'employee_code' => 'SB-001',
            'full_name' => 'Ayu Pratama',
            'status' => 'active',
        ]);
        $this->employeeUser1 = User::create([
            'employee_id' => $this->employee1->id,
            'name' => 'Ayu Pratama',
            'email' => 'ayu@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        $this->employee2 = Employee::create([
            'employee_code' => 'SB-002',
            'full_name' => 'Budi Santoso',
            'status' => 'active',
        ]);
        $this->employeeUser2 = User::create([
            'employee_id' => $this->employee2->id,
            'name' => 'Budi Santoso',
            'email' => 'budi@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        $this->shiftNormal = Shift::create([
            'name' => 'Shift Pagi',
            'code' => 'PAGI',
            'start_time' => '08:00',
            'end_time' => '16:00',
            'is_active' => true,
        ]);

        $this->shiftCrossMidnight = Shift::create([
            'name' => 'Shift Malam',
            'code' => 'MALAM',
            'start_time' => '20:00',
            'end_time' => '06:00',
            'crosses_midnight' => true,
            'is_active' => true,
        ]);
    }

    public function test_employee_can_submit_overtime_request(): void
    {
        $workDate = '2026-08-12';
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $workDate,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        $response = $this->actingAs($this->employeeUser1)->post(route('employee.overtime-requests.store'), [
            'work_date' => $workDate,
            'requested_minutes' => 60,
            'reason' => 'Menyelesaikan pekerjaan setelah jam operasional.',
        ]);

        $response->assertRedirect(route('employee.overtime-requests.index'));
        $response->assertSessionHas('success');

        $req = OvertimeRequest::where('employee_id', $this->employee1->id)->first();
        $this->assertNotNull($req);
        $this->assertEquals($workDate, $req->work_date->format('Y-m-d'));
        $this->assertEquals(60, $req->requested_minutes);
        $this->assertNull($req->approved_minutes);
        $this->assertEquals('Menyelesaikan pekerjaan setelah jam operasional.', $req->reason);
        $this->assertEquals('pending', $req->status);
    }

    public function test_employee_cannot_submit_overtime_for_another_employee(): void
    {
        $workDate = '2026-08-12';
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $workDate,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);
        EmployeeSchedule::create([
            'employee_id' => $this->employee2->id,
            'work_date' => $workDate,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        // Employee 1 submits form (even if maliciously injecting employee_id parameter)
        $response = $this->actingAs($this->employeeUser1)->post(route('employee.overtime-requests.store'), [
            'employee_id' => $this->employee2->id,
            'work_date' => $workDate,
            'requested_minutes' => 60,
            'reason' => 'Pengajuan atas nama Budi.',
        ]);

        // Must create overtime request ONLY for employee 1 (from Auth context)
        $this->assertDatabaseHas('overtime_requests', [
            'employee_id' => $this->employee1->id,
        ]);

        $this->assertDatabaseMissing('overtime_requests', [
            'employee_id' => $this->employee2->id,
        ]);
    }

    public function test_employee_sees_only_own_overtime_requests(): void
    {
        OvertimeRequest::create([
            'employee_id' => $this->employee1->id,
            'work_date' => '2026-08-12',
            'requested_minutes' => 60,
            'reason' => 'Lembur Ayu',
            'status' => 'pending',
        ]);

        OvertimeRequest::create([
            'employee_id' => $this->employee2->id,
            'work_date' => '2026-08-12',
            'requested_minutes' => 90,
            'reason' => 'Lembur Budi',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->employeeUser1)->get(route('employee.overtime-requests.index'));

        $response->assertOk();
        $response->assertSee('Lembur Ayu');
        $response->assertDontSee('Lembur Budi');
    }

    public function test_employee_without_valid_work_schedule_cannot_request_overtime(): void
    {
        $offDate = '2026-08-12';
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $offDate,
            'schedule_type' => 'off',
        ]);

        $response = $this->actingAs($this->employeeUser1)->post(route('employee.overtime-requests.store'), [
            'work_date' => $offDate,
            'requested_minutes' => 60,
            'reason' => 'Mencoba lembur hari OFF.',
        ]);

        $response->assertSessionHasErrors(['work_date']);
        $this->assertDatabaseMissing('overtime_requests', [
            'employee_id' => $this->employee1->id,
        ]);
    }

    public function test_owner_sees_overtime_requests(): void
    {
        OvertimeRequest::create([
            'employee_id' => $this->employee1->id,
            'work_date' => '2026-08-12',
            'requested_minutes' => 60,
            'reason' => 'Lembur Ayu untuk Owner',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->ownerUser)->get(route('admin.overtime-requests.index'));

        $response->assertOk();
        $response->assertSee('Lembur Ayu untuk Owner');
        $response->assertSee('Ayu Pratama');
    }

    public function test_owner_can_approve_overtime(): void
    {
        $req = OvertimeRequest::create([
            'employee_id' => $this->employee1->id,
            'work_date' => '2026-08-12',
            'requested_minutes' => 60,
            'reason' => 'Pekerjaan akhir shift',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->ownerUser)->post(route('admin.overtime-requests.approve', $req->id), [
            'approved_minutes' => 60,
            'reviewer_note' => 'Disetujui penuh.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('overtime_requests', [
            'id' => $req->id,
            'status' => 'approved',
            'approved_minutes' => 60,
            'reviewed_by' => $this->ownerUser->id,
            'reviewer_note' => 'Disetujui penuh.',
        ]);
    }

    public function test_owner_can_approve_fewer_minutes_than_requested(): void
    {
        $req = OvertimeRequest::create([
            'employee_id' => $this->employee1->id,
            'work_date' => '2026-08-12',
            'requested_minutes' => 90,
            'reason' => 'Pengajuan 90 menit',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->ownerUser)->post(route('admin.overtime-requests.approve', $req->id), [
            'approved_minutes' => 60,
            'reviewer_note' => 'Disetujui 60 menit saja.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('overtime_requests', [
            'id' => $req->id,
            'status' => 'approved',
            'requested_minutes' => 90,
            'approved_minutes' => 60,
        ]);
    }

    public function test_owner_can_reject_overtime(): void
    {
        $req = OvertimeRequest::create([
            'employee_id' => $this->employee1->id,
            'work_date' => '2026-08-12',
            'requested_minutes' => 60,
            'reason' => 'Pengajuan Ditolak',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->ownerUser)->post(route('admin.overtime-requests.reject', $req->id), [
            'reviewer_note' => 'Tidak ada instruksi lembur.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('overtime_requests', [
            'id' => $req->id,
            'status' => 'rejected',
            'reviewed_by' => $this->ownerUser->id,
            'reviewer_note' => 'Tidak ada instruksi lembur.',
        ]);
    }

    public function test_employee_cannot_approve_own_overtime(): void
    {
        $req = OvertimeRequest::create([
            'employee_id' => $this->employee1->id,
            'work_date' => '2026-08-12',
            'requested_minutes' => 60,
            'reason' => 'Self approve test',
            'status' => 'pending',
        ]);

        // Employee role attempting admin route -> redirected by EnsureUserRole middleware
        $response = $this->actingAs($this->employeeUser1)->post(route('admin.overtime-requests.approve', $req->id), [
            'approved_minutes' => 60,
        ]);

        $response->assertRedirect(route('employee.dashboard'));

        // Owner who is also an employee attempting to approve own request
        $ownerEmp = Employee::create([
            'employee_code' => 'SB-000',
            'full_name' => 'Owner Employee',
            'status' => 'active',
        ]);
        $this->ownerUser->update(['employee_id' => $ownerEmp->id]);

        $ownerReq = OvertimeRequest::create([
            'employee_id' => $ownerEmp->id,
            'work_date' => '2026-08-12',
            'requested_minutes' => 60,
            'reason' => 'Owner overtime request',
            'status' => 'pending',
        ]);

        $respOwner = $this->actingAs($this->ownerUser)->post(route('admin.overtime-requests.approve', $ownerReq->id), [
            'approved_minutes' => 60,
        ]);
        $respOwner->assertSessionHasErrors(['reviewer']);
    }

    public function test_pending_overtime_can_be_cancelled(): void
    {
        $req = OvertimeRequest::create([
            'employee_id' => $this->employee1->id,
            'work_date' => '2026-08-12',
            'requested_minutes' => 60,
            'reason' => 'Dibatalkan sendiri',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->employeeUser1)->post(route('employee.overtime-requests.cancel', $req->id));

        $response->assertRedirect(route('employee.overtime-requests.index'));
        $this->assertDatabaseHas('overtime_requests', [
            'id' => $req->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_approved_overtime_cannot_be_modified_by_employee(): void
    {
        $req = OvertimeRequest::create([
            'employee_id' => $this->employee1->id,
            'work_date' => '2026-08-12',
            'requested_minutes' => 60,
            'approved_minutes' => 60,
            'reason' => 'Sudah disetujui',
            'status' => 'approved',
            'reviewed_by' => $this->ownerUser->id,
        ]);

        $response = $this->actingAs($this->employeeUser1)->post(route('employee.overtime-requests.cancel', $req->id));

        $response->assertSessionHasErrors(['status']);
        $this->assertDatabaseHas('overtime_requests', [
            'id' => $req->id,
            'status' => 'approved',
        ]);
    }

    public function test_invalid_requested_minutes_rejected(): void
    {
        $workDate = '2026-08-12';
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $workDate,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        $response = $this->actingAs($this->employeeUser1)->post(route('employee.overtime-requests.store'), [
            'work_date' => $workDate,
            'requested_minutes' => 0,
            'reason' => 'Lembur 0 menit',
        ]);

        $response->assertSessionHasErrors(['requested_minutes']);
        $this->assertDatabaseMissing('overtime_requests', [
            'employee_id' => $this->employee1->id,
        ]);
    }

    public function test_approved_minutes_validation_works(): void
    {
        $req = OvertimeRequest::create([
            'employee_id' => $this->employee1->id,
            'work_date' => '2026-08-12',
            'requested_minutes' => 60,
            'reason' => 'Validation approved minutes',
            'status' => 'pending',
        ]);

        // Attempting negative approved_minutes
        $responseNegative = $this->actingAs($this->ownerUser)->post(route('admin.overtime-requests.approve', $req->id), [
            'approved_minutes' => -10,
        ]);
        $responseNegative->assertSessionHasErrors(['approved_minutes']);

        // Attempting approved_minutes > requested_minutes
        $responseExceed = $this->actingAs($this->ownerUser)->post(route('admin.overtime-requests.approve', $req->id), [
            'approved_minutes' => 120,
        ]);
        $responseExceed->assertSessionHasErrors(['approved_minutes']);

        $this->assertDatabaseHas('overtime_requests', [
            'id' => $req->id,
            'status' => 'pending',
        ]);
    }

    public function test_cross_midnight_overtime_keeps_original_work_date(): void
    {
        $workDate = '2026-08-11';
        $sch = EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $workDate,
            'shift_id' => $this->shiftCrossMidnight->id,
            'schedule_type' => 'work',
        ]);

        // Attendance record checkout on next day morning (2026-08-12 07:00)
        AttendanceRecord::create([
            'employee_id' => $this->employee1->id,
            'work_schedule_id' => $sch->id,
            'work_date' => $workDate,
            'status' => 'present',
            'check_in_at' => '2026-08-11 19:55:00',
            'check_out_at' => '2026-08-12 07:00:00',
            'worked_minutes' => 665,
            'overtime_minutes' => 60,
        ]);

        $response = $this->actingAs($this->employeeUser1)->post(route('employee.overtime-requests.store'), [
            'work_date' => $workDate,
            'requested_minutes' => 60,
            'reason' => 'Lembur shift malam 1 jam',
        ]);

        $response->assertRedirect(route('employee.overtime-requests.index'));
        
        $req = OvertimeRequest::where('employee_id', $this->employee1->id)->first();
        $this->assertNotNull($req);
        $this->assertEquals($workDate, $req->work_date->format('Y-m-d')); // Original work_date 2026-08-11 preserved
        $this->assertEquals(60, $req->requested_minutes);
    }

    public function test_overtime_candidate_is_not_automatically_approved(): void
    {
        $workDate = '2026-08-12';
        $sch = EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $workDate,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        // Attendance record calculates overtime_minutes candidate = 75 mins
        AttendanceRecord::create([
            'employee_id' => $this->employee1->id,
            'work_schedule_id' => $sch->id,
            'work_date' => $workDate,
            'status' => 'present',
            'check_in_at' => '2026-08-12 07:58:00',
            'check_out_at' => '2026-08-12 17:15:00',
            'worked_minutes' => 557,
            'overtime_minutes' => 75,
        ]);

        // Employee requests 60 mins
        $req = OvertimeRequest::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $workDate,
            'requested_minutes' => 60,
            'reason' => 'Lembur 60 menit',
            'status' => 'pending',
        ]);

        // Owner approves 45 mins
        $this->actingAs($this->ownerUser)->post(route('admin.overtime-requests.approve', $req->id), [
            'approved_minutes' => 45,
            'reviewer_note' => 'Disetujui 45m',
        ]);

        // Approved overtime in DB must be 45 mins, NOT candidate 75 mins
        $this->assertDatabaseHas('overtime_requests', [
            'id' => $req->id,
            'status' => 'approved',
            'approved_minutes' => 45,
        ]);
    }

    public function test_audit_log_created_on_approve_reject(): void
    {
        $req1 = OvertimeRequest::create([
            'employee_id' => $this->employee1->id,
            'work_date' => '2026-08-12',
            'requested_minutes' => 60,
            'reason' => 'Audit log test 1',
            'status' => 'pending',
        ]);

        $this->actingAs($this->ownerUser)->post(route('admin.overtime-requests.approve', $req1->id), [
            'approved_minutes' => 60,
            'reviewer_note' => 'Audit approve',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->ownerUser->id,
            'action' => 'overtime.approved',
            'auditable_type' => OvertimeRequest::class,
            'auditable_id' => $req1->id,
        ]);

        $req2 = OvertimeRequest::create([
            'employee_id' => $this->employee2->id,
            'work_date' => '2026-08-12',
            'requested_minutes' => 60,
            'reason' => 'Audit log test 2',
            'status' => 'pending',
        ]);

        $this->actingAs($this->adminUser)->post(route('admin.overtime-requests.reject', $req2->id), [
            'reviewer_note' => 'Audit reject',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->adminUser->id,
            'action' => 'overtime.rejected',
            'auditable_type' => OvertimeRequest::class,
            'auditable_id' => $req2->id,
        ]);
    }
}
