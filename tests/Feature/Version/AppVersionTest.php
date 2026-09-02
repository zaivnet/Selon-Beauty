<?php

namespace Tests\Feature\Version;

use App\Models\User;
use App\Services\AppVersionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppVersionTest extends TestCase
{
    use RefreshDatabase;

    public function test_version_file_exists_and_contains_valid_semver(): void
    {
        $versionFile = base_path('VERSION');
        $this->assertFileExists($versionFile);

        $content = trim((string) file_get_contents($versionFile));
        $this->assertEquals('1.0.0', $content);
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+(-[a-zA-Z0-9.]+)?$/', $content);
    }

    public function test_app_version_service_resolves_correct_version(): void
    {
        AppVersionService::clearCache();
        $version = AppVersionService::getVersion();

        $this->assertEquals('1.0.0', $version);
        $this->assertEquals('1.0.0', (new AppVersionService)->version());
    }

    public function test_config_app_version_matches_service_version(): void
    {
        $this->assertEquals('1.0.0', config('app.version'));
    }

    public function test_admin_pages_render_application_version(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($superadmin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Versi 1.0.0');

        $settingsResponse = $this->actingAs($superadmin)->get(route('admin.settings.attendance'));
        $settingsResponse->assertStatus(200);
        $settingsResponse->assertSee('Versi 1.0.0');
    }
}
