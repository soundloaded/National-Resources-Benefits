<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckUserActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip check if admin is impersonating
        if (session()->has('impersonator_id')) {
            return $next($request);
        }

        if (Auth::check() && !Auth::user()->is_active) {
            
            // Allow admin impersonators to still view specific pages if needed?
            // But usually even plugins shouldn't access inactive users unless viewing them.
            // If main user is inactive, they should be out.
            
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => 'Your account has been deactivated. Please contact support.']);
        }

        return $next($request);
    }
}
