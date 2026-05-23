<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_normal_plan', function (Blueprint $table) {
            $table->unsignedInteger('plan_id')->primary();
            $table->string('plan_period')->default('');
            $table->float('coverage');
            $table->float('monthly_plan');
            $table->float('yearly_plan');
            $table->float('two_year')->nullable();
            $table->float('three_year')->nullable();
            $table->float('four_year')->nullable();
            $table->float('five_year')->nullable();
        });

        Schema::create('legacy_high_plan', function (Blueprint $table) {
            $table->unsignedInteger('plan_id')->primary();
            $table->float('monthly_plan');
            $table->float('yearly_plan');
            $table->float('two_year');
            $table->float('three_year');
            $table->float('four_year');
            $table->float('five_year');
            $table->float('coverage');
        });

        Schema::create('legacy_combo_plan', function (Blueprint $table) {
            $table->unsignedInteger('plan_id')->primary();
            $table->float('monthly_plan');
            $table->float('yearly_plan');
            $table->float('two_year');
            $table->float('three_year');
            $table->float('four_year');
            $table->float('five_year');
            $table->float('coverage');
            $table->text('speciliaziation')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_combo_plan');
        Schema::dropIfExists('legacy_high_plan');
        Schema::dropIfExists('legacy_normal_plan');
    }
};
