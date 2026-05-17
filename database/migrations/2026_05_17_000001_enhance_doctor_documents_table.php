<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor_documents', function (Blueprint $table) {
            $table->string('document_category')->default('additional')->after('document_type');
            $table->string('verification_status')->default('pending')->after('document_file');
            $table->text('verification_remarks')->nullable()->after('verification_status');
            $table->foreignId('verified_by')->nullable()->after('verification_remarks')->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable()->after('verified_by');
            $table->string('original_filename')->nullable()->after('verified_at');
            $table->string('mime_type')->nullable()->after('original_filename');
            $table->unsignedBigInteger('file_size')->nullable()->after('mime_type');
            $table->string('source')->default('manual')->after('file_size');
            $table->string('source_key')->nullable()->after('source');
            $table->string('source_reference_type')->nullable()->after('source');
            $table->unsignedBigInteger('source_reference_id')->nullable()->after('source_reference_type');
            $table->foreignId('replaces_document_id')->nullable()->after('source_reference_id')
                ->constrained('doctor_documents')->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('replaces_document_id');

            $table->index(['enrollment_id', 'document_category']);
            $table->index(['enrollment_id', 'verification_status']);
            $table->index(['source_reference_type', 'source_reference_id'], 'doctor_documents_source_ref_index');
        });
    }

    public function down(): void
    {
        Schema::table('doctor_documents', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropForeign(['replaces_document_id']);
            $table->dropIndex('doctor_documents_source_ref_index');
            $table->dropIndex(['enrollment_id', 'document_category']);
            $table->dropIndex(['enrollment_id', 'verification_status']);
            $table->dropColumn([
                'document_category',
                'verification_status',
                'verification_remarks',
                'verified_by',
                'verified_at',
                'original_filename',
                'mime_type',
                'file_size',
                'source',
                'source_reference_type',
                'source_reference_id',
                'replaces_document_id',
                'is_active',
            ]);
        });
    }
};
