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
        Schema::table('charge_bases', function (Blueprint $table) {
            $table->string('en_description')->nullable()->after('es_name');
            $table->string('es_description')->nullable()->after('en_description');
        });

        DB::table('charge_bases')
            ->whereNotNull('description')
            ->update(['en_description' => DB::raw('description')]);

        Schema::table('charge_bases', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('charge_bases', function (Blueprint $table) {
            $table->string('description')->nullable()->after('es_name');
        });

        DB::table('charge_bases')
            ->whereNotNull('en_description')
            ->update(['description' => DB::raw('en_description')]);

        Schema::table('charge_bases', function (Blueprint $table) {
            $table->dropColumn(['en_description', 'es_description']);
        });
    }
};
