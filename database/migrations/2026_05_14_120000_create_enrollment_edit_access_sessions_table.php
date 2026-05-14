`<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollment_edit_access_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
            $table->foreignId('requested_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('otp_hash')->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->unsignedTinyInteger('otp_failed_attempts')->default(0);
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('granted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('session_expires_at')->nullable();
            $table->string('status', 32)->default('pending_otp');
            $table->timestamps();

            $table->index(['enrollment_id', 'status'], 'eeas_enrollment_status_idx');
            $table->index(['requested_by_user_id', 'status'], 'eeas_req_user_status_idx');
            $table->index(['session_expires_at'], 'eeas_session_expires_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollment_edit_access_sessions');
    }
};
