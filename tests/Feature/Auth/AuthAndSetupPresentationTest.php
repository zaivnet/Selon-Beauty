<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthAndSetupPresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders_absensi_zaiv_branding_and_theme_controls(): void
    {
        $response = $this->get('/login');

        $response->assertOk()
            ->assertSee('Absensi Zaiv')
            ->assertSee('Portal Presensi')
            ->assertSee('Email / Nomor HP / Kode Karyawan')
            ->assertSee('placeholder="name@example.com atau 081234567890"', false)
            ->assertSee('id="toggle-password"', false)
            ->assertSee('aria-label="Mode Terang"', false)
            ->assertSee('aria-label="Mode Gelap"', false)
            ->assertSee('aria-label="Sesuai Sistem"', false)
            ->assertSee('autocomplete="username"', false)
            ->assertSee('autocomplete="current-password"', false)
            ->assertDontSee('contoh@selonbeauty.com');
    }

    public function test_forgot_password_page_renders_theme_controls_and_branding(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertOk()
            ->assertSee('Lupa Password')
            ->assertSee('Absensi Zaiv')
            ->assertSee('Atur Ulang Password Akun')
            ->assertSee('Email Terdaftar *')
            ->assertSee('Kirim Link Reset')
            ->assertSee('Kembali ke Halaman Login')
            ->assertSee('aria-label="Mode Terang"', false)
            ->assertSee('aria-label="Mode Gelap"', false)
            ->assertSee('aria-label="Sesuai Sistem"', false);
    }

    public function test_reset_password_page_renders_theme_controls_and_password_toggles(): void
    {
        $user = User::create([
            'name' => 'Reset Target User',
            'email' => 'resettarget@example.com',
            'password' => bcrypt('oldpass123'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        $token = Password::broker()->createToken($user);

        $response = $this->get('/reset-password/' . $token . '?email=' . urlencode($user->email));

        $response->assertOk()
            ->assertSee('Buat Password Baru')
            ->assertSee('resettarget@example.com')
            ->assertSee('readonly', false)
            ->assertSee('name="password_confirmation"', false)
            ->assertSee('Simpan Password Baru')
            ->assertSee('aria-label="Mode Terang"', false)
            ->assertSee('aria-label="Mode Gelap"', false)
            ->assertSee('aria-label="Sesuai Sistem"', false);
    }

    public function test_setup_wizard_renders_step_indicator_and_theme_controls(): void
    {
        // No superadmin exists, so setup page is accessible
        $response = $this->get('/setup');

        $response->assertOk()
            ->assertSee('Inisialisasi Superadmin Utama')
            ->assertSee('Absensi Zaiv Installer')
            ->assertSee('Lingkungan')
            ->assertSee('Selesai')
            ->assertSee('Profil Organisasi (Opsional)')
            ->assertSee('Nama Aplikasi')
            ->assertSee('Nama Perusahaan / Bisnis')
            ->assertSee('Inisialisasi & Buat Superadmin', false)
            ->assertSee('Role')
            ->assertSee('Superadmin')
            ->assertSee('aria-label="Mode Terang"', false)
            ->assertSee('aria-label="Mode Gelap"', false)
            ->assertSee('aria-label="Sesuai Sistem"', false);
    }

    public function test_auth_pages_do_not_leak_default_or_hardcoded_passwords(): void
    {
        $loginResponse = $this->get('/login');
        $loginResponse->assertDontSee('password123');
        $loginResponse->assertDontSee('admin@selonbeauty.com');

        $forgotResponse = $this->get('/forgot-password');
        $forgotResponse->assertDontSee('password123');
        $forgotResponse->assertDontSee('admin@selonbeauty.com');

        $setupResponse = $this->get('/setup');
        $setupResponse->assertDontSee('password123');
        $setupResponse->assertDontSee('admin@selonbeauty.com');
    }

    public function test_auth_footer_renders_company_name_and_powered_by_product_name(): void
    {
        $response = $this->get('/login');

        $response->assertOk()
            ->assertSee('Powered by Absensi Zaiv')
            ->assertSee('Ade Zaiv');
    }
}
