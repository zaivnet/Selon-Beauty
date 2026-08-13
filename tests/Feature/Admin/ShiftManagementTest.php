<?php

namespace Tests\Feature\Admin;

use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ShiftManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $employeeUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::create([
            'name' => 'Owner System',
            'email' => 'owner@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->employeeUser = User::create([
            'name' => 'Employee Ordinary',
            'email' => 'employee@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'is_active' => true,
        ]);
    }

    public function test_shift_can_be_created(): void
    {
        $response = $this->actingAs($this->owner)->post('/admin/shifts', [
            'name' => 'Shift Pagi Utama',
            'code' => 'pagi', // test lower to upper conversion
            'start_time' => '09:00',
            'end_time' => '17:00',
            'grace_period_minutes' => 10,
            'check_in_open_minutes_before' => 60,
            'check_in_close_minutes_after' => 120,
            'check_out_open_minutes_before' => 60,
            'break_minutes' => 60,
            'is_active' => '1',
        ]);

        $response->assertRedirect('/admin/shifts');

        $this->assertDatabaseHas('shifts', [
            'name' => 'Shift Pagi Utama',
            'code' => 'PAGI',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'grace_period_minutes' => 10,
            'crosses_midnight' => false,
            'is_active' => true,
        ]);
    }

    public function test_shift_code_must_be_unique(): void
    {
        Shift::create([
            'name' => 'Shift Morning',
            'code' => 'PAGI',
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'grace_period_minutes' => 5,
            'check_in_open_minutes_before' => 60,
            'check_in_close_minutes_after' => 120,
            'check_out_open_minutes_before' => 60,
            'break_minutes' => 60,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->owner)->post('/admin/shifts', [
            'name' => 'Shift Pagi Duplicate',
            'code' => 'PAGI',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'grace_period_minutes' => 5,
            'check_in_open_minutes_before' => 60,
            'check_in_close_minutes_after' => 120,
            'check_out_open_minutes_before' => 60,
            'break_minutes' => 60,
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_invalid_time_settings_rejected(): void
    {
        $response = $this->actingAs($this->owner)->post('/admin/shifts', [
            'name' => 'Shift Invalid Time',
            'code' => 'INVALID',
            'start_time' => 'invalid-time',
            'end_time' => '17:00',
            'grace_period_minutes' => -5,
            'check_in_open_minutes_before' => 60,
            'check_in_close_minutes_after' => 120,
            'check_out_open_minutes_before' => 60,
            'break_minutes' => 60,
        ]);

        $response->assertSessionHasErrors(['start_time', 'grace_period_minutes']);
    }

    public function test_cross_midnight_supported(): void
    {
        $response = $this->actingAs($this->owner)->post('/admin/shifts', [
            'name' => 'Shift Malam Lintas Hari',
            'code' => 'NIGHT',
            'start_time' => '22:00',
            'end_time' => '06:00',
            'grace_period_minutes' => 5,
            'check_in_open_minutes_before' => 60,
            'check_in_close_minutes_after' => 120,
            'check_out_open_minutes_before' => 60,
            'break_minutes' => 60,
            'is_active' => '1',
        ]);

        $response->assertRedirect('/admin/shifts');

        $shift = Shift::where('code', 'NIGHT')->first();
        $this->assertNotNull($shift);
        $this->assertTrue($shift->crosses_midnight);
        $this->assertEquals(480, $shift->work_duration_minutes); // 8 hours = 480 mins
        $this->assertEquals(420, $shift->net_work_duration_minutes); // 480 - 60 break = 420 mins
    }

    public function test_shift_can_be_updated(): void
    {
        $shift = Shift::create([
            'name' => 'Shift Temporary',
            'code' => 'TEMP',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'grace_period_minutes' => 5,
            'check_in_open_minutes_before' => 60,
            'check_in_close_minutes_after' => 120,
            'check_out_open_minutes_before' => 60,
            'break_minutes' => 60,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->owner)->put("/admin/shifts/{$shift->id}", [
            'name' => 'Shift Updated',
            'code' => 'TEMP',
            'start_time' => '08:30',
            'end_time' => '16:30',
            'grace_period_minutes' => 10,
            'check_in_open_minutes_before' => 60,
            'check_in_close_minutes_after' => 120,
            'check_out_open_minutes_before' => 60,
            'break_minutes' => 30,
        ]);

        $response->assertRedirect('/admin/shifts');

        $this->assertDatabaseHas('shifts', [
            'id' => $shift->id,
            'name' => 'Shift Updated',
            'start_time' => '08:30',
            'grace_period_minutes' => 10,
        ]);
    }

    public function test_inactive_shift_toggle_works(): void
    {
        $shift = Shift::create([
            'name' => 'Shift Toggle',
            'code' => 'TOGGLE',
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'grace_period_minutes' => 5,
            'check_in_open_minutes_before' => 60,
            'check_in_close_minutes_after' => 120,
            'check_out_open_minutes_before' => 60,
            'break_minutes' => 60,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->owner)->post("/admin/shifts/{$shift->id}/toggle-status");

        $response->assertRedirect();
        $this->assertDatabaseHas('shifts', [
            'id' => $shift->id,
            'is_active' => false,
        ]);
    }

    public function test_employee_cannot_access_shift_management(): void
    {
        $response = $this->actingAs($this->employeeUser)->get('/admin/shifts');

        $response->assertRedirect('/app/dashboard');
        $response->assertSessionHas('error');
    }
}
