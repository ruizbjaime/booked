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
        if (! Schema::hasColumn('properties', 'en_name') && ! Schema::hasColumn('properties', 'es_name')) {
            return;
        }

        Schema::table('properties', function (Blueprint $table) {
            if (! Schema::hasColumn('properties', 'label')) {
                $table->string('label')->nullable()->after('name');
            }
        });

        DB::table('properties')->orderBy('id')->get()->each(function (object $property): void {
            DB::table('properties')
                ->where('id', $property->id)
                ->update([
                    'label' => $property->en_name ?: $property->es_name ?: $property->name,
                ]);
        });

        Schema::table('properties', function (Blueprint $table) {
            $table->dropUnique('properties_en_name_unique');
            $table->dropUnique('properties_es_name_unique');
            $table->dropColumn(['en_name', 'es_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('properties', 'label')) {
            return;
        }

        Schema::table('properties', function (Blueprint $table) {
            if (! Schema::hasColumn('properties', 'en_name')) {
                $table->string('en_name')->nullable()->after('name');
            }

            if (! Schema::hasColumn('properties', 'es_name')) {
                $table->string('es_name')->nullable()->after('en_name');
            }
        });

        DB::table('properties')->orderBy('id')->get()->each(function (object $property): void {
            DB::table('properties')
                ->where('id', $property->id)
                ->update([
                    'en_name' => $property->label,
                    'es_name' => $property->label,
                ]);
        });

        Schema::table('properties', function (Blueprint $table) {
            $table->unique('en_name');
            $table->unique('es_name');
            $table->dropColumn('label');
        });
    }
};
