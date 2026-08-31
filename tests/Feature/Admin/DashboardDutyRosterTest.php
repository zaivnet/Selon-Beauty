<?php

namespace Tests\Feature\Admin;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeOutletTransfer;
use App\Models\EmployeeSchedule;
use App\Models\EmployeeScheduleOverride;
use App\Models\Holiday;
use App\Models\JobTitle;
use App\Models\LeaveRequest;
use App\Models\Outlet;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardDutyRosterTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;
    protected User $owner;
    protected User $admin;
    protected User $unassignedAdmin;
    protected User $adminAll;

    protected Outlet $outletA;
    protected Outlet $outletB;
    protected Outlet $outletC;

    protected Shift $shiftPagi;
    protected Shift $shiftSiang;
    protected Shift $shiftMalam;

    protected JobTitle $therapistTitle;
    protected JobTitle $receptionistTitle;

    protected string $today;
    protected string $tomorrow;
    protected string $yesterday;

    protected function setUp(): void
    {
        parent::setUp();

        $tz = config('app.timezone', 'Asia/Jakarta');
        $now = Carbon::now($tz);
        $this->today = $now->toDateString();
        $this->tomorrow = (clone $now)->addDay()->toDateString();
        $this->yesterday = (clone $now)->subDay()->toDateString();

        $this->outletA = Outlet::query()->where('code', 'PUSAT')->first();
        if (! $this->outletA) {
            $this->outletA = Outlet::create([
                'name' => 'Selon Beauty Pusat',
                'code' => 'PUSAT',
                'address' => 'Jl. Merdeka No. 1',
                'latitude' => -6.175110,
                'longitude' => 106.827220,
                'radius_meters' => 100,
                'max_accuracy_meters' => 50,
                'is_active' => true,
            ]);
        } else {
            $this->outletA->update(['name' => 'Selon Beauty Pusat']);
        }

        $this->outletB = Outlet::create([
            'name' => 'Selon Beauty Cabang B',
            'code' => 'OUT-B',
            'address' => 'Jl. Sudirman No. 12',
            'latitude' => -6.208800,
            'longitude' => 106.845600,
            'radius_meters' => 150,
            'max_accuracy_meters' => 50,
            'is_active' => true,
        ]);

        $this->outletC = Outlet::create([
            'name' => 'Selon Beauty Cabang C',
            'code' => 'OUT-C',
            'address' => 'Jl. Gatot Subroto No. 45',
            'latitude' => -6.230000,
            'longitude' => 106.810000,
            'radius_meters' => 200,
            'max_accuracy_meters' => 50,
            'is_active' => true,
        ]);

        $this->shiftPagi = Shift::create([
            'name' => 'Shift Pagi',
            'code' => 'SP',
            'start_time' => '08:00:00',
            'end_time' => '14:00:00',
            'check_in_open_minutes_before' => 30,
            'check_in_close_minutes_after' => 120,
            'is_active' => true,
        ]);

        $this->shiftSiang = Shift::create([
            'name' => 'Shift Siang',
            'code' => 'SS',
            'start_time' => '14:00:00',
            'end_time' => '20:00:00',
            'check_in_open_minutes_before' => 30,
            'check_in_close_minutes_after' => 120,
            'is_active' => true,
        ]);

        $this->shiftMalam = Shift::create([
            'name' => 'Shift Malam',
            'code' => 'SM',
            'start_time' => '22:00:00',
            'end_time' => '06:00:00',
            'check_in_open_minutes_before' => 30,
            'check_in_close_minutes_after' => 120,
            'crosses_midnight' => true,
            'is_active' => true,
        ]);

        $this->therapistTitle = JobTitle::create(['name' => 'Therapist', 'is_active' => true]);
        $this->receptionistTitle = JobTitle::create(['name' => 'Receptionist', 'is_active' => true]);

        $this->superadmin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $this->owner = User::factory()->create([
            'name' => 'Owner User',
            'email' => 'owner@example.com',
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create([
            'name' => 'Admin Scoped',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'outlet_access_mode' => 'selected',
            'is_active' => true,
        ]);
        $this->admin->assignedOutlets()->attach([$this->outletA->id, $this->outletB->id]);

        $this->unassignedAdmin = User::factory()->create([
            'name' => 'Unassigned Admin',
            'email' => 'unassigned@example.com',
            'role' => 'admin',
            'outlet_access_mode' => 'selected',
            'is_active' => true,
        ]);

        $this->adminAll = User::factory()->create([
            'name' => 'Admin All Outlets',
            'email' => 'adminall@example.com',
            'role' => 'admin',
            'outlet_access_mode' => 'all',
            'is_active' => true,
        ]);
    }

    protected function createEmployee(string $name, string $code, Outlet $outlet, ?JobTitle $jobTitle = null): Employee
    {
        return Employee::create([
            'full_name' => $name,
            'employee_code' => $code,
            'email' => strtolower(str_replace(' ', '.', $name)).'@example.com',
            'phone' => '0812'.rand(10000000, 99999999),
            'outlet_id' => $outlet->id,
            'job_title_id' => $jobTitle?->id ?? $this->therapistTitle->id,
            'status' => 'active',
            'attendance_enabled' => true,
        ]);
    }

    /** 1. Superadmin sees multiple outlets */
    public function test_superadmin_sees_all_operational_outlets_in_duty_roster(): void
    {
        $empA = $this->createEmployee('Ayu Pusat', 'EMP-001', $this->outletA);
        $empC = $this->createEmployee('Citra Cabang C', 'EMP-003', $this->outletC);

        EmployeeSchedule::create([
            'employee_id' => $empA->id,
            'work_date' => $this->today,
            'shift_id' => $this->shiftPagi->id,
            'schedule_type' => 'work',
        ]);
        EmployeeSchedule::create([
            'employee_id' => $empC->id,
            'work_date' => $this->today,
            'shift_id' => $this->shiftPagi->id,
            'schedule_type' => 'work',
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Jadwal Piket');
        $response->assertSee('Selon Beauty Pusat');
        $response->assertSee('Selon Beauty Cabang C');
        $response->assertSee('Ayu Pusat');
        $response->assertSee('Citra Cabang C');
    }

    /** 2. Owner sees multiple outlets */
    public function test_owner_sees_all_operational_outlets_in_duty_roster(): void
    {
        $empB = $this->createEmployee('Budi Cabang B', 'EMP-002', $this->outletB);
        EmployeeSchedule::create([
            'employee_id' => $empB->id,
            'work_date' => $this->today,
            'shift_id' => $this->shiftSiang->id,
            'schedule_type' => 'work',
        ]);

        $response = $this->actingAs($this->owner)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Jadwal Piket');
        $response->assertSee('Selon Beauty Cabang B');
        $response->assertSee('Budi Cabang B');
    }

    /** 3. Admin selected mode sees all assigned outlets */
    public function test_admin_selected_mode_sees_assigned_outlets(): void
    {
        $empA = $this->createEmployee('Ayu Pusat', 'EMP-001', $this->outletA);
        $empB = $this->createEmployee('Budi Cabang B', 'EMP-002', $this->outletB);

        EmployeeSchedule::create([
            'employee_id' => $empA->id,
            'work_date' => $this->today,
            'shift_id' => $this->shiftPagi->id,
            'schedule_type' => 'work',
        ]);
        EmployeeSchedule::create([
            'employee_id' => $empB->id,
            'work_date' => $this->today,
            'shift_id' => $this->shiftSiang->id,
            'schedule_type' => 'work',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Selon Beauty Pusat');
        $response->assertSee('Selon Beauty Cabang B');
        $response->assertSee('Ayu Pusat');
        $response->assertSee('Budi Cabang B');
    }

    /** 4. Admin cannot see unassigned outlet */
    public function test_admin_cannot_see_unassigned_outlet_in_roster(): void
    {
        $empC = $this->createEmployee('Citra Cabang C', 'EMP-003', $this->outletC);
        EmployeeSchedule::create([
            'employee_id' => $empC->id,
            'work_date' => $this->today,
            'shift_id' => $this->shiftPagi->id,
            'schedule_type' => 'work',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertDontSee('Selon Beauty Cabang C');
        $response->assertDontSee('Citra Cabang C');
    }

    /** 5. Admin forged outlet query returns 403 */
    public function test_admin_forged_outlet_query_returns_403(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard', [
            'roster_outlet_id' => $this->outletC->id,
        ]));

        $response->assertStatus(403);
    }

    /** 6. Admin zero assignments fails closed */
    public function test_admin_zero_assignments_fails_closed(): void
    {
        $response = $this->actingAs($this->unassignedAdmin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Anda belum memiliki akses outlet.');
    }

    /** 7. Admin all mode sees active outlets according to OutletScopeService */
    public function test_admin_all_mode_sees_all_active_outlets(): void
    {
        $empC = $this->createEmployee('Citra Cabang C', 'EMP-003', $this->outletC);
        EmployeeSchedule::create([
            'employee_id' => $empC->id,
            'work_date' => $this->today,
            'shift_id' => $this->shiftPagi->id,
            'schedule_type' => 'work',
        ]);

        $response = $this->actingAs($this->adminAll)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Selon Beauty Cabang C');
        $response->assertSee('Citra Cabang C');
    }

    /** 8. HOME employee appears at HOME when no work override */
    public function test_home_employee_appears_at_home_outlet(): void
    {
        $empA = $this->createEmployee('Ayu Pusat', 'EMP-001', $this->outletA);
        EmployeeSchedule::create([
            'employee_id' => $empA->id,
            'work_date' => $this->today,
            'shift_id' => $this->shiftPagi->id,
            'schedule_type' => 'work',
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Ayu Pusat');
        $response->assertDontSee('PENUGASAN OUTLET');
    }

    /** 9. Temporary assignment appears at WORK Outlet */
    public function test_temporary_assignment_appears_at_work_outlet(): void
    {
        $empA = $this->createEmployee('Ayu Home Pusat', 'EMP-001', $this->outletA);

        // Schedule override placing Ayu at Outlet B on $this->today
        EmployeeScheduleOverride::create([
            'employee_id' => $empA->id,
            'date' => $this->today,
            'override_type' => 'work',
            'shift_id' => $this->shiftSiang->id,
            'work_outlet_id' => $this->outletB->id,
            'reason' => 'Bantuan cabang B',
            'created_by' => $this->superadmin->id,
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Ayu Home Pusat');
        $response->assertSee('PENUGASAN');
        $response->assertSee('dari Selon Beauty Pusat');
    }

    /** 10. Temporarily assigned employee does not simultaneously appear at HOME */
    public function test_temporarily_assigned_employee_does_not_appear_at_home_outlet(): void
    {
        $empA = $this->createEmployee('Ayu Assigned', 'EMP-001', $this->outletA);

        EmployeeScheduleOverride::create([
            'employee_id' => $empA->id,
            'date' => $this->today,
            'override_type' => 'work',
            'shift_id' => $this->shiftSiang->id,
            'work_outlet_id' => $this->outletB->id,
            'reason' => 'Bantuan cabang',
            'created_by' => $this->superadmin->id,
        ]);

        // Filter explicitly to Outlet A
        $responseOutletA = $this->actingAs($this->superadmin)->get(route('admin.dashboard', [
            'roster_outlet_id' => $this->outletA->id,
        ]));

        $responseOutletA->assertOk();
        $responseOutletA->assertDontSee('Ayu Assigned');

        // Filter explicitly to Outlet B
        $responseOutletB = $this->actingAs($this->superadmin)->get(route('admin.dashboard', [
            'roster_outlet_id' => $this->outletB->id,
        ]));

        $responseOutletB->assertOk();
        $responseOutletB->assertSee('Ayu Assigned');
        $responseOutletB->assertSee('PENUGASAN');
    }

    /** 11. PENUGASAN badge/context is present */
    public function test_penugasan_outlet_badge_is_displayed_cleanly(): void
    {
        $empA = $this->createEmployee('Ayu Temp', 'EMP-001', $this->outletA);

        EmployeeScheduleOverride::create([
            'employee_id' => $empA->id,
            'date' => $this->today,
            'override_type' => 'work',
            'shift_id' => $this->shiftSiang->id,
            'work_outlet_id' => $this->outletB->id,
            'reason' => 'Bantuan operasional',
            'created_by' => $this->superadmin->id,
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('PENUGASAN');
        $response->assertSee('dari Selon Beauty Pusat');
    }

    /** 12. Dynamic shift names are rendered */
    public function test_dynamic_shift_names_are_rendered(): void
    {
        $customShift = Shift::create([
            'name' => 'Shift Sore Khusus',
            'code' => 'SSK',
            'start_time' => '16:00:00',
            'end_time' => '22:00:00',
            'is_active' => true,
        ]);

        $empA = $this->createEmployee('Ayu Custom', 'EMP-001', $this->outletA);
        EmployeeSchedule::create([
            'employee_id' => $empA->id,
            'work_date' => $this->today,
            'shift_id' => $customShift->id,
            'schedule_type' => 'work',
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Shift Sore Khusus');
        $response->assertSee('16:00 - 22:00');
    }

    /** 13. OFF employee excluded from duty roster */
    public function test_off_employee_excluded_from_active_shift_roster(): void
    {
        $empA = $this->createEmployee('Ayu Off', 'EMP-001', $this->outletA);
        $empB = $this->createEmployee('Budi Active', 'EMP-002', $this->outletA);

        EmployeeSchedule::create([
            'employee_id' => $empA->id,
            'work_date' => $this->today,
            'schedule_type' => 'off',
        ]);
        EmployeeSchedule::create([
            'employee_id' => $empB->id,
            'work_date' => $this->today,
            'shift_id' => $this->shiftPagi->id,
            'schedule_type' => 'work',
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Budi Active');
        $response->assertDontSee('Ayu Off');
        $response->assertSee('Libur / OFF: 1');
    }

    /** 14. Holiday employee excluded from active duty roster */
    public function test_holiday_employee_excluded_from_active_duty_roster(): void
    {
        Holiday::create([
            'date' => $this->today,
            'name' => 'Hari Libur Nasional',
            'type' => 'public_holiday',
            'is_working_day' => false,
            'applies_to_all_employees' => true,
            'created_by' => $this->superadmin->id,
        ]);

        $empA = $this->createEmployee('Ayu Holiday', 'EMP-001', $this->outletA);
        EmployeeSchedule::create([
            'employee_id' => $empA->id,
            'work_date' => $this->today,
            'shift_id' => $this->shiftPagi->id,
            'schedule_type' => 'work',
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertDontSee('Ayu Holiday');
    }

    /** 15. Approved leave employee remains visible with leave status */
    public function test_approved_leave_employee_remains_visible_with_leave_badge(): void
    {
        $empA = $this->createEmployee('Ayu Cuti', 'EMP-001', $this->outletA);
        EmployeeSchedule::create([
            'employee_id' => $empA->id,
            'work_date' => $this->today,
            'shift_id' => $this->shiftPagi->id,
            'schedule_type' => 'work',
        ]);

        LeaveRequest::create([
            'employee_id' => $empA->id,
            'type' => 'leave',
            'start_date' => $this->today,
            'end_date' => $this->today,
            'reason' => 'Cuti tahunan',
            'status' => 'approved',
            'reviewed_by_user_id' => $this->superadmin->id,
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Ayu Cuti');
        $response->assertSee('CUTI');
    }

    /** 16. Today's checked-in employee shows correct attendance state */
    public function test_today_checked_in_employee_shows_hadir_status(): void
    {
        $empA = $this->createEmployee('Ayu Hadir', 'EMP-001', $this->outletA);
        EmployeeSchedule::create([
            'employee_id' => $empA->id,
            'work_date' => $this->today,
            'shift_id' => $this->shiftPagi->id,
            'schedule_type' => 'work',
        ]);

        AttendanceRecord::create([
            'employee_id' => $empA->id,
            'outlet_id' => $this->outletA->id,
            'work_date' => $this->today,
            'check_in_at' => Carbon::now(config('app.timezone')),
            'status' => 'present',
            'late_minutes' => 0,
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Ayu Hadir');
        $response->assertSee('HADIR');
    }

    /** 17. Today's late employee uses canonical late status */
    public function test_today_late_employee_shows_terlambat_status(): void
    {
        $empA = $this->createEmployee('Ayu Terlambat', 'EMP-001', $this->outletA);
        EmployeeSchedule::create([
            'employee_id' => $empA->id,
            'work_date' => $this->today,
            'shift_id' => $this->shiftPagi->id,
            'schedule_type' => 'work',
        ]);

        AttendanceRecord::create([
            'employee_id' => $empA->id,
            'outlet_id' => $this->outletA->id,
            'work_date' => $this->today,
            'check_in_at' => Carbon::now(config('app.timezone')),
            'status' => 'late',
            'late_minutes' => 25,
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Ayu Terlambat');
        $response->assertSee('TERLAMBAT');
    }

    /** 18. Future employee is not marked BELUM CHECK-IN/TIDAK HADIR */
    public function test_future_employee_is_marked_terjadwal(): void
    {
        $empA = $this->createEmployee('Ayu Besok', 'EMP-001', $this->outletA);
        EmployeeSchedule::create([
            'employee_id' => $empA->id,
            'work_date' => $this->tomorrow,
            'shift_id' => $this->shiftPagi->id,
            'schedule_type' => 'work',
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.dashboard', [
            'roster_date' => $this->tomorrow,
        ]));

        $response->assertOk();
        $response->assertSee('Ayu Besok');
        $response->assertDontSee('BELUM CHECK-IN');
        $response->assertDontSee('TIDAK HADIR');
        $response->assertDontSee('TERJADWAL');
    }

    /** 19. Past date uses historical attendance status */
    public function test_past_date_uses_historical_attendance_status(): void
    {
        $empA = $this->createEmployee('Ayu Kemarin', 'EMP-001', $this->outletA);
        EmployeeSchedule::create([
            'employee_id' => $empA->id,
            'work_date' => $this->yesterday,
            'shift_id' => $this->shiftPagi->id,
            'schedule_type' => 'work',
        ]);

        AttendanceRecord::create([
            'employee_id' => $empA->id,
            'outlet_id' => $this->outletA->id,
            'work_date' => $this->yesterday,
            'check_in_at' => Carbon::parse("{$this->yesterday} 08:00:00", config('app.timezone')),
            'status' => 'present',
            'late_minutes' => 0,
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.dashboard', [
            'roster_date' => $this->yesterday,
        ]));

        $response->assertOk();
        $response->assertSee('Ayu Kemarin');
        $response->assertSee('HADIR');
    }

    /** 20. Cross-midnight shift belongs to correct work_date */
    public function test_cross_midnight_shift_belongs_to_correct_work_date(): void
    {
        $empA = $this->createEmployee('Ayu Malam', 'EMP-001', $this->outletA);
        EmployeeSchedule::create([
            'employee_id' => $empA->id,
            'work_date' => $this->today,
            'shift_id' => $this->shiftMalam->id,
            'schedule_type' => 'work',
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.dashboard', [
            'roster_date' => $this->today,
        ]));

        $response->assertOk();
        $response->assertSee('Ayu Malam');
        $response->assertSee('Shift Malam');
        $response->assertSee('22:00 - 06:00');
    }

    /** 21. Attendance historical outlet snapshot remains authoritative where relevant */
    public function test_attendance_historical_outlet_snapshot_preserved(): void
    {
        $empA = $this->createEmployee('Ayu Transferred', 'EMP-001', $this->outletB);

        // Record on yesterday when employee was at Outlet A
        AttendanceRecord::create([
            'employee_id' => $empA->id,
            'outlet_id' => $this->outletA->id,
            'work_date' => $this->yesterday,
            'check_in_at' => Carbon::parse("{$this->yesterday} 08:00:00", config('app.timezone')),
            'status' => 'present',
            'late_minutes' => 0,
        ]);

        // Transfer happened today
        EmployeeOutletTransfer::create([
            'employee_id' => $empA->id,
            'from_outlet_id' => $this->outletA->id,
            'to_outlet_id' => $this->outletB->id,
            'effective_date' => $this->today,
            'transferred_by_user_id' => $this->superadmin->id,
        ]);

        EmployeeSchedule::create([
            'employee_id' => $empA->id,
            'work_date' => $this->yesterday,
            'shift_id' => $this->shiftPagi->id,
            'work_outlet_id' => $this->outletA->id,
            'schedule_type' => 'work',
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.dashboard', [
            'roster_date' => $this->yesterday,
            'roster_outlet_id' => $this->outletA->id,
        ]));

        $response->assertOk();
        $response->assertSee('Ayu Transferred');
    }

    /** 22. Unauthorized employee data does not appear in HTML */
    public function test_unauthorized_employee_data_does_not_appear_in_html(): void
    {
        $empC = $this->createEmployee('Secret Employee C', 'EMP-SECRET', $this->outletC);
        EmployeeSchedule::create([
            'employee_id' => $empC->id,
            'work_date' => $this->today,
            'shift_id' => $this->shiftPagi->id,
            'schedule_type' => 'work',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertDontSee('Secret Employee C');
        $response->assertDontSee('EMP-SECRET');
    }

    /** 23. Single-outlet Admin UX works */
    public function test_single_outlet_admin_ux_works(): void
    {
        $singleAdmin = User::factory()->create([
            'name' => 'Single Admin',
            'email' => 'single@example.com',
            'role' => 'admin',
            'outlet_access_mode' => 'selected',
            'is_active' => true,
        ]);
        $singleAdmin->assignedOutlets()->attach([$this->outletA->id]);

        $empA = $this->createEmployee('Ayu Single', 'EMP-001', $this->outletA);
        EmployeeSchedule::create([
            'employee_id' => $empA->id,
            'work_date' => $this->today,
            'shift_id' => $this->shiftPagi->id,
            'schedule_type' => 'work',
        ]);

        $response = $this->actingAs($singleAdmin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Ayu Single');
        $response->assertSee('Selon Beauty Pusat');
    }

    /** 24. Multi-outlet "Semua Outlet" groups employees by outlet first */
    public function test_multi_outlet_groups_employees_by_outlet_first(): void
    {
        $empA = $this->createEmployee('Ayu Pusat Group', 'EMP-001', $this->outletA);
        $empB = $this->createEmployee('Budi Cabang Group', 'EMP-002', $this->outletB);

        EmployeeSchedule::create([
            'employee_id' => $empA->id,
            'work_date' => $this->today,
            'shift_id' => $this->shiftPagi->id,
            'schedule_type' => 'work',
        ]);
        EmployeeSchedule::create([
            'employee_id' => $empB->id,
            'work_date' => $this->today,
            'shift_id' => $this->shiftSiang->id,
            'schedule_type' => 'work',
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Selon Beauty Pusat');
        $response->assertSee('Selon Beauty Cabang B');
        $response->assertSee('Ayu Pusat Group');
        $response->assertSee('Budi Cabang Group');
    }

    /** 25. Empty roster renders safe empty state */
    public function test_empty_roster_renders_safe_empty_state(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('admin.dashboard', [
            'roster_date' => $this->tomorrow,
        ]));

        $response->assertOk();
        $response->assertSee('Tidak ada jadwal kerja pada tanggal ini.');
    }

    /** 26. Operational summary calculates total duty, active outlets, unique shifts, and assignments for Semua Outlet */
    public function test_semua_outlet_operational_summary_counts(): void
    {
        $empA1 = $this->createEmployee('Ayu Pusat 1', 'EMP-001', $this->outletA);
        $empA2 = $this->createEmployee('Ayu Pusat 2', 'EMP-002', $this->outletA);
        $empB1 = $this->createEmployee('Budi Cabang 1', 'EMP-003', $this->outletB);

        // Schedule empA1 on Shift Pagi at Outlet A
        EmployeeSchedule::create([
            'employee_id' => $empA1->id,
            'work_date' => $this->today,
            'shift_id' => $this->shiftPagi->id,
            'schedule_type' => 'work',
        ]);

        // Schedule empA2 on Shift Siang at Outlet A
        EmployeeSchedule::create([
            'employee_id' => $empA2->id,
            'work_date' => $this->today,
            'shift_id' => $this->shiftSiang->id,
            'schedule_type' => 'work',
        ]);

        // Temporary assignment: empB1 (Home B) works at Outlet A on Shift Pagi
        EmployeeScheduleOverride::create([
            'employee_id' => $empB1->id,
            'date' => $this->today,
            'override_type' => 'work',
            'shift_id' => $this->shiftPagi->id,
            'work_outlet_id' => $this->outletA->id,
            'reason' => 'Bantuan piket',
            'created_by' => $this->superadmin->id,
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.dashboard'));

        $response->assertOk();
        // 3 employees working (all at Outlet A), 1 active outlet, 2 unique shifts (Pagi & Siang), 1 assignment
        $response->assertSee('3');
        $response->assertSee('Bertugas');
        $response->assertSee('1');
        $response->assertSee('Outlet');
        $response->assertSee('2');
        $response->assertSee('Shift');
        $response->assertSee('1');
        $response->assertSee('Penugasan');
    }

    /** 27. Filter-aware operational summary for individual outlet */
    public function test_individual_outlet_operational_summary_counts(): void
    {
        $empA = $this->createEmployee('Ayu Pusat', 'EMP-001', $this->outletA);
        $empB = $this->createEmployee('Budi Cabang', 'EMP-002', $this->outletB);

        EmployeeSchedule::create([
            'employee_id' => $empA->id,
            'work_date' => $this->today,
            'shift_id' => $this->shiftPagi->id,
            'schedule_type' => 'work',
        ]);
        EmployeeSchedule::create([
            'employee_id' => $empB->id,
            'work_date' => $this->today,
            'shift_id' => $this->shiftSiang->id,
            'schedule_type' => 'work',
        ]);

        // Filter explicitly to Outlet A
        $response = $this->actingAs($this->superadmin)->get(route('admin.dashboard', [
            'roster_outlet_id' => $this->outletA->id,
        ]));

        $response->assertOk();
        // For Outlet A only: 1 Bertugas, 1 Outlet, 1 Shift, 0 Penugasan
        $response->assertSee('1');
        $response->assertSee('Bertugas');
        $response->assertSee('1');
        $response->assertSee('Outlet');
        $response->assertSee('1');
        $response->assertSee('Shift');
        $response->assertSee('0');
        $response->assertSee('Penugasan');
        $response->assertDontSee('Budi Cabang');
    }

    /** 28. Unique shift count does not double-count same shift across outlets */
    public function test_operational_summary_unique_shift_count_does_not_duplicate_across_outlets(): void
    {
        $empA = $this->createEmployee('Ayu Pusat', 'EMP-001', $this->outletA);
        $empB = $this->createEmployee('Budi Cabang', 'EMP-002', $this->outletB);

        // Both outlets use the SAME shift (shiftPagi)
        EmployeeSchedule::create([
            'employee_id' => $empA->id,
            'work_date' => $this->today,
            'shift_id' => $this->shiftPagi->id,
            'schedule_type' => 'work',
        ]);
        EmployeeSchedule::create([
            'employee_id' => $empB->id,
            'work_date' => $this->today,
            'shift_id' => $this->shiftPagi->id,
            'schedule_type' => 'work',
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.dashboard'));

        $response->assertOk();
        // 2 Bertugas, 2 Outlet, 1 unique Shift, 0 Penugasan
        $response->assertSee('2');
        $response->assertSee('Bertugas');
        $response->assertSee('2');
        $response->assertSee('Outlet');
        $response->assertSee('1');
        $response->assertSee('Shift');
    }

    /** 29. Summary excludes OFF and unauthorized employees */
    public function test_operational_summary_excludes_off_and_unauthorized_employees(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin Single',
            'email' => 'adminsingle@selon.id',
            'role' => 'admin',
            'outlet_access_mode' => 'selected',
            'is_active' => true,
        ]);
        $admin->assignedOutlets()->attach([$this->outletA->id]);

        $empA = $this->createEmployee('Ayu Assigned', 'EMP-001', $this->outletA);
        $empA_Off = $this->createEmployee('Ayu Off', 'EMP-002', $this->outletA);
        $empUnauth = $this->createEmployee('Unauthorized Emp', 'EMP-003', $this->outletB);

        EmployeeSchedule::create([
            'employee_id' => $empA->id,
            'work_date' => $this->today,
            'shift_id' => $this->shiftPagi->id,
            'schedule_type' => 'work',
        ]);
        EmployeeSchedule::create([
            'employee_id' => $empA_Off->id,
            'work_date' => $this->today,
            'shift_id' => $this->shiftPagi->id,
            'schedule_type' => 'off',
        ]);
        EmployeeSchedule::create([
            'employee_id' => $empUnauth->id,
            'work_date' => $this->today,
            'shift_id' => $this->shiftPagi->id,
            'schedule_type' => 'work',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        // Admin only has Outlet A: 1 working employee, 1 OFF (excluded from Bertugas), Outlet B ignored
        $response->assertSee('1');
        $response->assertSee('Bertugas');
        $response->assertSee('1');
        $response->assertSee('Outlet');
        $response->assertDontSee('Unauthorized Emp');
    }

    /** 30. Shift groups and outlet summary are ordered chronologically by start_time ASC */
    public function test_shift_groups_and_outlet_summary_ordered_chronologically_by_start_time(): void
    {
        $shiftEarly = Shift::create([
            'name' => 'Shift Subuh',
            'code' => 'SSB',
            'start_time' => '05:00:00',
            'end_time' => '12:00:00',
        ]);
        $shiftLate = Shift::create([
            'name' => 'Shift Malam',
            'code' => 'SML',
            'start_time' => '22:00:00',
            'end_time' => '06:00:00',
        ]);

        $emp1 = $this->createEmployee('Emp Malam', 'EMP-001', $this->outletA);
        $emp2 = $this->createEmployee('Emp Siang', 'EMP-002', $this->outletA);
        $emp3 = $this->createEmployee('Emp Subuh', 'EMP-003', $this->outletA);

        // Created out of chronological order
        EmployeeSchedule::create([
            'employee_id' => $emp1->id,
            'work_date' => $this->today,
            'shift_id' => $shiftLate->id,
            'schedule_type' => 'work',
        ]);
        EmployeeSchedule::create([
            'employee_id' => $emp2->id,
            'work_date' => $this->today,
            'shift_id' => $this->shiftSiang->id, // 14:00
            'schedule_type' => 'work',
        ]);
        EmployeeSchedule::create([
            'employee_id' => $emp3->id,
            'work_date' => $this->today,
            'shift_id' => $shiftEarly->id, // 05:00
            'schedule_type' => 'work',
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('admin.dashboard'));

        $response->assertOk();
        $content = $response->getContent();

        // Check chronological order in outlet summary: Shift Subuh 1 • Shift Siang 1 • Shift Malam 1
        $posSubuh = strpos($content, 'Shift Subuh');
        $posSiang = strpos($content, 'Shift Siang');
        $posMalam = strpos($content, 'Shift Malam');

        $this->assertNotFalse($posSubuh);
        $this->assertNotFalse($posSiang);
        $this->assertNotFalse($posMalam);
        $this->assertTrue($posSubuh < $posSiang, 'Shift Subuh (05:00) should appear before Shift Siang (14:00)');
        $this->assertTrue($posSiang < $posMalam, 'Shift Siang (14:00) should appear before Shift Malam (22:00)');
    }

    /** 31. Zero-assignment Admin does not leak summary counts */
    public function test_zero_assignment_admin_does_not_leak_summary_counts(): void
    {
        $admin = $this->unassignedAdmin;

        $empA = $this->createEmployee('Ayu Secret', 'EMP-001', $this->outletA);
        EmployeeSchedule::create([
            'employee_id' => $empA->id,
            'work_date' => $this->today,
            'shift_id' => $this->shiftPagi->id,
            'schedule_type' => 'work',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Anda belum memiliki akses outlet.');
        $response->assertDontSee('Ayu Secret');
    }
}
