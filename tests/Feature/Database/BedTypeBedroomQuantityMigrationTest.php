<?php

use App\Models\Bedroom;
use App\Models\BedType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

test('quantity migration preserves existing bedroom bed type relations', function () {
    /** @var TestCase $this */
    $latestMigration = '2026_03_30_055416_create_bath_room_type_property_table';
    $laterMigration = '2026_03_30_052904_create_bath_room_type_bedroom_table';
    $migration = '2026_03_30_004030_add_quantity_to_bed_type_bedroom_table';

    $this->artisan('migrate:rollback', [
        '--database' => config('database.default'),
        '--path' => 'database/migrations/'.$latestMigration.'.php',
        '--step' => 1,
        '--no-interaction' => true,
    ])->assertSuccessful();

    $this->artisan('migrate:rollback', [
        '--database' => config('database.default'),
        '--path' => 'database/migrations/'.$laterMigration.'.php',
        '--step' => 1,
        '--no-interaction' => true,
    ])->assertSuccessful();

    $this->artisan('migrate:rollback', [
        '--database' => config('database.default'),
        '--path' => 'database/migrations/'.$migration.'.php',
        '--step' => 1,
        '--no-interaction' => true,
    ])->assertSuccessful();

    expect(Schema::hasColumn('bed_type_bedroom', 'quantity'))->toBeFalse();

    $bedroom = Bedroom::factory()->create();
    $bedType = BedType::factory()->create();

    DB::table('bed_type_bedroom')->insert([
        'bedroom_id' => $bedroom->id,
        'bed_type_id' => $bedType->id,
    ]);

    $this->artisan('migrate', [
        '--database' => config('database.default'),
        '--path' => 'database/migrations/'.$migration.'.php',
        '--realpath' => false,
        '--no-interaction' => true,
    ])->assertSuccessful();

    $this->artisan('migrate', [
        '--database' => config('database.default'),
        '--path' => 'database/migrations/'.$laterMigration.'.php',
        '--realpath' => false,
        '--no-interaction' => true,
    ])->assertSuccessful();

    $this->artisan('migrate', [
        '--database' => config('database.default'),
        '--path' => 'database/migrations/'.$latestMigration.'.php',
        '--realpath' => false,
        '--no-interaction' => true,
    ])->assertSuccessful();

    expect(Schema::hasColumns('bed_type_bedroom', ['id', 'quantity', 'created_at', 'updated_at']))->toBeTrue()
        ->and(DB::table('bed_type_bedroom')->count())->toBe(1)
        ->and(DB::table('bed_type_bedroom')->value('bedroom_id'))->toBe($bedroom->id)
        ->and(DB::table('bed_type_bedroom')->value('bed_type_id'))->toBe($bedType->id)
        ->and(DB::table('bed_type_bedroom')->value('quantity'))->toBe(1)
        ->and(DB::table('bed_type_bedroom')->value('created_at'))->not->toBeNull()
        ->and(DB::table('bed_type_bedroom')->value('updated_at'))->not->toBeNull();
});
