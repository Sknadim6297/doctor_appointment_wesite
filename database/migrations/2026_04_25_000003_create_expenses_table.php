<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_category_id')->nullable()->constrained('expense_categories')->nullOnDelete();
            $table->date('expense_date');
            $table->decimal('amount', 12, 2);
            $table->string('payment_mode', 30)->default('cash');
            $table->string('cheque_no')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('voucher_file')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('expense_date');
            $table->index('payment_mode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
