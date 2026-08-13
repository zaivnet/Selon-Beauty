<?php

namespace Tests\Feature\Employee;

use App\Models\AttendanceLocation;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\LeaveRequest;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AttendanceCoreTest extends TestCase
{
    use RefreshDatabase;

    protected Employee $employee1;

    protected User $user1;

    protected Employee $employee2;

    protected User $user2;

    protected Shift $shiftNormal;

    protected Shift $shiftNight;

    protected AttendanceLocation $activeLocation;

    protected array $validGps;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::today('Asia/Jakarta')->setTime(8, 0));

        $this->validGps = [
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'accuracy' => 10.0,
            'selfie' => UploadedFile::fake()->image('selfie.jpg', 640, 480),
        ];

        $this->employee1 = Employee::create([
            'employee_code' => 'SB-001',
            'full_name' => 'Ayu Lestari',
            'status' => 'active',
        ]);

        $this->user1 = User::create([
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

        $this->user2 = User::create([
            'employee_id' => $this->employee2->id,
            'name' => 'Budi Santoso',
            'email' => 'budi@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        $this->activeLocation = AttendanceLocation::create([
            'name' => 'SELON BEAUTY Salon',
            'address' => 'Jl. Boulevard Beauty No. 8',
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'radius_meters' => 50,
            'max_accuracy_meters' => 100,
            'is_active' => true,
        ]);

        $this->shiftNormal = Shift::create([
            'name' => 'Shift Pagi',
            'code' => 'PAGI',
            'start_time' => '08:00',
            'end_time' => '16:00',
            'check_out_open_minutes_before' => 60,
            'grace_period_minutes' => 5,
            'break_minutes' => 60,
            'is_active' => true,
        ]);

        $this->shiftNight = Shift::create([
            'name' => 'Shift Malam',
            'code' => 'NIGHT',
            'start_time' => '20:00',
            'end_time' => '04:00',
            'check_out_open_minutes_before' => 60,
            'grace_period_minutes' => 5,
            'break_minutes' => 60,
            'crosses_midnight' => true,
            'is_active' => true,
        ]);
    }

    public function test_employee_with_valid_work_schedule_can_check_in(): void
    {
        $today = date('Y-m-d');
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        $response = $this->actingAs($this->user1)->post('/app/attendance/check-in', $this->validGps);

        $response->assertRedirect();
        $record = AttendanceRecord::where('employee_id', $this->employee1->id)->first();
        $this->assertNotNull($record);
        $this->assertEquals($today, $record->work_date->format('Y-m-d'));
    }

    public function test_employee_without_work_schedule_cannot_check_in(): void
    {
        $response = $this->actingAs($this->user1)->post('/app/attendance/check-in', $this->validGps);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('attendance_records', [
            'employee_id' => $this->employee1->id,
        ]);
    }

    public function test_off_employee_cannot_check_in(): void
    {
        $today = date('Y-m-d');
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'schedule_type' => 'off',
        ]);

        $response = $this->actingAs($this->user1)->post('/app/attendance/check-in', $this->validGps);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_inactive_employee_cannot_check_in(): void
    {
        $today = date('Y-m-d');
        $this->user1->update(['is_active' => false]);

        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        $response = $this->actingAs($this->user1)->post('/app/attendance/check-in', $this->validGps);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_duplicate_check_in_rejected(): void
    {
        $today = date('Y-m-d');
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        // First check in
        $this->actingAs($this->user1)->post('/app/attendance/check-in', $this->validGps);

        // Duplicate check in
        $response = $this->actingAs($this->user1)->post('/app/attendance/check-in', $this->validGps);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertEquals(1, AttendanceRecord::where('employee_id', $this->employee1->id)->count());
    }

    public function test_server_timestamp_authoritative(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-11 07:58:00', 'Asia/Jakarta'));

        $today = '2026-08-11';
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        // Client attempts to send forged time
        $this->actingAs($this->user1)->post('/app/attendance/check-in', array_merge($this->validGps, [
            'check_in_at' => '2026-08-11 08:30:00',
        ]));

        $record = AttendanceRecord::where('employee_id', $this->employee1->id)->first();
        $this->assertNotNull($record);
        $this->assertEquals('07:58:00', $record->check_in_at->format('H:i:s'));

        Carbon::setTestNow();
    }

    public function test_on_time_calculation_correct(): void
    {
        $today = '2026-08-11';
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-08-11 07:55:00', 'Asia/Jakarta'));
        $this->actingAs($this->user1)->post('/app/attendance/check-in', $this->validGps);

        $record = AttendanceRecord::where('employee_id', $this->employee1->id)->first();
        $this->assertEquals('present', $record->status);
        $this->assertEquals(0, $record->late_minutes);

        Carbon::setTestNow();
    }

    public function test_grace_period_calculation_correct(): void
    {
        $today = '2026-08-11';
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-08-11 08:05:00', 'Asia/Jakarta'));
        $this->actingAs($this->user1)->post('/app/attendance/check-in', $this->validGps);

        $record = AttendanceRecord::where('employee_id', $this->employee1->id)->first();
        $this->assertEquals('present', $record->status);
        $this->assertEquals(0, $record->late_minutes);

        Carbon::setTestNow();
    }

    public function test_late_minutes_calculation_correct(): void
    {
        $today = '2026-08-11';
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-08-11 08:20:00', 'Asia/Jakarta'));
        $this->actingAs($this->user1)->post('/app/attendance/check-in', $this->validGps);

        $record = AttendanceRecord::where('employee_id', $this->employee1->id)->first();
        $this->assertEquals('late', $record->status);
        $this->assertEquals(15, $record->late_minutes);

        Carbon::setTestNow();
    }

    public function test_approved_leave_blocks_direct_check_in_but_pending_and_rejected_do_not(): void
    {
        foreach (['approved', 'pending', 'rejected'] as $index => $status) {
            $employee = Employee::create([
                'employee_code' => 'LEAVE-'.$index,
                'full_name' => 'Leave Test '.$index,
                'status' => 'active',
            ]);
            $user = User::create([
                'employee_id' => $employee->id,
                'name' => 'Leave Test '.$index,
                'email' => "leave{$index}@example.test",
                'password' => Hash::make('password123'),
                'role' => 'employee',
                'is_active' => true,
            ]);
            EmployeeSchedule::create([
                'employee_id' => $employee->id,
                'work_date' => '2026-08-11',
                'shift_id' => $this->shiftNormal->id,
                'schedule_type' => 'work',
            ]);
            LeaveRequest::create([
                'employee_id' => $employee->id,
                'type' => ['permission', 'sick', 'leave'][$index],
                'start_date' => '2026-08-11',
                'end_date' => '2026-08-11',
                'reason' => 'Regression test',
                'status' => $status,
            ]);

            Carbon::setTestNow(Carbon::parse('2026-08-11 08:00:00', 'Asia/Jakarta'));
            $response = $this->actingAs($user)->post('/app/attendance/check-in', array_merge($this->validGps, [
                'selfie' => UploadedFile::fake()->image("leave-{$index}.jpg"),
            ]));

            if ($status === 'approved') {
                $response->assertSessionHas('error');
                $this->assertDatabaseMissing('attendance_records', ['employee_id' => $employee->id]);
            } else {
                $this->assertDatabaseHas('attendance_records', ['employee_id' => $employee->id]);
            }
        }
    }

    public function test_check_in_window_boundaries_are_enforced_server_side(): void
    {
        $cases = [
            ['06:59:00', false, 'belum dibuka'],
            ['07:00:00', true, null],
            ['10:00:00', true, null],
            ['10:01:00', false, 'sudah ditutup'],
        ];

        foreach ($cases as $index => [$time, $accepted, $message]) {
            $employee = Employee::create(['employee_code' => "WINDOW-{$index}", 'full_name' => "Window {$index}", 'status' => 'active']);
            $user = User::create([
                'employee_id' => $employee->id,
                'name' => "Window {$index}",
                'email' => "window{$index}@example.test",
                'password' => Hash::make('password123'),
                'role' => 'employee',
                'is_active' => true,
            ]);
            EmployeeSchedule::create([
                'employee_id' => $employee->id,
                'work_date' => '2026-08-11',
                'shift_id' => $this->shiftNormal->id,
                'schedule_type' => 'work',
            ]);
            Carbon::setTestNow(Carbon::parse("2026-08-11 {$time}", 'Asia/Jakarta'));

            $response = $this->actingAs($user)->post('/app/attendance/check-in', array_merge($this->validGps, [
                'selfie' => UploadedFile::fake()->image("window-{$index}.jpg"),
            ]));

            $accepted
                ? $this->assertDatabaseHas('attendance_records', ['employee_id' => $employee->id])
                : $response->assertSessionHas('error', fn ($error) => str_contains($error, $message));
        }
    }

    public function test_cross_midnight_checkout_prioritizes_open_previous_schedule(): void
    {
        $nightDate = '2026-08-11';
        $overnightShift = Shift::create([
            'name' => 'Shift 22-06', 'code' => 'NIGHT-22',
            'start_time' => '22:00', 'end_time' => '06:00',
            'check_in_open_minutes_before' => 60, 'check_in_close_minutes_after' => 120,
            'check_out_open_minutes_before' => 60, 'grace_period_minutes' => 5,
            'break_minutes' => 60, 'crosses_midnight' => true, 'is_active' => true,
        ]);
        $nightSchedule = EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $nightDate,
            'shift_id' => $overnightShift->id,
            'schedule_type' => 'work',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-08-11 22:00:00', 'Asia/Jakarta'));
        $this->actingAs($this->user1)->post('/app/attendance/check-in', $this->validGps);

        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => '2026-08-12',
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-08-12 05:30:00', 'Asia/Jakarta'));
        $this->actingAs($this->user1)->post('/app/attendance/check-out', array_merge($this->validGps, [
            'selfie' => UploadedFile::fake()->image('night-out.jpg'),
        ]));

        $record = AttendanceRecord::where('work_schedule_id', $nightSchedule->id)->firstOrFail();
        $this->assertSame($nightDate, $record->work_date->format('Y-m-d'));
        $this->assertNotNull($record->check_out_at);
    }

    public function test_check_out_open_boundary_is_enforced(): void
    {
        $schedule = EmployeeSchedule::create([
            'employee_id' => $this->employee1->id, 'work_date' => '2026-08-11',
            'shift_id' => $this->shiftNormal->id, 'schedule_type' => 'work',
        ]);
        AttendanceRecord::create([
            'employee_id' => $this->employee1->id, 'work_schedule_id' => $schedule->id,
            'work_date' => '2026-08-11', 'status' => 'present',
            'check_in_at' => Carbon::parse('2026-08-11 08:00:00', 'Asia/Jakarta'),
        ]);

        Carbon::setTestNow(Carbon::parse('2026-08-11 14:59:00', 'Asia/Jakarta'));
        $this->actingAs($this->user1)->post('/app/attendance/check-out', $this->validGps)
            ->assertSessionHas('error', fn ($error) => str_contains($error, 'belum dibuka'));

        Carbon::setTestNow(Carbon::parse('2026-08-11 15:00:00', 'Asia/Jakarta'));
        $this->actingAs($this->user1)->post('/app/attendance/check-out', array_merge($this->validGps, [
            'selfie' => UploadedFile::fake()->image('checkout-boundary.jpg'),
        ]));

        $this->assertNotNull(AttendanceRecord::where('work_schedule_id', $schedule->id)->first()?->check_out_at);
    }

    public function test_check_out_requires_check_in(): void
    {
        $today = date('Y-m-d');
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        $response = $this->actingAs($this->user1)->post('/app/attendance/check-out', $this->validGps);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_duplicate_check_out_rejected(): void
    {
        $today = '2026-08-11';
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-08-11 08:00:00', 'Asia/Jakarta'));
        $this->actingAs($this->user1)->post('/app/attendance/check-in', $this->validGps);

        Carbon::setTestNow(Carbon::parse('2026-08-11 16:00:00', 'Asia/Jakarta'));
        $this->actingAs($this->user1)->post('/app/attendance/check-out', $this->validGps);

        $response = $this->actingAs($this->user1)->post('/app/attendance/check-out', $this->validGps);
        $response->assertSessionHas('error');

        Carbon::setTestNow();
    }

    public function test_worked_minutes_correct(): void
    {
        $today = '2026-08-11';
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-08-11 08:00:00', 'Asia/Jakarta'));
        $this->actingAs($this->user1)->post('/app/attendance/check-in', $this->validGps);

        Carbon::setTestNow(Carbon::parse('2026-08-11 16:00:00', 'Asia/Jakarta'));
        $this->actingAs($this->user1)->post('/app/attendance/check-out', $this->validGps);

        $record = AttendanceRecord::where('employee_id', $this->employee1->id)->first();
        $this->assertEquals(420, $record->worked_minutes);

        Carbon::setTestNow();
    }

    public function test_early_leave_calculation_correct(): void
    {
        $today = '2026-08-11';
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-08-11 08:00:00', 'Asia/Jakarta'));
        $this->actingAs($this->user1)->post('/app/attendance/check-in', $this->validGps);

        Carbon::setTestNow(Carbon::parse('2026-08-11 15:45:00', 'Asia/Jakarta'));
        $this->actingAs($this->user1)->post('/app/attendance/check-out', $this->validGps);

        $record = AttendanceRecord::where('employee_id', $this->employee1->id)->first();
        $this->assertEquals(15, $record->early_leave_minutes);

        Carbon::setTestNow();
    }

    public function test_overtime_candidate_calculation_correct(): void
    {
        $today = '2026-08-11';
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-08-11 08:00:00', 'Asia/Jakarta'));
        $this->actingAs($this->user1)->post('/app/attendance/check-in', $this->validGps);

        Carbon::setTestNow(Carbon::parse('2026-08-11 16:45:00', 'Asia/Jakarta'));
        $this->actingAs($this->user1)->post('/app/attendance/check-out', $this->validGps);

        $record = AttendanceRecord::where('employee_id', $this->employee1->id)->first();
        $this->assertEquals(45, $record->overtime_minutes);

        Carbon::setTestNow();
    }

    public function test_cross_midnight_check_in_out_correct(): void
    {
        $today = '2026-08-11';
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => $this->shiftNight->id,
            'schedule_type' => 'work',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-08-11 20:00:00', 'Asia/Jakarta'));
        $this->actingAs($this->user1)->post('/app/attendance/check-in', $this->validGps);

        Carbon::setTestNow(Carbon::parse('2026-08-12 04:00:00', 'Asia/Jakarta'));
        $this->actingAs($this->user1)->post('/app/attendance/check-out', $this->validGps);

        $record = AttendanceRecord::where('employee_id', $this->employee1->id)->first();
        $this->assertNotNull($record);
        $this->assertEquals('2026-08-11', $record->work_date->format('Y-m-d'));
        $this->assertEquals(420, $record->worked_minutes);

        Carbon::setTestNow();
    }

    public function test_cross_midnight_attendance_keeps_original_work_date(): void
    {
        $today = '2026-08-11';
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => $this->shiftNight->id,
            'schedule_type' => 'work',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-08-11 20:00:00', 'Asia/Jakarta'));
        $this->actingAs($this->user1)->post('/app/attendance/check-in', $this->validGps);

        Carbon::setTestNow(Carbon::parse('2026-08-12 04:05:00', 'Asia/Jakarta'));
        $this->actingAs($this->user1)->post('/app/attendance/check-out', $this->validGps);

        $record = AttendanceRecord::where('employee_id', $this->employee1->id)->first();
        $this->assertEquals('2026-08-11', $record->work_date->format('Y-m-d'));
        $this->assertEquals(1, AttendanceRecord::count());

        Carbon::setTestNow();
    }

    public function test_employee_cannot_create_attendance_for_another_employee(): void
    {
        $today = date('Y-m-d');
        EmployeeSchedule::create([
            'employee_id' => $this->employee2->id,
            'work_date' => $today,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        $this->actingAs($this->user1)->post('/app/attendance/check-in', array_merge($this->validGps, [
            'employee_id' => $this->employee2->id,
        ]));

        $this->assertDatabaseMissing('attendance_records', [
            'employee_id' => $this->employee2->id,
        ]);
    }
}
