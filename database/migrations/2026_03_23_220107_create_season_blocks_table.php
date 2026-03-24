<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('season_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('en_name', 255);
            $table->string('es_name', 255);
            $table->string('calculation_strategy', 50);
            $table->tinyInteger('fixed_start_month')->unsigned()->nullable();
            $table->tinyInteger('fixed_start_day')->unsigned()->nullable();
            $table->tinyInteger('fixed_end_month')->unsigned()->nullable();
            $table->tinyInteger('fixed_end_day')->unsigned()->nullable();
            $table->unsignedInteger('priority')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('season_blocks');
    }
};
