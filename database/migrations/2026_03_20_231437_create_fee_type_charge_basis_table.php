<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_type_charge_basis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('charge_basis_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('sort_order')->default(999);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['fee_type_id', 'charge_basis_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_type_charge_basis');
    }
};
