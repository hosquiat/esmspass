<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BasicAuthController extends Controller
{
    /**
     * Handle email/password login.
     */
    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = \App\Models\User::where('email', $request->email)->first();

        // Check if user exists
        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        // Check if user has a password set (not Google-only account)
        if (is_null($user->password)) {
            throw ValidationException::withMessages([
                'email' => ['This account uses Google sign-in. Please use the "Sign in with Google" button.'],
            ]);
        }

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        // Log the user in
        Auth::login($user, $request->filled('remember'));

        $request->session()->regenerate();

        // Check if password needs to be changed
        if ($request->password === 'changeme') {
            $request->session()->put('must_change_password', true);
            return redirect()->route('password.change.form')
                ->with('warning', 'Please change your password from the default value.');
        }

        return redirect()->intended('/records');
    }

    /**
     * Show the password change form.
     */
    public function showChangePasswordForm(): View
    {
        return view('auth.change-password');
    }

    /**
     * Handle password change.
     */
    public function changePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]+$/'
            ],
        ], [
            'new_password.regex' => 'The password must contain at least one uppercase letter, one lowercase letter, one number, and one special character (@$!%*?&#).',
        ]);

        $user = Auth::user();

        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        // Update password
        $user->password = Hash::make($request->new_password);
        $user->save();

        // Clear the must change password flag
        $request->session()->forget('must_change_password');

        return redirect()->route('records.index')
            ->with('success', 'Password changed successfully.');
    }
}
