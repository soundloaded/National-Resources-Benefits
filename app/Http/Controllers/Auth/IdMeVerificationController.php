<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class IdMeVerificationController extends Controller
{
    /**
     * Show the ID.me verification page
     */
    public function show(Request $request)
    {
        $user = $request->user();
        
        // If already verified, redirect to dashboard
        if ($user->idme_verified_at) {
            return redirect()->route('dashboard');
        }
        
        return Inertia::render('Auth/IdMeVerification', [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }
    
    /**
     * Redirect to ID.me for OAuth authentication
     */
    public function redirect(Request $request)
    {
        $clientId = config('services.idme.client_id');
        $redirectUri = route('auth.idme.callback');
        $scope = 'identity';
        $state = csrf_token();
        
        // Store state in session for validation
        session(['idme_state' => $state]);
        
        $idmeBaseUrl = config('services.idme.sandbox', true)
            ? 'https://api.idmelabs.com'
            : 'https://api.id.me';
        
        $authUrl = $idmeBaseUrl . '/oauth/authorize?' . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => $scope,
            'state' => $state,
        ]);
        
        return redirect($authUrl);
    }
    
    /**
     * Handle the callback from ID.me
     */
    public function callback(Request $request)
    {
        // Validate state
        if ($request->state !== session('idme_state')) {
            Log::warning('ID.me state mismatch', [
                'expected' => session('idme_state'),
                'received' => $request->state,
            ]);
            return redirect()->route('auth.idme.verify')->with('error', 'Invalid authentication state. Please try again.');
        }
        
        // Check for error from ID.me
        if ($request->has('error')) {
            Log::warning('ID.me OAuth error', [
                'error' => $request->error,
                'description' => $request->error_description ?? 'Unknown error',
            ]);
            return redirect()->route('auth.idme.verify')->with('error', $request->error_description ?? 'Authentication failed. Please try again.');
        }
        
        // Exchange code for access token
        $code = $request->code;
        
        try {
            $idmeBaseUrl = config('services.idme.sandbox', true)
                ? 'https://api.idmelabs.com'
                : 'https://api.id.me';
            
            $tokenResponse = Http::asForm()->post($idmeBaseUrl . '/oauth/token', [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => route('auth.idme.callback'),
                'client_id' => config('services.idme.client_id'),
                'client_secret' => config('services.idme.client_secret'),
            ]);
            
            if (!$tokenResponse->successful()) {
                Log::error('ID.me token exchange failed', [
                    'status' => $tokenResponse->status(),
                    'body' => $tokenResponse->body(),
                ]);
                return redirect()->route('auth.idme.verify')->with('error', 'Failed to verify identity. Please try again.');
            }
            
            $tokenData = $tokenResponse->json();
            $accessToken = $tokenData['access_token'];
            
            // Get user info from ID.me
            $userInfoResponse = Http::withToken($accessToken)
                ->get($idmeBaseUrl . '/api/public/v3/attributes.json');
            
            if (!$userInfoResponse->successful()) {
                Log::error('ID.me user info fetch failed', [
                    'status' => $userInfoResponse->status(),
                    'body' => $userInfoResponse->body(),
                ]);
                return redirect()->route('auth.idme.verify')->with('error', 'Failed to retrieve identity information. Please try again.');
            }
            
            $userInfo = $userInfoResponse->json();
            
            // Update user with ID.me verification data
            $user = $request->user();
            $user->update([
                'idme_verified_at' => now(),
                'idme_uuid' => $userInfo['uuid'] ?? null,
            ]);
            
            Log::info('User verified via ID.me', [
                'user_id' => $user->id,
                'idme_uuid' => $userInfo['uuid'] ?? 'unknown',
            ]);
            
            // Clear session state
            session()->forget('idme_state');
            
            return redirect()->route('dashboard')->with('success', 'Your identity has been verified successfully!');
            
        } catch (\Exception $e) {
            Log::error('ID.me verification exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->route('auth.idme.verify')->with('error', 'An error occurred during verification. Please try again.');
        }
    }
    
    /**
     * Skip ID.me verification (for testing or when feature is disabled)
     */
    public function skip(Request $request)
    {
        // Only allow skip if the feature is disabled or in development
        if (app()->environment('local') || !config('services.idme.required', false)) {
            return redirect()->route('dashboard');
        }
        
        return redirect()->route('auth.idme.verify')->with('error', 'Identity verification is required.');
    }
}
