<?php

namespace Tests\Feature\Employee;

use App\Models\AppSetting;
use App\Models\AttendanceLocation;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SelfieAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected Employee $employee1;

    protected User $user1;

    protected Employee $employee2;

    protected User $user2;

    protected User $ownerUser;

    protected Shift $shiftNormal;

    protected Shift $shiftNight;

    protected AttendanceLocation $activeLocation;

    protected array $validGps = [
        'latitude' => -6.200000,
        'longitude' => 106.816666,
        'accuracy' => 10.0,
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Carbon::setTestNow(Carbon::today('Asia/Jakarta')->setTime(8, 0));

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

        $this->ownerUser = User::create([
            'name' => 'Owner Utama',
            'email' => 'owner@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->activeLocation = AttendanceLocation::create([
            'name' => 'SELON BEAUTY',
            'address' => 'Jl. Kebon Jeruk No. 12',
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

    public function test_selfie_required_for_check_in(): void
    {
        AppSetting::set('attendance_require_selfie', true, 'boolean');
        $today = date('Y-m-d');
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        // Submit without selfie
        $response = $this->actingAs($this->user1)->post('/app/attendance/check-in', $this->validGps);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Foto selfie wajib diambil sebelum melakukan absensi.');
        $this->assertDatabaseMissing('attendance_records', [
            'employee_id' => $this->employee1->id,
        ]);
    }

    public function test_manipulated_json_check_in_without_required_selfie_is_rejected_server_side(): void
    {
        AppSetting::set('attendance_require_selfie', true, 'boolean');
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => date('Y-m-d'),
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        $this->actingAs($this->user1)
            ->postJson('/app/attendance/check-in', $this->validGps)
            ->assertStatus(422)
            ->assertJsonPath('message', 'Foto selfie wajib diambil sebelum melakukan absensi.');

        $this->assertDatabaseCount('attendance_records', 0);
    }

    public function test_selfie_required_for_check_out_and_rejection_keeps_attendance_open(): void
    {
        AppSetting::set('attendance_require_selfie', true, 'boolean');
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => date('Y-m-d'),
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);
        $this->actingAs($this->user1)->post('/app/attendance/check-in', array_merge($this->validGps, [
            'selfie' => UploadedFile::fake()->image('check-in.jpg'),
        ]));
        $record = AttendanceRecord::where('employee_id', $this->employee1->id)->firstOrFail();

        Carbon::setTestNow(Carbon::today('Asia/Jakarta')->setTime(16, 0));
        $this->actingAs($this->user1)->post('/app/attendance/check-out', $this->validGps)
            ->assertSessionHas('error', 'Foto selfie wajib diambil sebelum melakukan absensi.');

        $this->assertNull($record->fresh()->check_out_at);
        $this->assertNull($record->fresh()->check_out_selfie_path);
    }

    public function test_malformed_base64_selfie_is_rejected_without_creating_attendance(): void
    {
        AppSetting::set('attendance_require_selfie', true, 'boolean');
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => date('Y-m-d'),
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        $this->actingAs($this->user1)->post('/app/attendance/check-in', array_merge($this->validGps, [
            'selfie_base64' => 'data:image/jpeg;base64,not-valid-base64***',
        ]))->assertSessionHas('error');

        $this->assertDatabaseCount('attendance_records', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_invalid_selfie_mime_rejected(): void
    {
        $today = date('Y-m-d');
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        $svgFile = UploadedFile::fake()->create('vector.svg', 10, 'image/svg+xml');

        $response = $this->actingAs($this->user1)->post('/app/attendance/check-in', array_merge($this->validGps, [
            'selfie' => $svgFile,
        ]));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('attendance_records', [
            'employee_id' => $this->employee1->id,
        ]);
    }

    public function test_oversized_selfie_rejected(): void
    {
        $today = date('Y-m-d');
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        // File larger than 5MB (6MB = 6144KB)
        $hugeFile = UploadedFile::fake()->create('huge.jpg', 6144, 'image/jpeg');

        $response = $this->actingAs($this->user1)->post('/app/attendance/check-in', array_merge($this->validGps, [
            'selfie' => $hugeFile,
        ]));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('attendance_records', [
            'employee_id' => $this->employee1->id,
        ]);
    }

    public function test_selfie_stored_in_private_storage(): void
    {
        $today = date('Y-m-d');
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        $selfie = UploadedFile::fake()->image('selfie.jpg', 640, 480);

        $response = $this->actingAs($this->user1)->post('/app/attendance/check-in', array_merge($this->validGps, [
            'selfie' => $selfie,
        ]));

        $response->assertRedirect();
        $record = AttendanceRecord::where('employee_id', $this->employee1->id)->first();
        $this->assertNotNull($record);
        $this->assertNotNull($record->check_in_selfie_path);

        // Path must start with attendance/{employee_id}/...
        $this->assertStringStartsWith("attendance/{$this->employee1->id}/", $record->check_in_selfie_path);

        // File exists in local private disk
        Storage::disk('local')->assertExists($record->check_in_selfie_path);

        // File does not exist in public disk
        Storage::disk('public')->assertMissing($record->check_in_selfie_path);
    }

    public function test_check_in_and_check_out_persists_selfie_paths(): void
    {
        $today = '2026-08-11';
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        $inSelfie = UploadedFile::fake()->image('checkin_selfie.jpg');
        $outSelfie = UploadedFile::fake()->image('checkout_selfie.jpg');

        Carbon::setTestNow(Carbon::parse('2026-08-11 08:00:00', 'Asia/Jakarta'));

        // Check-in
        $this->actingAs($this->user1)->post('/app/attendance/check-in', array_merge($this->validGps, [
            'selfie' => $inSelfie,
        ]));

        $record = AttendanceRecord::where('employee_id', $this->employee1->id)->first();
        $this->assertNotNull($record->check_in_selfie_path);

        // Check-out
        Carbon::setTestNow(Carbon::parse('2026-08-11 16:00:00', 'Asia/Jakarta'));
        $this->actingAs($this->user1)->post('/app/attendance/check-out', array_merge($this->validGps, [
            'selfie' => $outSelfie,
        ]));

        $record->refresh();
        $this->assertNotNull($record->check_out_selfie_path);
        Storage::disk('local')->assertExists($record->check_in_selfie_path);
        Storage::disk('local')->assertExists($record->check_out_selfie_path);

        Carbon::setTestNow();
    }

    public function test_employee_cannot_access_another_employee_selfie(): void
    {
        $today = date('Y-m-d');
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        $selfie = UploadedFile::fake()->image('selfie.jpg');
        $this->actingAs($this->user1)->post('/app/attendance/check-in', array_merge($this->validGps, [
            'selfie' => $selfie,
        ]));

        $record = AttendanceRecord::where('employee_id', $this->employee1->id)->first();

        // Employee 2 attempts to view Employee 1's selfie
        $response = $this->actingAs($this->user2)->get("/attendance/selfie/{$record->id}/check_in");
        $response->assertStatus(403);
    }

    public function test_employee_can_access_own_selfie(): void
    {
        $today = date('Y-m-d');
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        $selfie = UploadedFile::fake()->image('selfie.jpg');
        $this->actingAs($this->user1)->post('/app/attendance/check-in', array_merge($this->validGps, [
            'selfie' => $selfie,
        ]));

        $record = AttendanceRecord::where('employee_id', $this->employee1->id)->first();

        // Employee 1 views own selfie
        $response = $this->actingAs($this->user1)->get("/attendance/selfie/{$record->id}/check_in");
        $response->assertOk();
    }

    public function test_owner_can_access_attendance_selfie(): void
    {
        $today = date('Y-m-d');
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        $selfie = UploadedFile::fake()->image('selfie.jpg');
        $this->actingAs($this->user1)->post('/app/attendance/check-in', array_merge($this->validGps, [
            'selfie' => $selfie,
        ]));

        $record = AttendanceRecord::where('employee_id', $this->employee1->id)->first();

        // Owner views Employee 1's selfie
        $response = $this->actingAs($this->ownerUser)->get("/attendance/selfie/{$record->id}/check_in");
        $response->assertOk();
    }

    public function test_superadmin_can_access_attendance_selfie(): void
    {
        $superadmin = User::create([
            'name' => 'Superadmin', 'email' => 'superadmin@example.test',
            'password' => Hash::make('password123'), 'role' => 'superadmin', 'is_active' => true,
        ]);
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id, 'work_date' => date('Y-m-d'),
            'shift_id' => $this->shiftNormal->id, 'schedule_type' => 'work',
        ]);
        $this->actingAs($this->user1)->post('/app/attendance/check-in', array_merge($this->validGps, [
            'selfie' => UploadedFile::fake()->image('superadmin-evidence.jpg'),
        ]));
        $record = AttendanceRecord::where('employee_id', $this->employee1->id)->firstOrFail();

        $this->actingAs($superadmin)->get("/attendance/selfie/{$record->id}/check_in")->assertOk();
    }

    public function test_selfie_setting_off_allows_attendance_without_selfie(): void
    {
        AppSetting::set('attendance_require_selfie', false, 'boolean');
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id, 'work_date' => date('Y-m-d'),
            'shift_id' => $this->shiftNormal->id, 'schedule_type' => 'work',
        ]);

        $this->actingAs($this->user1)->post('/app/attendance/check-in', $this->validGps);

        $record = AttendanceRecord::where('employee_id', $this->employee1->id)->firstOrFail();
        $this->assertNull($record->check_in_selfie_path);

        Carbon::setTestNow(Carbon::today('Asia/Jakarta')->setTime(16, 0));
        $this->actingAs($this->user1)->post('/app/attendance/check-out', $this->validGps);
        $record->refresh();
        $this->assertNotNull($record->check_out_at);
        $this->assertNull($record->check_out_selfie_path);
    }

    public function test_gps_still_required_when_selfie_valid(): void
    {
        $today = date('Y-m-d');
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        $selfie = UploadedFile::fake()->image('selfie.jpg');

        // Submit valid selfie but missing GPS coordinates
        $response = $this->actingAs($this->user1)->post('/app/attendance/check-in', [
            'selfie' => $selfie,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('attendance_records', [
            'employee_id' => $this->employee1->id,
        ]);
    }

    public function test_outside_radius_rejected_even_with_valid_selfie(): void
    {
        $today = date('Y-m-d');
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        $selfie = UploadedFile::fake()->image('selfie.jpg');

        // Submit valid selfie with GPS coordinates 500m outside radius
        $response = $this->actingAs($this->user1)->post('/app/attendance/check-in', [
            'latitude' => -6.205000,
            'longitude' => 106.816666,
            'accuracy' => 10.0,
            'selfie' => $selfie,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('attendance_records', [
            'employee_id' => $this->employee1->id,
        ]);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_duplicate_check_in_still_rejected(): void
    {
        $today = date('Y-m-d');
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => $this->shiftNormal->id,
            'schedule_type' => 'work',
        ]);

        $selfie1 = UploadedFile::fake()->image('selfie1.jpg');
        $selfie2 = UploadedFile::fake()->image('selfie2.jpg');

        // First check in
        $this->actingAs($this->user1)->post('/app/attendance/check-in', array_merge($this->validGps, [
            'selfie' => $selfie1,
        ]));

        // Second check in
        $response = $this->actingAs($this->user1)->post('/app/attendance/check-in', array_merge($this->validGps, [
            'selfie' => $selfie2,
        ]));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertEquals(1, AttendanceRecord::where('employee_id', $this->employee1->id)->count());
    }

    public function test_cross_midnight_attendance_still_works_with_selfie(): void
    {
        $today = '2026-08-11';
        EmployeeSchedule::create([
            'employee_id' => $this->employee1->id,
            'work_date' => $today,
            'shift_id' => $this->shiftNight->id, // 20:00 - 04:00
            'schedule_type' => 'work',
        ]);

        $inSelfie = UploadedFile::fake()->image('checkin_night.jpg');
        $outSelfie = UploadedFile::fake()->image('checkout_night.jpg');

        Carbon::setTestNow(Carbon::parse('2026-08-11 20:00:00', 'Asia/Jakarta'));
        $this->actingAs($this->user1)->post('/app/attendance/check-in', array_merge($this->validGps, [
            'selfie' => $inSelfie,
        ]));

        Carbon::setTestNow(Carbon::parse('2026-08-12 04:00:00', 'Asia/Jakarta'));
        $this->actingAs($this->user1)->post('/app/attendance/check-out', array_merge($this->validGps, [
            'selfie' => $outSelfie,
        ]));

        $record = AttendanceRecord::where('employee_id', $this->employee1->id)->first();
        $this->assertNotNull($record->check_out_at);
        $this->assertNotNull($record->check_in_selfie_path);
        $this->assertNotNull($record->check_out_selfie_path);

        Carbon::setTestNow();
    }
}
