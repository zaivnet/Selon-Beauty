<?php

namespace Tests\Feature\Admin;

use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ScheduleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected Employee $employee;
    protected Shift $activeShift;
    protected Shift $inactiveShift;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::create([
            'name' => 'Owner System',
            'email' => 'owner@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->employee = Employee::create([
            'employee_code' => 'SB-001',
            'full_name' => 'Ayu Lestari',
            'status' => 'active',
        ]);

        $this->activeShift = Shift::create([
            'name' => 'Shift Pagi',
            'code' => 'PAGI',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'grace_period_minutes' => 5,
            'check_in_open_minutes_before' => 60,
            'check_in_close_minutes_after' => 120,
            'check_out_open_minutes_before' => 60,
            'break_minutes' => 60,
            'is_active' => true,
        ]);

        $this->inactiveShift = Shift::create([
            'name' => 'Shift Old',
            'code' => 'OLD',
            'start_time' => '08:00',
            'end_time' => '16:00',
            'grace_period_minutes' => 5,
            'check_in_open_minutes_before' => 60,
            'check_in_close_minutes_after' => 120,
            'check_out_open_minutes_before' => 60,
            'break_minutes' => 60,
            'is_active' => false,
        ]);
    }

    public function test_owner_can_assign_schedule(): void
    {
        $today = date('Y-m-d');

        $response = $this->actingAs($this->owner)->post('/admin/schedules', [
            'employee_id' => $this->employee->id,
            'work_date' => $today,
            'schedule_type' => 'work',
            'shift_id' => $this->activeShift->id,
            'notes' => 'Shift reguler',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('work_schedules', [
            'employee_id' => $this->employee->id,
            'schedule_type' => 'work',
            'shift_id' => $this->activeShift->id,
        ]);
    }

    public function test_work_schedule_type_requires_shift(): void
    {
        $response = $this->actingAs($this->owner)->post('/admin/schedules', [
            'employee_id' => $this->employee->id,
            'work_date' => date('Y-m-d'),
            'schedule_type' => 'work',
            'shift_id' => '', // missing shift for work type
        ]);

        $response->assertSessionHasErrors('shift_id');
    }

    public function test_off_schedule_type_does_not_require_shift(): void
    {
        $today = date('Y-m-d');

        $response = $this->actingAs($this->owner)->post('/admin/schedules/mark-off', [
            'employee_id' => $this->employee->id,
            'work_date' => $today,
            'notes' => 'Jadwal Libur Pekanan',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('work_schedules', [
            'employee_id' => $this->employee->id,
            'schedule_type' => 'off',
            'shift_id' => null,
        ]);
    }

    public function test_employee_date_must_be_unique(): void
    {
        $today = date('Y-m-d');

        EmployeeSchedule::create([
            'employee_id' => $this->employee->id,
            'work_date' => $today,
            'shift_id' => $this->activeShift->id,
            'schedule_type' => 'work',
            'created_by' => $this->owner->id,
            'updated_by' => $this->owner->id,
        ]);

        // Attempting to post second schedule for same date & employee
        $response = $this->actingAs($this->owner)->post('/admin/schedules', [
            'employee_id' => $this->employee->id,
            'work_date' => $today,
            'schedule_type' => 'work',
            'shift_id' => $this->activeShift->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_cannot_assign_inactive_shift_to_new_schedule(): void
    {
        $response = $this->actingAs($this->owner)->post('/admin/schedules', [
            'employee_id' => $this->employee->id,
            'work_date' => date('Y-m-d'),
            'schedule_type' => 'work',
            'shift_id' => $this->inactiveShift->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_copy_previous_week_avoids_conflict_or_previews(): void
    {
        $targetWeekStart = Carbon::now()->startOfWeek();
        $prevWeekStart = (clone $targetWeekStart)->subWeek();

        // Create schedule in previous week
        EmployeeSchedule::create([
            'employee_id' => $this->employee->id,
            'work_date' => $prevWeekStart->format('Y-m-d'),
            'shift_id' => $this->activeShift->id,
            'schedule_type' => 'work',
            'created_by' => $this->owner->id,
            'updated_by' => $this->owner->id,
        ]);

        // Execute Copy Previous Week
        $response = $this->actingAs($this->owner)->post('/admin/schedules/copy-week/execute', [
            'target_start_date' => $targetWeekStart->format('Y-m-d'),
            'overwrite' => '0',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('work_schedules', [
            'employee_id' => $this->employee->id,
            'shift_id' => $this->activeShift->id,
        ]);
    }

    public function test_cross_midnight_shift_in_schedule_works(): void
    {
        $nightShift = Shift::create([
            'name' => 'Shift Malam',
            'code' => 'NIGHT',
            'start_time' => '22:00',
            'end_time' => '06:00',
            'grace_period_minutes' => 5,
            'check_in_open_minutes_before' => 60,
            'check_in_close_minutes_after' => 120,
            'check_out_open_minutes_before' => 60,
            'break_minutes' => 60,
            'crosses_midnight' => true,
            'is_active' => true,
        ]);

        $today = date('Y-m-d');

        $response = $this->actingAs($this->owner)->post('/admin/schedules', [
            'employee_id' => $this->employee->id,
            'work_date' => $today,
            'schedule_type' => 'work',
            'shift_id' => $nightShift->id,
        ]);

        $response->assertRedirect();

        $schedule = EmployeeSchedule::where('employee_id', $this->employee->id)->whereDate('work_date', $today)->first();
        $this->assertNotNull($schedule);
        $this->assertEquals('NIGHT', $schedule->shift->code);
        $this->assertTrue($schedule->shift->crosses_midnight);
    }

    public function test_owner_can_update_existing_schedule(): void
    {
        $today = date('Y-m-d');
        $schedule = EmployeeSchedule::create([
            'employee_id' => $this->employee->id,
            'work_date' => $today,
            'shift_id' => $this->activeShift->id,
            'schedule_type' => 'work',
            'created_by' => $this->owner->id,
            'updated_by' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)->put("/admin/schedules/{$schedule->id}", [
            'schedule_type' => 'work',
            'shift_id' => $this->activeShift->id,
            'notes' => 'Catatan diperbarui',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('work_schedules', [
            'id' => $schedule->id,
            'notes' => 'Catatan diperbarui',
        ]);
    }

    public function test_owner_can_change_shift(): void
    {
        $siangShift = Shift::create([
            'name' => 'Shift Siang',
            'code' => 'SIANG',
            'start_time' => '14:00',
            'end_time' => '22:00',
            'is_active' => true,
        ]);

        $today = date('Y-m-d');
        $schedule = EmployeeSchedule::create([
            'employee_id' => $this->employee->id,
            'work_date' => $today,
            'shift_id' => $this->activeShift->id,
            'schedule_type' => 'work',
            'created_by' => $this->owner->id,
            'updated_by' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)->put("/admin/schedules/{$schedule->id}", [
            'schedule_type' => 'work',
            'shift_id' => $siangShift->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('work_schedules', [
            'id' => $schedule->id,
            'shift_id' => $siangShift->id,
        ]);
    }

    public function test_owner_can_change_work_schedule_to_off(): void
    {
        $today = date('Y-m-d');
        $schedule = EmployeeSchedule::create([
            'employee_id' => $this->employee->id,
            'work_date' => $today,
            'shift_id' => $this->activeShift->id,
            'schedule_type' => 'work',
            'created_by' => $this->owner->id,
            'updated_by' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)->put("/admin/schedules/{$schedule->id}", [
            'schedule_type' => 'off',
            'shift_id' => null,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('work_schedules', [
            'id' => $schedule->id,
            'schedule_type' => 'off',
            'shift_id' => null,
        ]);
    }

    public function test_owner_can_change_off_schedule_to_work(): void
    {
        $today = date('Y-m-d');
        $schedule = EmployeeSchedule::create([
            'employee_id' => $this->employee->id,
            'work_date' => $today,
            'shift_id' => null,
            'schedule_type' => 'off',
            'created_by' => $this->owner->id,
            'updated_by' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)->put("/admin/schedules/{$schedule->id}", [
            'schedule_type' => 'work',
            'shift_id' => $this->activeShift->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('work_schedules', [
            'id' => $schedule->id,
            'schedule_type' => 'work',
            'shift_id' => $this->activeShift->id,
        ]);
    }

    public function test_updating_same_employee_date_does_not_trigger_duplicate_error(): void
    {
        $today = date('Y-m-d');
        $schedule = EmployeeSchedule::create([
            'employee_id' => $this->employee->id,
            'work_date' => $today,
            'shift_id' => $this->activeShift->id,
            'schedule_type' => 'work',
            'created_by' => $this->owner->id,
            'updated_by' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)->put("/admin/schedules/{$schedule->id}", [
            'employee_id' => $this->employee->id,
            'work_date' => $today,
            'schedule_type' => 'work',
            'shift_id' => $this->activeShift->id,
            'notes' => 'Update tanpa pindah tanggal',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_changing_schedule_to_an_already_occupied_employee_date_is_rejected(): void
    {
        $date1 = '2026-08-11';
        $date2 = '2026-08-12';

        $sch1 = EmployeeSchedule::create([
            'employee_id' => $this->employee->id,
            'work_date' => $date1,
            'shift_id' => $this->activeShift->id,
            'schedule_type' => 'work',
            'created_by' => $this->owner->id,
            'updated_by' => $this->owner->id,
        ]);

        $sch2 = EmployeeSchedule::create([
            'employee_id' => $this->employee->id,
            'work_date' => $date2,
            'shift_id' => $this->activeShift->id,
            'schedule_type' => 'work',
            'created_by' => $this->owner->id,
            'updated_by' => $this->owner->id,
        ]);

        // Attempting to move sch2 to date1 (which is occupied by sch1)
        $response = $this->actingAs($this->owner)->put("/admin/schedules/{$sch2->id}", [
            'employee_id' => $this->employee->id,
            'work_date' => $date1,
            'schedule_type' => 'work',
            'shift_id' => $this->activeShift->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_schedule_with_attendance_cannot_be_deleted(): void
    {
        $today = date('Y-m-d');
        $schedule = EmployeeSchedule::create([
            'employee_id' => $this->employee->id,
            'work_date' => $today,
            'shift_id' => $this->activeShift->id,
            'schedule_type' => 'work',
            'created_by' => $this->owner->id,
            'updated_by' => $this->owner->id,
        ]);

        // Insert mock attendance record for this schedule
        DB::table('attendance_records')->insert([
            'employee_id' => $this->employee->id,
            'work_schedule_id' => $schedule->id,
            'work_date' => $today,
            'status' => 'present',
            'check_in_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->owner)->delete("/admin/schedules/{$schedule->id}");

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('work_schedules', ['id' => $schedule->id]);
    }

    public function test_audit_log_created_after_schedule_update(): void
    {
        $today = date('Y-m-d');
        $schedule = EmployeeSchedule::create([
            'employee_id' => $this->employee->id,
            'work_date' => $today,
            'shift_id' => $this->activeShift->id,
            'schedule_type' => 'work',
            'created_by' => $this->owner->id,
            'updated_by' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)->put("/admin/schedules/{$schedule->id}", [
            'schedule_type' => 'off',
            'shift_id' => null,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'schedule.changed_to_off',
            'auditable_type' => EmployeeSchedule::class,
            'auditable_id' => $schedule->id,
        ]);
    }
}
