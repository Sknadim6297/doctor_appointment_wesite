<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_tbl_user', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->unsignedInteger('role_id')->nullable();
            $table->unsignedInteger('user_type_id')->nullable();
            $table->string('user_unique_id')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('full_name')->nullable();
            $table->string('email')->nullable();
            $table->string('mobile_no')->nullable();
            $table->string('alt_mobile_no')->nullable();
            $table->string('password')->nullable();
            $table->string('dob')->nullable();
            $table->text('country_id')->nullable();
            $table->unsignedInteger('state_id')->nullable();
            $table->unsignedInteger('city_id')->nullable();
            $table->unsignedInteger('pincode')->nullable();
            $table->text('full_address')->nullable();
            $table->string('aadhar_card_no')->nullable();
            $table->string('pan_card_no')->nullable();
            $table->text('profile_pic')->nullable();
            $table->date('created_date')->nullable();
            $table->date('edited_date')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('edited_by')->nullable();
            $table->string('status')->nullable();
            $table->string('is_block')->nullable();
            $table->string('auto_email_send')->nullable();
            $table->string('auto_sms')->nullable();
            $table->string('employee_no')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_tbl_user');
    }
};
