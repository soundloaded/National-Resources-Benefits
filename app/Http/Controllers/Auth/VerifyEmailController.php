<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        $user = $request->user();

        // Check if login OTP is required and not verified
        $loginOtpEnabled = \App\Models\Setting::get('login_otp_enabled', true);
        if ($loginOtpEnabled && !$user->login_otp_verified) {
            return redirect()->route('auth.login-otp.show')
                ->with('status', 'Email verified! Please complete OTP verification.');
        }

        // Check if ID.me verification is required
        if (config('services.idme.required', false) && !$user->idme_verified_at) {
            return redirect()->route('auth.idme.verify')
                ->with('status', 'Email verified! Please complete ID.me verification.');
        }

        return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
    }

    /**
     * Verify email using the verification code.
     */
    public function verifyCode(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('dashboard')->with('status', 'Email already verified');
        }

        if ($user->verifyEmailCode($request->code)) {
            event(new Verified($user));
            return redirect()->route('dashboard')->with('status', 'Email verified successfully');
        }

        return back()->withErrors([
            'code' => 'The verification code is invalid or has expired.',
        ]);
    }
}
