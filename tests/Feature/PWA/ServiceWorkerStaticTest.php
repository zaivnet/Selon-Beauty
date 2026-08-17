<?php

namespace Tests\Feature\PWA;

use Tests\TestCase;

class ServiceWorkerStaticTest extends TestCase
{
    public function test_service_worker_file_exists_and_has_valid_version(): void
    {
        $swPath = public_path('sw.js');
        $this->assertFileExists($swPath);

        $content = file_get_contents($swPath);
        $this->assertStringContainsString("const CACHE_NAME = 'selon-beauty-static-v3';", $content);
    }

    public function test_manifest_file_exists_and_is_valid_json(): void
    {
        $manifestPath = public_path('manifest.webmanifest');
        $this->assertFileExists($manifestPath);

        $json = json_decode(file_get_contents($manifestPath), true);
        $this->assertIsArray($json);
        $this->assertEquals('standalone', $json['display']);
        $this->assertEquals('/app/dashboard', $json['start_url']);
    }

    public function test_service_worker_enforces_network_only_for_private_and_authenticated_routes(): void
    {
        $content = file_get_contents(public_path('sw.js'));

        $privateRoutes = [
            "'/admin'",
            "'/employee'",
            "'/attendance'",
            "'/leave-requests'",
            "'/overtime-requests'",
            "'/overtime-sessions'",
            "'/shift-swaps'",
            "'/reports'",
            "'/monthly-recaps'",
            "'/notifications'",
            "'/profile'",
            "'/selfie'",
            "'/attachments'",
            "'/storage'",
            "'/login'",
            "'/logout'",
            "'/password'",
            "'/api'",
        ];

        foreach ($privateRoutes as $route) {
            $this->assertStringContainsString($route, $content, "Service worker missing Network-Only rule for private route: {$route}");
        }
    }

    public function test_service_worker_safely_cleans_up_old_application_caches(): void
    {
        $content = file_get_contents(public_path('sw.js'));
        $this->assertStringContainsString("cacheName.startsWith('selon-beauty-')", $content);
        $this->assertStringContainsString('cacheName !== CACHE_NAME', $content);
        $this->assertStringContainsString('caches.delete(cacheName)', $content);
    }
}
