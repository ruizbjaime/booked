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
        $this->rebuildPivotTable(
            sourceTable: 'bed_type_bedroom',
            targetTable: 'bed_type_bedroom_new',
            includeQuantity: true,
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->rebuildPivotTable(
            sourceTable: 'bed_type_bedroom',
            targetTable: 'bed_type_bedroom_old',
            includeQuantity: false,
        );
    }

    private function rebuildPivotTable(string $sourceTable, string $targetTable, bool $includeQuantity): void
    {
        $connection = Schema::getConnection();
        $grammar = $connection->getQueryGrammar();

        try {
            $connection->beginTransaction();

            Schema::create($targetTable, function (Blueprint $table) use ($includeQuantity) {
                if ($includeQuantity) {
                    $table->id();
                }

                $table->foreignId('bedroom_id')->constrained()->cascadeOnDelete();
                $table->foreignId('bed_type_id')->constrained()->cascadeOnDelete();

                if ($includeQuantity) {
                    $table->unsignedInteger('quantity')->default(1);
                    $table->timestamps();
                }

                $table->unique(['bedroom_id', 'bed_type_id']);
            });

            $now = now()->toDateTimeString();
            $sourceTableSql = $grammar->wrapTable($sourceTable);
            $targetTableSql = $grammar->wrapTable($targetTable);

            if ($includeQuantity) {
                DB::statement(
                    "insert into {$targetTableSql} (bedroom_id, bed_type_id, quantity, created_at, updated_at)
                    select bedroom_id, bed_type_id, 1, ?, ?
                    from {$sourceTableSql}",
                    [$now, $now],
                );
            } else {
                DB::statement(
                    "insert into {$targetTableSql} (bedroom_id, bed_type_id)
                    select bedroom_id, bed_type_id
                    from {$sourceTableSql}",
                );
            }

            Schema::drop($sourceTable);
            Schema::rename($targetTable, $sourceTable);

            $connection->commit();
        } catch (Throwable $exception) {
            $connection->rollBack();

            Schema::dropIfExists($targetTable);

            throw $exception;
        }
    }
};
