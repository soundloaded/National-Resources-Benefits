<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Notifications\LoginOtpNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LoginOtpController extends Controller
{
    /**
     * Display the login OTP verification page.
     */
    public function show(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        // If user has already verified OTP, redirect to dashboard
        if ($user->login_otp_verified) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        return Inertia::render('Auth/LoginOtp', [
            'email' => $this->maskEmail($user->email),
            'status' => session('status'),
        ]);
    }

    /**
     * Verify the login OTP.
     */
    public function verify(Request $request)
    {
        $request->validate([
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $user = $request->user();

        if (!$user->verifyLoginOtp($request->otp)) {
            return back()->withErrors(['otp' => 'The verification code is invalid or has expired.']);
        }

        // Check if email verification is required
        if (!$user->hasVerifiedEmail()) {
            return Inertia::location(route('verification.notice'));
        }

        // Check if ID.me verification is required
        if (config('services.idme.required', false) && !$user->idme_verified_at) {
            return Inertia::location(route('auth.idme.verify'));
        }

        return Inertia::location(route('dashboard'));
    }

    /**
     * Resend the login OTP.
     */
    public function resend(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Generate new OTP and send notification
        $otp = $user->generateLoginOtp();
        $user->notify(new LoginOtpNotification($otp));

        return back()->with('status', 'otp-sent');
    }

    /**
     * Mask email for display (e.g., j***@example.com).
     */
    protected function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        $name = $parts[0];
        $domain = $parts[1] ?? '';

        if (strlen($name) <= 2) {
            $maskedName = $name[0] . '***';
        } else {
            $maskedName = $name[0] . str_repeat('*', strlen($name) - 2) . substr($name, -1);
        }

        return $maskedName . '@' . $domain;
    }
}
