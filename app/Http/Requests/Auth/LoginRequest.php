<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'login.required' => 'Email, Nomor HP, atau Kode Karyawan wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $loginInput = trim($this->input('login'));
        $password = $this->input('password');
        $remember = $this->boolean('remember');

        // Generate phone variations (08xxx, 628xxx, +628xxx)
        $phoneVariations = [$loginInput];
        $cleanPhone = preg_replace('/[^0-9]/', '', $loginInput);
        if ($cleanPhone !== '') {
            $phoneVariations[] = $cleanPhone;
            if (str_starts_with($cleanPhone, '0')) {
                $phoneVariations[] = '62'.substr($cleanPhone, 1);
                $phoneVariations[] = '+62'.substr($cleanPhone, 1);
            } elseif (str_starts_with($cleanPhone, '62')) {
                $phoneVariations[] = '0'.substr($cleanPhone, 2);
                $phoneVariations[] = '+'.$cleanPhone;
            }
        }

        // Query user by email, phone, or linked employee record (email, phone, or employee_code)
        $user = User::query()
            ->where('is_active', true)
            ->where(function ($query) use ($loginInput, $phoneVariations) {
                $query->whereIn('email', [$loginInput, strtolower($loginInput)])
                    ->orWhereIn('phone', $phoneVariations)
                    ->orWhereHas('employee', function ($q) use ($loginInput, $phoneVariations) {
                        $q->whereIn('email', [$loginInput, strtolower($loginInput)])
                            ->orWhereIn('phone', $phoneVariations)
                            ->orWhere('employee_code', strtoupper($loginInput))
                            ->orWhere('employee_code', $loginInput);
                    });
            })
            ->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'login' => 'Email/Nomor HP/Kode Karyawan atau Password yang Anda masukkan tidak sesuai atau akun dalam status nonaktif.',
            ]);
        }

        // Login user via Guard
        Auth::login($user, $remember);

        // Update last login timestamp safely
        $user->update(['last_login_at' => now()]);

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => "Terlalu banyak percobaan login. Silakan coba lagi dalam {$seconds} detik.",
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->input('login')).'|'.$this->ip());
    }
}
