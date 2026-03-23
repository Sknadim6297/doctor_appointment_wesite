<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_login_logs', function (Blueprint $table) {
            $table->string('session_id', 255)->nullable()->after('user_id');
            $table->timestamp('logged_out_at')->nullable()->after('logged_in_at');
            $table->string('device_type', 50)->nullable()->after('user_agent');
            $table->string('device_name', 100)->nullable()->after('device_type');
            $table->string('browser_name', 100)->nullable()->after('device_name');
            $table->string('browser_version', 50)->nullable()->after('browser_name');

            $table->index(['user_id', 'session_id']);
        });
    }

    public function down(): void
    {
        Schema::table('admin_login_logs', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'session_id']);
            $table->dropColumn([
                'session_id',
                'logged_out_at',
                'device_type',
                'device_name',
                'browser_name',
                'browser_version',
            ]);
        });
    }
};