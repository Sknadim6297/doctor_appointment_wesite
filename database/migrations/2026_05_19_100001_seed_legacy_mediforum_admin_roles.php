<?php

use App\Models\AdminRole;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Legacy tbl_role rows (role_id 2–7) mapped into admin_roles.
     */
    public function up(): void
    {
        $roles = [
            2 => ['title' => 'Back Office', 'key' => 'back_office'],
            3 => ['title' => 'Legal Case Management', 'key' => 'legal_case_management'],
            4 => ['title' => 'Dispatched Post', 'key' => 'dispatched_post'],
            5 => ['title' => 'Accounts', 'key' => 'accounts'],
            6 => ['title' => 'Marketing', 'key' => 'marketing'],
            7 => ['title' => 'Insurance', 'key' => 'insurance'],
        ];

        foreach ($roles as $legacyRoleId => $role) {
            $existing = AdminRole::query()
                ->where('legacy_role_id', $legacyRoleId)
                ->orWhere('role_title', $role['title'])
                ->orWhere('role_key', $role['key'])
                ->first();

            if ($existing) {
                $existing->forceFill([
                    'role_title' => $role['title'],
                    'role_key' => $role['key'],
                    'legacy_role_id' => $legacyRoleId,
                ])->save();

                continue;
            }

            AdminRole::query()->create([
                'legacy_role_id' => $legacyRoleId,
                'role_title' => $role['title'],
                'role_key' => $role['key'],
            ]);
        }
    }

    public function down(): void
    {
        AdminRole::query()
            ->whereIn('legacy_role_id', [2, 3, 4, 5, 6, 7])
            ->delete();
    }
};
