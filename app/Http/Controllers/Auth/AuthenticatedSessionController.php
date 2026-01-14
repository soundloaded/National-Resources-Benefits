<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Notifications\LoginOtpNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();
        
        // Reset OTP verified status for new login session
        $user->update(['login_otp_verified' => false]);
        
        // Check if login OTP is enabled
        $loginOtpEnabled = \App\Models\Setting::get('login_otp_enabled', true);
        
        if ($loginOtpEnabled) {
            // Generate and send OTP
            $otp = $user->generateLoginOtp();
            $user->notify(new LoginOtpNotification($otp));
            
            return redirect()->route('auth.login-otp.show');
        }
        
        // Mark OTP as verified if not required
        $user->update(['login_otp_verified' => true]);
        
        // Check if email verification is required
        if (!$user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }
        
        // Check if ID.me verification is required and user hasn't verified yet
        if (config('services.idme.required', false) && !$user->idme_verified_at) {
            return redirect()->route('auth.idme.verify');
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Clear OTP status before logout
        if ($request->user()) {
            $request->user()->clearLoginOtpStatus();
        }
        
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
