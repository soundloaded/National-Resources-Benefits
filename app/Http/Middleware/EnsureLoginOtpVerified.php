<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureLoginOtpVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // If user is not authenticated, let auth middleware handle it
        if (!$user) {
            return $next($request);
        }

        // Check if login OTP is enabled
        $loginOtpEnabled = \App\Models\Setting::get('login_otp_enabled', true);
        
        // Skip OTP check if disabled or already verified
        if (!$loginOtpEnabled || $user->login_otp_verified) {
            return $next($request);
        }

        // Redirect to OTP verification if not verified
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Login OTP verification required.'], 403);
        }

        return redirect()->route('auth.login-otp.show');
    }
}
