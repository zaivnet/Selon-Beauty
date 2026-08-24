<?php

namespace Tests\Feature\Admin;

use App\Models\Outlet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsMenuVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;
    protected User $owner;
    protected User $admin;
    protected Outlet $outlet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outlet = Outlet::create([
            'name' => 'Selon Beauty Pusat',
            'code' => 'OUT-001',
            'latitude' => -6.175110,
            'longitude' => 106.827220,
            'radius_meters' => 100,
            'is_active' => true,
        ]);

        $this->superadmin = User::create([
            'name' => 'Super Administrator',
            'email' => 'superadmin@selonbeauty.com',
            'password' => bcrypt('password'),
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $this->owner = User::create([
            'name' => 'Owner System',
            'email' => 'owner@selonbeauty.com',
            'password' => bcrypt('password'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Admin Outlet',
            'email' => 'admin@selonbeauty.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'outlet_id' => $this->outlet->id,
            'is_active' => true,
        ]);
        $this->admin->assignedOutlets()->sync([$this->outlet->id]);
    }

    public function test_superadmin_sees_all_three_settings_tabs(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('admin.settings.attendance'));

        $response->assertOk();
        $response->assertSee('Profil & Branding', false);
        $response->assertSee('Pengaturan Absensi Global', false);
        $response->assertSee('Backup & Restore', false);
    }

    public function test_owner_sees_all_three_settings_tabs(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.settings.attendance'));

        $response->assertOk();
        $response->assertSee('Profil & Branding', false);
        $response->assertSee('Pengaturan Absensi Global', false);
        $response->assertSee('Backup & Restore', false);
    }

    public function test_admin_sees_only_attendance_settings_tab_and_no_unauthorized_links(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.settings.attendance'));

        $response->assertOk();
        $response->assertSee('Pengaturan Absensi Global', false);
        $response->assertDontSee('Profil & Branding', false);
        $response->assertDontSee('Backup & Restore', false);
        $response->assertDontSee(route('admin.settings.branding.index'));
        $response->assertDontSee(route('admin.settings.backups.index'));
        $response->assertDontSee(route('admin.outlets.index'));
    }

    public function test_admin_direct_access_to_branding_and_backups_returns_403(): void
    {
        $responseBranding = $this->actingAs($this->admin)->get(route('admin.settings.branding.index'));
        $responseBranding->assertForbidden();

        $responseBackups = $this->actingAs($this->admin)->get(route('admin.settings.backups.index'));
        $responseBackups->assertForbidden();

        $responseAttendance = $this->actingAs($this->admin)->get(route('admin.settings.attendance'));
        $responseAttendance->assertOk();
    }

    public function test_owner_and_superadmin_direct_access_to_all_three_routes_returns_200(): void
    {
        // Owner
        $this->actingAs($this->owner)->get(route('admin.settings.branding.index'))->assertOk();
        $this->actingAs($this->owner)->get(route('admin.settings.attendance'))->assertOk();
        $this->actingAs($this->owner)->get(route('admin.settings.backups.index'))->assertOk();

        // Superadmin
        $this->actingAs($this->superadmin)->get(route('admin.settings.branding.index'))->assertOk();
        $this->actingAs($this->superadmin)->get(route('admin.settings.attendance'))->assertOk();
        $this->actingAs($this->superadmin)->get(route('admin.settings.backups.index'))->assertOk();
    }
}
