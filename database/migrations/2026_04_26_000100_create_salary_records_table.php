<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('salary_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('salary_year');
            $table->string('salary_month', 20);
            $table->decimal('monthly_salary', 10, 2)->default(0);
            $table->unsignedSmallInteger('total_login_day')->nullable();
            $table->unsignedSmallInteger('total_absense')->default(0);
            $table->text('absense_reason')->nullable();
            $table->decimal('incentive', 10, 2)->default(0);
            $table->text('incentive_for')->nullable();
            $table->decimal('advance', 10, 2)->default(0);
            $table->decimal('additional_deduct', 10, 2)->default(0);
            $table->string('additional_deduct_reason')->nullable();
            $table->decimal('office_duty', 10, 2)->default(0);
            $table->decimal('bonus', 10, 2)->default(0);
            $table->decimal('pf', 10, 2)->default(0);
            $table->decimal('esi', 10, 2)->default(0);
            $table->decimal('ptax', 10, 2)->default(0);
            $table->string('cheque_no')->nullable();
            $table->string('bank_name')->nullable();
            $table->decimal('net_salary', 10, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'salary_year', 'salary_month'], 'salary_records_unique_period');
            $table->index(['salary_year', 'salary_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_records');
    }
};
