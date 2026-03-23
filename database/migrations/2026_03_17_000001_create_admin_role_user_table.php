<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['admin_role_id', 'user_id']);
            $table->index(['user_id', 'admin_role_id']);
        });

        $roleMap = DB::table('admin_roles')->pluck('id', 'role_key');
        $now = now();

        DB::table('users')
            ->select(['id', 'role'])
            ->orderBy('id')
            ->get()
            ->each(function ($user) use ($roleMap, $now) {
                $roleId = $roleMap[$user->role] ?? null;

                if (!$roleId) {
                    return;
                }

                DB::table('admin_role_user')->updateOrInsert(
                    [
                        'admin_role_id' => $roleId,
                        'user_id' => $user->id,
                    ],
                    [
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_role_user');
    }
};