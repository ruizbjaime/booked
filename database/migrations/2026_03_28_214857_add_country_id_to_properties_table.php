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
        if (Schema::hasColumn('properties', 'country_id')) {
            return;
        }

        Schema::table('properties', function (Blueprint $table) {
            $table->foreignId('country_id')->nullable()->after('city')->constrained()->restrictOnDelete();
        });

        $countries = DB::table('countries')
            ->select('id', 'en_name', 'es_name')
            ->get();

        DB::table('properties')->orderBy('id')->get()->each(function (object $property) use ($countries): void {
            $countryName = trim((string) ($property->country ?? ''));

            $countryId = $countries
                ->first(function (object $country) use ($countryName): bool {
                    return strcasecmp($country->en_name, $countryName) === 0
                        || strcasecmp($country->es_name, $countryName) === 0;
                })?->id;

            DB::table('properties')
                ->where('id', $property->id)
                ->update(['country_id' => $countryId]);
        });

        Schema::table('properties', function (Blueprint $table) {
            $table->foreignId('country_id')->nullable(false)->change();
            $table->dropColumn('country');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('properties', 'country_id')) {
            return;
        }

        Schema::table('properties', function (Blueprint $table) {
            $table->string('country')->nullable()->after('city');
        });

        DB::table('properties')
            ->join('countries', 'countries.id', '=', 'properties.country_id')
            ->update(['properties.country' => DB::raw('countries.en_name')]);

        Schema::table('properties', function (Blueprint $table) {
            $table->string('country')->nullable(false)->change();
            $table->dropConstrainedForeignId('country_id');
        });
    }
};
