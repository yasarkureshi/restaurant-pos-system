<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->with(['role.permissions', 'restaurant'])->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages(['email' => ['Invalid credentials.']]);
        }

        if (!$user->is_active) {
            return response()->json(['message' => 'Account is disabled.'], 403);
        }

        $user->update(['last_login_at' => now()]);

        $token = $user->createToken('pos-token')->plainTextToken;

        return response()->json([
            'user' => $this->formatUser($user),
            'token' => $token,
        ]);
    }

    public function pinLogin(Request $request)
    {
        $request->validate([
            'restaurant_id' => 'required|exists:restaurants,id',
            'pin_code' => 'required|digits:4,6',
        ]);

        $user = User::where('restaurant_id', $request->restaurant_id)
            ->where('pin_code', $request->pin_code)
            ->where('is_active', true)
            ->with(['role.permissions', 'restaurant'])
            ->first();

        if (!$user) {
            return response()->json(['message' => 'Invalid PIN.'], 401);
        }

        $user->update(['last_login_at' => now()]);
        $token = $user->createToken('pos-pin-token')->plainTextToken;

        return response()->json([
            'user' => $this->formatUser($user),
            'token' => $token,
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load(['role.permissions', 'restaurant.organization']);
        return response()->json(['user' => $this->formatUser($user)]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'avatar' => 'sometimes|nullable|string',
            'current_password' => 'required_with:new_password',
            'new_password' => 'sometimes|min:8|confirmed',
        ]);

        if (isset($validated['new_password'])) {
            if (!Hash::check($validated['current_password'], $user->password)) {
                return response()->json(['message' => 'Current password is incorrect.'], 422);
            }
            $validated['password'] = Hash::make($validated['new_password']);
        }

        unset($validated['current_password'], $validated['new_password'], $validated['new_password_confirmation']);
        $user->update($validated);

        return response()->json(['user' => $this->formatUser($user->load(['role.permissions', 'restaurant']))]);
    }

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar' => $user->avatar,
            'employee_code' => $user->employee_code,
            'restaurant_id' => $user->restaurant_id,
            'role_id' => $user->role_id,
            'is_active' => $user->is_active,
            'last_login_at' => $user->last_login_at,
            'restaurant' => $user->restaurant,
            'role' => $user->role ? [
                'id' => $user->role->id,
                'name' => $user->role->name,
                'slug' => $user->role->slug,
                'permissions' => $user->role->permissions->pluck('slug'),
            ] : null,
        ];
    }
}
