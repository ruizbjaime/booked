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
        Schema::create('bed_type_bedroom', function (Blueprint $table) {
            $table->foreignId('bedroom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bed_type_id')->constrained()->cascadeOnDelete();

            $table->unique(['bedroom_id', 'bed_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bed_type_bedroom');
    }
};
