<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->nullable()->constrained('enrollments')->nullOnDelete();
            $table->string('doctor_name')->nullable();
            $table->date('post_doc_date')->nullable();
            $table->string('post_doc_consignment_no')->nullable();
            $table->string('post_doc_by')->nullable();
            $table->date('post_doc_recieved_date')->nullable();
            $table->string('post_doc_recieved_by')->nullable();
            $table->string('post_doc_remark')->nullable();
            $table->string('tracking_link')->nullable();
            $table->string('post_doc_file')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_posts');
    }
};
