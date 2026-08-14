<?php

namespace Tests\Feature\MonthlyRecap;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MonthlyRecapHttpTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $employeeUser;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-08-14 10:00:00', config('app.timezone')));
        $this->admin = User::create([
            'name' => 'Admin Recap', 'email' => 'admin-http-recap@example.test',
            'password' => Hash::make('password'), 'role' => 'admin', 'is_active' => true,
        ]);
        $this->employee = Employee::create(['employee_code' => 'HTTP-REC', 'full_name' => 'Maya Rekap', 'status' => 'active']);
        $this->employeeUser = User::create([
            'employee_id' => $this->employee->id, 'name' => 'Maya Rekap', 'email' => 'maya-http-recap@example.test',
            'password' => Hash::make('password'), 'role' => 'employee', 'is_active' => true,
        ]);
        $shift = Shift::create(['name' => 'Shift Pagi', 'code' => 'HTTP-P', 'start_time' => '08:00', 'end_time' => '17:00', 'is_active' => true]);
        $schedule = EmployeeSchedule::create([
            'employee_id' => $this->employee->id, 'work_date' => '2026-07-01',
            'shift_id' => $shift->id, 'schedule_type' => 'work',
        ]);
        foreach (range(2, 31) as $day) {
            EmployeeSchedule::create([
                'employee_id' => $this->employee->id,
                'work_date' => sprintf('2026-07-%02d', $day),
                'schedule_type' => 'off',
            ]);
        }
        AttendanceRecord::create([
            'employee_id' => $this->employee->id, 'work_schedule_id' => $schedule->id,
            'work_date' => '2026-07-01', 'status' => 'present',
            'check_in_at' => '2026-07-01 08:00:00', 'check_out_at' => '2026-07-01 17:00:00', 'worked_minutes' => 480,
        ]);
    }

    public function test_admin_can_view_monthly_summary_and_employee_detail(): void
    {
        $this->actingAs($this->admin)->get(route('admin.monthly-recaps.index', ['year' => 2026, 'month' => 7]))
            ->assertOk()->assertSee('Rekap Kehadiran Bulanan')->assertSee('Maya Rekap')->assertSee('READY');
        $this->actingAs($this->admin)->get(route('admin.monthly-recaps.show', [$this->employee, 'year' => 2026, 'month' => 7]))
            ->assertOk()->assertSee('Maya Rekap')->assertSee('1 Juli 2026')->assertSee('HADIR');
    }

    public function test_employee_can_only_view_own_recap_and_cannot_access_admin_recap(): void
    {
        $other = Employee::create(['employee_code' => 'HTTP-OTHER', 'full_name' => 'Karyawan Lain', 'status' => 'active']);

        $this->actingAs($this->employeeUser)->get(route('employee.monthly-recap.show', ['year' => 2026, 'month' => 7]))
            ->assertOk()->assertSee('Maya Rekap')->assertDontSee('Karyawan Lain');
        $this->actingAs($this->employeeUser)->get(route('admin.monthly-recaps.show', [$other, 'year' => 2026, 'month' => 7]))
            ->assertRedirect(route('employee.dashboard'));
    }

    public function test_summary_and_detail_csv_are_spreadsheet_friendly(): void
    {
        $summary = $this->actingAs($this->admin)->get(route('admin.monthly-recaps.export-summary', ['year' => 2026, 'month' => 7]));
        $summary->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $summaryContent = $summary->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $summaryContent);
        $this->assertStringContainsString('Effective Work Days', $summaryContent);
        $this->assertStringContainsString('Maya Rekap', $summaryContent);

        $detail = $this->actingAs($this->admin)->get(route('admin.monthly-recaps.export-detail', [
            'year' => 2026, 'month' => 7, 'employee_id' => $this->employee->id,
        ]));
        $detail->assertOk();
        $detailContent = $detail->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $detailContent);
        $this->assertStringContainsString('Regular Worked Minutes', $detailContent);
        $this->assertStringContainsString('2026-07-01', $detailContent);
    }

    public function test_employee_csv_contains_only_authenticated_employee(): void
    {
        Employee::create(['employee_code' => 'HTTP-OTHER', 'full_name' => 'Karyawan Lain', 'status' => 'active']);
        $response = $this->actingAs($this->employeeUser)->get(route('employee.monthly-recap.export-csv', ['year' => 2026, 'month' => 7]));
        $content = $response->streamedContent();
        $this->assertStringContainsString('Maya Rekap', $content);
        $this->assertStringNotContainsString('Karyawan Lain', $content);
    }

    public function test_admin_and_employee_print_views_are_available(): void
    {
        $this->actingAs($this->admin)->get(route('admin.monthly-recaps.print', [$this->employee, 'year' => 2026, 'month' => 7]))
            ->assertOk()->assertSee('Rekap Kehadiran')->assertSee('bukan slip', false);
        $this->actingAs($this->employeeUser)->get(route('employee.monthly-recap.print', ['year' => 2026, 'month' => 7]))
            ->assertOk()->assertSee('Maya Rekap')->assertSee('Browser Print');
    }

    public function test_admin_detail_preserves_validated_summary_context(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.monthly-recaps.show', [
            'employee' => $this->employee,
            'year' => 2026,
            'month' => 7,
            'return_employee_id' => $this->employee->id,
            'return_page' => 2,
        ]));

        $response->assertOk()
            ->assertSee('employee_id='.$this->employee->id, false)
            ->assertSee('page=2', false);
    }

    public function test_monthly_recap_views_include_dedicated_mobile_cards(): void
    {
        $this->actingAs($this->admin)->get(route('admin.monthly-recaps.index', ['year' => 2026, 'month' => 7]))
            ->assertOk()->assertSee('md:hidden', false)->assertSee('min-h-[44px]', false);
        $this->actingAs($this->employeeUser)->get(route('employee.monthly-recap.show', ['year' => 2026, 'month' => 7]))
            ->assertOk()->assertSee('md:hidden', false)->assertSee('Rekap Saya');
    }
}
