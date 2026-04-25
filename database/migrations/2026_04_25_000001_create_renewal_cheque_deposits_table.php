<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('renewal_cheque_deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->nullable()->constrained('enrollments')->nullOnDelete();
            $table->string('doctor_name')->nullable();
            $table->string('member_no')->nullable();
            $table->string('policy_no')->nullable();
            $table->string('money_reciept_no')->nullable();
            $table->string('cheque_no')->nullable();
            $table->string('bank')->nullable();
            $table->string('bank_branch')->nullable();
            $table->decimal('cheque_amount', 12, 2)->nullable();
            $table->date('payment_date')->nullable();
            $table->string('cheque_file')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('renewal_cheque_deposits');
    }
};
