<?php

namespace Tests\Feature\MultiOutlet;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\Outlet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutletFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $superadmin;

    protected User $adminOutlet1;

    protected User $adminOutlet2;

    protected Outlet $outlet1;

    protected Outlet $outlet2;

    protected Outlet $inactiveOutlet;

    protected Employee $empOutlet1;

    protected Employee $empOutlet2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outlet1 = Outlet::create([
            'name' => 'Selon Beauty Pusat',
            'code' => 'OUT-001',
            'latitude' => -6.175110,
            'longitude' => 106.827220,
            'radius_meters' => 100,
            'is_active' => true,
        ]);

        $this->outlet2 = Outlet::create([
            'name' => 'Kopi Selon Cabang 2',
            'code' => 'OUT-002',
            'latitude' => -6.200000,
            'longitude' => 106.810000,
            'radius_meters' => 150,
            'is_active' => true,
        ]);

        $this->inactiveOutlet = Outlet::create([
            'name' => 'Outlet Nonaktif',
            'code' => 'OUT-999',
            'latitude' => -6.300000,
            'longitude' => 106.850000,
            'radius_meters' => 50,
            'is_active' => false,
        ]);

        $jobTitle = JobTitle::create([
            'name' => 'Stylist',
            'is_active' => true,
        ]);

        $this->owner = User::create([
            'name' => 'Owner System',
            'email' => 'owner@selonbeauty.com',
            'password' => bcrypt('password'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->superadmin = User::create([
            'name' => 'Super Administrator',
            'email' => 'superadmin@selonbeauty.com',
            'password' => bcrypt('password'),
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $this->adminOutlet1 = User::create([
            'name' => 'Admin Outlet 1',
            'email' => 'admin1@selonbeauty.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'outlet_id' => $this->outlet1->id,
            'is_active' => true,
        ]);

        $this->adminOutlet2 = User::create([
            'name' => 'Admin Outlet 2',
            'email' => 'admin2@selonbeauty.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'outlet_id' => $this->outlet2->id,
            'is_active' => true,
        ]);
        $this->adminOutlet1->assignedOutlets()->sync([$this->outlet1->id]);
        $this->adminOutlet2->assignedOutlets()->sync([$this->outlet2->id]);

        $this->empOutlet1 = Employee::create([
            'employee_code' => 'EMP-001',
            'full_name' => 'Karyawan Outlet 1',
            'email' => 'emp1@selonbeauty.com',
            'status' => 'active',
            'outlet_id' => $this->outlet1->id,
            'job_title_id' => $jobTitle->id,
        ]);

        $this->empOutlet2 = Employee::create([
            'employee_code' => 'EMP-002',
            'full_name' => 'Karyawan Outlet 2',
            'email' => 'emp2@selonbeauty.com',
            'status' => 'active',
            'outlet_id' => $this->outlet2->id,
            'job_title_id' => $jobTitle->id,
        ]);
    }

    public function test_owner_defaults_to_all_outlets_and_can_filter_specific_outlet(): void
    {
        $responseAll = $this->actingAs($this->owner)->get(route('admin.dashboard'));
        $responseAll->assertOk();
        $responseAll->assertViewHas('globalData', fn ($data) => $data['global_kpi']['total_employees'] === 2);

        $responseFiltered = $this->actingAs($this->owner)->get(route('admin.dashboard', ['outlet_id' => $this->outlet1->id]));
        $responseFiltered->assertOk();
        $responseFiltered->assertViewHas('metrics', fn ($m) => $m['total_employees'] === 1);

        // Verify session persistence across requests
        $responseNext = $this->actingAs($this->owner)->get(route('admin.employees.index'));
        $responseNext->assertOk();
        $responseNext->assertSee($this->empOutlet1->full_name);
        $responseNext->assertDontSee($this->empOutlet2->full_name);
    }

    public function test_superadmin_has_identical_global_filtering_behavior(): void
    {
        $responseFiltered = $this->actingAs($this->superadmin)->get(route('admin.employees.index', ['outlet_id' => $this->outlet2->id]));
        $responseFiltered->assertOk();
        $responseFiltered->assertSee($this->empOutlet2->full_name);
        $responseFiltered->assertDontSee($this->empOutlet1->full_name);
    }

    public function test_admin_is_hard_scoped_and_ignores_outlet_filter_tampering(): void
    {
        // Admin Outlet 1 tries to send ?outlet_id=outlet2
        $response = $this->actingAs($this->adminOutlet1)->get(route('admin.employees.index', ['outlet_id' => $this->outlet2->id]));
        $response->assertOk();

        // Admin must still see ONLY Outlet 1 employees
        $response->assertSee($this->empOutlet1->full_name);
        $response->assertDontSee($this->empOutlet2->full_name);
    }

    public function test_attendance_monitoring_follows_selected_outlet_filter(): void
    {
        $today = Carbon::now(config('app.timezone'))->toDateString();

        AttendanceRecord::create([
            'employee_id' => $this->empOutlet1->id,
            'work_date' => $today,
            'check_in_at' => Carbon::now(config('app.timezone'))->subHours(2),
            'status' => 'present',
        ]);

        AttendanceRecord::create([
            'employee_id' => $this->empOutlet2->id,
            'work_date' => $today,
            'check_in_at' => Carbon::now(config('app.timezone'))->subHours(2),
            'status' => 'present',
        ]);

        // Filter Outlet 1
        $response1 = $this->actingAs($this->owner)->get(route('admin.attendance.index', ['outlet_id' => $this->outlet1->id]));
        $response1->assertOk();
        $response1->assertSee($this->empOutlet1->full_name);
        $response1->assertDontSee($this->empOutlet2->full_name);

        // Clear filter -> All Outlets
        $responseAll = $this->actingAs($this->owner)->get(route('admin.attendance.index', ['outlet_id' => 0]));
        $responseAll->assertOk();
        $responseAll->assertSee($this->empOutlet1->full_name);
        $responseAll->assertSee($this->empOutlet2->full_name);
    }

    public function test_monthly_recap_follows_selected_outlet_filter(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.monthly-recaps.index', [
            'year' => now()->year,
            'month' => now()->month,
            'outlet_id' => $this->outlet2->id,
        ]));

        $response->assertOk();
        $response->assertSee($this->empOutlet2->full_name);
        $response->assertDontSee($this->empOutlet1->full_name);
    }

    public function test_invalid_or_inactive_outlet_id_falls_back_safely_without_http_500(): void
    {
        $responseInvalid = $this->actingAs($this->owner)->get(route('admin.dashboard', ['outlet_id' => 999999]));
        $responseInvalid->assertOk();
        $responseInvalid->assertViewHas('globalData', fn ($data) => $data['global_kpi']['total_employees'] === 2);

        $responseInactive = $this->actingAs($this->owner)->get(route('admin.dashboard', ['outlet_id' => $this->inactiveOutlet->id]));
        $responseInactive->assertOk();
        $responseInactive->assertViewHas('globalData', fn ($data) => $data['global_kpi']['total_employees'] === 2);
    }

    public function test_outlet_deactivation_prevents_deactivating_outlet_with_active_users(): void
    {
        $response = $this->actingAs($this->owner)->post(route('admin.outlets.toggle-status', $this->outlet1));
        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertTrue($this->outlet1->fresh()->is_active);
    }

    public function test_outlet_filter_component_renders_ui_select_icon_and_reserved_padding(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('admin.dashboard'));
        $response->assertOk();
        $response->assertSee('ui-select-icon');
        $response->assertSee('!pl-10');
        $response->assertSee('aria-hidden="true"', false);
    }
}
