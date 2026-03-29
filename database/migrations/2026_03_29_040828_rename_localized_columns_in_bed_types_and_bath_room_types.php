<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bed_types', function (Blueprint $table) {
            $table->renameColumn('name_en', 'en_name');
            $table->renameColumn('name_es', 'es_name');
        });

        Schema::table('bath_room_types', function (Blueprint $table) {
            $table->renameColumn('name_en', 'en_name');
            $table->renameColumn('name_es', 'es_name');
        });
    }

    public function down(): void
    {
        Schema::table('bed_types', function (Blueprint $table) {
            $table->renameColumn('en_name', 'name_en');
            $table->renameColumn('es_name', 'name_es');
        });

        Schema::table('bath_room_types', function (Blueprint $table) {
            $table->renameColumn('en_name', 'name_en');
            $table->renameColumn('es_name', 'name_es');
        });
    }
};
