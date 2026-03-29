<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->tables() as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->renameColumn('name', 'slug');
            });
        }

        // Convert any underscored slugs to hyphens to match the new convention.
        DB::table('charge_bases')->update([
            'slug' => DB::raw("REPLACE(slug, '_', '-')"),
        ]);

        DB::table('platforms')
            ->where('slug', 'like', '%\_%')
            ->update([
                'slug' => DB::raw("REPLACE(slug, '_', '-')"),
            ]);
    }

    public function down(): void
    {
        foreach ($this->tables() as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->renameColumn('slug', 'name');
            });
        }
    }

    /**
     * @return list<string>
     */
    private function tables(): array
    {
        return ['platforms', 'bed_types', 'bath_room_types', 'fee_types', 'charge_bases'];
    }
};
