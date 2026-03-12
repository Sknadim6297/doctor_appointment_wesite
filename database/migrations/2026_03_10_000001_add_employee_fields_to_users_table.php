<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('id');
            $table->string('last_name')->nullable()->after('first_name');
            $table->decimal('salary', 10, 2)->nullable()->after('is_active');
            $table->string('employee_no')->nullable()->unique()->after('salary');
            $table->string('phone', 20)->nullable()->after('employee_no');
            $table->string('aadhaar_no', 20)->nullable()->after('phone');
            $table->string('pan_no', 20)->nullable()->after('aadhaar_no');
            $table->date('dob')->nullable()->after('pan_no');
            $table->string('profile_pic')->nullable()->after('dob');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['employee_no']);
            $table->dropColumn([
                'first_name',
                'last_name',
                'salary',
                'employee_no',
                'phone',
                'aadhaar_no',
                'pan_no',
                'dob',
                'profile_pic',
            ]);
        });
    }
};
