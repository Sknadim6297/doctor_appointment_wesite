<?php

namespace App\Services;

use App\Models\AdminPrivilege;
use App\Models\AdminRole;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminAccessService
{
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
            ->values();

        $this->syncSidebarCatalogForUser($user);

        DB::transaction(function () use ($user, $normalizedKeys): void {
            AdminPrivilege::query()
                ->where('user_id', $user->id)
                ->where('group_key', 'sidebar')
                ->update([
                    'is_allowed' => false,
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
                    'updated_at' => now(),
                ]);
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
            $children = $isAllowed
                ? ($node['children'] ?? [])
                : $this->filterSidebarNodes($node['children'] ?? [], $allowed);

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
}