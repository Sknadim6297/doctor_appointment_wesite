<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('legacy_tbl_doctor_money_reciept')) {
            return;
        }

        Schema::table('legacy_tbl_doctor_money_reciept', function (Blueprint $table) {
            $table->string('money_reciept_year', 16)->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('legacy_tbl_doctor_money_reciept')) {
            return;
        }

        Schema::table('legacy_tbl_doctor_money_reciept', function (Blueprint $table) {
            $table->unsignedSmallInteger('money_reciept_year')->nullable()->change();
        });
    }
};
