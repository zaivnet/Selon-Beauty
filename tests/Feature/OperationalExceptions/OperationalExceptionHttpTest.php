<?php

namespace Tests\Feature\OperationalExceptions;

use App\Models\AppSetting;
use App\Models\BackupRecord;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OperationalExceptionHttpTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $employeeUser;

    private Employee $employee;

    private Shift $shift;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-08-14 09:00:00', config('app.timezone')));
        $this->admin = $this->user('Admin Operasional', 'admin-ops-http@example.test', 'admin');
        $this->employee = Employee::create([
            'employee_code' => 'OPS-HTTP', 'full_name' => 'Maya Exception', 'status' => 'active',
        ]);
        $this->employeeUser = $this->user('Maya Exception', 'maya-ops-http@example.test', 'employee', $this->employee->id);
        $this->shift = Shift::create([
            'name' => 'Shift Pagi', 'code' => 'OPS-H', 'start_time' => '08:00', 'end_time' => '17:00',
            'check_in_open_minutes_before' => 60, 'check_in_close_minutes_after' => 120,
            'check_out_open_minutes_before' => 60, 'is_active' => true,
        ]);
    }

    public function test_admin_can_open_operational_dashboard_and_exception_center(): void
    {
        $this->schedule('2026-08-14');

        $this->actingAs($this->admin)->get(route('admin.dashboard'))
            ->assertOk()->assertSee('Status Operasional Hari Ini')->assertSee('Perlu Perhatian')
            ->assertSee('Pusat Perhatian')->assertDontSee('Backup operasional');
        $this->actingAs($this->admin)->get(route('admin.operational-exceptions.index'))
            ->assertOk()->assertSee('Pusat Perhatian')->assertSee('Maya Exception');
    }

    public function test_employee_is_denied_operational_dashboard_and_center(): void
    {
        $this->actingAs($this->employeeUser)->get(route('admin.dashboard'))
            ->assertRedirect(route('employee.dashboard'));
        $this->actingAs($this->employeeUser)->get(route('admin.operational-exceptions.index'))
            ->assertRedirect(route('employee.dashboard'));
    }

    public function test_date_severity_and_category_filters_work(): void
    {
        $this->schedule('2026-08-13');

        $response = $this->actingAs($this->admin)->get(route('admin.operational-exceptions.index', [
            'date' => '2026-08-13', 'severity' => 'critical', 'category' => 'absent',
        ]));

        $response->assertOk()->assertSee('Maya Exception')->assertSee('TIDAK HADIR', false)
            ->assertSee('value="2026-08-13"', false);
    }

    public function test_future_date_is_rejected_by_server_side_validation(): void
    {
        $this->actingAs($this->admin)->get(route('admin.operational-exceptions.index', ['date' => '2026-08-15']))
            ->assertSessionHasErrors('date');
    }

    public function test_exception_deep_link_targets_existing_attendance_monitoring_route(): void
    {
        $this->schedule('2026-08-14');

        $response = $this->actingAs($this->admin)->get(route('admin.operational-exceptions.index', [
            'category' => 'pending_check_in',
        ]));

        $response->assertOk()->assertSee(route('admin.attendance.index', [
            'date' => '2026-08-14', 'employee_id' => $this->employee->id,
        ]), false);
    }

    public function test_empty_state_and_mobile_architecture_are_rendered(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.operational-exceptions.index'));

        $response->assertOk()->assertSee('Operasional hari ini aman')
            ->assertSee('lg:hidden', false)->assertSee('ios-date-field', false)
            ->assertSee('min-h-11', false);
    }

    public function test_owner_sees_backup_health_while_admin_does_not(): void
    {
        AppSetting::set('backup_scheduled_enabled', '1', 'boolean');
        BackupRecord::create([
            'backup_uuid' => 'ops-http-failed', 'type' => 'full', 'file_path' => 'private/backups/failed.zip',
            'file_size' => 0, 'status' => 'failed', 'is_pre_restore' => false,
        ]);
        $owner = $this->user('Owner Operasional', 'owner-ops-http@example.test', 'owner');

        $this->actingAs($owner)->get(route('admin.dashboard'))
            ->assertOk()->assertSee('Backup operasional')->assertSee('Backup terbaru gagal');
        $this->actingAs($this->admin)->get(route('admin.dashboard'))
            ->assertOk()->assertDontSee('Backup operasional')->assertDontSee('Backup terbaru gagal');
    }

    private function schedule(string $date): EmployeeSchedule
    {
        return EmployeeSchedule::create([
            'employee_id' => $this->employee->id, 'work_date' => $date,
            'shift_id' => $this->shift->id, 'schedule_type' => 'work',
        ]);
    }

    private function user(string $name, string $email, string $role, ?int $employeeId = null): User
    {
        return User::create([
            'employee_id' => $employeeId, 'name' => $name, 'email' => $email,
            'password' => Hash::make('password'), 'role' => $role, 'is_active' => true,
        ]);
    }
}
