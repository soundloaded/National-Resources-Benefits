<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLoginOtpIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Check if login OTP is enabled
        $loginOtpEnabled = \App\Models\Setting::get('login_otp_enabled', true);

        if ($loginOtpEnabled && !$user->login_otp_verified) {
            return redirect()->route('auth.login-otp.show');
        }

        return $next($request);
    }
}
