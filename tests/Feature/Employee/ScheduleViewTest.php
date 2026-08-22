<?php

namespace Tests\Feature\Employee;

use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Shift;
use App\Models\User;
use App\Services\EmployeeScheduleService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ScheduleViewTest extends TestCase
{
    use RefreshDatabase;

    protected User $employeeUser1;

    protected Employee $employee1;

    protected User $employeeUser2;

    protected Employee $employee2;

    protected Shift $shiftPagi;

    protected Shift $shiftNight;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employee1 = Employee::create([
            'employee_code' => 'SB-001',
            'full_name' => 'Ayu Lestari',
            'status' => 'active',
        ]);

        $this->employeeUser1 = User::create([
            'employee_id' => $this->employee1->id,
            'name' => 'Ayu Lestari',
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

        $this->shiftPagi = Shift::create([
            'name' => 'Shift Pagi',
            'code' => 'PAGI',
            'start_time' => '08:00',
            'end_time' => '14:00',
            'is_active' => true,
        ]);

        $this->shiftNight = Shift::create([
            'name' => 'Shift Malam',
            'code' => 'NIGHT',
            'start_time' => '20:00',
            'end_time' => '06:00',
            'crosses_midnight' => true,
            'is_active' => true,
        ]);
    }

    public function test_employee_can_only_see_own_schedule(): void
    {
        $today = date('Y-m-d');

        // Employee 1 Schedule
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => $this->shiftPagi->id,
            'schedule_type' => 'work',
        ]);

        // Employee 2 Schedule
        EmployeeSchedule::create([
            'employee_id' => $this->employee2->id,
            'work_date' => $today,
            'schedule_type' => 'off',
        ]);

        // Act as Employee 1
        $response = $this->actingAs($this->employeeUser1)->get('/app/schedules');

        $response->assertOk();
        $response->assertSee('PAGI');
        $response->assertDontSee('Budi Santoso');
    }

    public function test_employee_cannot_access_admin_schedules(): void
    {
        $response = $this->actingAs($this->employeeUser1)->get('/admin/schedules');

        $response->assertRedirect('/app/dashboard');
        $response->assertSessionHas('error');
    }

    public function test_employee_dashboard_reads_updated_schedule(): void
    {
        $today = Carbon::now('Asia/Jakarta')->format('Y-m-d');

        // Create schedule PAGI
        $schedule = EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => $this->shiftPagi->id,
            'schedule_type' => 'work',
        ]);

        // Verify Dashboard displays PAGI
        $this->actingAs($this->employeeUser1)->get('/app/dashboard')
            ->assertOk()
            ->assertSee('PAGI')
            ->assertSee('08:00');

        // Admin updates schedule from PAGI to NIGHT using same record
        $schedule->update([
            'shift_id' => $this->shiftNight->id,
        ]);

        // Verify Dashboard immediately reflects NIGHT without stale caching
        $this->actingAs($this->employeeUser1)->get('/app/dashboard')
            ->assertOk()
            ->assertSee('NIGHT')
            ->assertSee('20:00 — 06:00')
            ->assertDontSee('PAGI');
    }

    public function test_employee_dashboard_resolves_schedule_using_users_employee_id(): void
    {
        $today = Carbon::now('Asia/Jakarta')->format('Y-m-d');

        // Create User where users.id != users.employee_id
        $emp = Employee::create([
            'employee_code' => 'SB-999',
            'full_name' => 'Siti Nurhaliza',
            'status' => 'active',
        ]);

        // Consume a user ID so user->id != emp->id
        User::create([
            'name' => 'Dummy User',
            'email' => 'dummy@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'employee',
        ]);

        $user = User::create([
            'employee_id' => $emp->id,
            'name' => 'Siti Nurhaliza',
            'email' => 'siti@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        // Ensure users.id != employee.id
        $this->assertNotEquals($user->id, $emp->id);

        // Create schedule assigned to employee.id
        EmployeeSchedule::create([
            'employee_id' => $emp->id,
            'work_date' => $today,
            'shift_id' => $this->shiftPagi->id,
            'schedule_type' => 'work',
        ]);

        // Dashboard MUST query using user.employee_id (not user.id)
        $this->actingAs($user)->get('/app/dashboard')
            ->assertOk()
            ->assertSee('Siti Nurhaliza')
            ->assertSee('PAGI');
    }

    public function test_updated_schedule_uses_same_work_schedule_record(): void
    {
        $today = Carbon::now('Asia/Jakarta')->format('Y-m-d');

        $schedule = EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => $this->shiftPagi->id,
            'schedule_type' => 'work',
        ]);

        $initialId = $schedule->id;

        // Service update
        $service = app(EmployeeScheduleService::class);
        $owner = User::create([
            'name' => 'Owner Schedule Test', 'email' => 'owner-schedule-test@example.test',
            'password' => Hash::make('password123'), 'role' => 'owner', 'is_active' => true,
        ]);
        $updated = $service->updateSchedule($schedule, [
            'shift_id' => $this->shiftNight->id,
            'schedule_type' => 'work',
        ], $owner);

        $this->assertEquals($initialId, $updated->id);
        $this->assertEquals(1, EmployeeSchedule::where('employee_id', $this->employee1->id)->count());
    }

    public function test_cross_midnight_active_schedule_resolvable_after_midnight(): void
    {
        // Mock current time at 02:00 AM on 12 August 2026
        Carbon::setTestNow(Carbon::parse('2026-08-12 02:00:00', 'Asia/Jakarta'));

        // Schedule was created for 11 August 2026 with NIGHT shift (20:00 - 06:00)
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => '2026-08-11',
            'shift_id' => $this->shiftNight->id,
            'schedule_type' => 'work',
        ]);

        // Dashboard at 02:00 AM on 12 August resolves yesterday's cross-midnight shift as active
        $this->actingAs($this->employeeUser1)->get('/app/dashboard')
            ->assertOk()
            ->assertSee('NIGHT')
            ->assertSee('20:00 — 06:00');

        Carbon::setTestNow(); // Reset test time
    }
}
