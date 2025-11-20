<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * Redirect to Google OAuth consent screen.
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google OAuth callback.
     */
    public function callback(): RedirectResponse
    {
        try {
            // Get user info from Google
            $googleUser = Socialite::driver('google')->user();

            // Check if user already exists by email OR google_id
            $user = User::where('email', $googleUser->getEmail())
                ->orWhere('google_id', $googleUser->getId())
                ->first();

            if ($user) {
                // Update existing user's Google info (auto-link accounts)
                // Preserve the existing role - don't change it
                $user->update([
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(), // Update email in case it changed
                    // Role is NOT updated - preserve existing role
                ]);
            } else {
                // Create new user with 'user' role by default
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                    'role' => 'user', // New Google users are regular users by default
                    'email_verified_at' => now(),
                ]);
            }

            // Log the user in
            Auth::login($user, true);

            // Redirect to dashboard
            return redirect()->intended('/records');

        } catch (\Exception $e) {
            return redirect('/login')->with('error', 'Failed to authenticate with Google. Please try again.');
        }
    }

    /**
     * Log the user out.
     */
    public function logout(): RedirectResponse
    {
        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect('/login');
    }
}
