<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('specializations', function (Blueprint $table) {
            if (!Schema::hasColumn('specializations', 'legacy_user_type')) {
                $table->unsignedTinyInteger('legacy_user_type')->nullable()->after('name');
            }
        });

        Schema::table('specializations', function (Blueprint $table) {
            $table->dropUnique('specializations_name_unique');
        });
    }

    public function down(): void
    {
        Schema::table('specializations', function (Blueprint $table) {
            if (Schema::hasColumn('specializations', 'legacy_user_type')) {
                $table->dropColumn('legacy_user_type');
            }
        });

        Schema::table('specializations', function (Blueprint $table) {
            $table->unique('name');
        });
    }
};
