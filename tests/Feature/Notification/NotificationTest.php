<?php

namespace Tests\Feature\Notification;

use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\Shift;
use App\Models\User;
use App\Notifications\LeaveRequestApprovedNotification;
use App\Notifications\LeaveRequestRejectedNotification;
use App\Notifications\LeaveRequestSubmittedNotification;
use App\Notifications\OvertimeRequestApprovedNotification;
use App\Notifications\OvertimeRequestRejectedNotification;
use App\Notifications\OvertimeRequestSubmittedNotification;
use App\Services\LeaveRequestService;
use App\Services\OvertimeRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $ownerUser;
    protected User $adminUser;
    protected User $employeeUser1;
    protected Employee $employee1;
    protected User $employeeUser2;
    protected Employee $employee2;
    protected Shift $shiftNormal;
    protected LeaveRequestService $leaveService;
    protected OvertimeRequestService $overtimeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->leaveService = app(LeaveRequestService::class);
        $this->overtimeService = app(OvertimeRequestService::class);

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
    }

    public function test_leave_submission_notifies_owner_and_admin(): void
    {
        Notification::fake();

        $this->leaveService->submitRequest($this->employee1, [
            'type' => 'permission',
            'start_date' => '2026-08-15',
            'end_date' => '2026-08-15',
            'reason' => 'Acara keluarga',
        ]);

        Notification::assertSentTo(
            [$this->ownerUser, $this->adminUser],
            LeaveRequestSubmittedNotification::class
        );

        Notification::assertNotSentTo(
            [$this->employeeUser1, $this->employeeUser2],
            LeaveRequestSubmittedNotification::class
        );
    }

    public function test_leave_approval_notifies_employee(): void
    {
        Notification::fake();

        $leave = LeaveRequest::create([
            'employee_id' => $this->employee1->id,
            'type' => 'permission',
            'start_date' => '2026-08-15',
            'end_date' => '2026-08-15',
            'reason' => 'Izin keluarga',
            'status' => 'pending',
        ]);

        $this->leaveService->approveRequest($leave, $this->ownerUser, 'Disetujui');

        Notification::assertSentTo(
            $this->employeeUser1,
            LeaveRequestApprovedNotification::class
        );
    }

    public function test_leave_rejection_notifies_employee(): void
    {
        Notification::fake();

        $leave = LeaveRequest::create([
            'employee_id' => $this->employee1->id,
            'type' => 'leave',
            'start_date' => '2026-08-15',
            'end_date' => '2026-08-15',
            'reason' => 'Cuti liburan',
            'status' => 'pending',
        ]);

        $this->leaveService->rejectRequest($leave, $this->ownerUser, 'Jadwal toko sedang padat');

        Notification::assertSentTo(
            $this->employeeUser1,
            LeaveRequestRejectedNotification::class
        );
    }

    public function test_overtime_submission_notifies_owner_and_admin(): void
    {
        Notification::fake();

        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => '2026-08-15',
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        $this->overtimeService->submitRequest($this->employee1, [
            'work_date' => '2026-08-15',
            'requested_minutes' => 60,
            'reason' => 'Restock stok produk',
        ]);

        Notification::assertSentTo(
            [$this->ownerUser, $this->adminUser],
            OvertimeRequestSubmittedNotification::class
        );
    }

    public function test_overtime_approval_notifies_employee(): void
    {
        Notification::fake();

        $overtime = OvertimeRequest::create([
            'employee_id' => $this->employee1->id,
            'work_date' => '2026-08-15',
            'requested_minutes' => 60,
            'reason' => 'Restock barang',
            'status' => 'pending',
        ]);

        $this->overtimeService->approveRequest($overtime, $this->ownerUser, 60, 'Oke disetujui');

        Notification::assertSentTo(
            $this->employeeUser1,
            OvertimeRequestApprovedNotification::class
        );
    }

    public function test_overtime_rejection_notifies_employee(): void
    {
        Notification::fake();

        $overtime = OvertimeRequest::create([
            'employee_id' => $this->employee1->id,
            'work_date' => '2026-08-15',
            'requested_minutes' => 90,
            'reason' => 'Lembur melayani customer',
            'status' => 'pending',
        ]);

        $this->overtimeService->rejectRequest($overtime, $this->adminUser, 'Tidak ada instruksi lembur');

        Notification::assertSentTo(
            $this->employeeUser1,
            OvertimeRequestRejectedNotification::class
        );
    }

    public function test_unread_count_correct(): void
    {
        $this->assertEquals(0, $this->ownerUser->unreadNotifications()->count());

        // Create 2 database notifications directly for ownerUser
        $leave = LeaveRequest::create([
            'employee_id' => $this->employee1->id,
            'type' => 'permission',
            'start_date' => '2026-08-15',
            'end_date' => '2026-08-15',
            'reason' => 'Test 1',
            'status' => 'pending',
        ]);

        $this->ownerUser->notify(new LeaveRequestSubmittedNotification($leave));

        $this->assertEquals(1, $this->ownerUser->unreadNotifications()->count());

        $response = $this->actingAs($this->ownerUser)->getJson(route('notifications.unread-count'));
        $response->assertOk();
        $response->assertJson(['unread_count' => 1]);
    }

    public function test_mark_as_read_persists(): void
    {
        $leave = LeaveRequest::create([
            'employee_id' => $this->employee1->id,
            'type' => 'permission',
            'start_date' => '2026-08-15',
            'end_date' => '2026-08-15',
            'reason' => 'Test 1',
            'status' => 'pending',
        ]);

        $this->ownerUser->notify(new LeaveRequestSubmittedNotification($leave));
        $notification = $this->ownerUser->unreadNotifications->first();

        $this->assertNull($notification->read_at);

        $response = $this->actingAs($this->ownerUser)->post(route('notifications.read', $notification->id));

        $notification->refresh();
        $this->assertNotNull($notification->read_at);
        $this->assertEquals(0, $this->ownerUser->unreadNotifications()->count());
    }

    public function test_mark_all_as_read_only_affects_authenticated_user(): void
    {
        $leave = LeaveRequest::create([
            'employee_id' => $this->employee1->id,
            'type' => 'permission',
            'start_date' => '2026-08-15',
            'end_date' => '2026-08-15',
            'reason' => 'Test 1',
            'status' => 'pending',
        ]);

        // Send notification to both ownerUser and adminUser
        $this->ownerUser->notify(new LeaveRequestSubmittedNotification($leave));
        $this->adminUser->notify(new LeaveRequestSubmittedNotification($leave));

        $this->assertEquals(1, $this->ownerUser->unreadNotifications()->count());
        $this->assertEquals(1, $this->adminUser->unreadNotifications()->count());

        // ownerUser marks all read
        $this->actingAs($this->ownerUser)->post(route('notifications.mark-all-read'));

        $this->assertEquals(0, $this->ownerUser->unreadNotifications()->count());
        // adminUser must remain unread!
        $this->assertEquals(1, $this->adminUser->unreadNotifications()->count());
    }

    public function test_employee_cannot_read_another_user_notification(): void
    {
        $leave = LeaveRequest::create([
            'employee_id' => $this->employee1->id,
            'type' => 'permission',
            'start_date' => '2026-08-15',
            'end_date' => '2026-08-15',
            'reason' => 'Test 1',
            'status' => 'pending',
        ]);

        // Sent to employeeUser1
        $this->employeeUser1->notify(new LeaveRequestApprovedNotification($leave));
        $notification = $this->employeeUser1->unreadNotifications->first();

        // employeeUser2 attempts to mark employeeUser1's notification as read
        $response = $this->actingAs($this->employeeUser2)->post(route('notifications.read', $notification->id));

        $response->assertStatus(403);
        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_notification_target_link_resolves_correctly(): void
    {
        $leave = LeaveRequest::create([
            'employee_id' => $this->employee1->id,
            'type' => 'permission',
            'start_date' => '2026-08-15',
            'end_date' => '2026-08-15',
            'reason' => 'Test 1',
            'status' => 'pending',
        ]);

        $this->employeeUser1->notify(new LeaveRequestApprovedNotification($leave));
        $notification = $this->employeeUser1->unreadNotifications->first();

        $response = $this->actingAs($this->employeeUser1)->post(route('notifications.read', $notification->id));

        $response->assertRedirect(route('employee.leave-requests.index'));
    }

    public function test_application_works_without_redis(): void
    {
        // Force default queue connection to sync or null, ensuring no Redis dependency
        Config::set('queue.default', 'sync');
        Config::set('broadcasting.default', 'log');

        $leave = LeaveRequest::create([
            'employee_id' => $this->employee1->id,
            'type' => 'permission',
            'start_date' => '2026-08-15',
            'end_date' => '2026-08-15',
            'reason' => 'Test tanpa redis',
            'status' => 'pending',
        ]);

        $this->ownerUser->notify(new LeaveRequestSubmittedNotification($leave));

        $this->assertEquals(1, $this->ownerUser->unreadNotifications()->count());
    }

    public function test_application_works_without_websocket_server(): void
    {
        // App relies on database notifications, requiring 0 websocket connections
        Config::set('broadcasting.default', 'log');

        $response = $this->actingAs($this->employeeUser1)->get(route('notifications.index'));
        $response->assertOk();
    }
}
