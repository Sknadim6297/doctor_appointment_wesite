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

    private function defaultActions(): array
    {
        return ['view', 'edit', 'delete'];
    }
}