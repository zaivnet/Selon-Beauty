<?php

namespace Tests\Feature\WorkCalendar;

use App\Models\AppSetting;
use App\Models\AttendanceLocation;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Holiday;
use App\Models\OvertimeRequest;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class HolidayOvertimeTest extends TestCase
{
    use RefreshDatabase;

    private Employee $employee;

    private User $user;

    private Shift $shift;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-08-17 18:00:00', config('app.timezone')));
        AppSetting::set('attendance_require_selfie', false, 'boolean');
        $this->employee = Employee::create(['employee_code' => 'CAL-OT', 'full_name' => 'Rina', 'status' => 'active']);
        $this->user = User::create([
            'employee_id' => $this->employee->id, 'name' => 'Rina', 'email' => 'rina-cal-ot@example.test',
            'password' => Hash::make('password'), 'role' => 'employee', 'is_active' => true,
        ]);
        $this->shift = Shift::create([
            'name' => 'Pagi', 'code' => 'CAL-OT-P', 'start_time' => '08:00', 'end_time' => '17:00',
            'check_in_open_minutes_before' => 60, 'check_in_close_minutes_after' => 60,
            'check_out_open_minutes_before' => 60, 'grace_period_minutes' => 5,
            'break_minutes' => 60, 'crosses_midnight' => false, 'is_active' => true,
        ]);
        AttendanceLocation::create([
            'name' => 'Toko', 'latitude' => -6.2, 'longitude' => 106.816666,
            'radius_meters' => 100, 'max_accuracy_meters' => 100, 'is_active' => true,
        ]);
    }

    public function test_approved_overtime_on_holiday_can_start_without_regular_attendance(): void
    {
        Holiday::create(['date' => '2026-08-17', 'type' => 'public_holiday', 'name' => 'Hari Nasional', 'is_working_day' => false]);
        $request = $this->approvedRequest();

        $this->actingAs($this->user)->get(route('employee.dashboard'))
            ->assertOk()->assertSee('Mulai Lembur')->assertDontSee('Selesaikan absensi kerja reguler terlebih dahulu.');
        $this->actingAs($this->user)->get(route('employee.overtime-requests.index'))
            ->assertOk()->assertSee(route('employee.overtime-requests.start', $request), false)
            ->assertSee('Mulai Lembur');

        $this->actingAs($this->user)->post(route('employee.overtime-requests.start', $request), $this->gps())
            ->assertRedirect();

        $this->assertDatabaseHas('overtime_sessions', [
            'overtime_request_id' => $request->id, 'status' => 'active', 'work_schedule_id' => null,
        ]);
        $this->assertDatabaseCount('attendance_records', 0);
    }

    public function test_overtime_on_effective_work_day_still_requires_regular_checkout(): void
    {
        EmployeeSchedule::create([
            'employee_id' => $this->employee->id, 'work_date' => '2026-08-17',
            'schedule_type' => 'work', 'shift_id' => $this->shift->id,
        ]);
        $request = $this->approvedRequest();

        $this->actingAs($this->user)->post(route('employee.overtime-requests.start', $request), $this->gps())
            ->assertSessionHasErrors('overtime');
        $this->assertDatabaseCount('overtime_sessions', 0);
    }

    private function approvedRequest(): OvertimeRequest
    {
        return OvertimeRequest::create([
            'employee_id' => $this->employee->id, 'work_date' => '2026-08-17',
            'requested_minutes' => 120, 'approved_minutes' => 120,
            'reason' => 'Stock opname', 'status' => 'approved',
        ]);
    }

    private function gps(): array
    {
        return ['latitude' => -6.2, 'longitude' => 106.816666, 'accuracy' => 10];
    }
}
