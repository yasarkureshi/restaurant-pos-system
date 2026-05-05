<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        $restaurantId = $request->user()->restaurant_id;
        $staff = User::where('restaurant_id', $restaurantId)
            ->with('role')
            ->orderBy('name')
            ->get();
        return response()->json(['staff' => $staff]);
    }

    public function store(Request $request)
    {
        $restaurantId = $request->user()->restaurant_id;
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role_id' => 'required|exists:roles,id',
            'phone' => 'nullable|string|max:20',
            'employee_code' => 'nullable|string|max:50',
            'date_of_joining' => 'nullable|date',
            'salary' => 'nullable|numeric',
            'shift_start' => 'nullable|date_format:H:i',
            'shift_end' => 'nullable|date_format:H:i',
            'pin_code' => 'nullable|digits_between:4,6',
        ]);

        $validated['restaurant_id'] = $restaurantId;
        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);
        return response()->json(['staff' => $user->load('role')], 201);
    }

    public function update(Request $request, User $user)
    {
        abort_if($user->restaurant_id !== $request->user()->restaurant_id, 403);
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'role_id' => 'sometimes|exists:roles,id',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'salary' => 'nullable|numeric',
            'shift_start' => 'nullable|date_format:H:i',
            'shift_end' => 'nullable|date_format:H:i',
            'pin_code' => 'nullable|digits_between:4,6',
            'password' => 'sometimes|min:6',
        ]);
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }
        $user->update($validated);
        return response()->json(['staff' => $user->load('role')]);
    }

    public function roles(Request $request)
    {
        $restaurantId = $request->user()->restaurant_id;
        $roles = Role::where('restaurant_id', $restaurantId)->with('permissions')->get();
        return response()->json(['roles' => $roles]);
    }

    public function permissions()
    {
        $permissions = Permission::orderBy('module')->orderBy('name')->get();
        return response()->json(['permissions' => $permissions->groupBy('module')]);
    }

    public function updateRolePermissions(Request $request, Role $role)
    {
        abort_if($role->restaurant_id !== $request->user()->restaurant_id, 403);
        $validated = $request->validate(['permission_ids' => 'required|array', 'permission_ids.*' => 'exists:permissions,id']);
        $role->permissions()->sync($validated['permission_ids']);
        return response()->json(['role' => $role->load('permissions')]);
    }
}
