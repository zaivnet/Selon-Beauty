<?php

namespace Tests\Feature\Security;

use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\CustomResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_open_forgot_password_page(): void
    {
        $response = $this->get('/forgot-password');
        $response->assertOk();
        $response->assertSee('Lupa Password');
    }

    public function test_registered_active_user_can_request_password_reset(): void
    {
        Notification::fake();

        $user = User::create([
            'name' => 'Active Reset User',
            'email' => 'activeuser@selonbeauty.com',
            'password' => Hash::make('oldpassword123'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        $response = $this->post('/forgot-password', [
            'email' => 'activeuser@selonbeauty.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Jika email tersebut terdaftar, kami akan mengirimkan link untuk mengatur ulang password.');

        Notification::assertSentTo($user, CustomResetPasswordNotification::class);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'password_reset.requested',
        ]);
    }

    public function test_unknown_email_receives_generic_response_without_user_enumeration(): void
    {
        Notification::fake();

        $response = $this->post('/forgot-password', [
            'email' => 'unknown_person_999@selonbeauty.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Jika email tersebut terdaftar, kami akan mengirimkan link untuk mengatur ulang password.');

        Notification::assertNothingSent();
    }

    public function test_inactive_account_receives_generic_response_and_no_reset_email(): void
    {
        Notification::fake();

        $user = User::create([
            'name' => 'Inactive User',
            'email' => 'inactiveuser@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'is_active' => false,
        ]);

        $response = $this->post('/forgot-password', [
            'email' => 'inactiveuser@selonbeauty.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Jika email tersebut terdaftar, kami akan mengirimkan link untuk mengatur ulang password.');

        Notification::assertNothingSent();
    }

    public function test_forgot_password_endpoint_is_rate_limited(): void
    {
        // Execute 6 requests
        for ($i = 0; $i < 6; $i++) {
            $this->post('/forgot-password', ['email' => 'spam@selonbeauty.com']);
        }

        // 7th request must be rate limited (429 Too Many Requests)
        $response = $this->post('/forgot-password', ['email' => 'spam@selonbeauty.com']);
        $response->assertStatus(429);
    }

    public function test_reset_password_page_can_be_rendered_with_valid_token(): void
    {
        $user = User::create([
            'name' => 'Reset View User',
            'email' => 'resetview@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        $token = Password::broker()->createToken($user);

        $response = $this->get('/reset-password/' . $token . '?email=' . urlencode($user->email));
        $response->assertOk();
        $response->assertSee('Buat Password Baru');
    }

    public function test_invalid_reset_token_rejected(): void
    {
        $user = User::create([
            'name' => 'Invalid Token User',
            'email' => 'invalidtoken@selonbeauty.com',
            'password' => Hash::make('oldpassword123'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        $response = $this->post('/reset-password', [
            'token' => 'invalid-token-12345',
            'email' => 'invalidtoken@selonbeauty.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect('/forgot-password');
        $response->assertSessionHas('error');

        $user->refresh();
        $this->assertTrue(Hash::check('oldpassword123', $user->password));
    }

    public function test_successful_reset_updates_password_and_invalidates_old_sessions(): void
    {
        $user = User::create([
            'name' => 'Session Invalidate User',
            'email' => 'invalidateuser@selonbeauty.com',
            'password' => Hash::make('oldpassword123'),
            'role' => 'employee',
            'is_active' => true,
            'remember_token' => 'old_remember_token',
        ]);

        // Insert fake active HTTP session in sessions table
        DB::table('sessions')->insert([
            'id' => 'session_id_123',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => 'payload_data',
            'last_activity' => time(),
        ]);

        $token = Password::broker()->createToken($user);

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => 'invalidateuser@selonbeauty.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('success', 'Password berhasil diperbarui. Silakan login menggunakan password baru.');

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
        $this->assertNotEquals('old_remember_token', $user->remember_token);

        // Assert sessions table was cleared for target user
        $this->assertEquals(0, DB::table('sessions')->where('user_id', $user->id)->count());

        // Assert audit log event
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'password_reset.completed',
        ]);
    }

    public function test_used_reset_token_cannot_be_reused(): void
    {
        $user = User::create([
            'name' => 'Reuse Token User',
            'email' => 'reusetoken@selonbeauty.com',
            'password' => Hash::make('oldpassword123'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        $token = Password::broker()->createToken($user);

        // First reset request (Success)
        $this->post('/reset-password', [
            'token' => $token,
            'email' => 'reusetoken@selonbeauty.com',
            'password' => 'firstnewpass123',
            'password_confirmation' => 'firstnewpass123',
        ]);

        // Second reset request using SAME token (Must fail)
        $responseSecond = $this->post('/reset-password', [
            'token' => $token,
            'email' => 'reusetoken@selonbeauty.com',
            'password' => 'secondnewpass123',
            'password_confirmation' => 'secondnewpass123',
        ]);

        $responseSecond->assertRedirect('/forgot-password');
        $responseSecond->assertSessionHas('error');

        $user->refresh();
        $this->assertTrue(Hash::check('firstnewpass123', $user->password));
        $this->assertFalse(Hash::check('secondnewpass123', $user->password));
    }

    public function test_password_policy_and_confirmation_enforced(): void
    {
        $user = User::create([
            'name' => 'Policy User',
            'email' => 'policyuser@selonbeauty.com',
            'password' => Hash::make('oldpassword123'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        $token = Password::broker()->createToken($user);

        // Short password (< 8 chars)
        $responseShort = $this->post('/reset-password', [
            'token' => $token,
            'email' => 'policyuser@selonbeauty.com',
            'password' => '12345',
            'password_confirmation' => '12345',
        ]);
        $responseShort->assertSessionHasErrors(['password']);

        // Password mismatch
        $responseMismatch = $this->post('/reset-password', [
            'token' => $token,
            'email' => 'policyuser@selonbeauty.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'mismatchpass',
        ]);
        $responseMismatch->assertSessionHasErrors(['password']);

        $user->refresh();
        $this->assertTrue(Hash::check('oldpassword123', $user->password));
    }

    public function test_custom_reset_password_notification_rendering_and_branding(): void
    {
        $user = User::create([
            'name' => 'Notification Test User',
            'email' => 'notification_test@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        $notification = new CustomResetPasswordNotification('fake-token-123');
        $mail = $notification->toMail($user);

        $this->assertStringContainsString('Reset Password', $mail->subject);
        $this->assertStringContainsString('fake-token-123', $mail->actionUrl);
        $this->assertStringContainsString(urlencode('notification_test@selonbeauty.com'), $mail->actionUrl);
        $this->assertNotEmpty($mail->introLines);
    }
}
