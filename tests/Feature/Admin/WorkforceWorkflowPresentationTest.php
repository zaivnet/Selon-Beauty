<?php

namespace Tests\Feature\Admin;

use App\Models\AttendanceLocation;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\JobTitle;
use App\Models\LeaveRequest;
use App\Models\Outlet;
use App\Models\OvertimeRequest;
use App\Models\Shift;
use App\Models\ShiftSwapRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WorkforceWorkflowPresentationTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $employeeUser;
    protected Employee $employee;
    protected User $colleagueUser;
    protected Employee $colleague;
    protected Outlet $outlet;
    protected Shift $shiftPagi;
    protected Shift $shiftSiang;

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

        $this->owner = User::create([
            'name' => 'Executive Owner',
            'email' => 'executive.owner@zaiv.test',
            'password' => Hash::make('password123'),
            'role' => 'owner',
            'is_active' => true,
        ]);
        $this->owner->assignedOutlets()->sync([$this->outlet->id]);

        $jobTitle = JobTitle::create([
            'name' => 'Hair Stylist',
            'is_active' => true,
        ]);

        $this->employee = Employee::create([
            'employee_code' => 'ZV-001',
            'full_name' => 'Ayu Pratama',
            'outlet_id' => $this->outlet->id,
            'job_title_id' => $jobTitle->id,
            'status' => 'active',
            'attendance_enabled' => true,
        ]);

        $this->employeeUser = User::create([
            'employee_id' => $this->employee->id,
            'name' => 'Ayu Pratama',
            'email' => 'ayu@zaiv.test',
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        $this->colleague = Employee::create([
            'employee_code' => 'ZV-002',
            'full_name' => 'Budi Santoso',
            'outlet_id' => $this->outlet->id,
            'job_title_id' => $jobTitle->id,
            'status' => 'active',
            'attendance_enabled' => true,
        ]);

        $this->colleagueUser = User::create([
            'employee_id' => $this->colleague->id,
            'name' => 'Budi Santoso',
            'email' => 'budi@zaiv.test',
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        $this->shiftPagi = Shift::create([
            'name' => 'Shift Pagi',
            'code' => 'PAGI',
            'start_time' => '08:00',
            'end_time' => '16:00',
            'grace_period_minutes' => 10,
            'break_minutes' => 60,
            'is_active' => true,
        ]);

        $this->shiftSiang = Shift::create([
            'name' => 'Shift Siang',
            'code' => 'SIANG',
            'start_time' => '13:00',
            'end_time' => '21:00',
            'grace_period_minutes' => 10,
            'break_minutes' => 60,
            'is_active' => true,
        ]);
    }

    public function test_admin_leave_requests_view_renders_modern_executive_layout(): void
    {
        LeaveRequest::create([
            'employee_id' => $this->employee->id,
            'type' => 'permission',
            'start_date' => Carbon::now()->addDays(2)->toDateString(),
            'end_date' => Carbon::now()->addDays(2)->toDateString(),
            'reason' => 'Urusan keluarga penting',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->owner)->get(route('admin.leave-requests.index'));

        $response->assertOk();
        $response->assertSee('Kelola Pengajuan Karyawan');
        $response->assertSee('Pengajuan Lembur');
        $response->assertSee('Ayu Pratama');
        $response->assertSee('Urusan keluarga penting');
        $response->assertSee('modal-approve', false);
        $response->assertSee('modal-reject', false);
    }

    public function test_employee_leave_requests_view_renders_modern_mobile_layout(): void
    {
        $response = $this->actingAs($this->employeeUser)->get(route('employee.leave-requests.index'));

        $response->assertOk();
        $response->assertSee('Pengajuan Izin');
        $response->assertSee('Buat Pengajuan Baru');
        $response->assertSee('Riwayat Pengajuan Saya');
        $response->assertSee('Kirim Pengajuan');
    }

    public function test_admin_overtime_requests_view_renders_modern_executive_layout(): void
    {
        $workDate = Carbon::now()->toDateString();
        OvertimeRequest::create([
            'employee_id' => $this->employee->id,
            'work_date' => $workDate,
            'requested_minutes' => 60,
            'status' => 'pending',
            'reason' => 'Menyelesaikan inventaris bulanan',
        ]);

        $response = $this->actingAs($this->owner)->get(route('admin.overtime-requests.index'));

        $response->assertOk();
        $response->assertSee('Monitoring Lembur');
        $response->assertSee('Ayu Pratama');
        $response->assertSee('Menyelesaikan inventaris bulanan');
        $response->assertSee('sessionRecoveryModal', false);
        $response->assertSee('approveModal', false);
        $response->assertSee('rejectModal', false);
    }

    public function test_employee_overtime_requests_view_renders_modern_mobile_layout(): void
    {
        $response = $this->actingAs($this->employeeUser)->get(route('employee.overtime-requests.index'));

        $response->assertOk();
        $response->assertSee('Pengajuan Lembur');
        $response->assertSee('Ajukan Lembur Baru');
        $response->assertSee('Riwayat Lembur Saya');
        $response->assertSee('30 Menit');
        $response->assertSee('60 Menit (1 Jam)');
    }

    public function test_admin_shift_swaps_index_and_show_views_render_modern_layout(): void
    {
        $targetDate = Carbon::now()->addDays(3)->toDateString();

        $swap = ShiftSwapRequest::create([
            'requester_employee_id' => $this->employee->id,
            'target_employee_id' => $this->colleague->id,
            'requester_work_date' => $targetDate,
            'target_work_date' => $targetDate,
            'requester_original_shift_id' => $this->shiftPagi->id,
            'target_original_shift_id' => $this->shiftSiang->id,
            'requester_reason' => 'Ada acara mendadak',
            'status' => 'pending_admin',
        ]);

        // 1. Index
        $indexResponse = $this->actingAs($this->owner)->get(route('admin.shift-swaps.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Permintaan Pertukaran Jadwal Kerja');
        $indexResponse->assertSee('Ayu Pratama');
        $indexResponse->assertSee('Budi Santoso');
        $indexResponse->assertSee('Shift Pagi');
        $indexResponse->assertSee('Shift Siang');

        // 2. Show
        $showResponse = $this->actingAs($this->owner)->get(route('admin.shift-swaps.show', $swap));
        $showResponse->assertOk();
        $showResponse->assertSee('Detail Tukar Jadwal');
        $showResponse->assertSee('Perbandingan Arah Penukaran Shift');
        $showResponse->assertSee('Pemohon Tukar');
        $showResponse->assertSee('Rekan Tujuan');
        $showResponse->assertSee('Setujui Tukar Jadwal');
        $showResponse->assertSee('Tolak Permintaan');
    }

    public function test_employee_shift_swaps_index_and_create_views_render_modern_layout(): void
    {
        $targetDate = Carbon::now()->addDays(4)->toDateString();

        // Give both schedules on targetDate so create view effective schedule preview works
        EmployeeSchedule::create([
            'employee_id' => $this->employee->id,
            'work_date' => $targetDate,
            'shift_id' => $this->shiftPagi->id,
            'outlet_id' => $this->outlet->id,
            'is_off' => false,
        ]);

        EmployeeSchedule::create([
            'employee_id' => $this->colleague->id,
            'work_date' => $targetDate,
            'shift_id' => $this->shiftSiang->id,
            'outlet_id' => $this->outlet->id,
            'is_off' => false,
        ]);

        // 1. Employee Index
        $indexResponse = $this->actingAs($this->employeeUser)->get(route('employee.shift-swaps.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Pertukaran Jadwal Kerja');
        $indexResponse->assertSee('Permintaan Saya');
        $indexResponse->assertSee('Permintaan Masuk');
        $indexResponse->assertSee('Ajukan Tukar Jadwal');

        // 2. Employee Create
        $createResponse = $this->actingAs($this->employeeUser)->get(route('employee.shift-swaps.create', [
            'work_date' => $targetDate,
            'target_employee_id' => $this->colleague->id,
        ]));
        $createResponse->assertOk();
        $createResponse->assertSee('Pengajuan Tukar Jadwal Baru');
        $createResponse->assertSee('Pratinjau Jadwal Efektif');
        $createResponse->assertSee('Hasil Setelah Ditukar (Pratinjau)');
        $createResponse->assertSee('Alasan Penukaran Jadwal');
    }
}
