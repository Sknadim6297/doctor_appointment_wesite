<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_verification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('subject_type', 100);
            $table->unsignedBigInteger('subject_id');
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->json('delivery_channels')->nullable();
            $table->string('otp_code_hash');
            $table->dateTime('requested_at');
            $table->dateTime('expires_at');
            $table->dateTime('verified_at')->nullable();
            $table->dateTime('last_attempt_at')->nullable();
            $table->unsignedTinyInteger('failed_attempts')->default(0);
            $table->string('status', 20)->default('sent'); // sent | verified | expired | failed
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'subject_type', 'subject_id']);
            $table->index(['status', 'expires_at']);
            $table->index(['requested_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_verification_logs');
    }
};
