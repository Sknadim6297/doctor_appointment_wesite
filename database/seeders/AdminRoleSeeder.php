<?php

namespace Database\Seeders;

use App\Models\AdminRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            'Insurance',
            'Marketing',
            'Accounts',
            'Dispatched Post',
            'Legal Case Management',
            'Back Office',
        ];

        foreach ($roles as $roleTitle) {
            AdminRole::firstOrCreate(
                ['role_title' => $roleTitle],
                ['role_key' => Str::of($roleTitle)->lower()->replace(' ', '_')->toString()]
            );
        }
    }
}
