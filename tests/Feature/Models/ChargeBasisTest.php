<?php

use App\Models\ChargeBasis;
use App\Models\FeeType;

it('filters only active records with scopeActive', function () {
    ChargeBasis::factory()->create(['is_active' => true]);
    ChargeBasis::factory()->create(['is_active' => false]);

    $active = ChargeBasis::query()->active()->get();

    expect($active)->toHaveCount(1)
        ->and($active->first()->is_active)->toBeTrue();
});

it('searches by name with scopeSearch', function () {
    ChargeBasis::factory()->create(['name' => 'per_night', 'en_name' => 'Per Night', 'es_name' => 'Por Noche']);
    ChargeBasis::factory()->create(['name' => 'per_guest', 'en_name' => 'Per Guest', 'es_name' => 'Por Huesped']);

    expect(ChargeBasis::query()->search('night')->pluck('name')->all())->toBe(['per_night']);
});

it('searches by en_name with scopeSearch', function () {
    ChargeBasis::factory()->create(['name' => 'per_night', 'en_name' => 'Per Night', 'es_name' => 'Por Noche']);
    ChargeBasis::factory()->create(['name' => 'per_guest', 'en_name' => 'Per Guest', 'es_name' => 'Por Huesped']);

    expect(ChargeBasis::query()->search('Night')->pluck('name')->all())->toBe(['per_night']);
});

it('searches by es_name with scopeSearch', function () {
    ChargeBasis::factory()->create(['name' => 'per_night', 'en_name' => 'Per Night', 'es_name' => 'Por Noche']);
    ChargeBasis::factory()->create(['name' => 'per_guest', 'en_name' => 'Per Guest', 'es_name' => 'Por Huesped']);

    expect(ChargeBasis::query()->search('Huesped')->pluck('name')->all())->toBe(['per_guest']);
});

it('escapes special SQL characters in search to prevent wildcard matching', function () {
    ChargeBasis::factory()->create(['name' => 'alpha', 'en_name' => 'Alpha Basis', 'es_name' => 'Base Alpha']);
    ChargeBasis::factory()->create(['name' => 'beta', 'en_name' => 'Beta Basis', 'es_name' => 'Base Beta']);

    expect(ChargeBasis::query()->search('%')->count())->toBe(0)
        ->and(ChargeBasis::query()->search('_')->count())->toBe(0);
});

it('returns en_name for en locale with localizedName', function () {
    app()->setLocale('en');

    $basis = ChargeBasis::factory()->create([
        'en_name' => 'Per Night',
        'es_name' => 'Por Noche',
    ]);

    expect($basis->localizedName())->toBe('Per Night');
});

it('returns es_name for es locale with localizedName', function () {
    app()->setLocale('es');

    $basis = ChargeBasis::factory()->create([
        'en_name' => 'Per Night',
        'es_name' => 'Por Noche',
    ]);

    expect($basis->localizedName())->toBe('Por Noche');
});

it('returns en_description for en locale with localizedDescription', function () {
    app()->setLocale('en');

    $basis = ChargeBasis::factory()->create([
        'en_description' => 'Applied per night.',
        'es_description' => 'Aplicado por noche.',
    ]);

    expect($basis->localizedDescription())->toBe('Applied per night.');
});

it('returns es_description for es locale with localizedDescription', function () {
    app()->setLocale('es');

    $basis = ChargeBasis::factory()->create([
        'en_description' => 'Applied per night.',
        'es_description' => 'Aplicado por noche.',
    ]);

    expect($basis->localizedDescription())->toBe('Aplicado por noche.');
});

it('returns correct column per locale with localizedDescriptionColumn', function () {
    app()->setLocale('en');
    expect(ChargeBasis::localizedDescriptionColumn())->toBe('en_description');

    app()->setLocale('es');
    expect(ChargeBasis::localizedDescriptionColumn())->toBe('es_description');
});

it('returns correct column per locale with localizedNameColumn', function () {
    app()->setLocale('en');
    expect(ChargeBasis::localizedNameColumn())->toBe('en_name');

    app()->setLocale('es');
    expect(ChargeBasis::localizedNameColumn())->toBe('es_name');
});

it('returns active label when active with statusLabel', function () {
    $basis = ChargeBasis::factory()->create(['is_active' => true]);

    expect($basis->statusLabel())->toBe(__('charge_bases.show.status.active'));
});

it('returns inactive label when inactive with statusLabel', function () {
    $basis = ChargeBasis::factory()->create(['is_active' => false]);

    expect($basis->statusLabel())->toBe(__('charge_bases.show.status.inactive'));
});

it('returns related fee types through feeTypes relationship', function () {
    $basis = ChargeBasis::factory()->create();
    $feeType = FeeType::factory()->create();

    $basis->feeTypes()->attach($feeType->id, ['sort_order' => 1]);

    $related = $basis->feeTypes()->get();

    expect($related)->toHaveCount(1)
        ->and($related->first()->id)->toBe($feeType->id);
});

it('casts metadata to array', function () {
    $basis = ChargeBasis::factory()->create([
        'metadata' => ['requires_quantity' => true, 'quantity_subject' => 'guest'],
    ]);

    $fresh = $basis->fresh();

    expect($fresh->metadata)->toBeArray()
        ->and($fresh->metadata['requires_quantity'])->toBeTrue()
        ->and($fresh->metadata['quantity_subject'])->toBe('guest');
});
