<?php

namespace Tests\Feature\Admin;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Outlet;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ReportAndRecapPresentationTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;
    protected User $adminOutlet1;
    protected User $employeeUser;
    protected Employee $employee;
    protected Outlet $outlet1;
    protected Outlet $outlet2;
    protected Shift $shift;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-08-15 10:00:00', config('app.timezone')));

        $this->outlet1 = Outlet::create([
            'name' => 'Outlet Pusat',
            'code' => 'PST',
            'address' => 'Jl. Pusat No. 1',
            'latitude' => -7.250445,
            'longitude' => 112.768845,
            'radius_meters' => 100,
            'max_accuracy_meters' => 50,
            'is_active' => true,
        ]);

        $this->outlet2 = Outlet::create([
            'name' => 'Outlet Cabang',
            'code' => 'CBG',
            'address' => 'Jl. Cabang No. 2',
            'latitude' => -7.260000,
            'longitude' => 112.770000,
            'radius_meters' => 100,
            'max_accuracy_meters' => 50,
            'is_active' => true,
        ]);

        $this->superadmin = User::create([
            'name' => 'Super Administrator',
            'email' => 'superadmin-c3@example.test',
            'password' => Hash::make('password'),
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $this->adminOutlet1 = User::create([
            'name' => 'Admin Pusat',
            'email' => 'admin-pusat-c3@example.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'outlet_id' => $this->outlet1->id,
            'is_active' => true,
        ]);
        $this->adminOutlet1->assignedOutlets()->sync([$this->outlet1->id]);

        $this->employee = Employee::create([
            'employee_code' => 'EMP-C3-001',
            'full_name' => 'Dewi Sartika',
            'outlet_id' => $this->outlet1->id,
            'status' => 'active',
        ]);

        $this->employeeUser = User::create([
            'employee_id' => $this->employee->id,
            'name' => 'Dewi Sartika',
            'email' => 'dewi-c3@example.test',
            'password' => Hash::make('password'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        $this->shift = Shift::create([
            'name' => 'Shift Normal',
            'code' => 'NORM',
            'start_time' => '08:00',
            'end_time' => '17:00',
            'is_active' => true,
        ]);

        // Create schedule and attendance record
        $schedule = EmployeeSchedule::create([
            'employee_id' => $this->employee->id,
            'work_date' => '2026-08-01',
            'shift_id' => $this->shift->id,
            'schedule_type' => 'work',
        ]);

        AttendanceRecord::create([
            'employee_id' => $this->employee->id,
            'work_schedule_id' => $schedule->id,
            'work_date' => '2026-08-01',
            'status' => 'present',
            'check_in_at' => '2026-08-01 07:55:00',
            'check_out_at' => '2026-08-01 17:05:00',
            'worked_minutes' => 490,
            'work_location_id' => null,
            'work_outlet_id' => $this->outlet1->id,
        ]);
    }

    public function test_admin_attendance_report_renders_navigation_tabs_and_export_controls(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('admin.reports.attendance', [
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
        ]));

        $response->assertOk()
            ->assertSee('Laporan Kehadiran')
            ->assertSee('Rekap Bulanan')
            ->assertSee('Cetak / Print View')
            ->assertSee('Export CSV')
            ->assertSee('Filter Laporan')
            ->assertSee('Dari Tanggal')
            ->assertSee('Sampai Tanggal')
            ->assertSee('Hari Kerja')
            ->assertSee('Tingkat Hadir')
            ->assertSee('Detail Rincian Harian')
            ->assertSee('Dewi Sartika')
            ->assertSee('md:hidden');
    }

    public function test_admin_attendance_report_print_view_renders_clean_document_structure(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('admin.reports.attendance.print', [
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
        ]));

        $response->assertOk()
            ->assertSee('Kembali ke Aplikasi')
            ->assertSee('Cetak Dokumen (Print / Save PDF)')
            ->assertSee('Laporan Kehadiran Karyawan')
            ->assertSee('Dewi Sartika')
            ->assertSee('Admin Operasional')
            ->assertSee('Owner');
    }

    public function test_admin_monthly_recap_index_renders_period_card_and_accessible_modals(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('admin.monthly-recaps.index', [
            'year' => 2026,
            'month' => 8,
        ]));

        $response->assertOk()
            ->assertSee('Rekap Kehadiran Bulanan')
            ->assertSee('PERIODE TERBUKA')
            ->assertSee('CSV Ringkasan')
            ->assertSee('CSV Detail')
            ->assertSee('Tutup Periode Kehadiran')
            ->assertSee('Buka Kembali Periode Kehadiran')
            ->assertSee('role="dialog"', false)
            ->assertSee('aria-modal="true"', false)
            ->assertSee('md:hidden');
    }

    public function test_admin_monthly_recap_show_renders_complete_summary_and_navigation(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('admin.monthly-recaps.show', [
            'employee' => $this->employee->id,
            'year' => 2026,
            'month' => 8,
        ]));

        $response->assertOk()
            ->assertSee('Dewi Sartika')
            ->assertSee('Bulan sebelumnya')
            ->assertSee('Bulan berikutnya')
            ->assertSee('Kembali')
            ->assertSee('Cetak')
            ->assertSee('CSV Detail')
            ->assertSee('Audit Trail')
            ->assertSee('Waktu reguler')
            ->assertSee('Izin dan cuti')
            ->assertSee('Lembur')
            ->assertSee('Rincian harian')
            ->assertSee('md:hidden');
    }

    public function test_employee_monthly_recap_show_renders_employee_navigation_and_recap(): void
    {
        $response = $this->actingAs($this->employeeUser)->get(route('employee.monthly-recap.show', [
            'year' => 2026,
            'month' => 8,
        ]));

        $response->assertOk()
            ->assertSee('Jadwal')
            ->assertSee('Rekap Saya')
            ->assertSee('Dewi Sartika')
            ->assertSee('Cetak')
            ->assertSee('CSV Detail')
            ->assertDontSee('Kembali')
            ->assertDontSee('Audit Trail')
            ->assertSee('md:hidden');
    }

    public function test_monthly_recap_print_view_renders_on_screen_preview_bar_and_print_structure(): void
    {
        $response = $this->actingAs($this->employeeUser)->get(route('employee.monthly-recap.print', [
            'year' => 2026,
            'month' => 8,
        ]));

        $response->assertOk()
            ->assertSee('Kembali ke Aplikasi')
            ->assertSee('Cetak Dokumen (Print / Save PDF)')
            ->assertSee('Rekap Kehadiran Bulanan')
            ->assertSee('Dewi Sartika')
            ->assertSee('bukan slip', false);
    }

    public function test_multi_outlet_scoping_prevents_unauthorized_report_access(): void
    {
        // Admin Pusat is scoped to Outlet Pusat ($this->outlet1->id), not Outlet Cabang ($this->outlet2->id)
        $this->actingAs($this->adminOutlet1)
            ->get(route('admin.reports.attendance', [
                'outlet_id' => $this->outlet2->id,
            ]))
            ->assertForbidden();
    }
}

