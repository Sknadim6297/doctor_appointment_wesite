<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

final class DashboardCacheService
{
    public static function cacheKeyForUser(?int $userId): string
    {
        return 'dashboard_stats_' . ($userId ?? 'guest') . '_' . date('YmdH');
    }

    public static function forgetForUser(?User $user): void
    {
        if (!$user) {
            return;
        }

        for ($offset = 0; $offset < 3; $offset++) {
            $hour = now()->subHours($offset)->format('YmdH');
            Cache::forget('dashboard_stats_' . $user->id . '_' . $hour);
        }
    }

    public static function forgetForAdmins(): void
    {
        User::query()
            ->whereIn('role', ['admin', 'super_admin'])
            ->pluck('id')
            ->each(function (int $userId): void {
                for ($offset = 0; $offset < 3; $offset++) {
                    $hour = now()->subHours($offset)->format('YmdH');
                    Cache::forget('dashboard_stats_' . $userId . '_' . $hour);
                }
            });
    }

    public static function bump(?User $actor = null, bool $notifyAdmins = false): void
    {
        self::forgetForUser($actor);

        if ($notifyAdmins) {
            self::forgetForAdmins();
        }
    }
}
