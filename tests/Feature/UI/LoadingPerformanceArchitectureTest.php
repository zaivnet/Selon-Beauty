<?php

namespace Tests\Feature\UI;

use Tests\TestCase;
use App\Models\User;
use App\Models\Outlet;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LoadingPerformanceArchitectureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_layout_includes_skeleton_loader()
    {
        $user = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('id="app-page-loader"', false);
        $response->assertSee('ui-skeleton', false);
    }

    public function test_employee_layout_includes_skeleton_loader()
    {
        $outlet = Outlet::create([
            'name' => 'Test',
            'code' => 'TEST',
            'latitude' => 0,
            'longitude' => 0,
            'radius_meters' => 50,
            'is_active' => true,
        ]);
        $employee = \App\Models\Employee::create([
            'employee_code' => 'EMP-TEST',
            'full_name' => 'Test Employee',
            'email' => 'test@test.test',
            'status' => 'active',
            'outlet_id' => $outlet->id,
        ]);
        $user = User::factory()->create(['role' => 'employee', 'is_active' => true, 'employee_id' => $employee->id]);
        $employee->update(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('employee.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('id="app-page-loader"', false);
        $response->assertSee('ui-skeleton', false);
    }

    public function test_css_contains_skeleton_classes()
    {
        $cssPath = resource_path('css/app.css');
        $cssContent = file_get_contents($cssPath);

        $this->assertStringContainsString('.ui-skeleton', $cssContent);
        $this->assertStringContainsString('#app-page-loader', $cssContent);
        $this->assertStringContainsString('@media (prefers-reduced-motion: reduce)', $cssContent);
    }

    public function test_js_contains_global_transition_logic()
    {
        $jsPath = resource_path('js/app.js');
        $jsContent = file_get_contents($jsPath);

        $this->assertStringContainsString("document.getElementById('app-page-loader')", $jsContent);
        $this->assertStringContainsString("document.addEventListener('submit'", $jsContent);
        $this->assertStringContainsString("window.addEventListener('pageshow'", $jsContent);
        $this->assertStringContainsString("requestAnimationFrame", $jsContent);
        $this->assertStringContainsString("e.preventDefault()", $jsContent);
    }
}
