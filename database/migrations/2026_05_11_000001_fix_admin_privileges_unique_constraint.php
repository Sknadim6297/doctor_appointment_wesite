<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Skip - constraints are already properly configured in the database
    }

    public function down(): void
    {
        Schema::table('admin_privileges', function (Blueprint $table): void {
            try {
                $table->dropUnique('admin_privileges_user_page_action_unique');
            } catch (\Throwable $e) {
                // Ignore if the new index is absent.
            }

            $table->unique(['user_id', 'page_key']);
        });
    }

    private function constraintExists(string $table, string $name): bool
    {
        $constraints = \Illuminate\Support\Facades\DB::select(
            "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_NAME = ? AND CONSTRAINT_NAME = ?",
            [$table, $name]
        );
        return count($constraints) > 0;
    }
};