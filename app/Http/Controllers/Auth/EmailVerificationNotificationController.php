<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Notifications\EmailVerificationCodeNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        // Generate verification code
        $code = $request->user()->generateEmailVerificationCode();
        
        // Generate verification URL
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(30),
            ['id' => $request->user()->id, 'hash' => sha1($request->user()->email)]
        );
        
        // Send notification with both code and link
        $request->user()->notify(new EmailVerificationCodeNotification($code, $verificationUrl));

        return back()->with('status', 'verification-link-sent');
    }
}
