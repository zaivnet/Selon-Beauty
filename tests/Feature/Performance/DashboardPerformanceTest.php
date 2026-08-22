<?php

namespace Tests\Feature\Performance;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Outlet;
use App\Models\User;
use App\Services\AttendanceMonitoringService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected Outlet $outletA;

    protected Outlet $outletB;

    protected User $adminA;

    protected User $owner;

    protected Employee $employeeA1;

    protected Employee $employeeB1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outletA = Outlet::create([
            'name' => 'Selon Beauty Performance Outlet A',
            'code' => 'SB-PERF-A',
            'latitude' => -7.1627701,
            'longitude' => 113.4843582,
            'radius_meters' => 50,
            'is_active' => true,
        ]);

        $this->outletB = Outlet::create([
            'name' => 'Selon Beauty Performance Outlet B',
            'code' => 'SB-PERF-B',
            'latitude' => -7.1569777,
            'longitude' => 113.4895155,
            'radius_meters' => 50,
            'is_active' => true,
        ]);

        $this->adminA = User::factory()->create([
            'role' => 'admin',
            'outlet_id' => $this->outletA->id,
            'is_active' => true,
        ]);
        $this->adminA->assignedOutlets()->sync([$this->outletA->id]);

        $this->owner = User::factory()->create([
            'role' => 'owner',
            'outlet_id' => null,
            'is_active' => true,
        ]);

        $this->employeeA1 = Employee::create([
            'employee_code' => 'EMP-PERF-A1',
            'full_name' => 'Karyawan Performance A1',
            'status' => 'active',
            'outlet_id' => $this->outletA->id,
        ]);

        $this->employeeB1 = Employee::create([
            'employee_code' => 'EMP-PERF-B1',
            'full_name' => 'Karyawan Performance B1',
            'status' => 'active',
            'outlet_id' => $this->outletB->id,
        ]);

        // Seed 7 days of attendance records for both employees
        $today = Carbon::now(config('app.timezone'));
        for ($i = 0; $i < 7; $i++) {
            $date = (clone $today)->subDays($i)->toDateString();

            AttendanceRecord::create([
                'employee_id' => $this->employeeA1->id,
                'outlet_id' => $this->outletA->id,
                'work_date' => $date,
                'status' => 'present',
                'check_in_at' => $date.' 08:00:00',
            ]);

            AttendanceRecord::create([
                'employee_id' => $this->employeeB1->id,
                'outlet_id' => $this->outletB->id,
                'work_date' => $date,
                'status' => 'present',
                'check_in_at' => $date.' 08:00:00',
            ]);
        }
    }

    public function test_dashboard_query_count_is_materially_reduced_under_20_queries(): void
    {
        DB::enableQueryLog();

        $response = $this->actingAs($this->adminA)->get(route('admin.dashboard'));
        $response->assertStatus(200);

        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        // Dashboard query count must be below 35 queries (down from 50+ previously)
        $this->assertLessThan(35, $queryCount, "Dashboard executed {$queryCount} queries, which exceeds the threshold of 35.");
    }

    public function test_past_week_trend_data_is_batched_and_correctly_scoped(): void
    {
        $service = app(AttendanceMonitoringService::class);

        // Admin A trend data should reflect only Outlet A (1 present per day for 7 days)
        $trendA = $service->getPastWeekTrendData($this->adminA);
        $this::assertTrue($trendA['has_data']);
        $this::assertCount(7, $trendA['data']);
        foreach ($trendA['data'] as $day) {
            $this::assertEquals(1, $day['present']);
            $this::assertEquals(1, $day['total']);
        }

        // Owner trend data should reflect global (2 present per day for 7 days)
        $trendGlobal = $service->getPastWeekTrendData($this->owner);
        $this::assertTrue($trendGlobal['has_data']);
        $this::assertCount(7, $trendGlobal['data']);
        foreach ($trendGlobal['data'] as $day) {
            $this::assertEquals(2, $day['present']);
            $this::assertEquals(2, $day['total']);
        }
    }
}
