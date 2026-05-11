<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
        ]);

        $user = User::create([
            'name' => $validated['first_name'] . ' ' . $validated['last_name'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $workspace = Workspace::create([
            'name' => $validated['first_name'] . "'s Workspace",
            'owner_id' => $user->id,
            'plan' => 'free',
            'currency' => 'USD',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully',
            'data' => [
                'user' => $user,
                'workspace' => $workspace,
                'token' => $token,
            ],
        ], 201);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Logged in successfully',
            'data' => [
                'user' => $user->load('workspace'),
                'token' => $token,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->user()->load('workspace'),
        ]);
        
    }
    public function updateProfile(Request $request)
{
    $user = $request->user();
    
    $validated = $request->validate([
        'first_name' => 'sometimes|string|max:255',
        'last_name' => 'sometimes|string|max:255',
        'email' => 'sometimes|email|unique:users,email,' . $user->id,
        'current_password' => 'required_with:password|string',
        'password' => 'sometimes|string|min:8|confirmed',
    ]);
    
    // Verify current password if changing password
    if (isset($validated['password'])) {
        if (!\Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect'
            ], 422);
        }
        $validated['password'] = \Hash::make($validated['password']);
    }
    
    unset($validated['current_password']);
    
    // Update name field if first/last changed
    if (isset($validated['first_name']) || isset($validated['last_name'])) {
        $first = $validated['first_name'] ?? $user->first_name;
        $last = $validated['last_name'] ?? $user->last_name;
        $validated['name'] = trim($first . ' ' . $last);
    }
    
    $user->update($validated);
    
    return response()->json([
        'success' => true,
        'message' => 'Profile updated successfully',
        'data' => $user->fresh()
    ]);
}
}