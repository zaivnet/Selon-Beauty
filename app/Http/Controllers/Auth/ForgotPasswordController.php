<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    /**
     * Display the form to request a password reset link.
     */
    public function showLinkRequestForm(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Send a reset link to the given user.
     */
    public function sendResetLinkEmail(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
        ]);

        $email = strtolower(trim($request->input('email')));
        $user = User::where('email', $email)->first();

        // If active user exists, create token and dispatch notification
        if ($user && $user->is_active) {
            $token = Password::broker()->createToken($user);
            $user->sendPasswordResetNotification($token);

            AuditLog::log(
                'password_reset.requested',
                $user,
                null,
                [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ],
                null
            );
        }

        // Generic response to prevent user enumeration
        return redirect()->back()->with(
            'status',
            'Jika email tersebut terdaftar, kami akan mengirimkan link untuk mengatur ulang password.'
        );
    }
}
