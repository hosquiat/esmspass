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

            // Check if email is in admin list
            $adminEmails = explode(',', env('ADMIN_EMAILS', ''));
            $adminEmails = array_map('trim', $adminEmails);
            $isAdmin = in_array($googleUser->getEmail(), $adminEmails);

            // Check if user already exists
            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                // Update existing user's Google info and role
                $user->update([
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                    'name' => $googleUser->getName(),
                    'role' => $isAdmin ? 'admin' : 'user',
                ]);
            } else {
                // Create new user
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                    'role' => $isAdmin ? 'admin' : 'user',
                    'email_verified_at' => now(),
                ]);
            }

            // Log the user in
            Auth::login($user, true);

            // Redirect to dashboard
            return redirect()->intended('/records');

        } catch (\Exception $e) {
            return redirect('/login')->with('error', 'Failed to authenticate with Google. Please try again.', $e);
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
