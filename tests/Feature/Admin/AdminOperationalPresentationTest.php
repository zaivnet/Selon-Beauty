<?php

namespace Tests\Feature\Admin;

use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\Outlet;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminOperationalPresentationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Outlet $outlet;
    protected Employee $employee;
    protected Shift $shift;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outlet = Outlet::create([
            'name' => 'Outlet Pusat Zaiv',
            'code' => 'PST-01',
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'radius_meters' => 100,
            'max_accuracy_meters' => 100,
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Executive Manager',
            'email' => 'executive.manager@zaiv.test',
            'password' => Hash::make('secret12345'),
            'role' => 'owner',
            'is_active' => true,
        ]);
        $this->admin->assignedOutlets()->sync([$this->outlet->id]);

        $jobTitle = JobTitle::create([
            'name' => 'Senior Stylist',
            'is_active' => true,
        ]);

        $this->employee = Employee::create([
            'employee_code' => 'ZV-001',
            'full_name' => 'Budi Pratama',
            'outlet_id' => $this->outlet->id,
            'job_title_id' => $jobTitle->id,
            'status' => 'active',
            'is_attendance_active' => true,
        ]);

        $this->shift = Shift::create([
            'name' => 'Shift Reguler Pagi',
            'code' => 'REG-P',
            'start_time' => '08:00',
            'end_time' => '17:00',
            'grace_period_minutes' => 10,
            'break_minutes' => 60,
            'check_in_open_minutes_before' => 60,
            'check_in_close_minutes_after' => 120,
            'check_out_open_minutes_before' => 60,
            'auto_checkout_enabled' => true,
            'auto_checkout_grace_minutes' => 15,
            'crosses_midnight' => false,
            'is_active' => true,
        ]);
    }

    public function test_attendance_monitoring_renders_executive_kpi_and_evidence_modal(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.attendance.index'));

        $response->assertOk();
        $response->assertSee('Monitoring Absensi');
        $response->assertSee('Total Karyawan');
        $response->assertSee('Hadir');
        $response->assertSee('Terlambat');
        $response->assertSee('Belum Check-In');
        $response->assertSee('Bukti Presensi', false);
        $response->assertSee('attendance-detail-modal');
    }

    public function test_employee_management_views_render_executive_layout(): void
    {
        // 1. Index
        $indexResponse = $this->actingAs($this->admin)->get(route('admin.employees.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Daftar Karyawan');
        $indexResponse->assertSee('Cari nama, kode, email...');
        $indexResponse->assertSee('Budi Pratama');
        $indexResponse->assertSee('ZV-001');

        // 2. Create
        $createResponse = $this->actingAs($this->admin)->get(route('admin.employees.create'));
        $createResponse->assertOk();
        $createResponse->assertSee('Tambah Karyawan Baru');
        $createResponse->assertSee('Akses Outlet Admin');

        // 3. Edit
        $editResponse = $this->actingAs($this->admin)->get(route('admin.employees.edit', $this->employee));
        $editResponse->assertOk();
        $editResponse->assertSee('Edit Data Karyawan');
        $editResponse->assertSee('Budi Pratama');
        $editResponse->assertSee('Keikutsertaan Absensi');

        // 4. Show
        $showResponse = $this->actingAs($this->admin)->get(route('admin.employees.show', $this->employee));
        $showResponse->assertOk();
        $showResponse->assertSee('Budi Pratama');
        $showResponse->assertSee('ZV-001');
        $showResponse->assertSee('Pindah Outlet');
    }

    public function test_shift_management_views_render_executive_layout(): void
    {
        // 1. Index
        $indexResponse = $this->actingAs($this->admin)->get(route('admin.shifts.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Daftar Shift Kerja');
        $indexResponse->assertSee('REG-P');
        $indexResponse->assertSee('Shift Reguler Pagi');
        $indexResponse->assertSee('Tambah Shift Baru');

        // 2. Create
        $createResponse = $this->actingAs($this->admin)->get(route('admin.shifts.create'));
        $createResponse->assertOk();
        $createResponse->assertSee('Informasi Shift Kerja Baru');
        $createResponse->assertSee('Automatic Shift Checkout');

        // 3. Edit
        $editResponse = $this->actingAs($this->admin)->get(route('admin.shifts.edit', $this->shift));
        $editResponse->assertOk();
        $editResponse->assertSee('Edit Shift: Shift Reguler Pagi');
        $editResponse->assertSee('Automatic Shift Checkout');

        // 4. Show
        $showResponse = $this->actingAs($this->admin)->get(route('admin.shifts.show', $this->shift));
        $showResponse->assertOk();
        $showResponse->assertSee('REG-P');
        $showResponse->assertSee('Shift Reguler Pagi');
        $showResponse->assertSee('Grace Period (Keterlambatan)');
    }

    public function test_scheduling_and_work_calendar_render_executive_layout(): void
    {
        // 1. Weekly Schedule Matrix
        $scheduleResponse = $this->actingAs($this->admin)->get(route('admin.schedules.index'));
        $scheduleResponse->assertOk();
        $scheduleResponse->assertSee('Jadwal Mingguan');
        $scheduleResponse->assertSee('Kalender Kerja');
        $scheduleResponse->assertSee('Atur Jadwal');
        $scheduleResponse->assertSee('modal-assign-schedule');

        // 2. Work Calendar & Override Panel
        $calendarResponse = $this->actingAs($this->admin)->get(route('admin.work-calendar.index'));
        $calendarResponse->assertOk();
        $calendarResponse->assertSee('Kalender Kerja', false);
        $calendarResponse->assertSee('Tambah Kalender Global');
        $calendarResponse->assertSee('Buat Jadwal Khusus Karyawan');
        $calendarResponse->assertSee('override-context');
        $calendarResponse->assertSee('override-context-body');
    }
}
