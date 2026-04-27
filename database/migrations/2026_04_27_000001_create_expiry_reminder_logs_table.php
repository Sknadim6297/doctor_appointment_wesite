<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expiry_reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->string('reminder_type', 50); // doctor_40 | admin_25
            $table->foreignId('enrollment_id')->nullable()->constrained('enrollments')->nullOnDelete();
            $table->string('doctor_name')->nullable();
            $table->string('recipient_email');
            $table->date('expiry_date');
            $table->unsignedTinyInteger('days_before_expiry');
            $table->string('status', 20)->default('pending'); // pending | sent | failed | skipped
            $table->dateTime('sent_at')->nullable();
            $table->string('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['reminder_type', 'enrollment_id', 'recipient_email', 'expiry_date'], 'expiry_reminder_logs_unique_once');
            $table->index(['reminder_type', 'expiry_date']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expiry_reminder_logs');
    }
};
