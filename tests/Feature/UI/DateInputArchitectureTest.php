<?php

namespace Tests\Feature\UI;

use App\Models\User;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DateInputArchitectureTest extends TestCase
{
    use RefreshDatabase;

    public function test_date_input_component_renders_ios_date_field_wrapper_clipping_architecture(): void
    {
        $view = $this->blade('<x-date-input name="test_date" value="2026-08-13" required />');

        $view->assertSee('class="ios-date-field', false);
        $view->assertSee('type="date"', false);
        $view->assertSee('name="test_date"', false);
        $view->assertSee('value="2026-08-13"', false);
        $view->assertSee('required', false);
    }

    public function test_employee_leave_requests_view_uses_ios_date_field_architecture(): void
    {
        $emp = Employee::create([
            'employee_code' => 'EMP-TEST-01',
            'full_name' => 'Karyawan Test',
            'status' => 'active',
            'join_date' => '2026-01-01',
        ]);

        $user = User::create([
            'employee_id' => $emp->id,
            'name' => 'Karyawan Test',
            'email' => 'karyawan.test@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('employee.leave-requests.index'));

        $response->assertStatus(200);
        $response->assertSee('ios-date-field', false);
    }

    public function test_admin_attendance_view_uses_ios_date_field_architecture(): void
    {
        $owner = User::create([
            'name' => 'Owner Admin Test',
            'email' => 'owner.test@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        $response = $this->actingAs($owner)->get(route('admin.attendance.index'));

        $response->assertStatus(200);
        $response->assertSee('ios-date-field', false);
    }

    public function test_admin_reports_attendance_view_uses_ios_date_field_architecture(): void
    {
        $owner = User::create([
            'name' => 'Owner Admin Test 2',
            'email' => 'owner.test2@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        $response = $this->actingAs($owner)->get(route('admin.reports.attendance'));

        $response->assertStatus(200);
        $response->assertSee('ios-date-field', false);
    }
}
