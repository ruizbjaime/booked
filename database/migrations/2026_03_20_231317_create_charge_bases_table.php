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
        Schema::create('charge_bases', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('en_name');
            $table->string('es_name');
            $table->string('en_description')->nullable();
            $table->string('es_description')->nullable();
            $table->unsignedInteger('order')->default(999);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('charge_bases');
    }
};
