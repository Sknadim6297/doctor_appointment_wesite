<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_specialization', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->string('role');
            $table->unsignedInteger('user_type')->default(2);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_specialization');
    }
};
