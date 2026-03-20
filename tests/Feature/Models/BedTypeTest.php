<?php

use App\Models\BedType;

it('returns localized name in english by default', function () {
    app()->setLocale('en');

    $bedType = BedType::factory()->create([
        'name_en' => 'King Bed',
        'name_es' => 'Cama King',
    ]);

    expect($bedType->localizedName())->toBe('King Bed');
});

it('returns localized name in spanish when locale is es', function () {
    app()->setLocale('es');

    $bedType = BedType::factory()->create([
        'name_en' => 'Double Bed',
        'name_es' => 'Cama Doble',
    ]);

    expect($bedType->localizedName())->toBe('Cama Doble');
});

it('searches by slug and localized labels', function () {
    BedType::factory()->create([
        'name' => 'king-bed',
        'name_en' => 'King Bed',
        'name_es' => 'Cama King',
    ]);

    BedType::factory()->create([
        'name' => 'single-bed',
        'name_en' => 'Single Bed',
        'name_es' => 'Cama Sencilla',
    ]);

    expect(BedType::query()->search('king-bed')->pluck('name')->all())->toBe(['king-bed'])
        ->and(BedType::query()->search('King Bed')->pluck('name')->all())->toBe(['king-bed'])
        ->and(BedType::query()->search('Sencilla')->pluck('name')->all())->toBe(['single-bed']);
});

it('returns the localized name column for each locale', function () {
    app()->setLocale('en');
    expect(BedType::localizedNameColumn())->toBe('name_en');

    app()->setLocale('es');
    expect(BedType::localizedNameColumn())->toBe('name_es');
});

it('exposes localized name as eloquent attribute accessor', function () {
    app()->setLocale('en');

    $bedType = BedType::factory()->create([
        'name_en' => 'Queen Bed',
        'name_es' => 'Cama Queen',
    ]);

    expect($bedType->localized_name_attribute)->toBe('Queen Bed');

    app()->setLocale('es');

    expect($bedType->localized_name_attribute)->toBe('Cama Queen');
});
