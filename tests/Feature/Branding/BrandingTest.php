<?php

namespace Tests\Feature\Branding;

use App\Models\AppSetting;
use App\Models\User;
use App\Services\BrandingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class BrandingTest extends TestCase
{
    use RefreshDatabase;

    protected User $ownerUser;

    protected User $employeeUser;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->ownerUser = User::create([
            'name' => 'Owner Utama',
            'email' => 'owner@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->employeeUser = User::create([
            'name' => 'Karyawan',
            'email' => 'karyawan@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'is_active' => true,
        ]);
    }

    public function test_owner_can_update_app_name_and_branding(): void
    {
        $response = $this->actingAs($this->ownerUser)->post(route('admin.settings.branding.update'), [
            'app_name' => 'BEAUTY SALON PRO',
            'app_short_name' => 'BEAUTY PRO',
            'company_name' => 'PT Beauty Indonesia',
            'app_tagline' => 'Sistem Presensi Modern',
            'brand_primary' => '#111827',
            'brand_accent' => '#F43F5E',
            'pwa_theme_color' => '#111827',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('BEAUTY SALON PRO', AppSetting::get('app_name'));
        $this->assertEquals('#111827', AppSetting::get('brand_primary'));
    }

    public function test_employee_cannot_update_branding(): void
    {
        $response = $this->actingAs($this->employeeUser)->post(route('admin.settings.branding.update'), [
            'app_name' => 'HACKED BRAND',
            'app_short_name' => 'HACKED',
            'brand_primary' => '#000000',
            'brand_accent' => '#000000',
            'pwa_theme_color' => '#000000',
        ]);

        $response->assertRedirect(route('employee.dashboard'));
        $this->assertNotEquals('HACKED BRAND', AppSetting::get('app_name'));
    }

    public function test_invalid_hex_color_rejected(): void
    {
        $response = $this->actingAs($this->ownerUser)->post(route('admin.settings.branding.update'), [
            'app_name' => 'BEAUTY PRO',
            'app_short_name' => 'BEAUTY',
            'brand_primary' => 'invalid-color-code',
            'brand_accent' => '#F43F5E',
            'pwa_theme_color' => '#111827',
        ]);

        $response->assertSessionHasErrors('brand_primary');
    }

    public function test_invalid_logo_mime_rejected(): void
    {
        $invalidFile = UploadedFile::fake()->create('script.php', 100, 'text/x-php');

        $response = $this->actingAs($this->ownerUser)->post(route('admin.settings.branding.update'), [
            'app_name' => 'BEAUTY PRO',
            'app_short_name' => 'BEAUTY',
            'brand_primary' => '#111827',
            'brand_accent' => '#F43F5E',
            'pwa_theme_color' => '#111827',
            'logo' => $invalidFile,
        ]);

        $response->assertSessionHasErrors('logo');
    }

    public function test_branding_logo_stored_safely(): void
    {
        $logoFile = UploadedFile::fake()->image('logo.png');

        $response = $this->actingAs($this->ownerUser)->post(route('admin.settings.branding.update'), [
            'app_name' => 'NEW SALON',
            'app_short_name' => 'SALON',
            'brand_primary' => '#111827',
            'brand_accent' => '#F43F5E',
            'pwa_theme_color' => '#111827',
            'logo' => $logoFile,
        ]);

        $response->assertSessionHas('success');

        $logoPath = AppSetting::get('app_logo_path');
        $this->assertNotNull($logoPath);
        Storage::disk('public')->assertExists($logoPath);
    }

    public function test_uploaded_logo_is_served_without_public_storage_symlink(): void
    {
        $this->actingAs($this->ownerUser)->post(route('admin.settings.branding.update'), [
            ...$this->validPayload(),
            'logo' => UploadedFile::fake()->image('logo.png', 320, 120),
        ])->assertSessionHas('success');

        $url = app(BrandingService::class)->getAppLogoUrl();

        $this->assertStringStartsWith('/branding/logo?v=', $url);
        $this->assertStringNotContainsString('/storage/', $url);
        $this->get($url)->assertOk()->assertHeader('Content-Type', 'image/png');
    }

    public function test_uploaded_pwa_icon_is_served_with_correct_content_type(): void
    {
        $this->actingAs($this->ownerUser)->post(route('admin.settings.branding.update'), [
            ...$this->validPayload(),
            'icon' => UploadedFile::fake()->image('icon.png', 192, 192),
        ])->assertSessionHas('success');

        $url = app(BrandingService::class)->getAppIconUrl();

        $this->assertStringStartsWith('/branding/pwa-icon?v=', $url);
        $this->get($url)->assertOk()->assertHeader('Content-Type', 'image/png');
    }

    public function test_uploaded_favicon_is_served_with_correct_content_type(): void
    {
        $this->actingAs($this->ownerUser)->post(route('admin.settings.branding.update'), [
            ...$this->validPayload(),
            'favicon' => UploadedFile::fake()->image('favicon.png', 32, 32),
        ])->assertSessionHas('success');

        $url = app(BrandingService::class)->getFaviconUrl();

        $this->assertStringStartsWith('/branding/favicon?v=', $url);
        $this->get($url)->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->actingAs($this->ownerUser)->get(route('admin.settings.branding.index'))
            ->assertOk()
            ->assertSee('type="image/png"', false)
            ->assertSee($url, false);
    }

    public function test_missing_custom_media_falls_back_without_broken_urls(): void
    {
        AppSetting::set('app_logo_path', 'branding/missing-logo.png', 'string', true);
        AppSetting::set('app_icon_path', 'branding/missing-icon.png', 'string', true);
        AppSetting::set('app_favicon_path', 'branding/missing-favicon.png', 'string', true);
        $branding = new BrandingService;

        $this->assertNull($branding->getAppLogoUrl());
        $this->assertStringEndsWith('/icons/icon-192x192.png', $branding->getAppIconUrl());
        $this->assertStringEndsWith('/favicon.ico', $branding->getFaviconUrl());
    }

    public function test_successful_replacement_deletes_old_branding_file(): void
    {
        Storage::disk('public')->put('branding/old-logo.png', 'old-logo');
        AppSetting::set('app_logo_path', 'branding/old-logo.png', 'string', true);

        $this->actingAs($this->ownerUser)->post(route('admin.settings.branding.update'), [
            ...$this->validPayload(),
            'logo' => UploadedFile::fake()->image('new-logo.png', 320, 120),
        ])->assertSessionHas('success');

        Storage::disk('public')->assertMissing('branding/old-logo.png');
        Storage::disk('public')->assertExists(AppSetting::get('app_logo_path'));
    }

    public function test_failed_database_update_removes_new_branding_file(): void
    {
        AppSetting::saving(function (AppSetting $setting): void {
            if ($setting->key === 'app_logo_path') {
                throw new RuntimeException('Simulated setting failure');
            }
        });
        $this->withoutExceptionHandling();

        try {
            $this->actingAs($this->ownerUser)->post(route('admin.settings.branding.update'), [
                ...$this->validPayload(),
                'logo' => UploadedFile::fake()->image('orphan.png', 320, 120),
            ]);
            $this->fail('Database failure was not raised.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated setting failure', $exception->getMessage());
        } finally {
            AppSetting::flushEventListeners();
        }

        $this->assertSame([], Storage::disk('public')->allFiles('branding'));
        $this->assertNull(AppSetting::get('app_logo_path'));
    }

    public function test_dynamic_pwa_manifest_uses_app_name_and_theme_color(): void
    {
        AppSetting::set('app_name', 'DYNAMIC SALON APP', 'string', true);
        AppSetting::set('app_short_name', 'DYNAMIC SALON', 'string', true);
        AppSetting::set('pwa_theme_color', '#123456', 'string', true);

        $response = $this->get(route('pwa.manifest'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/manifest+json; charset=UTF-8');
        $response->assertJson([
            'name' => 'DYNAMIC SALON APP',
            'short_name' => 'DYNAMIC SALON',
            'theme_color' => '#123456',
        ]);
    }

    public function test_manifest_custom_icon_url_is_publicly_accessible(): void
    {
        $this->actingAs($this->ownerUser)->post(route('admin.settings.branding.update'), [
            ...$this->validPayload(),
            'icon' => UploadedFile::fake()->image('manifest-icon.png', 512, 512),
        ])->assertSessionHas('success');

        $iconUrl = $this->get(route('pwa.manifest'))->assertOk()->json('icons.0.src');

        $this->assertStringStartsWith('/branding/pwa-icon?v=', $iconUrl);
        $this->get($iconUrl)->assertOk()->assertHeader('Content-Type', 'image/png');
    }

    public function test_branding_endpoint_rejects_unknown_types_and_traversal_paths(): void
    {
        AppSetting::set('app_logo_path', '../private/secret.txt', 'string', true);

        $this->get('/branding/not-supported')->assertNotFound();
        $this->get('/branding/%2E%2E%2F.env')->assertNotFound();
        $this->get(route('branding.media', ['type' => 'logo']))->assertNotFound();
    }

    public function test_pwa_manifest_start_url_is_root_path(): void
    {
        $response = $this->get(route('pwa.manifest'));

        $response->assertOk();
        $response->assertJson([
            'start_url' => '/',
            'scope' => '/',
            'display' => 'standalone',
        ]);
    }

    public function test_root_route_redirects_guest_to_login(): void
    {
        User::create([
            'name' => 'Superadmin Initial',
            'email' => 'super@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }

    public function test_root_route_redirects_employee_to_employee_dashboard(): void
    {
        $response = $this->actingAs($this->employeeUser)->get('/');

        $response->assertRedirect(route('employee.dashboard'));
    }

    public function test_root_route_redirects_admin_to_admin_dashboard(): void
    {
        $admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get('/');

        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_root_route_redirects_owner_to_admin_dashboard(): void
    {
        $response = $this->actingAs($this->ownerUser)->get('/');

        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_root_route_redirects_superadmin_to_admin_dashboard(): void
    {
        $superadmin = User::create([
            'name' => 'Superadmin Test',
            'email' => 'superadmin@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($superadmin)->get('/');

        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_login_employee_redirect_behavior_unchanged(): void
    {
        $response = $this->post('/login', [
            'login' => $this->employeeUser->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('employee.dashboard'));
    }

    public function test_login_owner_redirect_behavior_unchanged(): void
    {
        $response = $this->post('/login', [
            'login' => $this->ownerUser->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_login_admin_redirect_behavior_unchanged(): void
    {
        $admin = User::create([
            'name' => 'Admin Test 2',
            'email' => 'admin2@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'login' => $admin->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_service_worker_does_not_cache_private_authenticated_html(): void
    {
        $response = $this->get('/sw.js');

        $response->assertOk();
        $content = $response->getContent();

        $this->assertStringContainsString('selon-beauty-static-v2', $content);
        $this->assertStringContainsString("url.pathname === '/'", $content);
        $this->assertStringContainsString("url.pathname === '/login'", $content);
        $this->assertStringContainsString("url.pathname.startsWith('/app')", $content);
        $this->assertStringContainsString("url.pathname.startsWith('/admin')", $content);
        $this->assertStringNotContainsString("'/manifest.webmanifest'", $content);
    }

    public function test_pwa_employee_behavior_does_not_regress(): void
    {
        $this->actingAs($this->employeeUser)
            ->get(route('employee.dashboard'))
            ->assertOk()
            ->assertSee('Home');
    }

    private function validPayload(): array
    {
        return [
            'app_name' => 'BEAUTY PRO',
            'app_short_name' => 'BEAUTY',
            'company_name' => 'PT Beauty Indonesia',
            'app_tagline' => 'Sistem Presensi Modern',
            'brand_primary' => '#111827',
            'brand_accent' => '#F43F5E',
            'pwa_theme_color' => '#111827',
        ];
    }
}
