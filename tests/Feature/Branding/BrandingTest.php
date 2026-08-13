<?php

namespace Tests\Feature\Branding;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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
}
