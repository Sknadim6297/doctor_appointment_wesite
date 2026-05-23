<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_tbl_doctor_post', function (Blueprint $table) {
            $table->unsignedInteger('post_id')->primary();
            $table->unsignedInteger('doctor_id')->default(0)->index();
            $table->unsignedInteger('consignment_document_id')->default(0);
            $table->date('post_date')->nullable();
            $table->string('post_document_name')->nullable();
            $table->string('consignment_no')->nullable();
            $table->string('post_by')->nullable();
            $table->date('recieved_date')->nullable();
            $table->string('recieved_by')->nullable();
            $table->text('remark')->nullable();
            $table->date('created_date')->nullable();
            $table->unsignedInteger('created_by')->default(0);
            $table->date('edited_date')->nullable();
            $table->unsignedInteger('edited_by')->default(0);
            $table->string('post_month')->nullable();
            $table->string('post_year')->nullable();
            $table->text('tracking_link')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_tbl_doctor_post');
    }
};
