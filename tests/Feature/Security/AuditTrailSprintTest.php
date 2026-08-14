<?php

namespace Tests\Feature\Security;

use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use LogicException;
use ReflectionClass;
use Tests\TestCase;

class AuditTrailSprintTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_page_authorization_and_filters_work(): void
    {
        $employee = Employee::create(['employee_code' => 'AUD-01', 'full_name' => 'Audit Employee', 'status' => 'active']);
        $owner = User::create(['name' => 'Owner', 'email' => 'audit-owner@example.test', 'password' => Hash::make('password'), 'role' => 'owner', 'is_active' => true]);
        $admin = User::create(['name' => 'Admin', 'email' => 'audit-admin@example.test', 'password' => Hash::make('password'), 'role' => 'admin', 'is_active' => true]);
        AuditLog::log('attendance.corrected', new AttendanceRecord(['id' => 10]), ['worked_minutes' => 1], ['worked_minutes' => 2], $owner, 'Koreksi audit', ['employee_id' => $employee->id]);
        AuditLog::log('backup.created', null, null, null, $owner);

        $today = now()->toDateString();
        $response = $this->actingAs($owner)->get(route('admin.audit-logs.index', [
            'module' => 'attendance', 'action' => 'corrected', 'employee_id' => $employee->id,
            'user_id' => $owner->id, 'date_from' => $today, 'date_to' => $today,
        ]));
        $response->assertOk()->assertSee('attendance.corrected');
        $this->assertSame(1, $response->viewData('logs')->total());
        $this->actingAs($admin)->get(route('admin.audit-logs.index'))->assertForbidden();
    }

    public function test_audit_records_are_immutable_through_model_operations(): void
    {
        $log = AuditLog::create(['action' => 'attendance.corrected', 'created_at' => now()]);
        try {
            $log->update(['action' => 'tampered']);
            $this->fail('Update audit seharusnya ditolak.');
        } catch (LogicException) {
            $this->assertSame('attendance.corrected', $log->fresh()->action);
        }

        $this->expectException(LogicException::class);
        $log->delete();
    }

    public function test_backup_configuration_and_schema_include_audit_correction_metadata(): void
    {
        $reflection = new ReflectionClass(BackupService::class);
        $property = $reflection->getProperty('applicationTables');
        $tables = $property->getValue(app(BackupService::class));
        $this->assertContains('audit_logs', $tables);
        $this->assertContains('attendance_records', $tables);
        $this->assertContains('overtime_sessions', $tables);
        $this->assertTrue(Schema::hasColumns('audit_logs', ['reason', 'metadata']));
        $this->assertTrue(Schema::hasColumns('attendance_records', ['corrected_at', 'corrected_by']));
        $this->assertTrue(Schema::hasColumns('overtime_sessions', ['completion_source', 'completed_by_user_id', 'corrected_at', 'corrected_by']));
    }
}
