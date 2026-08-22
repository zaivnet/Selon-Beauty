<?php

namespace Tests\Feature\Leave;

use App\Models\AttendanceLocation;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\LeaveRequest;
use App\Models\Shift;
use App\Models\User;
use App\Services\AttendanceMonitoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LeaveRequestTest extends TestCase
{
    use RefreshDatabase;

    protected User $ownerUser;

    protected User $adminUser;

    protected User $employeeUser1;

    protected Employee $employee1;

    protected User $employeeUser2;

    protected Employee $employee2;

    protected Shift $shiftNormal;

    protected AttendanceLocation $location;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

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
        $this->adminUser->assignedOutlets()->sync([$this->adminUser->outlet_id]);

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

        $this->location = AttendanceLocation::create([
            'name' => 'SELON BEAUTY',
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'radius_meters' => 100,
            'max_accuracy_meters' => 100,
            'is_active' => true,
        ]);

        $this->shiftNormal = Shift::create([
            'name' => 'Shift Pagi',
            'code' => 'PAGI',
            'start_time' => '08:00',
            'end_time' => '16:00',
            'is_active' => true,
        ]);
    }

    public function test_employee_can_submit_permission(): void
    {
        $today = '2026-08-15';

        $response = $this->actingAs($this->employeeUser1)->post('/app/leave-requests', [
            'type' => 'permission',
            'start_date' => $today,
            'end_date' => $today,
            'reason' => 'Ada urusan keluarga mendesak',
        ]);

        $response->assertRedirect('/app/leave-requests');
        $req = LeaveRequest::where('employee_id', $this->employee1->id)->where('type', 'permission')->first();
        $this->assertNotNull($req);
        $this->assertEquals('pending', $req->status);
        $this->assertEquals($today, $req->start_date->format('Y-m-d'));
    }

    public function test_employee_can_submit_sick_request(): void
    {
        $today = '2026-08-15';
        $file = UploadedFile::fake()->image('surat_dokter.jpg');

        $response = $this->actingAs($this->employeeUser1)->post('/app/leave-requests', [
            'type' => 'sick',
            'start_date' => $today,
            'end_date' => $today,
            'reason' => 'Demam tinggi dan flu',
            'attachment' => $file,
        ]);

        $response->assertRedirect('/app/leave-requests');

        $req = LeaveRequest::where('employee_id', $this->employee1->id)->where('type', 'sick')->first();
        $this->assertNotNull($req);
        $this->assertEquals('sick', $req->type);
        $this->assertNotNull($req->attachment_path);
        Storage::disk('local')->assertExists($req->attachment_path);
    }

    public function test_employee_can_submit_leave_request(): void
    {
        $startDate = '2026-08-15';
        $endDate = '2026-08-17';

        $response = $this->actingAs($this->employeeUser1)->post('/app/leave-requests', [
            'type' => 'leave',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => 'Cuti tahunan acara keluarga',
        ]);

        $response->assertRedirect('/app/leave-requests');
        $req = LeaveRequest::where('employee_id', $this->employee1->id)->where('type', 'leave')->first();
        $this->assertNotNull($req);
        $this->assertEquals('pending', $req->status);
        $this->assertEquals($startDate, $req->start_date->format('Y-m-d'));
        $this->assertEquals($endDate, $req->end_date->format('Y-m-d'));
    }

    public function test_employee_cannot_submit_request_for_another_employee(): void
    {
        // Employee 1 submitting form cannot override employee_id to Employee 2
        $response = $this->actingAs($this->employeeUser1)->post('/app/leave-requests', [
            'employee_id' => $this->employee2->id,
            'type' => 'permission',
            'start_date' => '2026-08-15',
            'end_date' => '2026-08-15',
            'reason' => 'Mencoba submit akun lain',
        ]);

        $response->assertRedirect('/app/leave-requests');

        // Created request MUST belong to Employee 1
        $this->assertDatabaseHas('leave_requests', [
            'employee_id' => $this->employee1->id,
            'reason' => 'Mencoba submit akun lain',
        ]);
    }

    public function test_employee_sees_only_own_requests(): void
    {
        LeaveRequest::create([
            'employee_id' => $this->employee1->id,
            'type' => 'permission',
            'start_date' => '2026-08-15',
            'end_date' => '2026-08-15',
            'reason' => 'Izin Ayu',
            'status' => 'pending',
        ]);

        LeaveRequest::create([
            'employee_id' => $this->employee2->id,
            'type' => 'sick',
            'start_date' => '2026-08-15',
            'end_date' => '2026-08-15',
            'reason' => 'Sakit Budi',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->employeeUser1)->get('/app/leave-requests');

        $response->assertOk();
        $response->assertSee('Izin Ayu');
        $response->assertDontSee('Sakit Budi');
    }

    public function test_owner_sees_all_requests(): void
    {
        LeaveRequest::create([
            'employee_id' => $this->employee1->id,
            'type' => 'permission',
            'start_date' => '2026-08-15',
            'end_date' => '2026-08-15',
            'reason' => 'Izin Ayu',
            'status' => 'pending',
        ]);

        LeaveRequest::create([
            'employee_id' => $this->employee2->id,
            'type' => 'sick',
            'start_date' => '2026-08-15',
            'end_date' => '2026-08-15',
            'reason' => 'Sakit Budi',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->ownerUser)->get('/admin/leave-requests?status=all');

        $response->assertOk();
        $response->assertSee('Ayu Pratama');
        $response->assertSee('Budi Santoso');
    }

    public function test_owner_can_approve_request(): void
    {
        $req = LeaveRequest::create([
            'employee_id' => $this->employee1->id,
            'type' => 'permission',
            'start_date' => '2026-08-15',
            'end_date' => '2026-08-15',
            'reason' => 'Izin keperluan',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->ownerUser)->post("/admin/leave-requests/{$req->id}/approve", [
            'reviewer_note' => 'Disetujui silakan.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('leave_requests', [
            'id' => $req->id,
            'status' => 'approved',
            'reviewed_by' => $this->ownerUser->id,
            'reviewer_note' => 'Disetujui silakan.',
        ]);
    }

    public function test_owner_can_reject_request(): void
    {
        $req = LeaveRequest::create([
            'employee_id' => $this->employee1->id,
            'type' => 'leave',
            'start_date' => '2026-08-15',
            'end_date' => '2026-08-15',
            'reason' => 'Cuti dadakan',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->ownerUser)->post("/admin/leave-requests/{$req->id}/reject", [
            'reviewer_note' => 'Jadwal toko sedang padat.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('leave_requests', [
            'id' => $req->id,
            'status' => 'rejected',
            'reviewed_by' => $this->ownerUser->id,
            'reviewer_note' => 'Jadwal toko sedang padat.',
        ]);
    }

    public function test_employee_cannot_approve_own_request(): void
    {
        $req = LeaveRequest::create([
            'employee_id' => $this->employee1->id,
            'type' => 'permission',
            'start_date' => '2026-08-15',
            'end_date' => '2026-08-15',
            'reason' => 'Izin',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->employeeUser1)->post("/admin/leave-requests/{$req->id}/approve");
        $response->assertRedirect(route('employee.dashboard'));

        $jsonResponse = $this->actingAs($this->employeeUser1)->post("/admin/leave-requests/{$req->id}/approve", [], [
            'Accept' => 'application/json',
        ]);
        $jsonResponse->assertStatus(403);

        $this->assertDatabaseHas('leave_requests', [
            'id' => $req->id,
            'status' => 'pending',
        ]);
    }

    public function test_pending_request_can_be_cancelled_by_employee(): void
    {
        $req = LeaveRequest::create([
            'employee_id' => $this->employee1->id,
            'type' => 'permission',
            'start_date' => '2026-08-15',
            'end_date' => '2026-08-15',
            'reason' => 'Izin batal',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->employeeUser1)->post("/app/leave-requests/{$req->id}/cancel");

        $response->assertRedirect('/app/leave-requests');
        $this->assertDatabaseHas('leave_requests', [
            'id' => $req->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_approved_request_cannot_be_changed_by_employee(): void
    {
        $req = LeaveRequest::create([
            'employee_id' => $this->employee1->id,
            'type' => 'permission',
            'start_date' => '2026-08-15',
            'end_date' => '2026-08-15',
            'reason' => 'Izin',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->employeeUser1)->post("/app/leave-requests/{$req->id}/cancel");

        $response->assertSessionHasErrors();
        $this->assertDatabaseHas('leave_requests', [
            'id' => $req->id,
            'status' => 'approved',
        ]);
    }

    public function test_overlapping_request_rejected(): void
    {
        LeaveRequest::create([
            'employee_id' => $this->employee1->id,
            'type' => 'leave',
            'start_date' => '2026-08-12',
            'end_date' => '2026-08-14',
            'reason' => 'Cuti awal',
            'status' => 'approved',
        ]);

        // Overlapping request: 13-15 Aug
        $response = $this->actingAs($this->employeeUser1)->post('/app/leave-requests', [
            'type' => 'leave',
            'start_date' => '2026-08-13',
            'end_date' => '2026-08-15',
            'reason' => 'Cuti tumpang tindih',
        ]);

        $response->assertSessionHasErrors(['start_date']);
    }

    public function test_invalid_date_range_rejected(): void
    {
        // end_date earlier than start_date
        $response = $this->actingAs($this->employeeUser1)->post('/app/leave-requests', [
            'type' => 'permission',
            'start_date' => '2026-08-15',
            'end_date' => '2026-08-12',
            'reason' => 'Tanggal terbalik',
        ]);

        $response->assertSessionHasErrors(['end_date']);
    }

    public function test_private_attachment_protected(): void
    {
        $file = UploadedFile::fake()->image('bukti.jpg');
        $path = Storage::disk('local')->putFile('leave-attachments/1/2026/08', $file);

        $req = LeaveRequest::create([
            'employee_id' => $this->employee1->id,
            'type' => 'sick',
            'start_date' => '2026-08-15',
            'end_date' => '2026-08-15',
            'reason' => 'Sakit',
            'attachment_path' => $path,
            'status' => 'pending',
        ]);

        // Unauthenticated access
        $this->get("/leave-requests/attachment/{$req->id}")->assertRedirect('/login');

        // Owner access -> 200
        $this->actingAs($this->ownerUser)->get("/leave-requests/attachment/{$req->id}")->assertOk();

        // Employee 1 access -> 200
        $this->actingAs($this->employeeUser1)->get("/leave-requests/attachment/{$req->id}")->assertOk();
    }

    public function test_employee_cannot_access_another_employee_attachment(): void
    {
        $file = UploadedFile::fake()->image('bukti.jpg');
        $path = Storage::disk('local')->putFile('leave-attachments/1/2026/08', $file);

        $req = LeaveRequest::create([
            'employee_id' => $this->employee1->id,
            'type' => 'sick',
            'start_date' => '2026-08-15',
            'end_date' => '2026-08-15',
            'reason' => 'Sakit Ayu',
            'attachment_path' => $path,
            'status' => 'pending',
        ]);

        // Employee 2 attempting to view Employee 1's attachment -> 403 Forbidden
        $response = $this->actingAs($this->employeeUser2)->get("/leave-requests/attachment/{$req->id}");
        $response->assertStatus(403);
    }

    public function test_superadmin_can_access_private_leave_attachment(): void
    {
        $superadmin = User::create([
            'name' => 'Superadmin', 'email' => 'superadmin@example.test',
            'password' => Hash::make('password123'), 'role' => 'superadmin', 'is_active' => true,
        ]);
        $path = Storage::disk('local')->putFile(
            'leave-attachments/1/2026/08',
            UploadedFile::fake()->image('bukti-superadmin.jpg')
        );
        $request = LeaveRequest::create([
            'employee_id' => $this->employee1->id, 'type' => 'sick',
            'start_date' => '2026-08-15', 'end_date' => '2026-08-15',
            'reason' => 'Sakit', 'attachment_path' => $path, 'status' => 'approved',
        ]);

        $this->actingAs($superadmin)->get("/leave-requests/attachment/{$request->id}")->assertOk();
    }

    public function test_approved_permission_reflected_in_attendance_dashboard(): void
    {
        $today = '2026-08-15';

        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        LeaveRequest::create([
            'employee_id' => $this->employee1->id,
            'type' => 'permission',
            'start_date' => $today,
            'end_date' => $today,
            'reason' => 'Izin disetujui',
            'status' => 'approved',
        ]);

        $service = app(AttendanceMonitoringService::class);
        $items = $service->getAttendanceMonitoringList(['date' => $today]);

        $empItem = collect($items)->firstWhere('employee.id', $this->employee1->id);
        $this->assertNotNull($empItem);
        $this->assertEquals('IZIN', $empItem['status_label']);
    }

    public function test_approved_sick_reflected_in_attendance_dashboard(): void
    {
        $today = '2026-08-15';

        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        LeaveRequest::create([
            'employee_id' => $this->employee1->id,
            'type' => 'sick',
            'start_date' => $today,
            'end_date' => $today,
            'reason' => 'Sakit disetujui',
            'status' => 'approved',
        ]);

        $service = app(AttendanceMonitoringService::class);
        $items = $service->getAttendanceMonitoringList(['date' => $today]);

        $empItem = collect($items)->firstWhere('employee.id', $this->employee1->id);
        $this->assertNotNull($empItem);
        $this->assertEquals('SAKIT', $empItem['status_label']);
    }

    public function test_approved_leave_reflected_in_attendance_dashboard(): void
    {
        $today = '2026-08-15';

        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        LeaveRequest::create([
            'employee_id' => $this->employee1->id,
            'type' => 'leave',
            'start_date' => $today,
            'end_date' => $today,
            'reason' => 'Cuti disetujui',
            'status' => 'approved',
        ]);

        $service = app(AttendanceMonitoringService::class);
        $items = $service->getAttendanceMonitoringList(['date' => $today]);

        $empItem = collect($items)->firstWhere('employee.id', $this->employee1->id);
        $this->assertNotNull($empItem);
        $this->assertEquals('CUTI', $empItem['status_label']);
    }

    public function test_approved_leave_employee_not_counted_as_not_checked_in(): void
    {
        $today = '2026-08-15';

        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        LeaveRequest::create([
            'employee_id' => $this->employee1->id,
            'type' => 'permission',
            'start_date' => $today,
            'end_date' => $today,
            'reason' => 'Izin disetujui',
            'status' => 'approved',
        ]);

        $service = app(AttendanceMonitoringService::class);
        $metrics = $service->getSummaryMetrics($today);

        $this->assertEquals(0, $metrics['pending_check_in_today']);
        $this->assertEquals(1, $metrics['leave_today']);
    }

    public function test_off_employee_does_not_consume_attendance_leave_status_incorrectly(): void
    {
        $today = '2026-08-15';

        // Employee 1 is OFF
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => null,
            'schedule_type' => 'off',
        ]);

        LeaveRequest::create([
            'employee_id' => $this->employee1->id,
            'type' => 'leave',
            'start_date' => $today,
            'end_date' => $today,
            'reason' => 'Cuti di hari OFF',
            'status' => 'approved',
        ]);

        $service = app(AttendanceMonitoringService::class);
        $items = $service->getAttendanceMonitoringList(['date' => $today]);

        $empItem = collect($items)->firstWhere('employee.id', $this->employee1->id);
        $this->assertNotNull($empItem);
        // Scheduled OFF takes priority over leave status display on OFF day
        $this->assertEquals('OFF', $empItem['status_label']);
    }

    public function test_multi_day_leave_resolved_correctly(): void
    {
        $startDate = '2026-08-12';
        $midDate = '2026-08-13';
        $endDate = '2026-08-14';

        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $midDate,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        LeaveRequest::create([
            'employee_id' => $this->employee1->id,
            'type' => 'permission',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => 'Izin 3 hari',
            'status' => 'approved',
        ]);

        $service = app(AttendanceMonitoringService::class);
        $items = $service->getAttendanceMonitoringList(['date' => $midDate]);

        $empItem = collect($items)->firstWhere('employee.id', $this->employee1->id);
        $this->assertNotNull($empItem);
        $this->assertEquals('IZIN', $empItem['status_label']);
    }
}
