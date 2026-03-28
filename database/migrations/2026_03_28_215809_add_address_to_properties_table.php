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
        if (Schema::hasColumn('properties', 'address')) {
            return;
        }

        Schema::table('properties', function (Blueprint $table) {
            $table->string('address')->nullable()->after('city');
        });

        DB::table('properties')->whereNull('address')->update(['address' => 'Pending address']);

        Schema::table('properties', function (Blueprint $table) {
            $table->string('address')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('properties', 'address')) {
            return;
        }

        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn('address');
        });
    }
};
