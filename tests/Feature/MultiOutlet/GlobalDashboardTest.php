<?php

namespace Tests\Feature\MultiOutlet;

use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\Outlet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GlobalDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $superadmin;

    protected User $adminOutlet1;

    protected Outlet $outlet1;

    protected Outlet $outlet2;

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
        $this->adminOutlet1->assignedOutlets()->sync([$this->outlet1->id]);

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

    public function test_owner_sees_global_dashboard_by_default(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.dashboard'));
        $response->assertOk();
        $response->assertViewIs('admin.dashboard_global');

        $response->assertViewHas('globalData', function ($data) {
            $expectedOutletsCount = Outlet::where('is_active', true)->count();
            $expectedEmployeesCount = Employee::where('status', 'active')->count();

            return $data['global_kpi']['total_outlets'] === $expectedOutletsCount
                && $data['global_kpi']['total_employees'] === $expectedEmployeesCount
                && count($data['outlets']) === $expectedOutletsCount;
        });
    }

    public function test_superadmin_sees_global_dashboard_by_default(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('admin.dashboard'));
        $response->assertOk();
        $response->assertViewIs('admin.dashboard_global');
    }

    public function test_admin_does_not_see_global_dashboard(): void
    {
        $response = $this->actingAs($this->adminOutlet1)->get(route('admin.dashboard'));
        $response->assertOk();
        // Should fall back to the normal scoped dashboard
        $response->assertViewIs('admin.dashboard');
        $response->assertViewHas('requestedOutletId', $this->outlet1->id);
    }

    public function test_owner_can_filter_to_specific_outlet_and_bypass_global_dashboard(): void
    {
        // Add specific outlet_id parameter
        $response = $this->actingAs($this->owner)->get(route('admin.dashboard', ['outlet_id' => $this->outlet1->id]));
        $response->assertOk();
        // Should now see normal dashboard for single outlet
        $response->assertViewIs('admin.dashboard');
        $response->assertViewHas('requestedOutletId', $this->outlet1->id);
        $response->assertViewHas('metrics', fn ($m) => $m['total_employees'] === 1);
    }

    public function test_global_dashboard_has_constant_query_count_avoiding_n_plus_1(): void
    {
        // Generate a baseline query count with 2 outlets
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($this->owner)->get(route('admin.dashboard'));
        $queryCountBase = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Add 5 more active outlets and 5 more employees
        for ($i = 3; $i <= 7; $i++) {
            $newOutlet = Outlet::create([
                'name' => "Outlet $i",
                'code' => "OUT-00$i",
                'latitude' => -6.2,
                'longitude' => 106.8,
                'radius_meters' => 100,
                'is_active' => true,
            ]);

            Employee::create([
                'employee_code' => "EMP-00$i",
                'full_name' => "Karyawan Outlet $i",
                'email' => "emp$i@test.com",
                'status' => 'active',
                'outlet_id' => $newOutlet->id,
                'job_title_id' => $this->empOutlet1->job_title_id,
            ]);
        }

        // Test query count with 7 outlets
        DB::flushQueryLog();
        DB::enableQueryLog();
        $response = $this->actingAs($this->owner)->get(route('admin.dashboard'));
        $queryCountExpanded = count(DB::getQueryLog());
        DB::disableQueryLog();

        $response->assertOk();
        $expectedCount = Outlet::where('is_active', true)->count();
        $response->assertViewHas('globalData', fn ($data) => $data['global_kpi']['total_outlets'] === $expectedCount);

        // The number of queries should be extremely close (or identical) regardless of outlet count
        // Allow a tiny margin of error (+- 3 queries) for internal Laravel framework differences if any.
        $this->assertTrue(
            abs($queryCountExpanded - $queryCountBase) <= 3,
            "Query count increased significantly ($queryCountBase -> $queryCountExpanded), indicating N+1 queries."
        );
    }
}
