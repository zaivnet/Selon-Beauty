<?php

namespace Tests\Feature\PWA;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PwaTest extends TestCase
{
    use RefreshDatabase;

    protected User $employeeUser;
    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employee = Employee::create([
            'employee_code' => 'SB-001',
            'full_name' => 'Ayu Pratama',
            'status' => 'active',
        ]);

        $this->employeeUser = User::create([
            'employee_id' => $this->employee->id,
            'name' => 'Ayu Pratama',
            'email' => 'ayu@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'is_active' => true,
        ]);
    }

    public function test_manifest_webmanifest_is_accessible_and_valid(): void
    {
        $response = $this->get('/manifest.webmanifest');
        $response->assertOk();

        $json = json_decode($response->getContent(), true);
        $this->assertIsArray($json);
        $this->assertEquals('SELON BEAUTY Attendance', $json['name']);
        $this->assertEquals('SELON BEAUTY', $json['short_name']);
        $this->assertEquals('/', $json['start_url']);
        $this->assertEquals('standalone', $json['display']);
        $this->assertEquals('#e11d48', $json['theme_color']);
        $this->assertCount(3, $json['icons']);
    }

    public function test_service_worker_js_is_accessible(): void
    {
        $response = $this->get('/sw.js');
        $response->assertOk();
        $this->assertStringContainsString('selon-beauty-static-v3', $response->getContent());
        $this->assertStringContainsString('offline.html', $response->getContent());
    }

    public function test_offline_html_is_accessible(): void
    {
        $response = $this->get('/offline.html');
        $response->assertOk();
        $this->assertStringContainsString('Koneksi Terputus (Offline)', $response->getContent());
        $this->assertStringContainsString('SELON BEAUTY', $response->getContent());
    }

    public function test_pwa_icons_exist_and_accessible(): void
    {
        $this->assertTrue(file_exists(public_path('icons/icon-192x192.png')));
        $this->assertTrue(file_exists(public_path('icons/icon-512x512.png')));
        $this->assertTrue(file_exists(public_path('icons/maskable-icon-512x512.png')));
        $this->assertTrue(file_exists(public_path('favicon.ico')));
    }

    public function test_authenticated_routes_have_strict_no_store_cache_control_headers(): void
    {
        $response = $this->actingAs($this->employeeUser)->get(route('employee.dashboard'));
        $response->assertOk();
        
        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $response->assertHeader('Pragma', 'no-cache');
    }

    public function test_unauthenticated_user_redirected_to_login_from_pwa_start_url(): void
    {
        $response = $this->get(route('employee.dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_login_page_includes_pwa_manifest_link(): void
    {
        $response = $this->get(route('login'));
        $response->assertOk();
        $response->assertSee('href="/manifest.webmanifest"', false);
    }

    public function test_owner_without_employee_hitting_app_dashboard_redirects_to_admin_dashboard(): void
    {
        $adminOwner = User::create([
            'name' => 'Owner Admin Only',
            'email' => 'owneradmin@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        $response = $this->actingAs($adminOwner)->get(route('employee.dashboard'));
        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_owner_without_employee_hitting_app_profile_redirects_to_admin_dashboard(): void
    {
        $adminOwner = User::create([
            'name' => 'Owner Admin Only 2',
            'email' => 'owneradmin2@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        $response = $this->actingAs($adminOwner)->get(route('employee.profile.index'));
        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_admin_hitting_app_dashboard_redirects_to_admin_dashboard(): void
    {
        $admin = User::create([
            'name' => 'Admin Role',
            'email' => 'adminrole@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('employee.dashboard'));
        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_superadmin_hitting_app_dashboard_redirects_to_admin_dashboard(): void
    {
        $superadmin = User::create([
            'name' => 'Superadmin Role',
            'email' => 'superadminrole@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($superadmin)->get(route('employee.dashboard'));
        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_employee_with_profile_can_access_dashboard_and_profile(): void
    {
        $this->actingAs($this->employeeUser)
            ->get(route('employee.dashboard'))
            ->assertOk();

        $this->actingAs($this->employeeUser)
            ->get(route('employee.profile.index'))
            ->assertOk()
            ->assertSee('Ayu Pratama');
    }

    public function test_employee_with_attendance_disabled_still_accesses_employee_dashboard(): void
    {
        $this->employee->update(['attendance_enabled' => false]);

        $this->actingAs($this->employeeUser)
            ->get(route('employee.dashboard'))
            ->assertOk()
            ->assertSee('Home');
    }

    public function test_missing_employee_relation_for_employee_role_does_not_500(): void
    {
        $brokenEmployeeUser = User::create([
            'employee_id' => null,
            'name' => 'Broken Employee',
            'email' => 'broken@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        $response = $this->actingAs($brokenEmployeeUser)->get(route('employee.dashboard'));
        $response->assertOk();

        $responseProfile = $this->actingAs($brokenEmployeeUser)->get(route('employee.profile.index'));
        $responseProfile->assertRedirect(route('login'));
    }

    public function test_owner_with_legitimate_employee_profile_can_access_employee_portal(): void
    {
        $dualOwnerEmp = Employee::create([
            'employee_code' => 'SB-OWNER-01',
            'full_name' => 'Owner Dual Capability',
            'status' => 'active',
            'attendance_enabled' => true,
        ]);

        $dualOwnerUser = User::create([
            'employee_id' => $dualOwnerEmp->id,
            'name' => 'Owner Dual Capability',
            'email' => 'ownerdual@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->actingAs($dualOwnerUser)
            ->get(route('employee.dashboard'))
            ->assertOk();

        $this->actingAs($dualOwnerUser)
            ->get(route('employee.profile.index'))
            ->assertOk()
            ->assertSee('Owner Dual Capability');
    }

    public function test_employee_dashboard_renders_ios_pwa_geolocation_guidance_and_refresh_button(): void
    {
        $shift = \App\Models\Shift::create([
            'code' => 'SP',
            'name' => 'Shift Pagi',
            'start_time' => '08:00',
            'end_time' => '17:00',
            'is_active' => true,
        ]);

        \App\Models\EmployeeSchedule::create([
            'employee_id' => $this->employee->id,
            'shift_id' => $shift->id,
            'work_date' => now()->toDateString(),
            'schedule_type' => 'work',
        ]);

        $response = $this->actingAs($this->employeeUser)->get(route('employee.dashboard'));
        $response->assertOk();

        // User-initiated button
        $response->assertSee('Perbarui Lokasi');
        $response->assertSee('onclick="detectGPSLocation()"', false);

        // Guidance Box elements
        $response->assertSee('id="gps-guidance-box"', false);
        $response->assertSee('id="gps-guidance-title"', false);
        $response->assertSee('id="gps-guidance-desc"', false);

        // iOS Standalone & Error code JS logic checks
        $response->assertSee('display-mode: standalone', false);
        $response->assertSee('isIOSDevice', false);
        $response->assertSee('permission_denied', false);
        $response->assertSee('position_unavailable', false);
        $response->assertSee('timeout', false);
        $response->assertSee('low_accuracy', false);
        $response->assertSee('Pengaturan iPhone', false);
        $response->assertSee('Lokasi Presisi', false);
    }
}
