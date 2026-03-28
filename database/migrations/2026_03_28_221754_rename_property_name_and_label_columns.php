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
        if (Schema::hasColumn('properties', 'slug') && ! Schema::hasColumn('properties', 'label')) {
            return;
        }

        Schema::table('properties', function (Blueprint $table) {
            if (Schema::hasColumn('properties', 'name')) {
                $table->renameColumn('name', 'slug');
            }
        });

        Schema::table('properties', function (Blueprint $table) {
            if (Schema::hasColumn('properties', 'label')) {
                $table->renameColumn('label', 'name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('properties', 'label')) {
            return;
        }

        Schema::table('properties', function (Blueprint $table) {
            if (Schema::hasColumn('properties', 'name')) {
                $table->renameColumn('name', 'label');
            }
        });

        Schema::table('properties', function (Blueprint $table) {
            if (Schema::hasColumn('properties', 'slug')) {
                $table->renameColumn('slug', 'name');
            }
        });
    }
};
