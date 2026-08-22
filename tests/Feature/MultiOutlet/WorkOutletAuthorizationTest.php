<?php

namespace Tests\Feature\MultiOutlet;

use App\Models\AppSetting;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\EmployeeScheduleOverride;
use App\Models\Outlet;
use App\Models\Shift;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\EffectiveScheduleService;
use App\Services\MultiOutletDashboardService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WorkOutletAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private Outlet $pusat;

    private Outlet $cabang;

    private Employee $employee;

    private Shift $shift;

    private User $adminPusat;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-08-22 09:00:00');
        $this->pusat = $this->outlet('Pusat', 'WORK-PUSAT');
        $this->cabang = $this->outlet('Cabang', 'WORK-CABANG');
        $this->employee = Employee::create([
            'employee_code' => 'WORK-EMP', 'full_name' => 'Employee Pusat', 'status' => 'active', 'outlet_id' => $this->pusat->id,
        ]);
        $this->shift = Shift::create([
            'name' => 'Pagi', 'code' => 'WORK-P', 'start_time' => '08:00', 'end_time' => '17:00',
            'check_in_open_minutes_before' => 60, 'check_in_close_minutes_after' => 60,
            'check_out_open_minutes_before' => 60, 'grace_period_minutes' => 5, 'break_minutes' => 60, 'is_active' => true,
        ]);
        $this->adminPusat = $this->admin('admin.pusat@work.test', [$this->pusat->id]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_admin_can_assign_home_outlet_but_not_unassigned_work_outlet(): void
    {
        $this->actingAs($this->adminPusat)->post(route('admin.schedules.store'), $this->schedulePayload($this->pusat->id))
            ->assertRedirect();

        $this->assertDatabaseHas('work_schedules', ['employee_id' => $this->employee->id, 'work_outlet_id' => $this->pusat->id]);

        $other = Employee::create(['employee_code' => 'WORK-EMP-2', 'full_name' => 'Other', 'status' => 'active', 'outlet_id' => $this->pusat->id]);
        $this->actingAs($this->adminPusat)->post(route('admin.schedules.store'), $this->schedulePayload($this->cabang->id, $other->id))
            ->assertForbidden();
    }

    public function test_multi_outlet_admin_can_assign_cabang_work_outlet_for_pusat_home_employee(): void
    {
        $this->adminPusat->assignedOutlets()->sync([$this->pusat->id, $this->cabang->id]);

        $this->actingAs($this->adminPusat)->post(route('admin.schedules.store'), $this->schedulePayload($this->cabang->id))
            ->assertRedirect();

        $this->assertDatabaseHas('work_schedules', ['employee_id' => $this->employee->id, 'work_outlet_id' => $this->cabang->id]);
    }

    public function test_cabang_only_admin_cannot_manage_pusat_home_employee_even_for_cabang_work_outlet(): void
    {
        $adminCabang = $this->admin('admin.cabang@work.test', [$this->cabang->id]);

        $this->actingAs($adminCabang)->post(route('admin.schedules.store'), $this->schedulePayload($this->cabang->id))
            ->assertForbidden();
    }

    public function test_all_outlet_admin_can_assign_active_work_outlet_and_zero_assignment_admin_fails_closed(): void
    {
        $this->adminPusat->forceFill(['outlet_access_mode' => 'all'])->save();
        $this->adminPusat->assignedOutlets()->sync([]);
        $this->actingAs($this->adminPusat)->post(route('admin.schedules.store'), $this->schedulePayload($this->cabang->id))
            ->assertRedirect();

        $zeroAdmin = $this->admin('admin.zero@work.test', []);
        $other = Employee::create(['employee_code' => 'WORK-EMP-3', 'full_name' => 'No Access', 'status' => 'active', 'outlet_id' => $this->pusat->id]);
        $this->actingAs($zeroAdmin)->post(route('admin.schedules.store'), $this->schedulePayload($this->pusat->id, $other->id))
            ->assertForbidden();
    }

    public function test_check_in_uses_work_outlet_and_checkout_uses_attendance_outlet_snapshot(): void
    {
        $this->cabang->update(['latitude' => -6.3000000, 'longitude' => 106.9000000]);
        AppSetting::set('attendance_require_selfie', false, 'boolean');
        AppSetting::set('attendance_require_checkout_geofence', true, 'boolean');
        $employeeUser = User::create([
            'employee_id' => $this->employee->id, 'name' => 'Employee Pusat', 'email' => 'employee.work@work.test',
            'password' => Hash::make('password'), 'role' => 'employee', 'is_active' => true,
        ]);
        $schedule = EmployeeSchedule::create([
            'employee_id' => $this->employee->id, 'work_date' => '2026-08-22', 'schedule_type' => 'work',
            'shift_id' => $this->shift->id, 'work_outlet_id' => $this->cabang->id,
        ]);
        $service = app(AttendanceService::class);

        try {
            $service->checkIn($employeeUser, ['latitude' => -6.2, 'longitude' => 106.8, 'accuracy' => 10]);
            $this->fail('Home Outlet coordinates must not pass a Cabang Work Outlet geofence.');
        } catch (\InvalidArgumentException) {
            $this->assertDatabaseCount('attendance_records', 0);
        }

        $record = $service->checkIn($employeeUser, ['latitude' => -6.3, 'longitude' => 106.9, 'accuracy' => 10]);
        $this->assertSame($this->cabang->id, $record->outlet_id);

        $this->employee->update(['outlet_id' => $this->cabang->id]);
        $schedule->update(['work_outlet_id' => $this->pusat->id]);
        Carbon::setTestNow('2026-08-22 16:30:00');
        $checkedOut = $service->checkOut($employeeUser, ['latitude' => -6.3, 'longitude' => 106.9, 'accuracy' => 10]);

        $this->assertNotNull($checkedOut->check_out_at);
        $this->assertSame($this->cabang->id, $checkedOut->fresh()->outlet_id);
    }

    public function test_operational_dashboard_groups_an_employee_by_work_outlet_not_home_outlet(): void
    {
        $schedule = EmployeeSchedule::create([
            'employee_id' => $this->employee->id, 'work_date' => '2026-08-22', 'schedule_type' => 'work',
            'shift_id' => $this->shift->id, 'work_outlet_id' => $this->cabang->id,
        ]);
        $effective = app(EffectiveScheduleService::class)->resolve($this->employee, '2026-08-22');
        $overview = app(MultiOutletDashboardService::class)->generateOverview(
            Outlet::whereIn('id', [$this->pusat->id, $this->cabang->id])->get(),
            [[
                'employee' => $this->employee,
                'effective_schedule' => $effective,
                'status_key' => 'pending',
            ]],
            ['summary' => [], 'items' => []],
        );

        $byOutlet = collect($overview['outlets'])->keyBy(fn (array $row) => $row['outlet']->id);
        $this->assertSame(0, $byOutlet[$this->pusat->id]['metrics']['total_employees']);
        $this->assertSame(1, $byOutlet[$this->cabang->id]['metrics']['total_employees']);
        $this->assertSame($this->cabang->id, $schedule->work_outlet_id);
    }

    public function test_forged_override_id_cannot_be_reassigned_across_home_outlets(): void
    {
        $cabangEmployee = Employee::create([
            'employee_code' => 'WORK-CABANG-EMP', 'full_name' => 'Employee Cabang', 'status' => 'active', 'outlet_id' => $this->cabang->id,
        ]);
        $override = EmployeeScheduleOverride::create([
            'employee_id' => $cabangEmployee->id, 'date' => '2026-08-23', 'override_type' => 'work',
            'shift_id' => $this->shift->id, 'work_outlet_id' => $this->cabang->id, 'reason' => 'Jadwal Cabang asli',
        ]);

        $this->actingAs($this->adminPusat)->put(route('admin.schedule-overrides.update', $override), [
            'employee_id' => $this->employee->id, 'date' => '2026-08-23', 'override_type' => 'work',
            'shift_id' => $this->shift->id, 'work_outlet_id' => $this->pusat->id, 'reason' => 'Percobaan manipulasi override',
        ])->assertForbidden();

        $this->assertDatabaseHas('employee_schedule_overrides', [
            'id' => $override->id, 'employee_id' => $cabangEmployee->id, 'work_outlet_id' => $this->cabang->id,
        ]);
    }

    private function outlet(string $name, string $code): Outlet
    {
        return Outlet::create(['name' => $name, 'code' => $code, 'latitude' => -6.2, 'longitude' => 106.8, 'radius_meters' => 100, 'is_active' => true]);
    }

    /** @param array<int, int> $outletIds */
    private function admin(string $email, array $outletIds): User
    {
        $admin = User::create(['name' => $email, 'email' => $email, 'password' => Hash::make('password'), 'role' => 'admin', 'outlet_access_mode' => 'selected', 'is_active' => true]);
        $admin->assignedOutlets()->sync($outletIds);

        return $admin;
    }

    private function schedulePayload(int $workOutletId, ?int $employeeId = null): array
    {
        return ['employee_id' => $employeeId ?? $this->employee->id, 'work_date' => '2026-08-23', 'schedule_type' => 'work', 'shift_id' => $this->shift->id, 'work_outlet_id' => $workOutletId];
    }
}
