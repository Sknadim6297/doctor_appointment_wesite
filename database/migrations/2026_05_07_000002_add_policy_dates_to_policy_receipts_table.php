<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('policy_receipts', function (Blueprint $table) {
            if (!Schema::hasColumn('policy_receipts', 'policy_start_date')) {
                $table->date('policy_start_date')->nullable()->after('last_renewed_date');
            }
            
            if (!Schema::hasColumn('policy_receipts', 'policy_end_date')) {
                $table->date('policy_end_date')->nullable()->after('policy_start_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('policy_receipts', function (Blueprint $table) {
            if (Schema::hasColumn('policy_receipts', 'policy_start_date')) {
                $table->dropColumn('policy_start_date');
            }
            
            if (Schema::hasColumn('policy_receipts', 'policy_end_date')) {
                $table->dropColumn('policy_end_date');
            }
        });
    }
};
