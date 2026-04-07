<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('policy_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('policy_no')->nullable();
            $table->unsignedBigInteger('enrollment_id')->nullable();
            $table->string('doctor_name')->nullable();
            $table->date('last_renewed_date')->nullable();
            $table->date('receive_date')->nullable();
            $table->string('policy_file')->nullable();
            $table->timestamps();

            $table->foreign('enrollment_id')->references('id')->on('enrollments')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('policy_receipts');
    }
};
