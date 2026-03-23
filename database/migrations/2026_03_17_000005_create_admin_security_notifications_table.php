<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_security_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('module_key', 100);
            $table->string('action', 100);
            $table->string('email');
            $table->string('otp_code', 10);
            $table->dateTime('otp_expires_at');
            $table->dateTime('notified_at');
            $table->string('ip_address', 45)->nullable();
            $table->string('device_name', 100)->nullable();
            $table->string('browser_name', 100)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['owner_user_id', 'notified_at']);
            $table->index(['actor_user_id', 'notified_at']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_security_notifications');
    }
};