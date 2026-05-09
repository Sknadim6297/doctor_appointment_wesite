<?php

namespace App\Services;

use App\Models\AdminPrivilege;
use App\Models\AdminRole;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class AdminAccessService
{
    /**
     * Find all sidebar access conflicts for given keys
     * Returns array of all conflicts found
     */
    public function findAllSidebarAccessConflicts(array $selectedSidebarKeys, ?int $excludeUserId = null): array
    {
        $normalizedKeys = collect($selectedSidebarKeys)
            ->filter(fn ($value) => is_string($value) && $value !== '')
            ->unique()
            ->values();

        if ($normalizedKeys->isEmpty()) {
            return [];
        }

        $query = AdminPrivilege::query()
            ->with('user:id,name,first_name,last_name,phone')
            ->where('group_key', 'sidebar')
            ->where('is_allowed', true)
            ->whereIn('page_key', $normalizedKeys->all());

        if ($excludeUserId !== null) {
            $query->where('user_id', '!=', $excludeUserId);
        }

        $conflicts = $query->get();
        
        return $conflicts->map(function ($conflict) {
            $ownerName = trim((string) ($conflict->user?->name ?? ''));
            if ($ownerName === '') {
                $ownerName = trim((string) (($conflict->user?->first_name ?? '') . ' ' . ($conflict->user?->last_name ?? '')));
            }
            if ($ownerName === '') {
                $ownerName = 'Unknown User';
            }

            return [
                'permission_key' => (string) $conflict->page_key,
                'menu_title' => (string) ($conflict->page_title ?: $conflict->page_key),
                'owner_name' => $ownerName,
                'owner_phone' => (string) ($conflict->user?->phone ?? ''),
                'owner_user_id' => (int) $conflict->user_id,
            ];
        })->all();
    }

    /**
     * Finds the FIRST sidebar access conflict
     * Used for backward compatibility
     */
    public function firstSidebarAccessConflict(array $selectedSidebarKeys, ?int $excludeUserId = null): ?array
    {
        $conflicts = $this->findAllSidebarAccessConflicts($selectedSidebarKeys, $excludeUserId);
        return empty($conflicts) ? null : $conflicts[0];
    }

    public function sidebarAccessOwnerName(string $permissionKey): string
    {
        return $this->sidebarAccessOwnerDetails($permissionKey)['name'];
    }

    public function sidebarAccessOwnerDetails(string $permissionKey): array
    {
        $owner = AdminPrivilege::query()
            ->with('user:id,name,first_name,last_name,phone')
            ->where('group_key', 'sidebar')
            ->where('page_key', $permissionKey)
            ->where('action_key', 'view')
            ->where('is_allowed', true)
            ->orderByDesc('updated_at')
            ->first();

        if (!$owner) {
            $superAdminPhone = User::query()
                ->where('role', 'super_admin')
                ->orWhereHas('roles', function ($query) {
                    $query->where('role_key', 'super_admin');
                })
                ->value('phone');

            return [
                'name' => 'Super Admin',
                'phone' => (string) ($superAdminPhone ?? ''),
            ];
        }

        $ownerName = trim((string) ($owner->user?->name ?? ''));
        if ($ownerName !== '') {
            return [
                'name' => $ownerName,
                'phone' => (string) ($owner->user?->phone ?? ''),
            ];
        }

        $fallbackName = trim((string) (($owner->user?->first_name ?? '') . ' ' . ($owner->user?->last_name ?? '')));

        return [
            'name' => $fallbackName !== '' ? $fallbackName : 'Super Admin',
            'phone' => (string) ($owner->user?->phone ?? ''),
        ];
    }

    public function allowedAdminRoles(): array
    {
        $roleKeys = AdminRole::query()->pluck('role_key')->all();

        return array_values(array_unique(array_merge(['super_admin', 'admin'], $roleKeys)));
    }

    public function syncRoles(User $user, array $roleKeys): void
    {
        $cleanRoleKeys = array_values(array_unique(array_filter($roleKeys)));
        $roleIds = AdminRole::query()
            ->whereIn('role_key', $cleanRoleKeys)
            ->pluck('id')
            ->all();

        $user->roles()->sync($roleIds);

        $primaryRole = $cleanRoleKeys[0] ?? 'admin';

        if ($user->role !== 'super_admin') {
            $user->forceFill(['role' => $primaryRole])->save();
        }
    }

    public function syncPrivilegeCatalogForUser(User $user): void
    {
        $catalog = config('admin_privileges', []);
        $defaultActions = $this->defaultActions();

        DB::transaction(function () use ($user, $catalog, $defaultActions): void {
            foreach ($catalog as $groupKey => $group) {
                $groupTitle = (string) ($group['title'] ?? Str::title(str_replace('_', ' ', $groupKey)));

                foreach (($group['pages'] ?? []) as $page) {
                    $pageKey = (string) ($page['key'] ?? '');
                    if ($pageKey === '') {
                        continue;
                    }

                    $pageTitle = (string) ($page['title'] ?? Str::title(str_replace('_', ' ', $pageKey)));
                    $actions = Arr::wrap($page['actions'] ?? $defaultActions);

                    foreach ($actions as $actionKey) {
                        $actionKey = (string) $actionKey;

                        $privilege = AdminPrivilege::query()->firstOrCreate(
                            [
                                'user_id' => $user->id,
                                'page_key' => $pageKey,
                                'action_key' => $actionKey,
                            ],
                            [
                                'group_key' => (string) $groupKey,
                                'group_title' => $groupTitle,
                                'page_title' => $pageTitle,
                                'action_title' => Str::headline($actionKey),
                                'is_allowed' => false,
                            ]
                        );

                        if (!$privilege->wasRecentlyCreated) {
                            $privilege->forceFill([
                                'group_key' => (string) $groupKey,
                                'group_title' => $groupTitle,
                                'page_title' => $pageTitle,
                                'action_title' => Str::headline($actionKey),
                            ])->save();
                        }
                    }
                }
            }
        });
    }

    public function syncSidebarCatalogForUser(User $user): void
    {
        $catalog = $this->sidebarCatalogForView();

        DB::transaction(function () use ($user, $catalog): void {
            foreach ($this->flattenSidebarCatalog($catalog) as $node) {
                $permissionKey = (string) $node['permission_key'];
                $legacyKey = (string) $node['key'];

                $privilege = AdminPrivilege::query()->firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'group_key' => 'sidebar',
                        'page_key' => $permissionKey,
                        'action_key' => 'view',
                    ],
                    [
                        'group_title' => 'Sidebar Permissions',
                        'page_title' => $node['title'],
                        'action_title' => 'Access',
                        'is_allowed' => false,
                    ]
                );

                $legacyPrivilege = AdminPrivilege::query()
                    ->where('user_id', $user->id)
                    ->where('group_key', 'sidebar')
                    ->where('page_key', $legacyKey)
                    ->where('action_key', 'view')
                    ->first();

                if ($legacyPrivilege && $legacyPrivilege->is_allowed && !$privilege->is_allowed) {
                    $privilege->forceFill(['is_allowed' => true])->save();
                }
            }

            $this->syncRoutePrivilegesForSidebarAssignments($user);
        });
    }

    public function syncPrivilegesFromSelection(User $user, array $selectedPrivilegeKeys): void
    {
        $normalizedKeys = collect($selectedPrivilegeKeys)
            ->filter(fn ($value) => is_string($value) && str_contains($value, ':'))
            ->values();

        $this->syncPrivilegeCatalogForUser($user);

        DB::transaction(function () use ($user, $normalizedKeys): void {
            AdminPrivilege::query()
                ->where('user_id', $user->id)
                ->update([
                    'is_allowed' => false,
                    'updated_at' => now(),
                ]);

            if ($normalizedKeys->isEmpty()) {
                return;
            }

            AdminPrivilege::query()
                ->where('user_id', $user->id)
                ->where(function ($query) use ($normalizedKeys) {
                    foreach ($normalizedKeys as $key) {
                        [$pageKey, $actionKey] = explode(':', $key, 2);

                        $query->orWhere(function ($inner) use ($pageKey, $actionKey) {
                            $inner->where('page_key', $pageKey)
                                ->where('action_key', $actionKey);
                        });
                    }
                })
                ->update([
                    'is_allowed' => true,
                    'updated_at' => now(),
                ]);
        });
    }

    public function syncSidebarPrivilegesFromSelection(User $user, array $selectedSidebarKeys): void
    {
        $normalizedKeys = collect($selectedSidebarKeys)
            ->filter(fn ($value) => is_string($value) && $value !== '')
            ->unique()
            ->values();

        $this->syncSidebarCatalogForUser($user);
        $this->syncPrivilegeCatalogForUser($user);

        DB::transaction(function () use ($user, $normalizedKeys): void {
            // Before enabling any sidebar privileges, check for conflicts
            $keysToEnable = $normalizedKeys->all();
            if (!empty($keysToEnable)) {
                // Get all sidebar permissions that would conflict
                $conflictingPrivileges = AdminPrivilege::query()
                    ->where('group_key', 'sidebar')
                    ->where('is_allowed', true)
                    ->whereIn('page_key', $keysToEnable)
                    ->where('user_id', '!=', $user->id)
                    ->with('user')
                    ->get();

                if ($conflictingPrivileges->isNotEmpty()) {
                    $conflictDetails = $conflictingPrivileges->map(fn ($p) => 
                        "'{$p->page_title}' assigned to " . ($p->user?->name ?? 'Unknown')
                    )->implode(', ');
                    
                    throw new \Exception(
                        "Cannot assign the following menu items as they are already assigned to other sub-admins: {$conflictDetails}. " .
                        "Each sidebar menu can only be assigned to ONE sub-admin."
                    );
                }
            }

            // Sidebar selection is the source of truth for non-sidebar route access.
            AdminPrivilege::query()
                ->where('user_id', $user->id)
                ->where('group_key', '!=', 'sidebar')
                ->where('action_key', 'view')
                ->update([
                    'is_allowed' => false,
                    'updated_at' => now(),
                ]);

            AdminPrivilege::query()
                ->where('user_id', $user->id)
                ->where('group_key', 'sidebar')
                ->update([
                    'is_allowed' => false,
                    'sidebar_unique_marker' => null,
                    'updated_at' => now(),
                ]);

            if ($normalizedKeys->isEmpty()) {
                return;
            }

            AdminPrivilege::query()
                ->where('user_id', $user->id)
                ->where('group_key', 'sidebar')
                ->whereIn('page_key', $normalizedKeys->all())
                ->update([
                    'is_allowed' => true,
                    'sidebar_unique_marker' => DB::raw("CONCAT('sidebar:', page_key)"),
                    'updated_at' => now(),
                ]);

            // Also enable corresponding route-level page privileges when a sidebar node is granted.
            $pageKeysToEnable = $this->resolvePageKeysFromSidebarPermissionKeys($normalizedKeys->all());
            $pageActionsToEnable = $this->resolvePageActionKeysFromSidebarPermissionKeys($normalizedKeys->all());

            // Disable all non-sidebar route permissions first
            AdminPrivilege::query()
                ->where('user_id', $user->id)
                ->where('group_key', '!=', 'sidebar')
                ->update([
                    'is_allowed' => false,
                    'updated_at' => now(),
                ]);

            // Then enable only the specific page_key + action_key combinations required by the sidebar permissions
            if (!empty($pageActionsToEnable)) {
                foreach ($pageActionsToEnable as $item) {
                    AdminPrivilege::query()
                        ->where('user_id', $user->id)
                        ->where('page_key', $item['page_key'])
                        ->where('action_key', $item['action_key'])
                        ->where('group_key', '!=', 'sidebar')
                        ->update([
                            'is_allowed' => true,
                            'updated_at' => now(),
                        ]);
                }
            }
        });
    }

    public function hasPrivilege(User $user, string $pageKey, string $actionKey = 'view'): bool
    {
        if ($user->role === 'super_admin') {
            return true;
        }

        $this->syncPrivilegeCatalogForUser($user);

        return AdminPrivilege::query()
            ->where('user_id', $user->id)
            ->where('page_key', $pageKey)
            ->where('action_key', $actionKey)
            ->where('is_allowed', true)
            ->exists();
    }

    public function privilegeCatalogForView(): array
    {
        $catalog = config('admin_privileges', []);
        $defaultActions = $this->defaultActions();

        return collect($catalog)->map(function (array $group, string $groupKey) use ($defaultActions) {
            return [
                'group_key' => $groupKey,
                'group_title' => (string) ($group['title'] ?? Str::headline($groupKey)),
                'pages' => collect($group['pages'] ?? [])->map(function (array $page) use ($defaultActions) {
                    return [
                        'key' => (string) $page['key'],
                        'title' => (string) ($page['title'] ?? Str::headline((string) $page['key'])),
                        'actions' => collect(Arr::wrap($page['actions'] ?? $defaultActions))
                            ->map(fn (string $action) => [
                                'key' => $action,
                                'title' => Str::headline($action),
                                'compound_key' => $page['key'] . ':' . $action,
                            ])
                            ->all(),
                    ];
                })->all(),
            ];
        })->values()->all();
    }

    public function sidebarCatalogForView(): array
    {
        return $this->normalizeSidebarCatalog(config('sidebar_permissions', []));
    }

    public function allowedSidebarKeysForUser(User $user): array
    {
        if ($user->role === 'super_admin') {
            return array_column($this->flattenSidebarCatalog($this->sidebarCatalogForView()), 'permission_key');
        }

        $this->syncSidebarCatalogForUser($user);

        return AdminPrivilege::query()
            ->where('user_id', $user->id)
            ->where('group_key', 'sidebar')
            ->where('is_allowed', true)
            ->pluck('page_key')
            ->all();
    }

    public function visibleSidebarCatalogForUser(User $user): array
    {
        $catalog = $this->sidebarCatalogForView();
        $allowed = array_flip($this->allowedSidebarKeysForUser($user));

        return $this->filterSidebarNodes($catalog, $allowed);
    }

    private function defaultActions(): array
    {
        return ['view', 'edit', 'delete'];
    }

    private function flattenSidebarCatalog(array $nodes): array
    {
        $flat = [];

        foreach ($nodes as $node) {
            if (!is_array($node) || empty($node['key'])) {
                continue;
            }

            $flat[] = [
                'key' => (string) $node['key'],
                'permission_key' => (string) ($node['permission_key'] ?? ('sidebar.' . $node['key'])),
                'title' => (string) ($node['title'] ?? $node['key']),
                'route' => $node['route'] ?? null,
                'route_names' => array_values(array_filter(Arr::wrap($node['route_names'] ?? []))),
            ];

            foreach ($this->flattenSidebarCatalog($node['children'] ?? []) as $child) {
                $flat[] = $child;
            }
        }

        return $flat;
    }

    private function filterSidebarNodes(array $nodes, array $allowed): array
    {
        $filtered = [];

        foreach ($nodes as $node) {
            if (!is_array($node) || empty($node['key'])) {
                continue;
            }

            $permissionKey = (string) ($node['permission_key'] ?? ('sidebar.' . $node['key']));
            $isAllowed = isset($allowed[$permissionKey]);
            // Always recursively filter children to check individual permissions
            $children = $this->filterSidebarNodes($node['children'] ?? [], $allowed);

            if (!$isAllowed && empty($children)) {
                continue;
            }

            $node['children'] = $children;
            $filtered[] = $node;
        }

        return $filtered;
    }

    private function normalizeSidebarCatalog(array $nodes, string $prefix = 'sidebar'): array
    {
        $normalized = [];

        foreach ($nodes as $node) {
            if (!is_array($node) || empty($node['key'])) {
                continue;
            }

            $permissionKey = $prefix . '.' . (string) $node['key'];

            $normalizedNode = $node;
            $normalizedNode['permission_key'] = $permissionKey;
            $normalizedNode['children'] = $this->normalizeSidebarCatalog($node['children'] ?? [], $permissionKey);

            $normalized[] = $normalizedNode;
        }

        return $normalized;
    }

    private function resolvePageKeysFromSidebarPermissionKeys(array $permissionKeys): array
    {
        $nodesByPermissionKey = [];

        foreach ($this->flattenSidebarCatalog($this->sidebarCatalogForView()) as $node) {
            $nodesByPermissionKey[(string) ($node['permission_key'] ?? '')] = $node;
        }

        $pageKeys = [];
        $routePageKeyMap = $this->routeNameToPrivilegePageKeys();

        foreach ($permissionKeys as $permissionKey) {
            $node = $nodesByPermissionKey[(string) $permissionKey] ?? null;
            if (!is_array($node)) {
                continue;
            }

            $routeNamePatterns = collect($node['route_names'] ?? [])
                ->merge([(string) ($node['route'] ?? '')])
                ->filter(fn ($value) => is_string($value) && $value !== '')
                ->unique()
                ->values()
                ->all();

            foreach ($routeNamePatterns as $pattern) {
                $hasWildcard = str_contains($pattern, '*');

                if (!$hasWildcard && isset($routePageKeyMap[$pattern])) {
                    $pageKeys = array_merge($pageKeys, $routePageKeyMap[$pattern]);
                    continue;
                }

                foreach ($routePageKeyMap as $routeName => $keys) {
                    if (Str::is($pattern, $routeName)) {
                        $pageKeys = array_merge($pageKeys, $keys);
                    }
                }
            }
        }

        return array_values(array_unique(array_filter($pageKeys)));
    }

    private function resolvePageActionKeysFromSidebarPermissionKeys(array $permissionKeys): array
    {
        $nodesByPermissionKey = [];

        foreach ($this->flattenSidebarCatalog($this->sidebarCatalogForView()) as $node) {
            $nodesByPermissionKey[(string) ($node['permission_key'] ?? '')] = $node;
        }

        $pageActions = [];
        $routePageActionMap = $this->routeNameToPrivilegePageActions();

        foreach ($permissionKeys as $permissionKey) {
            $node = $nodesByPermissionKey[(string) $permissionKey] ?? null;
            if (!is_array($node)) {
                continue;
            }

            $routeNamePatterns = collect($node['route_names'] ?? [])
                ->merge([(string) ($node['route'] ?? '')])
                ->filter(fn ($value) => is_string($value) && $value !== '')
                ->unique()
                ->values()
                ->all();

            foreach ($routeNamePatterns as $pattern) {
                $hasWildcard = str_contains($pattern, '*');

                if (!$hasWildcard && isset($routePageActionMap[$pattern])) {
                    $pageActions = array_merge($pageActions, $routePageActionMap[$pattern]);
                    continue;
                }

                foreach ($routePageActionMap as $routeName => $actions) {
                    if (Str::is($pattern, $routeName)) {
                        $pageActions = array_merge($pageActions, $actions);
                    }
                }
            }
        }

        $unique = [];
        foreach ($pageActions as $item) {
            $key = $item['page_key'] . ':' . $item['action_key'];
            if (!isset($unique[$key])) {
                $unique[$key] = $item;
            }
        }

        return array_values($unique);
    }

    private function routeNameToPrivilegePageKeys(): array
    {
        $map = [];

        foreach (Route::getRoutes() as $route) {
            $routeName = $route->getName();
            if (!is_string($routeName) || $routeName === '') {
                continue;
            }

            foreach ($route->gatherMiddleware() as $middleware) {
                if (!is_string($middleware) || !str_starts_with($middleware, 'admin.privilege:')) {
                    continue;
                }

                $payload = substr($middleware, strlen('admin.privilege:'));
                if (!is_string($payload) || $payload === '') {
                    continue;
                }

                $parts = array_map('trim', explode(',', $payload));
                $pageKey = $parts[0] ?? '';

                if ($pageKey === '') {
                    continue;
                }

                foreach ($this->routeNameAliases($routeName) as $routeNameAlias) {
                    $map[$routeNameAlias] ??= [];
                    $map[$routeNameAlias][] = $pageKey;
                }
            }
        }

        foreach ($map as $routeName => $pageKeys) {
            $map[$routeName] = array_values(array_unique($pageKeys));
        }

        return $map;
    }

    private function routeNameToPrivilegePageActions(): array
    {
        $map = [];

        foreach (Route::getRoutes() as $route) {
            $routeName = $route->getName();
            if (!is_string($routeName) || $routeName === '') {
                continue;
            }

            foreach ($route->gatherMiddleware() as $middleware) {
                if (!is_string($middleware) || !str_starts_with($middleware, 'admin.privilege:')) {
                    continue;
                }

                $payload = substr($middleware, strlen('admin.privilege:'));
                if (!is_string($payload) || $payload === '') {
                    continue;
                }

                $parts = array_map('trim', explode(',', $payload));
                $pageKey = $parts[0] ?? '';
                $actionKey = $parts[1] ?? 'view';

                if ($pageKey === '') {
                    continue;
                }

                foreach ($this->routeNameAliases($routeName) as $routeNameAlias) {
                    $map[$routeNameAlias] ??= [];
                    $map[$routeNameAlias][] = ['page_key' => $pageKey, 'action_key' => $actionKey];
                }
            }
        }

        foreach ($map as $routeName => $items) {
            $unique = [];
            foreach ($items as $item) {
                $key = $item['page_key'] . ':' . $item['action_key'];
                if (!isset($unique[$key])) {
                    $unique[$key] = $item;
                }
            }
            $map[$routeName] = array_values($unique);
        }

        return $map;
    }

    private function routeNameAliases(string $routeName): array
    {
        $aliases = [$routeName];

        if (Str::startsWith($routeName, 'admin.')) {
            $aliases[] = substr($routeName, strlen('admin.'));
        }

        return array_values(array_unique(array_filter($aliases)));
    }

    private function syncRoutePrivilegesForSidebarAssignments(User $user): void
    {
        $allowedSidebarKeys = AdminPrivilege::query()
            ->where('user_id', $user->id)
            ->where('group_key', 'sidebar')
            ->where('is_allowed', true)
            ->pluck('page_key')
            ->all();

        if (empty($allowedSidebarKeys)) {
            return;
        }

        $pageActionsToEnable = $this->resolvePageActionKeysFromSidebarPermissionKeys($allowedSidebarKeys);

        if (empty($pageActionsToEnable)) {
            return;
        }

        // Disable all non-sidebar route permissions first
        AdminPrivilege::query()
            ->where('user_id', $user->id)
            ->where('group_key', '!=', 'sidebar')
            ->update([
                'is_allowed' => false,
                'updated_at' => now(),
            ]);

        // Enable only the specific page_key + action_key combinations
        foreach ($pageActionsToEnable as $item) {
            AdminPrivilege::query()
                ->where('user_id', $user->id)
                ->where('page_key', $item['page_key'])
                ->where('action_key', $item['action_key'])
                ->where('group_key', '!=', 'sidebar')
                ->update([
                    'is_allowed' => true,
                    'updated_at' => now(),
                ]);
        }
    }
}