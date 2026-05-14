<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('policy_receipts', function (Blueprint $table) {
            if (!Schema::hasColumn('policy_receipts', 'workflow_status')) {
                $table->string('workflow_status', 20)->default('completed')->after('policy_file');
                $table->index('workflow_status');
            }
        });

        DB::table('policy_receipts')
            ->whereNull('workflow_status')
            ->update(['workflow_status' => 'completed']);
    }

    public function down(): void
    {
        Schema::table('policy_receipts', function (Blueprint $table) {
            if (Schema::hasColumn('policy_receipts', 'workflow_status')) {
                $table->dropIndex(['workflow_status']);
                $table->dropColumn('workflow_status');
            }
        });
    }
};
