<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const string BACKUP_TABLE = 'catalog_slug_migration_backups';

    /**
     * @var list<string>
     */
    private array $normalizedTables = [
        'platforms',
        'bed_types',
        'bath_room_types',
        'fee_types',
        'charge_bases',
    ];

    public function up(): void
    {
        $this->createBackupTable();

        foreach ($this->tables() as $table) {
            $this->backupOriginalNames($table);
        }

        foreach ($this->tables() as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->renameColumn('name', 'slug');
            });
        }

        foreach ($this->normalizedTables as $table) {
            $this->replaceSlugCharacter($table, '_', '-');
        }
    }

    public function down(): void
    {
        foreach ($this->tables() as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->renameColumn('slug', 'name');
            });
        }

        foreach ($this->tables() as $table) {
            $this->restoreOriginalNames($table);
        }

        Schema::dropIfExists(self::BACKUP_TABLE);
    }

    /**
     * @return list<string>
     */
    private function tables(): array
    {
        return ['platforms', 'bed_types', 'bath_room_types', 'fee_types', 'charge_bases'];
    }

    private function replaceSlugCharacter(string $table, string $search, string $replace): void
    {
        DB::table($table)->update([
            'slug' => DB::raw("REPLACE(slug, '{$search}', '{$replace}')"),
        ]);
    }

    private function createBackupTable(): void
    {
        if (Schema::hasTable(self::BACKUP_TABLE)) {
            return;
        }

        Schema::create(self::BACKUP_TABLE, function (Blueprint $table): void {
            $table->id();
            $table->string('table_name');
            $table->unsignedBigInteger('record_id');
            $table->string('original_name');
            $table->timestamps();

            $table->unique(['table_name', 'record_id']);
        });
    }

    private function backupOriginalNames(string $table): void
    {
        $timestamp = now();

        DB::table($table)
            ->selectRaw('? as table_name, id as record_id, name as original_name, ? as created_at, ? as updated_at', [
                $table,
                $timestamp,
                $timestamp,
            ])
            ->orderBy('record_id')
            ->chunkById(500, function ($rows): void {
                DB::table(self::BACKUP_TABLE)->insert(
                    collect($rows)
                        ->map(fn (object $row): array => [
                            'table_name' => $row->table_name,
                            'record_id' => $row->record_id,
                            'original_name' => $row->original_name,
                            'created_at' => $row->created_at,
                            'updated_at' => $row->updated_at,
                        ])
                        ->all(),
                );
            }, column: 'id', alias: 'record_id');
    }

    private function restoreOriginalNames(string $table): void
    {
        DB::table(self::BACKUP_TABLE)
            ->where('table_name', $table)
            ->orderBy('record_id')
            ->chunkById(500, function ($rows) use ($table): void {
                foreach ($rows as $row) {
                    DB::table($table)
                        ->where('id', $row->record_id)
                        ->update(['name' => $row->original_name]);
                }
            });
    }
};
