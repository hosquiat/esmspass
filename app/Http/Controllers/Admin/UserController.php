<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * Get all users with their roles.
     */
    public function index(): JsonResponse
    {
        $users = User::select('id', 'name', 'email', 'role', 'google_id', 'avatar', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'avatar' => $user->avatar,
                    'login_type' => $user->google_id ? 'google' : 'email',
                    'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json($users);
    }

    /**
     * Update a user's role.
     */
    public function updateRole(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'role' => ['required', Rule::in(['admin', 'user'])],
        ]);

        // Prevent users from changing their own role
        if ($user->id === auth()->id()) {
            throw ValidationException::withMessages([
                'role' => ['You cannot change your own role.'],
            ]);
        }

        // Check if this is the last admin
        if ($user->role === 'admin' && $request->role === 'user') {
            $adminCount = User::where('role', 'admin')->count();
            if ($adminCount <= 1) {
                throw ValidationException::withMessages([
                    'role' => ['Cannot change the last admin to a regular user.'],
                ]);
            }
        }

        $user->role = $request->role;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'User role updated successfully.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ]);
    }

    /**
     * Delete a user.
     */
    public function destroy(User $user): JsonResponse
    {
        // Prevent users from deleting themselves
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete your own account.',
            ], 403);
        }

        // Check if this is the last admin
        if ($user->role === 'admin') {
            $adminCount = User::where('role', 'admin')->count();
            if ($adminCount <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete the last admin user.',
                ], 403);
            }
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.',
        ]);
    }
}
