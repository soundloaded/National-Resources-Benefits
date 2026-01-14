<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\EmailVerificationCodeNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('Auth/Register', [
            'referralCode' => $request->query('ref'),
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'referral_code' => ['nullable', 'string', 'exists:users,referral_code'],
            // Funding profile fields
            'funding_type' => ['nullable', 'string', 'max:100'],
            'funding_amount' => ['nullable', 'string', 'max:50'],
            'citizenship_status' => ['nullable', 'string', 'max:50'],
            'zip_code' => ['nullable', 'string', 'max:10'],
            'gender' => ['nullable', 'string', 'in:male,female'],
            'age_range' => ['nullable', 'string', 'max:20'],
            'ethnicity' => ['nullable', 'string', 'max:50'],
            'employment_status' => ['nullable', 'string', 'max:50'],
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
        ]);

        $referrer = null;
        if ($request->filled('referral_code')) {
            $referrer = User::where('referral_code', $request->referral_code)->first();
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'referred_by' => $referrer?->id,
            // Funding profile
            'funding_category' => $request->funding_type,
            'funding_amount' => $request->funding_amount,
            'citizenship_status' => $request->citizenship_status,
            'zip_code' => $request->zip_code,
            'gender' => $request->gender,
            'age_range' => $request->age_range,
            'ethnicity' => $request->ethnicity,
            'employment_status' => $request->employment_status,
        ]);

        if ($referrer) {
             // Create a formal Referral record as well for detailed tracking
            \App\Models\Referral::create([
                'referrer_id' => $referrer->id,
                'referee_id' => $user->id,
                'status' => 'pending',
            ]);
        }

        event(new Registered($user));

        Auth::login($user);

        // Mark OTP as verified for registration (OTP will be required on next login)
        $user->update(['login_otp_verified' => true]);

        // Generate and send email verification code
        $code = $user->generateEmailVerificationCode();
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(30),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );
        $user->notify(new EmailVerificationCodeNotification($code, $verificationUrl));

        // Redirect to email verification
        return redirect()->route('verification.notice');
    }
}
