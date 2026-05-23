<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('policy_receipts', function (Blueprint $table) {
            if (!Schema::hasColumn('policy_receipts', 'legacy_policy_id')) {
                $table->unsignedInteger('legacy_policy_id')->nullable()->unique()->after('id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('policy_receipts', function (Blueprint $table) {
            if (Schema::hasColumn('policy_receipts', 'legacy_policy_id')) {
                $table->dropColumn('legacy_policy_id');
            }
        });
    }
};
