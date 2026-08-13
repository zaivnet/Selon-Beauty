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
        $this->assertEquals('/app/dashboard', $json['start_url']);
        $this->assertEquals('standalone', $json['display']);
        $this->assertEquals('#e11d48', $json['theme_color']);
        $this->assertCount(3, $json['icons']);
    }

    public function test_service_worker_js_is_accessible(): void
    {
        $response = $this->get('/sw.js');
        $response->assertOk();
        $this->assertStringContainsString('selon-beauty-static-v1', $response->getContent());
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
}
