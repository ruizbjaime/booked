<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_days', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->smallInteger('year')->index();
            $table->tinyInteger('month')->unsigned();
            $table->tinyInteger('day_of_week')->unsigned();
            $table->string('day_of_week_name', 10);
            $table->boolean('is_holiday')->default(false);
            $table->foreignId('holiday_definition_id')->nullable()->constrained()->nullOnDelete();
            $table->date('holiday_original_date')->nullable();
            $table->date('holiday_observed_date')->nullable();
            $table->string('holiday_group', 20)->nullable();
            $table->unsignedTinyInteger('holiday_impact')->nullable();
            $table->boolean('is_bridge_day')->default(false);
            $table->foreignId('season_block_id')->nullable()->constrained()->nullOnDelete();
            $table->string('season_block_name', 100)->nullable();
            $table->foreignId('pricing_category_id')->nullable()->constrained()->nullOnDelete();
            $table->tinyInteger('pricing_category_level')->unsigned()->nullable();
            $table->boolean('is_quincena_adjacent')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_days');
    }
};
