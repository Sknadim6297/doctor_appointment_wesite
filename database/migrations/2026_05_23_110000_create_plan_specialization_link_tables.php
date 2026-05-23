<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_tbl_combo_plan_specialization', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->unsignedInteger('combo_plan_id');
            $table->unsignedInteger('specialization_id');
        });

        Schema::create('legacy_tbl_insurence_plan_specialization', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->unsignedInteger('insurence_plan_id');
            $table->unsignedInteger('specialization_id');
        });

        Schema::create('combo_plan_specialization', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('combo_plan_id');
            $table->unsignedBigInteger('specialization_id');
            $table->unsignedInteger('legacy_link_id')->nullable()->unique();
            $table->timestamps();

            $table->unique(['combo_plan_id', 'specialization_id']);
            $table->index('specialization_id');
        });

        Schema::table('insurance_plans', function (Blueprint $table) {
            $table->decimal('yearly_amount', 12, 2)->nullable()->after('amount_per_lakh');
            $table->decimal('two_year_amount', 12, 2)->nullable()->after('yearly_amount');
            $table->decimal('three_year_amount', 12, 2)->nullable()->after('two_year_amount');
            $table->decimal('four_year_amount', 12, 2)->nullable()->after('three_year_amount');
            $table->decimal('five_year_amount', 12, 2)->nullable()->after('four_year_amount');
        });

        Schema::create('legacy_tbl_insurence', function (Blueprint $table) {
            $table->unsignedInteger('insurence_id')->primary();
            $table->text('specialization')->nullable();
            $table->float('amount');
            $table->float('tax');
            $table->float('yearly_plan');
            $table->float('two_year');
            $table->float('three_year');
            $table->float('four_year');
            $table->float('five_year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_tbl_insurence');
        Schema::table('insurance_plans', function (Blueprint $table) {
            $table->dropColumn([
                'yearly_amount',
                'two_year_amount',
                'three_year_amount',
                'four_year_amount',
                'five_year_amount',
            ]);
        });
        Schema::dropIfExists('combo_plan_specialization');
        Schema::dropIfExists('legacy_tbl_insurence_plan_specialization');
        Schema::dropIfExists('legacy_tbl_combo_plan_specialization');
    }
};
