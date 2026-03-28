<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('properties', 'label')) {
            return;
        }

        DB::table('properties')
            ->whereNull('label')
            ->update(['label' => DB::raw('name')]);

        Schema::table('properties', function (Blueprint $table) {
            $table->string('label')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->string('label')->nullable()->change();
        });
    }
};
