<?php

namespace Tests\Feature\Employee;

use App\Models\AttendanceLocation;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AttendanceGpsTest extends TestCase
{
    use RefreshDatabase;

    protected Employee $employee1;
    protected User $user1;
    protected Employee $employee2;
    protected User $user2;
    protected Shift $shiftNormal;
    protected Shift $shiftNight;
    protected AttendanceLocation $activeLocation;

    protected function setUp(): void
    {
        parent::setUp();

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
            'grace_period_minutes' => 5,
            'break_minutes' => 60,
            'is_active' => true,
        ]);

        $this->shiftNight = Shift::create([
            'name' => 'Shift Malam',
            'code' => 'NIGHT',
            'start_time' => '20:00',
            'end_time' => '04:00',
            'grace_period_minutes' => 5,
            'break_minutes' => 60,
            'crosses_midnight' => true,
            'is_active' => true,
        ]);

        // Default app setting
        DB::table('app_settings')->updateOrInsert(
            ['key' => 'attendance_require_checkout_geofence'],
            ['value' => '1', 'type' => 'boolean', 'is_public' => true]
        );
    }

    public function test_inside_radius_accepted(): void
    {
        $today = date('Y-m-d');
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        // Submit position 10 meters away with 15m accuracy
        $response = $this->actingAs($this->user1)->post('/app/attendance/check-in', [
            'latitude' => -6.200050,
            'longitude' => 106.816666,
            'accuracy' => 15.0,
            'selfie' => \Illuminate\Http\UploadedFile::fake()->image('selfie.jpg'),
        ]);

        $response->assertRedirect();
        $record = AttendanceRecord::where('employee_id', $this->employee1->id)->first();
        $this->assertNotNull($record);
        $this->assertEquals($this->activeLocation->id, $record->attendance_location_id);
    }

    public function test_outside_radius_rejected(): void
    {
        $today = date('Y-m-d');
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        // Position 300 meters away
        $response = $this->actingAs($this->user1)->post('/app/attendance/check-in', [
            'latitude' => -6.203000,
            'longitude' => 106.816666,
            'accuracy' => 10.0,
            'selfie' => \Illuminate\Http\UploadedFile::fake()->image('selfie.jpg'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('attendance_records', [
            'employee_id' => $this->employee1->id,
        ]);
    }

    public function test_poor_gps_accuracy_rejected(): void
    {
        $today = date('Y-m-d');
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        // Accuracy 145m (max allowed is 100m)
        $response = $this->actingAs($this->user1)->post('/app/attendance/check-in', [
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'accuracy' => 145.0,
            'selfie' => \Illuminate\Http\UploadedFile::fake()->image('selfie.jpg'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_invalid_latitude_rejected(): void
    {
        $today = date('Y-m-d');
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        $response = $this->actingAs($this->user1)->post('/app/attendance/check-in', [
            'latitude' => 120.0, // Invalid latitude (> 90)
            'longitude' => 106.816666,
            'accuracy' => 10.0,
            'selfie' => \Illuminate\Http\UploadedFile::fake()->image('selfie.jpg'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_invalid_longitude_rejected(): void
    {
        $today = date('Y-m-d');
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        $response = $this->actingAs($this->user1)->post('/app/attendance/check-in', [
            'latitude' => -6.200000,
            'longitude' => 200.0, // Invalid longitude (> 180)
            'accuracy' => 10.0,
            'selfie' => \Illuminate\Http\UploadedFile::fake()->image('selfie.jpg'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_inactive_attendance_location_rejected(): void
    {
        $this->activeLocation->update(['is_active' => false]);

        $today = date('Y-m-d');
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        $response = $this->actingAs($this->user1)->post('/app/attendance/check-in', [
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'accuracy' => 10.0,
            'selfie' => \Illuminate\Http\UploadedFile::fake()->image('selfie.jpg'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_server_recalculates_distance(): void
    {
        $today = date('Y-m-d');
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        // Submit position with forged client distance parameter (e.g. distance = 0)
        $this->actingAs($this->user1)->post('/app/attendance/check-in', [
            'latitude' => -6.200100, // ~11 meters away
            'longitude' => 106.816666,
            'accuracy' => 10.0,
            'distance' => 0.0, // Forged distance
            'selfie' => \Illuminate\Http\UploadedFile::fake()->image('selfie.jpg'),
        ]);

        $record = AttendanceRecord::where('employee_id', $this->employee1->id)->first();
        $this->assertNotNull($record);
        $this->assertGreaterThan(5.0, $record->check_in_distance_meters); // Server calculated real distance
    }

    public function test_client_supplied_distance_ignored(): void
    {
        $today = date('Y-m-d');
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        // Client position is outside radius (~500m), but client sends distance = 1.0m
        $response = $this->actingAs($this->user1)->post('/app/attendance/check-in', [
            'latitude' => -6.205000,
            'longitude' => 106.816666,
            'accuracy' => 10.0,
            'distance' => 1.0,
            'selfie' => \Illuminate\Http\UploadedFile::fake()->image('selfie.jpg'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_employee_cannot_submit_gps_attendance_for_another_employee(): void
    {
        $today = date('Y-m-d');
        EmployeeSchedule::create([
            'employee_id' => $this->employee2->id,
            'work_date' => $today,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        // User 1 sends employee_id = employee2->id in payload
        $this->actingAs($this->user1)->post('/app/attendance/check-in', [
            'employee_id' => $this->employee2->id,
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'accuracy' => 10.0,
            'selfie' => \Illuminate\Http\UploadedFile::fake()->image('selfie.jpg'),
        ]);

        // Must NOT check in for Employee 2
        $this->assertDatabaseMissing('attendance_records', [
            'employee_id' => $this->employee2->id,
        ]);
    }

    public function test_check_in_gps_evidence_persisted(): void
    {
        $today = date('Y-m-d');
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        $this->actingAs($this->user1)->post('/app/attendance/check-in', [
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'accuracy' => 12.5,
            'selfie' => \Illuminate\Http\UploadedFile::fake()->image('selfie.jpg'),
        ]);

        $record = AttendanceRecord::where('employee_id', $this->employee1->id)->first();
        $this->assertNotNull($record);
        $this->assertEquals(-6.200000, (float) $record->check_in_latitude);
        $this->assertEquals(106.816666, (float) $record->check_in_longitude);
        $this->assertEquals(12.5, (float) $record->check_in_accuracy_meters);
        $this->assertNotNull($record->check_in_ip);
        $this->assertNotNull($record->check_in_user_agent);
    }

    public function test_checkout_geofence_required_when_setting_enabled(): void
    {
        DB::table('app_settings')->updateOrInsert(
            ['key' => 'attendance_require_checkout_geofence'],
            ['value' => '1', 'type' => 'boolean']
        );

        $today = '2026-08-11';
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-08-11 08:00:00', 'Asia/Jakarta'));
        $this->actingAs($this->user1)->post('/app/attendance/check-in', [
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'accuracy' => 10.0,
            'selfie' => \Illuminate\Http\UploadedFile::fake()->image('selfie.jpg'),
        ]);

        // Checkout outside radius (300m away)
        Carbon::setTestNow(Carbon::parse('2026-08-11 16:00:00', 'Asia/Jakarta'));
        $response = $this->actingAs($this->user1)->post('/app/attendance/check-out', [
            'latitude' => -6.203000,
            'longitude' => 106.816666,
            'accuracy' => 10.0,
            'selfie' => \Illuminate\Http\UploadedFile::fake()->image('selfie.jpg'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        Carbon::setTestNow();
    }

    public function test_checkout_geofence_optional_when_setting_disabled(): void
    {
        DB::table('app_settings')->updateOrInsert(
            ['key' => 'attendance_require_checkout_geofence'],
            ['value' => '0', 'type' => 'boolean']
        );

        $today = '2026-08-11';
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-08-11 08:00:00', 'Asia/Jakarta'));
        $this->actingAs($this->user1)->post('/app/attendance/check-in', [
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'accuracy' => 10.0,
            'selfie' => \Illuminate\Http\UploadedFile::fake()->image('selfie.jpg'),
        ]);

        // Checkout outside radius with setting disabled -> accepted & evidence saved
        Carbon::setTestNow(Carbon::parse('2026-08-11 16:00:00', 'Asia/Jakarta'));
        $response = $this->actingAs($this->user1)->post('/app/attendance/check-out', [
            'latitude' => -6.203000,
            'longitude' => 106.816666,
            'accuracy' => 10.0,
            'selfie' => \Illuminate\Http\UploadedFile::fake()->image('selfie.jpg'),
        ]);

        $response->assertRedirect();
        $record = AttendanceRecord::where('employee_id', $this->employee1->id)->first();
        $this->assertNotNull($record->check_out_at);
        $this->assertEquals(-6.203000, (float) $record->check_out_latitude);

        Carbon::setTestNow();
    }

    public function test_cross_midnight_attendance_still_works_with_geofence(): void
    {
        $today = '2026-08-11';
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => $this->shiftNight->id, // 20:00 - 04:00
            'schedule_type' => 'work',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-08-11 20:00:00', 'Asia/Jakarta'));
        $this->actingAs($this->user1)->post('/app/attendance/check-in', [
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'accuracy' => 10.0,
            'selfie' => \Illuminate\Http\UploadedFile::fake()->image('selfie.jpg'),
        ]);

        Carbon::setTestNow(Carbon::parse('2026-08-12 04:00:00', 'Asia/Jakarta'));
        $this->actingAs($this->user1)->post('/app/attendance/check-out', [
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'accuracy' => 10.0,
            'selfie' => \Illuminate\Http\UploadedFile::fake()->image('selfie.jpg'),
        ]);

        $record = AttendanceRecord::where('employee_id', $this->employee1->id)->first();
        $this->assertNotNull($record->check_out_at);
        $this->assertEquals('2026-08-11', $record->work_date->format('Y-m-d'));

        Carbon::setTestNow();
    }
}
