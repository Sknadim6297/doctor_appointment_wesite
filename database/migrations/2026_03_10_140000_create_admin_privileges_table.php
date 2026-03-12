<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('admin_privileges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('group_key', 100);
            $table->string('group_title', 150);
            $table->string('page_key', 150);
            $table->string('page_title', 255);
            $table->boolean('is_allowed')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'page_key']);
            $table->index(['user_id', 'group_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_privileges');
    }
};
