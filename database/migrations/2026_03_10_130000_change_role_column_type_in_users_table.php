<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 100)->default('admin')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')
            ->whereNotIn('role', ['super_admin', 'admin'])
            ->update(['role' => 'admin']);

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['super_admin', 'admin'])->default('admin')->change();
        });
    }
};
