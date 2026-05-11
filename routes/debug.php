<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Models\AdminPrivilege;

Route::middleware('admin')->group(function () {
    Route::get('debug/user-state', function () {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['error' => 'Not logged in']);
        }

        $allRoleKeys = [];
        if (!empty($user->role)) {
            $allRoleKeys[] = $user->role;
        }
        foreach ($user->roles as $role) {
            $allRoleKeys[] = $role->role_key;
        }
        $allRoleKeys = array_unique($allRoleKeys);

        return response()->json([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role_field' => $user->role ?? null,
            'is_active' => $user->is_active,
            'all_role_keys' => array_values($allRoleKeys),
            'has_super_admin' => in_array('super_admin', $allRoleKeys),
            'assigned_roles' => $user->roles->pluck('role_key')->all(),
        ]);
    });

    Route::get('debug/permissions', function () {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['error' => 'Not logged in']);
        }

        $privileges = AdminPrivilege::where('user_id', $user->id)->get();

        return response()->json([
            'user_id' => $user->id,
            'total_permissions' => count($privileges),
            'permissions' => $privileges->map(fn($p) => [
                'page_key' => $p->page_key,
                'action_key' => $p->action_key,
                'is_allowed' => $p->is_allowed,
            ])->all(),
        ]);
    });

    Route::get('debug/pending-access', function () {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['error' => 'Not logged in']);
        }

        $allRoleKeys = [];
        if (!empty($user->role)) {
            $allRoleKeys[] = $user->role;
        }
        foreach ($user->roles as $role) {
            $allRoleKeys[] = $role->role_key;
        }
        $allRoleKeys = array_unique($allRoleKeys);

        $hasEnrollmentApprovePermission = AdminPrivilege::where('user_id', $user->id)
            ->where('page_key', 'enrollment')
            ->where('action_key', 'approve')
            ->where('is_allowed', true)
            ->exists();

        return response()->json([
            'user_id' => $user->id,
            'role_field' => $user->role,
            'has_super_admin_role' => in_array('super_admin', $allRoleKeys),
            'has_enrollment_approve_permission' => $hasEnrollmentApprovePermission,
            'middleware_check_passes' => in_array('super_admin', $allRoleKeys),
            'should_pass' => 'User should have either role=super_admin OR enrollment,approve permission',
        ]);
    });
});
