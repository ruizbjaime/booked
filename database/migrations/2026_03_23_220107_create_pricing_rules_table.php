<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('en_description', 500);
            $table->string('es_description', 500);
            $table->foreignId('pricing_category_id')->constrained()->cascadeOnDelete();
            $table->string('rule_type', 50);
            $table->json('conditions');
            $table->unsignedInteger('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }
};
