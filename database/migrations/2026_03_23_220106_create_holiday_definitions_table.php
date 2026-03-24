<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holiday_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('en_name', 255);
            $table->string('es_name', 255);
            $table->string('group', 20);
            $table->tinyInteger('month')->unsigned()->nullable();
            $table->tinyInteger('day')->unsigned()->nullable();
            $table->smallInteger('easter_offset')->nullable();
            $table->boolean('moves_to_monday')->default(false);
            $table->json('base_impact_weights');
            $table->json('special_overrides')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holiday_definitions');
    }
};
