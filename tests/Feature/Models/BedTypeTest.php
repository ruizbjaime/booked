<?php

use App\Models\BedType;

it('returns localized name in english by default', function () {
    app()->setLocale('en');

    $bedType = BedType::factory()->create([
        'en_name' => 'King Bed',
        'es_name' => 'Cama King',
    ]);

    expect($bedType->localizedName())->toBe('King Bed');
});

it('returns localized name in spanish when locale is es', function () {
    app()->setLocale('es');

    $bedType = BedType::factory()->create([
        'en_name' => 'Double Bed',
        'es_name' => 'Cama Doble',
    ]);

    expect($bedType->localizedName())->toBe('Cama Doble');
});

it('searches by slug and localized labels', function () {
    BedType::factory()->create([
        'name' => 'king-bed',
        'en_name' => 'King Bed',
        'es_name' => 'Cama King',
    ]);

    BedType::factory()->create([
        'name' => 'single-bed',
        'en_name' => 'Single Bed',
        'es_name' => 'Cama Sencilla',
    ]);

    expect(BedType::query()->search('king-bed')->pluck('name')->all())->toBe(['king-bed'])
        ->and(BedType::query()->search('King Bed')->pluck('name')->all())->toBe(['king-bed'])
        ->and(BedType::query()->search('Sencilla')->pluck('name')->all())->toBe(['single-bed']);
});

it('returns the localized name column for each locale', function () {
    app()->setLocale('en');
    expect(BedType::localizedNameColumn())->toBe('en_name');

    app()->setLocale('es');
    expect(BedType::localizedNameColumn())->toBe('es_name');
});

it('exposes localized name as eloquent attribute accessor', function () {
    app()->setLocale('en');

    $bedType = BedType::factory()->create([
        'en_name' => 'Queen Bed',
        'es_name' => 'Cama Queen',
    ]);

    expect($bedType->localized_name_attribute)->toBe('Queen Bed');

    app()->setLocale('es');

    expect($bedType->localized_name_attribute)->toBe('Cama Queen');
});

it('filters only active bed types with the active scope', function () {
    BedType::factory()->create(['is_active' => true, 'name' => 'active-bed']);
    BedType::factory()->create(['is_active' => false, 'name' => 'inactive-bed']);

    $results = BedType::query()->active()->pluck('name')->all();

    expect($results)->toBe(['active-bed']);
});
