<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_case_categories', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('legal_court_links', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->string('name');
            $table->text('url');
            $table->timestamps();
        });

        Schema::table('legal_cases', function (Blueprint $table) {
            if (!Schema::hasColumn('legal_cases', 'legacy_case_id')) {
                $table->unsignedInteger('legacy_case_id')->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('legal_cases', 'legal_case_category_id')) {
                $table->unsignedInteger('legal_case_category_id')->nullable()->after('case_cat');
            }
        });

        Schema::create('legal_case_proceedings', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->foreignId('legal_case_id')->constrained('legal_cases')->cascadeOnDelete();
            $table->unsignedInteger('legacy_case_id')->index();
            $table->text('body');
            $table->date('proceed_date')->nullable();
            $table->unsignedInteger('legacy_created_by')->nullable();
            $table->unsignedInteger('legacy_edited_by')->nullable();
            $table->timestamps();
        });

        Schema::create('legal_case_documents', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->foreignId('legal_case_id')->constrained('legal_cases')->cascadeOnDelete();
            $table->unsignedInteger('legacy_case_id')->index();
            $table->string('document_title');
            $table->string('file_slug');
            $table->timestamps();
        });

        Schema::create('legal_case_payments', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->foreignId('legal_case_id')->constrained('legal_cases')->cascadeOnDelete();
            $table->unsignedInteger('legacy_case_id')->index();
            $table->string('cheque_no')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('amount')->nullable();
            $table->string('payment_date')->nullable();
            $table->string('acknowledge_reciept')->nullable();
            $table->timestamps();
        });

        Schema::create('legacy_tbl_case_category', function (Blueprint $table) {
            $table->unsignedInteger('case_cat_id')->primary();
            $table->string('case_cat_name');
        });

        Schema::create('legacy_tbl_case_details', function (Blueprint $table) {
            $table->unsignedInteger('case_details_id')->primary();
            $table->unsignedInteger('case_id')->index();
            $table->text('case_details');
            $table->date('date')->nullable();
            $table->unsignedInteger('created_by')->default(0);
            $table->unsignedInteger('edited_by')->default(0);
        });

        Schema::create('legacy_tbl_case_document', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->unsignedInteger('case_id')->index();
            $table->string('document_title');
            $table->string('document_file');
        });

        Schema::create('legacy_tbl_case_payment', function (Blueprint $table) {
            $table->unsignedInteger('case_payment_id')->primary();
            $table->unsignedInteger('case_id')->index();
            $table->string('cheque_no')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('amount')->nullable();
            $table->string('payment_date')->nullable();
            $table->string('acknowledge_reciept')->nullable();
        });

        Schema::create('legacy_tbl_case_link', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->string('case_link_name');
            $table->text('case_link');
        });

        Schema::create('legacy_tbl_case', function (Blueprint $table) {
            $table->unsignedInteger('case_id')->primary();
            $table->string('case_number')->nullable();
            $table->string('doctor_name')->nullable();
            $table->string('case_cat_name')->nullable();
            $table->string('stage')->nullable();
            $table->unsignedInteger('enrollment_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_case_payments');
        Schema::dropIfExists('legal_case_documents');
        Schema::dropIfExists('legal_case_proceedings');
        Schema::dropIfExists('legacy_tbl_case');
        Schema::dropIfExists('legacy_tbl_case_link');
        Schema::dropIfExists('legacy_tbl_case_payment');
        Schema::dropIfExists('legacy_tbl_case_document');
        Schema::dropIfExists('legacy_tbl_case_details');
        Schema::dropIfExists('legacy_tbl_case_category');
        Schema::dropIfExists('legal_court_links');
        Schema::dropIfExists('legal_case_categories');

        if (Schema::hasTable('legal_cases')) {
            Schema::table('legal_cases', function (Blueprint $table) {
                if (Schema::hasColumn('legal_cases', 'legacy_case_id')) {
                    $table->dropColumn('legacy_case_id');
                }
                if (Schema::hasColumn('legal_cases', 'legal_case_category_id')) {
                    $table->dropColumn('legal_case_category_id');
                }
            });
        }
    }
};
