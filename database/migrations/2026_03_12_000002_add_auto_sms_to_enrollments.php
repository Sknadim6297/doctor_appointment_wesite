<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            if (!Schema::hasColumn('enrollments', 'auto_sms_enabled')) {
                $table->boolean('auto_sms_enabled')->default(false)->after('bond_to_mail');
            }
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            if (Schema::hasColumn('enrollments', 'auto_sms_enabled')) {
                $table->dropColumn('auto_sms_enabled');
            }
        });
    }
};
