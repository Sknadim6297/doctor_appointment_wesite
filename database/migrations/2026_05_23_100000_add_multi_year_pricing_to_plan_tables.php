<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['normal_plans', 'high_risk_plans', 'combo_plans'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->decimal('monthly_amount', 12, 2)->nullable()->after('yearly_amount');
                $table->decimal('two_year_amount', 12, 2)->nullable()->after('monthly_amount');
                $table->decimal('three_year_amount', 12, 2)->nullable()->after('two_year_amount');
                $table->decimal('four_year_amount', 12, 2)->nullable()->after('three_year_amount');
                $table->decimal('five_year_amount', 12, 2)->nullable()->after('four_year_amount');
            });
        }
    }

    public function down(): void
    {
        foreach (['normal_plans', 'high_risk_plans', 'combo_plans'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn([
                    'monthly_amount',
                    'two_year_amount',
                    'three_year_amount',
                    'four_year_amount',
                    'five_year_amount',
                ]);
            });
        }
    }
};
