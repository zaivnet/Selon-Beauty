<?php

namespace Tests\Feature\WorkCalendar;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\EmployeeScheduleOverride;
use App\Models\Holiday;
use App\Models\Shift;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use Tests\TestCase;

class WorkCalendarManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $employeeUser;

    private Employee $employee;

    private Shift $shift;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::create(['name' => 'Admin Kalender', 'email' => 'admin-cal@example.test', 'password' => Hash::make('password'), 'role' => 'admin', 'is_active' => true]);
        $this->employee = Employee::create(['employee_code' => 'CAL-M-01', 'full_name' => 'Maya', 'status' => 'active']);
        $this->employeeUser = User::create([
            'employee_id' => $this->employee->id, 'name' => 'Maya', 'email' => 'maya-cal@example.test',
            'password' => Hash::make('password'), 'role' => 'employee', 'is_active' => true,
        ]);
        $this->shift = Shift::create([
            'name' => 'Pagi', 'code' => 'MGT-P', 'start_time' => '08:00', 'end_time' => '17:00',
            'check_in_open_minutes_before' => 60, 'check_in_close_minutes_after' => 60,
            'check_out_open_minutes_before' => 60, 'grace_period_minutes' => 5,
            'break_minutes' => 60, 'crosses_midnight' => false, 'is_active' => true,
        ]);
    }

    public function test_admin_can_manage_calendar_and_changes_are_audited(): void
    {
        $this->actingAs($this->admin)->post(route('admin.work-calendar.store'), [
            'date' => '2026-08-17', 'type' => 'company_holiday', 'name' => 'Libur Perusahaan',
            'description' => 'Toko tutup', 'audit_reason' => 'Keputusan operasional',
        ])->assertRedirect();

        $day = Holiday::firstOrFail();
        $this->assertSame('2026-08-17', $day->date->format('Y-m-d'));
        $this->assertSame('company_holiday', $day->type);
        $this->assertFalse($day->is_working_day);
        $log = AuditLog::where('action', 'work_calendar.created')->firstOrFail();
        $this->assertSame('Keputusan operasional', $log->reason);
        $this->assertStringStartsWith('2026-08-17', $log->after_data['date']);

        $this->actingAs($this->admin)->put(route('admin.work-calendar.update', $day), [
            'date' => '2026-08-17', 'type' => 'special_working_day', 'name' => 'Buka Khusus',
            'description' => null, 'audit_reason' => 'Perubahan operasional',
        ])->assertRedirect();
        $this->assertDatabaseHas('audit_logs', ['action' => 'work_calendar.updated']);

        $this->actingAs($this->admin)->delete(route('admin.work-calendar.destroy', $day), [
            'reason' => 'Kalender dibatalkan kembali',
        ])->assertRedirect();
        $this->assertDatabaseHas('audit_logs', ['action' => 'work_calendar.deleted']);
        $this->assertDatabaseMissing('holidays', ['id' => $day->id]);
    }

    public function test_employee_cannot_manage_calendar_server_side(): void
    {
        $this->actingAs($this->employeeUser)->post('/admin/work-calendar', [
            'date' => '2026-08-17', 'type' => 'company_holiday', 'name' => 'Tidak Sah', 'audit_reason' => 'Tidak berwenang',
        ])->assertRedirect();
        $this->assertDatabaseCount('holidays', 0);
    }

    public function test_override_is_audited_and_employee_is_notified_with_valid_deep_link(): void
    {
        $this->actingAs($this->admin)->post(route('admin.schedule-overrides.store'), [
            'employee_id' => $this->employee->id, 'date' => '2026-08-17', 'override_type' => 'work',
            'shift_id' => $this->shift->id, 'reason' => 'Masuk untuk stok opname',
        ])->assertRedirect();

        $override = EmployeeScheduleOverride::firstOrFail();
        $log = AuditLog::where('action', 'schedule_override.created')->firstOrFail();
        $this->assertSame($this->employee->id, $log->metadata['employee_id']);
        $notification = $this->employeeUser->notifications()->firstOrFail();
        $this->assertSame('Jadwal Anda berubah', $notification->data['title']);
        $this->assertStringContainsString('/app/schedules', $notification->data['target_url']);
        $this->assertSame('work', $override->override_type);
    }

    public function test_admin_can_preview_current_effective_schedule_before_override(): void
    {
        EmployeeSchedule::create([
            'employee_id' => $this->employee->id, 'work_date' => '2026-08-17',
            'schedule_type' => 'work', 'shift_id' => $this->shift->id,
        ]);

        $this->actingAs($this->admin)->getJson(route('admin.work-calendar.effective-preview', [
            'employee_id' => $this->employee->id, 'date' => '2026-08-17',
        ]))->assertOk()->assertJsonPath('source', 'regular_schedule')
            ->assertJsonPath('regular.shift', $this->shift->name);

        $this->actingAs($this->employeeUser)->getJson(route('admin.work-calendar.effective-preview', [
            'employee_id' => $this->employee->id, 'date' => '2026-08-17',
        ]))->assertForbidden();

        $this->actingAs($this->admin)->get(route('admin.work-calendar.index'))
            ->assertOk()->assertSee('override-context', false)
            ->assertSee('effective-preview', false);
    }

    public function test_override_double_submit_is_safe(): void
    {
        $payload = ['employee_id' => $this->employee->id, 'date' => '2026-08-17', 'override_type' => 'off', 'reason' => 'Libur khusus pegawai'];
        $this->actingAs($this->admin)->post(route('admin.schedule-overrides.store'), $payload)->assertRedirect();
        $this->actingAs($this->admin)->post(route('admin.schedule-overrides.store'), $payload)->assertRedirect()->assertSessionHas('error');

        $this->assertDatabaseCount('employee_schedule_overrides', 1);
        $this->assertSame(1, AuditLog::where('action', 'schedule_override.created')->count());
    }

    public function test_calendar_and_override_tables_are_in_full_backup_configuration(): void
    {
        $reflection = new ReflectionClass(BackupService::class);
        $property = $reflection->getProperty('applicationTables');
        $tables = $property->getValue(app(BackupService::class));

        $this->assertContains('holidays', $tables);
        $this->assertContains('employee_schedule_overrides', $tables);
        $this->assertTrue(Schema::hasColumns('holidays', ['type', 'is_working_day', 'created_by']));
        $this->assertTrue(Schema::hasTable('employee_schedule_overrides'));
    }

    public function test_employee_weekly_schedule_shows_effective_holiday_and_override(): void
    {
        $date = now(config('app.timezone'))->startOfWeek()->format('Y-m-d');
        Holiday::create(['date' => $date, 'type' => 'public_holiday', 'name' => 'Libur Nasional Test', 'is_working_day' => false]);
        EmployeeScheduleOverride::create([
            'employee_id' => $this->employee->id, 'date' => now(config('app.timezone'))->startOfWeek()->addDay()->format('Y-m-d'),
            'override_type' => 'off', 'reason' => 'Libur khusus jadwal test',
        ]);

        $this->actingAs($this->employeeUser)->get(route('employee.schedules.index', ['start_date' => $date]))
            ->assertOk()->assertSee('LIBUR KHUSUS')->assertSee('Libur Nasional Test');
    }
}
