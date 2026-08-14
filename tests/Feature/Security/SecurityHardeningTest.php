<?php

namespace Tests\Feature\Security;

use App\Models\AttendanceLocation;
use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\LeaveRequest;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected User $ownerUser;

    protected User $employeeUserA;

    protected Employee $employeeA;

    protected User $employeeUserB;

    protected Employee $employeeB;

    protected Shift $shift;

    protected AttendanceLocation $location;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::today('Asia/Jakarta')->setTime(8, 0));

        Storage::fake('local');

        $this->ownerUser = User::create([
            'name' => 'Owner Utama',
            'email' => 'owner@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->employeeA = Employee::create([
            'employee_code' => 'SB-001',
            'full_name' => 'Ayu Pratama',
            'status' => 'active',
        ]);

        $this->employeeUserA = User::create([
            'employee_id' => $this->employeeA->id,
            'name' => 'Ayu Pratama',
            'email' => 'ayu@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        $this->employeeB = Employee::create([
            'employee_code' => 'SB-002',
            'full_name' => 'Budi Santoso',
            'status' => 'active',
        ]);

        $this->employeeUserB = User::create([
            'employee_id' => $this->employeeB->id,
            'name' => 'Budi Santoso',
            'email' => 'budi@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        $this->shift = Shift::create([
            'name' => 'Shift Pagi',
            'code' => 'PAGI',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'grace_period_minutes' => 5,
            'break_minutes' => 60,
            'is_active' => true,
        ]);

        $this->location = AttendanceLocation::create([
            'name' => 'SELON BEAUTY Utama',
            'latitude' => -6.2000000,
            'longitude' => 106.8166667,
            'radius_meters' => 100,
            'max_accuracy_meters' => 100,
            'is_active' => true,
        ]);
    }

    public function test_employee_cannot_access_admin_routes(): void
    {
        $response = $this->actingAs($this->employeeUserA)->get(route('admin.dashboard'));
        $response->assertRedirect(route('employee.dashboard'));

        $responseJson = $this->actingAs($this->employeeUserA)
            ->getJson(route('admin.audit-logs.index'));
        $responseJson->assertForbidden();
    }

    public function test_inactive_employee_cannot_authenticate(): void
    {
        $this->employeeUserA->update(['is_active' => false]);

        $response = $this->post(route('login'), [
            'login' => 'ayu@selonbeauty.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_employee_cannot_access_another_employee_selfie(): void
    {
        $schedule = EmployeeSchedule::create([
            'employee_id' => $this->employeeB->id,
            'work_date' => now('Asia/Jakarta')->toDateString(),
            'shift_id' => $this->shift->id,
            'schedule_type' => 'work',
        ]);

        $selfieFile = UploadedFile::fake()->image('selfieB.jpg');
        $selfiePath = Storage::disk('local')->putFile('selfies', $selfieFile);

        $attendanceB = AttendanceRecord::create([
            'employee_id' => $this->employeeB->id,
            'work_schedule_id' => $schedule->id,
            'work_date' => now('Asia/Jakarta')->toDateString(),
            'attendance_location_id' => $this->location->id,
            'status' => 'present',
            'check_in_at' => now('Asia/Jakarta'),
            'check_in_selfie_path' => $selfiePath,
        ]);

        // Employee A tries to access Employee B's selfie -> 403 Forbidden
        $response = $this->actingAs($this->employeeUserA)->get(route('attendance.selfie', ['record' => $attendanceB->id, 'type' => 'check_in']));
        $response->assertForbidden();

        // Owner tries to access Employee B's selfie -> 200 OK
        $responseOwner = $this->actingAs($this->ownerUser)->get(route('attendance.selfie', ['record' => $attendanceB->id, 'type' => 'check_in']));
        $responseOwner->assertOk();
    }

    public function test_employee_cannot_access_another_employee_leave_attachment(): void
    {
        $file = UploadedFile::fake()->create('surat_dokter.pdf', 100, 'application/pdf');
        $attachmentPath = Storage::disk('local')->putFile('leave-attachments', $file);

        $leaveB = LeaveRequest::create([
            'employee_id' => $this->employeeB->id,
            'type' => 'sick',
            'start_date' => now('Asia/Jakarta')->toDateString(),
            'end_date' => now('Asia/Jakarta')->addDays(2)->toDateString(),
            'reason' => 'Demam tinggi',
            'attachment_path' => $attachmentPath,
            'status' => 'pending',
        ]);

        // Employee A tries to view Employee B's attachment -> 403 Forbidden
        $response = $this->actingAs($this->employeeUserA)->get(route('leave-requests.attachment', $leaveB->id));
        $response->assertForbidden();

        // Owner tries to view Employee B's attachment -> 200 OK
        $responseOwner = $this->actingAs($this->ownerUser)->get(route('leave-requests.attachment', $leaveB->id));
        $responseOwner->assertOk();
    }

    public function test_employee_cannot_access_another_employee_notification(): void
    {
        $notificationB = DatabaseNotification::create([
            'id' => (string) Str::uuid(),
            'type' => 'App\Notifications\LeaveRequestSubmittedNotification',
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $this->employeeUserB->id,
            'data' => ['title' => 'Pengajuan Izin', 'message' => 'Pengajuan izin berhasil'],
            'created_at' => now(),
        ]);

        // Employee A tries to mark read Employee B's notification -> 403 Forbidden
        $response = $this->actingAs($this->employeeUserA)->post(route('notifications.read', $notificationB->id));
        $response->assertForbidden();
    }

    public function test_login_rate_limiting_works(): void
    {
        $throttleKey = Str::transliterate('baduser@selonbeauty.com|127.0.0.1');
        RateLimiter::clear($throttleKey);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login'), [
                'login' => 'baduser@selonbeauty.com',
                'password' => 'wrongpassword',
            ]);
        }

        // 6th attempt should trigger 422 Rate limit error
        $response = $this->post(route('login'), [
            'login' => 'baduser@selonbeauty.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertStringContainsString('Terlalu banyak percobaan login', session('errors')->get('login')[0]);
    }

    public function test_attendance_check_in_ignores_forged_employee_id(): void
    {
        $todayStr = now('Asia/Jakarta')->toDateString();

        EmployeeSchedule::create([
            'employee_id' => $this->employeeA->id,
            'work_date' => $todayStr,
            'shift_id' => $this->shift->id,
            'schedule_type' => 'work',
        ]);

        $selfieFile = UploadedFile::fake()->image('selfieA.jpg');

        // Employee A sends malicious forged `employee_id = 999` payload
        $response = $this->actingAs($this->employeeUserA)->post(route('employee.attendance.check-in'), [
            'employee_id' => 999,
            'latitude' => -6.2000000,
            'longitude' => 106.8166667,
            'accuracy' => 10,
            'selfie' => $selfieFile,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('attendance_records', [
            'employee_id' => $this->employeeA->id, // Server enforced employeeA id, ignoring forged 999!
        ]);
    }

    public function test_attendance_correction_requires_authorization_and_reason(): void
    {
        $todayStr = now('Asia/Jakarta')->toDateString();

        $schedule = EmployeeSchedule::create([
            'employee_id' => $this->employeeA->id,
            'work_date' => $todayStr,
            'shift_id' => $this->shift->id,
            'schedule_type' => 'work',
        ]);

        $attendance = AttendanceRecord::create([
            'employee_id' => $this->employeeA->id,
            'work_schedule_id' => $schedule->id,
            'work_date' => $todayStr,
            'status' => 'present',
            'check_in_at' => Carbon::parse($todayStr.' 08:00:00', 'Asia/Jakarta'),
        ]);

        // 1. Employee A tries to correct attendance -> Redirected with error to employee dashboard
        $responseEmp = $this->actingAs($this->employeeUserA)->post(route('admin.attendance.correct', $attendance->id), [
            'reason' => 'Salah jam masuk',
            'check_in_at' => '08:30',
        ]);
        $responseEmp->assertRedirect(route('employee.dashboard'));

        // 2. Owner tries to correct without reason -> Validation Error
        $responseNoReason = $this->actingAs($this->ownerUser)->post(route('admin.attendance.correct', $attendance->id), [
            'reason' => '',
            'check_in_at' => '08:30',
        ]);
        $responseNoReason->assertSessionHasErrors('reason');
    }

    public function test_attendance_correction_creates_audit_log_and_recalculates_metrics(): void
    {
        $todayStr = now('Asia/Jakarta')->toDateString();

        $schedule = EmployeeSchedule::create([
            'employee_id' => $this->employeeA->id,
            'work_date' => $todayStr,
            'shift_id' => $this->shift->id,
            'schedule_type' => 'work',
        ]);

        $attendance = AttendanceRecord::create([
            'employee_id' => $this->employeeA->id,
            'work_schedule_id' => $schedule->id,
            'work_date' => $todayStr,
            'status' => 'present',
            'check_in_at' => Carbon::parse($todayStr.' 08:00:00', 'Asia/Jakarta'),
            'check_out_at' => Carbon::parse($todayStr.' 16:00:00', 'Asia/Jakarta'),
            'late_minutes' => 0,
            'worked_minutes' => 420,
        ]);

        // Owner corrects check-in time to 08:30 (Late by 30 mins, grace period is 5m)
        $response = $this->actingAs($this->ownerUser)->post(route('admin.attendance.correct', $attendance->id), [
            'reason' => 'Koreksi terlambat 30 menit karena macet parah',
            'check_in_at' => '08:30',
            'check_out_at' => '16:00',
            'status' => 'late',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $attendance->refresh();
        $this->assertTrue($attendance->is_manually_adjusted);
        $this->assertEquals('late', $attendance->status);
        $this->assertEquals(25, $attendance->late_minutes);

        // Audit Log created
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'attendance.corrected',
            'auditable_type' => AttendanceRecord::class,
            'auditable_id' => $attendance->id,
            'user_id' => $this->ownerUser->id,
        ]);
    }

    public function test_audit_log_sensitive_fields_redacted(): void
    {
        AuditLog::log('user.password_reset', $this->employeeUserA, [
            'password' => 'secret123',
            'email' => 'ayu@selonbeauty.com',
        ], [
            'password' => 'newsecret456',
            'email' => 'ayu@selonbeauty.com',
        ], $this->ownerUser);

        $log = AuditLog::where('action', 'user.password_reset')->latest()->first();
        $this->assertNotNull($log);
        $this->assertEquals('[REDACTED]', $log->before_data['password']);
        $this->assertEquals('[REDACTED]', $log->after_data['password']);
        $this->assertEquals('ayu@selonbeauty.com', $log->before_data['email']);
    }

    public function test_csv_formula_injection_mitigated(): void
    {
        $todayStr = now('Asia/Jakarta')->toDateString();

        $schedule = EmployeeSchedule::create([
            'employee_id' => $this->employeeA->id,
            'work_date' => $todayStr,
            'shift_id' => $this->shift->id,
            'schedule_type' => 'work',
        ]);

        AttendanceRecord::create([
            'employee_id' => $this->employeeA->id,
            'work_schedule_id' => $schedule->id,
            'work_date' => $todayStr,
            'status' => 'present',
            'check_in_at' => Carbon::parse($todayStr.' 08:00:00', 'Asia/Jakarta'),
        ]);

        $this->employeeA->update(['full_name' => '=CMD|"/C calc"!A0']);

        $response = $this->actingAs($this->ownerUser)->get(route('admin.reports.attendance.export-csv', [
            'start_date' => $todayStr,
            'end_date' => $todayStr,
        ]));

        $response->assertOk();
        $this->assertStringContainsString("'=CMD|", $response->streamedContent());
    }
}
