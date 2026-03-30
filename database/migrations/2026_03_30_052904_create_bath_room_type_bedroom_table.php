<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bath_room_type_bedroom', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bedroom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bath_room_type_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            $table->unique(['bedroom_id', 'bath_room_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bath_room_type_bedroom');
    }
};
