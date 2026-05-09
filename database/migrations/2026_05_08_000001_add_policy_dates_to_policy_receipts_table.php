<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('policy_receipts', function (Blueprint $table) {
            // Add missing columns if they don't exist
            if (!Schema::hasColumn('policy_receipts', 'policy_start_date')) {
                $table->date('policy_start_date')->nullable()->after('receive_date');
            }
            if (!Schema::hasColumn('policy_receipts', 'policy_end_date')) {
                $table->date('policy_end_date')->nullable()->after('policy_start_date');
            }
        });
    }

    public function down()
    {
        Schema::table('policy_receipts', function (Blueprint $table) {
            $table->dropColumn(['policy_start_date', 'policy_end_date']);
        });
    }
};
