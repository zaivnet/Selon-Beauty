<?php

namespace Tests\Feature\MultiOutlet;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\LeaveRequest;
use App\Models\Outlet;
use App\Models\OvertimeRequest;
use App\Models\OvertimeSession;
use App\Models\Shift;
use App\Models\ShiftSwapRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMultiOutletAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected Outlet $outletA;
    protected Outlet $outletB;
    protected User $adminA;
    protected User $adminB;
    protected User $owner;
    protected User $superadmin;
    protected User $adminNoOutlet;
    protected Employee $employeeA;
    protected Employee $employeeB;
    protected Shift $shift;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outletA = Outlet::create([
            'name' => 'Outlet Selon A',
            'code' => 'OUTA',
            'latitude' => -7.1627701,
            'longitude' => 113.4843582,
            'radius_meters' => 50,
            'is_active' => true,
        ]);

        $this->outletB = Outlet::create([
            'name' => 'Outlet Selon B',
            'code' => 'OUTB',
            'latitude' => -7.1569777,
            'longitude' => 113.4895155,
            'radius_meters' => 50,
            'is_active' => true,
        ]);

        $this->adminA = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'outlet_id' => $this->outletA->id,
        ]);

        $this->adminB = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'outlet_id' => $this->outletB->id,
        ]);

        $this->owner = User::factory()->create([
            'role' => 'owner',
            'is_active' => true,
            'outlet_id' => null,
        ]);

        $this->superadmin = User::factory()->create([
            'role' => 'superadmin',
            'is_active' => true,
            'outlet_id' => null,
        ]);

        $this->adminNoOutlet = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'outlet_id' => null,
        ]);

        $this->employeeA = Employee::create([
            'employee_code' => 'EMP-A',
            'full_name' => 'Karyawan Outlet A',
            'email' => 'employee.a@selon.test',
            'status' => 'active',
            'outlet_id' => $this->outletA->id,
        ]);

        $this->employeeB = Employee::create([
            'employee_code' => 'EMP-B',
            'full_name' => 'Karyawan Outlet B',
            'email' => 'employee.b@selon.test',
            'status' => 'active',
            'outlet_id' => $this->outletB->id,
        ]);

        $this->shift = Shift::create([
            'name' => 'Shift Pagi',
            'code' => 'SP',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'is_active' => true,
        ]);
        $userA = User::factory()->create(['role' => 'employee', 'is_active' => true, 'employee_id' => $this->employeeA->id]);
        $this->employeeA->update(['user_id' => $userA->id]);

        $userB = User::factory()->create(['role' => 'employee', 'is_active' => true, 'employee_id' => $this->employeeB->id]);
        $this->employeeB->update(['user_id' => $userB->id]);
    }

    /* -------------------------------------------------------------------------- */
    /* 1. ATTENDANCE AUTHORIZATION TESTS                                         */
    /* -------------------------------------------------------------------------- */

    public function test_admin_a_can_view_and_correct_attendance_a_but_forbidden_on_attendance_b(): void
    {
        $attendanceA = AttendanceRecord::create([
            'employee_id' => $this->employeeA->id,
            'outlet_id' => $this->outletA->id,
            'work_date' => '2026-08-17',
            'status' => 'present',
            'check_in_at' => '2026-08-17 08:00:00',
        ]);

        $attendanceB = AttendanceRecord::create([
            'employee_id' => $this->employeeB->id,
            'outlet_id' => $this->outletB->id,
            'work_date' => '2026-08-17',
            'status' => 'present',
            'check_in_at' => '2026-08-17 08:00:00',
        ]);

        // Admin A can view Attendance A
        $response = $this->actingAs($this->adminA)->getJson(route('admin.attendance.show', $attendanceA));
        $response->assertStatus(200);

        // Admin A CANNOT view Attendance B -> 403
        $response = $this->actingAs($this->adminA)->getJson(route('admin.attendance.show', $attendanceB));
        $response->assertStatus(403);

        // Admin A can correct Attendance A
        $response = $this->actingAs($this->adminA)->post(route('admin.attendance.correct', $attendanceA), [
            'reason' => 'Koreksi jam masuk',
            'check_in_at' => '2026-08-17T08:05',
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertEquals('2026-08-17 08:05:00', $attendanceA->fresh()->check_in_at->format('Y-m-d H:i:s'));

        // Admin A CANNOT correct Attendance B -> 403 & DB Unchanged
        $originalCheckIn = $attendanceB->check_in_at->format('Y-m-d H:i:s');
        $response = $this->actingAs($this->adminA)->post(route('admin.attendance.correct', $attendanceB), [
            'reason' => 'Koreksi ilegal',
            'check_in_at' => '2026-08-17T09:00',
        ]);
        $response->assertStatus(403);
        $this->assertEquals($originalCheckIn, $attendanceB->fresh()->check_in_at->format('Y-m-d H:i:s'));

        // Owner & Superadmin can view and correct both
        $this->actingAs($this->owner)->getJson(route('admin.attendance.show', $attendanceB))->assertStatus(200);
        $this->actingAs($this->superadmin)->getJson(route('admin.attendance.show', $attendanceB))->assertStatus(200);

        // Admin without outlet fails closed
        $this->actingAs($this->adminNoOutlet)->getJson(route('admin.attendance.show', $attendanceA))->assertStatus(403);
    }

    /* -------------------------------------------------------------------------- */
    /* 2. LEAVE AUTHORIZATION TESTS                                               */
    /* -------------------------------------------------------------------------- */

    public function test_admin_a_cannot_approve_or_reject_leave_request_b(): void
    {
        $leaveB = LeaveRequest::create([
            'employee_id' => $this->employeeB->id,
            'type' => 'permission',
            'start_date' => '2026-08-20',
            'end_date' => '2026-08-20',
            'reason' => 'Urusan keluarga',
            'status' => 'pending',
        ]);

        // Admin A attempt approve Leave B -> 403 & Status remains pending
        $response = $this->actingAs($this->adminA)->post(route('admin.leave-requests.approve', $leaveB));
        $response->assertStatus(403);
        $this->assertEquals('pending', $leaveB->fresh()->status);

        // Admin A attempt reject Leave B -> 403 & Status remains pending
        $response = $this->actingAs($this->adminA)->post(route('admin.leave-requests.reject', $leaveB), [
            'reviewer_note' => 'Penolakan unauthorized',
        ]);
        $response->assertStatus(403);
        $this->assertEquals('pending', $leaveB->fresh()->status);

        // Admin B can approve Leave B
        $response = $this->actingAs($this->adminB)->post(route('admin.leave-requests.approve', $leaveB));
        $response->assertRedirect();
        $this->assertEquals('approved', $leaveB->fresh()->status);
    }

    /* -------------------------------------------------------------------------- */
    /* 3. OVERTIME AUTHORIZATION TESTS                                            */
    /* -------------------------------------------------------------------------- */

    public function test_admin_a_cannot_approve_or_mutate_overtime_b(): void
    {
        $overtimeB = OvertimeRequest::create([
            'employee_id' => $this->employeeB->id,
            'work_date' => '2026-08-17',
            'requested_minutes' => 60,
            'reason' => 'Lembur project',
            'status' => 'pending',
        ]);

        $sessionB = OvertimeSession::create([
            'overtime_request_id' => $overtimeB->id,
            'employee_id' => $this->employeeB->id,
            'work_date' => '2026-08-17',
            'status' => 'active',
            'check_in_at' => '2026-08-17 17:00:00',
        ]);

        // Admin A approve Overtime B -> 403
        $response = $this->actingAs($this->adminA)->post(route('admin.overtime-requests.approve', $overtimeB), [
            'approved_minutes' => 60,
        ]);
        $response->assertStatus(403);
        $this->assertEquals('pending', $overtimeB->fresh()->status);

        // Admin A force finish Session B -> 403
        $response = $this->actingAs($this->adminA)->post(route('admin.overtime-sessions.force-finish', $sessionB), [
            'finish_at' => '2026-08-17 18:00:00',
            'reason' => 'Selesaikan paksa',
        ]);
        $response->assertStatus(403);
        $this->assertEquals('active', $sessionB->fresh()->status);

        // Admin B can force finish Session B
        $response = $this->actingAs($this->adminB)->post(route('admin.overtime-sessions.force-finish', $sessionB), [
            'finish_at' => '2026-08-17 18:00:00',
            'reason' => 'Selesaikan paksa sah',
        ]);
        $response->assertRedirect();
        $this->assertEquals('completed', $sessionB->fresh()->status);
    }

    /* -------------------------------------------------------------------------- */
    /* 4. SHIFT SWAP AUTHORIZATION TESTS                                          */
    /* -------------------------------------------------------------------------- */

    public function test_admin_a_cannot_show_or_approve_shift_swap_b(): void
    {
        $employeeB2 = Employee::create([
            'employee_code' => 'EMP-B2',
            'full_name' => 'Karyawan Outlet B2',
            'email' => 'employee.b2@selon.test',
            'status' => 'active',
            'outlet_id' => $this->outletB->id,
        ]);
        $userB2 = User::factory()->create(['role' => 'employee', 'is_active' => true, 'employee_id' => $employeeB2->id]);
        $employeeB2->update(['user_id' => $userB2->id]);

        EmployeeSchedule::create([
            'employee_id' => $this->employeeB->id,
            'work_date' => '2026-08-20',
            'schedule_type' => 'work',
            'shift_id' => $this->shift->id,
        ]);

        EmployeeSchedule::create([
            'employee_id' => $employeeB2->id,
            'work_date' => '2026-08-21',
            'schedule_type' => 'work',
            'shift_id' => $this->shift->id,
        ]);

        $swapB = ShiftSwapRequest::create([
            'requester_employee_id' => $this->employeeB->id,
            'target_employee_id' => $employeeB2->id,
            'requester_work_date' => '2026-08-20',
            'target_work_date' => '2026-08-21',
            'requester_shift_id' => $this->shift->id,
            'target_shift_id' => $this->shift->id,
            'requester_original_shift_id' => $this->shift->id,
            'target_original_shift_id' => $this->shift->id,
            'status' => ShiftSwapRequest::STATUS_PENDING_ADMIN,
        ]);

        // Admin A show Swap B -> 403
        $response = $this->actingAs($this->adminA)->get(route('admin.shift-swaps.show', $swapB));
        $response->assertStatus(403);

        // Admin A approve Swap B -> 403 & status remains pending_admin
        $response = $this->actingAs($this->adminA)->post(route('admin.shift-swaps.approve', $swapB));
        $response->assertStatus(403);
        $this->assertEquals(ShiftSwapRequest::STATUS_PENDING_ADMIN, $swapB->fresh()->status);

        // Admin B can approve Swap B
        $response = $this->actingAs($this->adminB)->post(route('admin.shift-swaps.approve', $swapB));
        $response->assertRedirect();
        $this->assertEquals(ShiftSwapRequest::STATUS_APPROVED, $swapB->fresh()->status);
    }

    /* -------------------------------------------------------------------------- */
    /* 5. DASHBOARD & EXCEPTION SCOPING TESTS                                     */
    /* -------------------------------------------------------------------------- */

    public function test_admin_a_dashboard_kpi_and_employee_table_are_scoped_to_outlet_a(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-17 12:00:00', config('app.timezone')));

        AttendanceRecord::create([
            'employee_id' => $this->employeeA->id,
            'outlet_id' => $this->outletA->id,
            'work_date' => '2026-08-17',
            'status' => 'present',
            'check_in_at' => '2026-08-17 08:00:00',
        ]);

        AttendanceRecord::create([
            'employee_id' => $this->employeeB->id,
            'outlet_id' => $this->outletB->id,
            'work_date' => '2026-08-17',
            'status' => 'present',
            'check_in_at' => '2026-08-17 08:00:00',
        ]);

        $response = $this->actingAs($this->adminA)->get(route('admin.dashboard'));
        $response->assertStatus(200);

        // Metrics should reflect only 1 employee for Outlet A
        $metrics = $response->viewData('metrics');
        $this->assertEquals(1, $metrics['total_employees']);
        $this->assertEquals(1, $metrics['present_today']);

        // Owner dashboard reflects total 2 employees
        $ownerResponse = $this->actingAs($this->owner)->get(route('admin.dashboard'));
        $ownerMetrics = $ownerResponse->viewData('globalData');
        $this->assertEquals(2, $ownerMetrics['global_kpi']['total_employees']);

        Carbon::setTestNow(); // Explicitly reset for safety
    }

    /* -------------------------------------------------------------------------- */
    /* 6. MONTHLY RECAP & EXPORT AUTHORIZATION TESTS                              */
    /* -------------------------------------------------------------------------- */

    public function test_admin_a_monthly_recap_and_csv_exports_contain_no_outlet_b_data(): void
    {
        // Admin A show Employee B recap -> 403
        $response = $this->actingAs($this->adminA)->get(route('admin.monthly-recaps.show', $this->employeeB));
        $response->assertStatus(403);

        // Admin A print Employee B recap -> 403
        $response = $this->actingAs($this->adminA)->get(route('admin.monthly-recaps.print', $this->employeeB));
        $response->assertStatus(403);

        // Admin A summary CSV export contains only Outlet A employee
        $csvResponse = $this->actingAs($this->adminA)->get(route('admin.monthly-recaps.export-summary'));
        $csvResponse->assertStatus(200);

        // Get streamed output content
        ob_start();
        $csvResponse->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('Karyawan Outlet A', $content);
        $this->assertStringNotContainsString('Karyawan Outlet B', $content);
    }

    /* -------------------------------------------------------------------------- */
    /* 7. SCHEDULE MANAGEMENT AUTHORIZATION TESTS                                 */
    /* -------------------------------------------------------------------------- */

    public function test_admin_a_cannot_assign_update_or_delete_schedule_for_employee_b(): void
    {
        // Admin A assign schedule for Employee B -> 403
        $response = $this->actingAs($this->adminA)->post(route('admin.schedules.store'), [
            'employee_id' => $this->employeeB->id,
            'work_date' => '2026-08-25',
            'schedule_type' => 'work',
            'shift_id' => $this->shift->id,
        ]);
        $response->assertStatus(403);
        $this->assertDatabaseMissing('work_schedules', [
            'employee_id' => $this->employeeB->id,
            'work_date' => '2026-08-25',
        ]);

        // Create schedule for Employee B via Admin B
        $scheduleB = EmployeeSchedule::create([
            'employee_id' => $this->employeeB->id,
            'work_date' => '2026-08-25',
            'schedule_type' => 'work',
            'shift_id' => $this->shift->id,
            'created_by' => $this->adminB->id,
            'updated_by' => $this->adminB->id,
        ]);

        // Admin A delete Schedule B -> 403 & DB intact
        $response = $this->actingAs($this->adminA)->delete(route('admin.schedules.destroy', $scheduleB));
        $response->assertStatus(403);
        $this->assertDatabaseHas('work_schedules', ['id' => $scheduleB->id]);

        // Admin B delete Schedule B -> Success
        $response = $this->actingAs($this->adminB)->delete(route('admin.schedules.destroy', $scheduleB));
        $response->assertRedirect();
        $this->assertDatabaseMissing('work_schedules', ['id' => $scheduleB->id]);
    }
}
