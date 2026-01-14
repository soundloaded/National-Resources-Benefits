<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Setting;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => session('status'),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar_url' => $user->avatar_url ? Storage::url($user->avatar_url) : null,
                'city' => $user->city,
                'state' => $user->state,
                'zip_code' => $user->zip_code,
                'citizenship_status' => $user->citizenship_status,
                'gender' => $user->gender,
                'age_range' => $user->age_range,
                'ethnicity' => $user->ethnicity,
                'employment_status' => $user->employment_status ?? null,
                'funding_category' => $user->funding_category,
                'funding_amount' => $user->funding_amount,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at->format('M d, Y'),
            ],
        ]);
    }

    /**
     * Display the security settings page.
     */
    public function security(Request $request): Response
    {
        $user = $request->user();
        
        return Inertia::render('Profile/Security', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'two_factor_enabled' => (bool) ($user->two_factor_secret ?? false),
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at->format('M d, Y'),
                'updated_at' => $user->updated_at->format('M d, Y'),
            ],
            'sessions' => $this->getActiveSessions($request),
        ]);
    }

    /**
     * Get active sessions for the user.
     */
    protected function getActiveSessions(Request $request): array
    {
        if (config('session.driver') !== 'database') {
            return [];
        }

        try {
            $sessions = \DB::table('sessions')
                ->where('user_id', $request->user()->id)
                ->orderByDesc('last_activity')
                ->get()
                ->map(function ($session) use ($request) {
                    $userAgent = $session->user_agent ?? '';
                    
                    // Simple user agent parsing without external library
                    $browser = 'Unknown';
                    $platform = 'Unknown';
                    $device = 'Desktop';
                    
                    // Detect browser
                    if (str_contains($userAgent, 'Firefox')) $browser = 'Firefox';
                    elseif (str_contains($userAgent, 'Edg')) $browser = 'Edge';
                    elseif (str_contains($userAgent, 'Chrome')) $browser = 'Chrome';
                    elseif (str_contains($userAgent, 'Safari')) $browser = 'Safari';
                    elseif (str_contains($userAgent, 'Opera')) $browser = 'Opera';
                    
                    // Detect platform
                    if (str_contains($userAgent, 'Windows')) $platform = 'Windows';
                    elseif (str_contains($userAgent, 'Mac')) $platform = 'macOS';
                    elseif (str_contains($userAgent, 'Linux')) $platform = 'Linux';
                    elseif (str_contains($userAgent, 'Android')) $platform = 'Android';
                    elseif (str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad')) $platform = 'iOS';
                    
                    // Detect device type
                    if (str_contains($userAgent, 'Mobile') || str_contains($userAgent, 'Android')) {
                        $device = 'Mobile';
                    } elseif (str_contains($userAgent, 'Tablet') || str_contains($userAgent, 'iPad')) {
                        $device = 'Tablet';
                    }
                    
                    return [
                        'id' => $session->id,
                        'ip_address' => $session->ip_address,
                        'is_current' => $session->id === $request->session()->getId(),
                        'browser' => $browser,
                        'platform' => $platform,
                        'device' => $device,
                        'last_active' => \Carbon\Carbon::createFromTimestamp($session->last_activity)->diffForHumans(),
                    ];
                })
                ->all();

            return $sessions;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        
        $validated = $request->validated();
        
        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar
            if ($user->avatar_url) {
                Storage::disk('public')->delete($user->avatar_url);
            }
            
            $path = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar_url'] = $path;
        }
        
        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit')->with('success', 'Profile updated successfully.');
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Password updated successfully.');
    }

    /**
     * Remove the user's avatar.
     */
    public function removeAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();
        
        if ($user->avatar_url) {
            Storage::disk('public')->delete($user->avatar_url);
            $user->avatar_url = null;
            $user->save();
        }

        return back()->with('success', 'Avatar removed successfully.');
    }

    /**
     * Log out other browser sessions.
     */
    public function logoutOtherSessions(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        if (config('session.driver') === 'database') {
            \DB::table('sessions')
                ->where('user_id', $request->user()->id)
                ->where('id', '!=', $request->session()->getId())
                ->delete();
        }

        return back()->with('success', 'All other sessions have been logged out.');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        // Delete avatar if exists
        if ($user->avatar_url) {
            Storage::disk('public')->delete($user->avatar_url);
        }

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
