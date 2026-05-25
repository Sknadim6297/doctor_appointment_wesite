<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_tbl_renew_history', function (Blueprint $table) {
            $table->unsignedInteger('id')->nullable()->index();
            $table->unsignedInteger('renew_doctor_id')->index();
            $table->date('renewed_date')->nullable();
            $table->string('renew_month', 20)->nullable();
            $table->string('renew_day', 10)->nullable();
            $table->string('renew_year', 10)->nullable();
            $table->string('renew_medeforum_amount', 255)->nullable();
            $table->string('renew_insurance_amount', 255)->nullable();
            $table->string('renew_insurance_coverage', 50)->nullable();
            $table->string('renew_legal_service', 50)->nullable();
            $table->string('renew_payment_mode', 50)->nullable();
            $table->unsignedTinyInteger('renew_plan_id')->nullable();
            $table->string('renew_policy_no', 255)->nullable();
        });

        Schema::create('renewal_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
            $table->unsignedInteger('legacy_renewal_id')->nullable()->index();
            $table->unsignedInteger('legacy_doctor_id')->index();
            $table->date('renewed_date')->nullable()->index();
            $table->string('renew_month', 20)->nullable();
            $table->string('renew_day', 10)->nullable();
            $table->string('renew_year', 10)->nullable();
            $table->decimal('medeforum_amount', 12, 2)->nullable();
            $table->decimal('insurance_amount', 12, 2)->nullable();
            $table->decimal('coverage_lakh', 8, 2)->nullable();
            $table->unsignedTinyInteger('plan_type')->nullable();
            $table->string('payment_mode', 50)->nullable();
            $table->string('policy_no', 255)->nullable();
            $table->timestamps();

            $table->index(['enrollment_id', 'renewed_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('renewal_histories');
        Schema::dropIfExists('legacy_tbl_renew_history');
    }
};
