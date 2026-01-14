<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureKycVerified
{
    /**
     * Routes that require KYC verification.
     * Users without verified KYC will be redirected to KYC page.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Check if user has verified KYC
        if (!$user->kyc_verified_at) {
            // For Inertia requests, redirect with a flash message
            if ($request->header('X-Inertia')) {
                return redirect()->route('kyc.index')->with('warning', 'Please complete your KYC verification to access this feature.');
            }

            // For regular requests
            return redirect()->route('kyc.index')->with('warning', 'Please complete your KYC verification to access this feature.');
        }

        return $next($request);
    }
}
