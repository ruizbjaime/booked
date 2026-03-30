<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

test('normalizes legacy catalog slugs to hyphen format after the rename migration', function () {
    /** @var TestCase $this */
    $migration = '2026_03_29_160353_rename_name_to_slug_in_catalog_tables';
    $laterMigrations = [
        '2026_03_30_004030_add_quantity_to_bed_type_bedroom_table',
        '2026_03_30_003051_create_bed_type_bedroom_table',
        '2026_03_30_001631_create_bedrooms_table',
    ];

    foreach ($laterMigrations as $laterMigration) {
        $this->artisan('migrate:rollback', [
            '--database' => config('database.default'),
            '--path' => 'database/migrations/'.$laterMigration.'.php',
            '--step' => 1,
            '--no-interaction' => true,
        ])->assertSuccessful();
    }

    $this->artisan('migrate:rollback', [
        '--database' => config('database.default'),
        '--path' => 'database/migrations/'.$migration.'.php',
        '--step' => 1,
        '--no-interaction' => true,
    ])->assertSuccessful();

    expect(Schema::hasColumns('platforms', ['name']))->toBeTrue()
        ->and(Schema::hasColumns('bed_types', ['name']))->toBeTrue()
        ->and(Schema::hasColumns('bath_room_types', ['name']))->toBeTrue()
        ->and(Schema::hasColumns('fee_types', ['name']))->toBeTrue()
        ->and(Schema::hasColumns('charge_bases', ['name']))->toBeTrue();

    DB::table('platforms')->insert([
        'name' => 'booking_com',
        'en_name' => 'Booking.com',
        'es_name' => 'Booking.com ES',
        'color' => 'zinc',
        'sort_order' => 999,
        'commission' => 0,
        'commission_tax' => 0,
        'is_active' => 1,
    ]);

    DB::table('platforms')->insert([
        'name' => 'air-bnb',
        'en_name' => 'Airbnb',
        'es_name' => 'Airbnb ES',
        'color' => 'sky',
        'sort_order' => 1000,
        'commission' => 0,
        'commission_tax' => 0,
        'is_active' => 1,
    ]);

    DB::table('bed_types')->insert([
        'name' => 'king_bed',
        'en_name' => 'King Bed',
        'es_name' => 'Cama King',
        'bed_capacity' => 2,
        'sort_order' => 1,
        'is_active' => 1,
    ]);

    DB::table('bath_room_types')->insert([
        'name' => 'private_bathroom',
        'en_name' => 'Private Bathroom',
        'es_name' => 'Bano Privado',
        'description' => 'Private bathroom',
        'sort_order' => 1,
    ]);

    DB::table('fee_types')->insert([
        'name' => 'pet_fee',
        'en_name' => 'Pet Fee',
        'es_name' => 'Cargo por mascota',
        'order' => 1,
        'is_active' => 1,
    ]);

    DB::table('charge_bases')->insert([
        'name' => 'per_pet',
        'en_name' => 'Per Pet',
        'es_name' => 'Por mascota',
        'en_description' => 'Per pet',
        'es_description' => 'Por mascota',
        'order' => 1,
        'is_active' => 1,
        'metadata' => json_encode(['requires_quantity' => false, 'quantity_subject' => null]),
    ]);

    $this->artisan('migrate', [
        '--database' => config('database.default'),
        '--path' => 'database/migrations/'.$migration.'.php',
        '--realpath' => false,
        '--no-interaction' => true,
    ])->assertSuccessful();

    expect(Schema::hasColumns('platforms', ['slug']))->toBeTrue()
        ->and(DB::table('platforms')->where('en_name', 'Booking.com')->value('slug'))->toBe('booking-com')
        ->and(DB::table('platforms')->where('en_name', 'Airbnb')->value('slug'))->toBe('air-bnb')
        ->and(DB::table('bed_types')->value('slug'))->toBe('king-bed')
        ->and(DB::table('bath_room_types')->value('slug'))->toBe('private-bathroom')
        ->and(DB::table('fee_types')->value('slug'))->toBe('pet-fee')
        ->and(DB::table('charge_bases')->value('slug'))->toBe('per-pet');

    $this->artisan('migrate:rollback', [
        '--database' => config('database.default'),
        '--path' => 'database/migrations/'.$migration.'.php',
        '--step' => 1,
        '--no-interaction' => true,
    ])->assertSuccessful();

    expect(Schema::hasColumns('platforms', ['name']))->toBeTrue()
        ->and(DB::table('platforms')->where('en_name', 'Booking.com')->value('name'))->toBe('booking_com')
        ->and(DB::table('platforms')->where('en_name', 'Airbnb')->value('name'))->toBe('air-bnb')
        ->and(DB::table('bed_types')->value('name'))->toBe('king_bed')
        ->and(DB::table('bath_room_types')->value('name'))->toBe('private_bathroom')
        ->and(DB::table('fee_types')->value('name'))->toBe('pet_fee')
        ->and(DB::table('charge_bases')->value('name'))->toBe('per_pet');

    $this->artisan('migrate', [
        '--database' => config('database.default'),
        '--path' => 'database/migrations/'.$migration.'.php',
        '--realpath' => false,
        '--no-interaction' => true,
    ])->assertSuccessful();

    foreach (array_reverse($laterMigrations) as $laterMigration) {
        $this->artisan('migrate', [
            '--database' => config('database.default'),
            '--path' => 'database/migrations/'.$laterMigration.'.php',
            '--realpath' => false,
            '--no-interaction' => true,
        ])->assertSuccessful();
    }
});
