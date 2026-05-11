<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use App\Models\AdminPrivilege;
use App\Services\AdminAccessService;

Route::middleware('admin')->group(function () {
    /**
     * Clear all caches and sync fresh permissions
     */
    Route::get('debug/clear-and-sync/{userId?}', function (?int $userId = null) {
        try {
            // Get the user
            $user = $userId ? \App\Models\User::findOrFail($userId) : Auth::user();
            if (!$user) {
                return response()->json(['error' => 'Not authenticated']);
            }

            // Clear config cache
            Artisan::call('config:clear');
            
            // Clear all cache
            Artisan::call('cache:clear');

            // Sync fresh permissions
            $adminAccessService = app(AdminAccessService::class);
            $adminAccessService->syncPrivilegeCatalogForUser($user);

            // Get updated permissions
            $privileges = AdminPrivilege::where('user_id', $user->id)
                ->where('page_key', 'enrollment')
                ->orderBy('action_key')
                ->get();

            return response()->json([
                'message' => 'Cache cleared and permissions synced',
                'user_id' => $user->id,
                'user_name' => $user->name,
                'enrollment_permissions' => $privileges->map(fn($p) => [
                    'action' => $p->action_key,
                    'is_allowed' => $p->is_allowed,
                ])->all(),
                'total_enrollment_permissions' => $privileges->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    })->name('admin.debug.clear-and-sync');

    /**
     * Check current permissions for a specific user
     */
    Route::get('debug/check-permissions/{userId?}', function (?int $userId = null) {
        $user = $userId ? \App\Models\User::findOrFail($userId) : Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Not authenticated']);
        }

        $enrollmentPrivileges = AdminPrivilege::where('user_id', $user->id)
            ->where('page_key', 'enrollment')
            ->orderBy('action_key')
            ->get();

        $approvePermission = AdminPrivilege::where('user_id', $user->id)
            ->where('page_key', 'enrollment')
            ->where('action_key', 'approve')
            ->first();

        return response()->json([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_role' => $user->role,
            'is_super_admin_role' => $user->role === 'super_admin',
            'enrollment_permissions' => $enrollmentPrivileges->map(fn($p) => [
                'action' => $p->action_key,
                'is_allowed' => (bool) $p->is_allowed,
            ])->all(),
            'has_approve_permission_row' => (bool) $approvePermission,
            'approve_is_allowed' => $approvePermission?->is_allowed ?? null,
        ]);
    })->name('admin.debug.check-permissions');

    /**
     * Grant approve and reject permissions to a user
     */
    Route::get('debug/grant-permissions/{userId?}', function (?int $userId = null) {
        $user = $userId ? \App\Models\User::findOrFail($userId) : Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Not authenticated']);
        }

        try {
            // Grant approve and reject permissions
            $granted = [];
            foreach (['approve', 'reject'] as $action) {
                $priv = AdminPrivilege::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'page_key' => 'enrollment',
                        'action_key' => $action,
                    ],
                    [
                        'group_key' => 'back_office_management',
                        'group_title' => 'BACK OFFICE MANAGEMENT',
                        'page_title' => 'Enrollment Records',
                        'action_title' => ucfirst($action),
                        'is_allowed' => true,
                    ]
                );
                $granted[] = $action;
            }

            return response()->json([
                'message' => 'Permissions granted',
                'user_id' => $user->id,
                'user_name' => $user->name,
                'granted_permissions' => $granted,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    })->name('admin.debug.grant-permissions');

    /**
     * Show all available actions in config
     */
    Route::get('debug/available-actions', function () {
        $catalog = config('admin_privileges', []);
        $pages = [];

        foreach ($catalog as $groupKey => $group) {
            foreach ($group['pages'] ?? [] as $page) {
                $pageKey = $page['key'] ?? '';
                if ($pageKey === '') continue;

                $actions = $page['actions'] ?? ['view', 'edit', 'delete'];
                $pages[] = [
                    'page_key' => $pageKey,
                    'page_title' => $page['title'] ?? $pageKey,
                    'actions' => $actions,
                ];
            }
        }

        return response()->json([
            'enrollment_page' => collect($pages)->firstWhere('page_key', 'enrollment'),
            'all_pages' => $pages,
        ]);
    })->name('admin.debug.available-actions');
});
