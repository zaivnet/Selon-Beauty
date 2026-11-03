<?php

namespace Tests\Feature\Branding;

use App\Models\AppSetting;
use App\Models\Employee;
use App\Models\User;
use App\Services\BrandingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RebrandingArchitectureTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadminUser;
    protected User $employeeUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superadminUser = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $employee = Employee::create([
            'employee_code' => 'EMP-001',
            'full_name' => 'Budi Santoso',
            'status' => 'active',
        ]);

        $this->employeeUser = User::create([
            'employee_id' => $employee->id,
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'is_active' => true,
        ]);
    }

    /**
     * Scenario A: Fresh installation without branding settings
     */
    public function test_scenario_a_fresh_installation_defaults_cleanly_to_product_and_generic_org(): void
    {
        AppSetting::query()->delete();
        $branding = app(BrandingService::class);
        $branding->clearCache();

        $this->assertEquals('Absensi Zaiv', $branding->getProductName());
        $this->assertEquals('Sistem Presensi & Manajemen Kerja', $branding->getProductTagline());
        $this->assertEquals('Perusahaan', $branding->getCompanyName());
        $this->assertEquals('Absensi Zaiv', $branding->getAppName());
        $this->assertEquals('Absensi Zaiv', $branding->getPwaName());
        $this->assertEquals('Absensi Zaiv', $branding->getPwaShortName());
        $this->assertEquals('#0F766E', $branding->getBrandPrimary());
        $this->assertEquals('#0284C7', $branding->getBrandAccent());
        $this->assertEquals('#0F766E', $branding->getPwaThemeColor());

        $response = $this->get(route('login'));
        $response->assertOk();
        $response->assertSee('Masuk · Perusahaan — Absensi Zaiv');
        $response->assertSee('Powered by Absensi Zaiv');
        $response->assertSee('--brand-primary: #0F766E;', false);
        $response->assertSee('--brand-accent: #0284C7;', false);
    }

    /**
     * Scenario B: Existing installation with custom company name
     */
    public function test_scenario_b_custom_company_name_respected_with_dual_branding(): void
    {
        AppSetting::set('company_name', 'PT Surya Gemilang', 'string', true);
        $branding = app(BrandingService::class);
        $branding->clearCache();

        $this->assertEquals('Absensi Zaiv', $branding->getProductName());
        $this->assertEquals('PT Surya Gemilang', $branding->getCompanyName());
        $this->assertEquals('PT Surya Gemilang (Absensi Zaiv)', $branding->getPwaName());
        $this->assertEquals('PT Surya Gemilang', $branding->getPwaShortName());

        // Login page verification
        $loginRes = $this->get(route('login'));
        $loginRes->assertOk();
        $loginRes->assertSee('Masuk · PT Surya Gemilang — Absensi Zaiv');
        $loginRes->assertSee('PT Surya Gemilang');
        $loginRes->assertSee('Powered by Absensi Zaiv');

        // Admin dashboard verification
        $adminRes = $this->actingAs($this->superadminUser)->get(route('admin.dashboard'));
        $adminRes->assertOk();
        $adminRes->assertSee('PT Surya Gemilang');
        $adminRes->assertSee('Admin Portal · Absensi Zaiv');
        $adminRes->assertSee('Powered by Absensi Zaiv');

        // Employee dashboard verification
        $empRes = $this->actingAs($this->employeeUser)->get(route('employee.dashboard'));
        $empRes->assertOk();
        $empRes->assertSee('PT Surya Gemilang');
        $empRes->assertSee('Portal Karyawan · Absensi Zaiv');
    }

    /**
     * Scenario C: Blank company_name but legacy app_name / app_short_name configured
     */
    public function test_scenario_c_blank_company_name_falls_back_to_legacy_app_name(): void
    {
        AppSetting::set('company_name', '', 'string', true);
        AppSetting::set('app_name', 'Klinik Sehat Selalu', 'string', true);
        AppSetting::set('app_short_name', 'Klinik Sehat', 'string', true);
        $branding = app(BrandingService::class);
        $branding->clearCache();

        $this->assertEquals('Klinik Sehat', $branding->getCompanyName());
        $this->assertEquals('Klinik Sehat Selalu', $branding->getPwaName());
        $this->assertEquals('Klinik Sehat', $branding->getPwaShortName());

        $response = $this->get(route('login'));
        $response->assertOk();
        $response->assertSee('Klinik Sehat');
        $response->assertSee('Powered by Absensi Zaiv');
    }

    /**
     * Scenario D: Custom logo, favicon, PWA icon, and theme colors
     */
    public function test_scenario_d_custom_media_and_colors_preserved(): void
    {
        Storage::fake('public');

        $logoPath = 'branding/custom-logo.png';
        Storage::disk('public')->put($logoPath, 'dummy-logo-content');
        AppSetting::set('app_logo_path', $logoPath, 'string', true);
        AppSetting::set('brand_primary', '#891DE2', 'string', true);
        AppSetting::set('brand_accent', '#F443CE', 'string', true);
        AppSetting::set('pwa_theme_color', '#891DE2', 'string', true);

        $branding = app(BrandingService::class);
        $branding->clearCache();

        $this->assertEquals('#891DE2', $branding->getBrandPrimary());
        $this->assertEquals('#F443CE', $branding->getBrandAccent());
        $this->assertEquals('#891DE2', $branding->getPwaThemeColor());
        $this->assertNotNull($branding->getAppLogoUrl());

        $response = $this->get(route('login'));
        $response->assertOk();
        $response->assertSee('--brand-primary: #891DE2;', false);
        $response->assertSee('--brand-accent: #F443CE;', false);
        $response->assertSee($branding->getAppLogoUrl(), false);
    }

    /**
     * Scenario E: Multiple instances isolation (service returns state based on current settings)
     */
    public function test_scenario_e_brand_isolation_across_multiple_configurations(): void
    {
        // Tenant 1
        AppSetting::set('company_name', 'Tenant Satu Aesthetic', 'string', true);
        $branding = app(BrandingService::class);
        $branding->clearCache();
        $this->assertEquals('Tenant Satu Aesthetic', $branding->getCompanyName());
        $this->assertEquals('Absensi Zaiv', $branding->getProductName());

        // Tenant 2
        AppSetting::set('company_name', 'Tenant Dua Barbershop', 'string', true);
        $branding->clearCache();
        $this->assertEquals('Tenant Dua Barbershop', $branding->getCompanyName());
        $this->assertEquals('Absensi Zaiv', $branding->getProductName());
    }

    /**
     * Scenario F: Database setting keys remain standard and unchanged
     */
    public function test_scenario_f_settings_keys_remain_unchanged(): void
    {
        $validKeys = [
            'app_name',
            'app_short_name',
            'company_name',
            'app_tagline',
            'brand_primary',
            'brand_accent',
            'pwa_theme_color',
            'app_logo_path',
            'app_icon_path',
            'app_favicon_path',
        ];

        foreach ($validKeys as $key) {
            AppSetting::set($key, 'test_val', 'string', true);
            $this->assertEquals('test_val', AppSetting::get($key));
        }
    }

    /**
     * Scenario G: PWA service worker namespace migration and legacy cleanup
     */
    public function test_scenario_g_service_worker_upgrades_to_v4_and_cleans_legacy_cache(): void
    {
        $sw = file_get_contents(public_path('sw.js'));
        $this->assertStringContainsString("const CACHE_NAME = 'absensi-zaiv-static-v4';", $sw);
        $this->assertStringContainsString("cacheName.startsWith('selon-beauty-')", $sw);
        $this->assertStringContainsString("cacheName.startsWith('absensi-zaiv-')", $sw);
        $this->assertStringContainsString("caches.delete(cacheName)", $sw);
    }

    /**
     * Scenario H: Authenticated session remains active and secure
     */
    public function test_scenario_h_authenticated_session_persists_across_branding_change(): void
    {
        AppSetting::set('company_name', 'Original Brand', 'string', true);
        app(BrandingService::class)->clearCache();

        $this->actingAs($this->employeeUser);
        $this->assertAuthenticatedAs($this->employeeUser);

        // Simulate rebranding update
        AppSetting::set('company_name', 'Updated Brand New', 'string', true);
        app(BrandingService::class)->clearCache();

        // User remains fully authenticated
        $res = $this->get(route('employee.dashboard'));
        $res->assertOk();
        $this->assertAuthenticatedAs($this->employeeUser);
        $res->assertSee('Updated Brand New');
    }

    /**
     * Test invalid hex color fallback
     */
    public function test_invalid_hex_colors_fall_back_safely_to_product_defaults(): void
    {
        AppSetting::set('brand_primary', 'not-a-color', 'string', true);
        AppSetting::set('brand_accent', '#123', 'string', true);
        AppSetting::set('pwa_theme_color', 'blue', 'string', true);

        $branding = app(BrandingService::class);
        $branding->clearCache();

        $this->assertEquals('#0F766E', $branding->getBrandPrimary());
        $this->assertEquals('#0284C7', $branding->getBrandAccent());
        $this->assertEquals('#0F766E', $branding->getPwaThemeColor());
    }

    /**
     * Test offline page copy improvement
     */
    public function test_offline_page_copy_improvement(): void
    {
        $response = $this->get('/offline.html');
        $response->assertOk();
        $response->assertSee('Absensi Zaiv memerlukan koneksi internet untuk memverifikasi dan menyimpan presensi secara aman.');
        $response->assertDontSee('Sistem Presensi Absensi Zaiv memerlukan verifikasi server waktu nyata');
    }

    /**
     * Test PWA manifest dynamic resolution and icon sizes
     */
    public function test_pwa_manifest_dynamic_resolution_and_icons(): void
    {
        // 1. Fresh install without custom media
        AppSetting::query()->delete();
        app(BrandingService::class)->clearCache();

        $res = $this->get('/manifest.webmanifest');
        $res->assertOk();
        $json = $res->json();
        $this->assertEquals('Absensi Zaiv', $json['name']);
        $this->assertEquals('#0F766E', $json['theme_color']);
        $this->assertStringEndsWith('/icons/icon-192x192.png', $json['icons'][0]['src']);
        $this->assertStringEndsWith('/icons/icon-512x512.png', $json['icons'][1]['src']);
        $this->assertStringEndsWith('/icons/maskable-icon-512x512.png', $json['icons'][2]['src']);

        // 2. Custom organization branding
        AppSetting::set('company_name', 'Klinik Cantik', 'string', true);
        AppSetting::set('brand_primary', '#4F46E5', 'string', true);
        app(BrandingService::class)->clearCache();

        $customRes = $this->get('/manifest.webmanifest');
        $customJson = $customRes->json();
        $this->assertEquals('Klinik Cantik (Absensi Zaiv)', $customJson['name']);
        $this->assertEquals('Klinik Cantik', $customJson['short_name']);
        $this->assertEquals('#4F46E5', $customJson['theme_color']);
    }
}
