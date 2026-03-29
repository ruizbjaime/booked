<?php

use App\Models\ChargeBasis;
use App\Models\FeeType;

it('searches by name with scopeSearch', function () {
    FeeType::factory()->create(['slug' => 'cleaning-fee', 'en_name' => 'Cleaning Fee', 'es_name' => 'Tarifa de Limpieza']);
    FeeType::factory()->create(['slug' => 'service-fee', 'en_name' => 'Service Fee', 'es_name' => 'Tarifa de Servicio']);

    expect(FeeType::query()->search('cleaning')->pluck('slug')->all())->toBe(['cleaning-fee']);
});

it('searches by en_name with scopeSearch', function () {
    FeeType::factory()->create(['slug' => 'cleaning-fee', 'en_name' => 'Cleaning Fee', 'es_name' => 'Tarifa de Limpieza']);
    FeeType::factory()->create(['slug' => 'service-fee', 'en_name' => 'Service Fee', 'es_name' => 'Tarifa de Servicio']);

    expect(FeeType::query()->search('Cleaning')->pluck('slug')->all())->toBe(['cleaning-fee']);
});

it('searches by es_name with scopeSearch', function () {
    FeeType::factory()->create(['slug' => 'cleaning-fee', 'en_name' => 'Cleaning Fee', 'es_name' => 'Tarifa de Limpieza']);
    FeeType::factory()->create(['slug' => 'service-fee', 'en_name' => 'Service Fee', 'es_name' => 'Tarifa de Servicio']);

    expect(FeeType::query()->search('Servicio')->pluck('slug')->all())->toBe(['service-fee']);
});

it('escapes special SQL characters in search to prevent wildcard matching', function () {
    FeeType::factory()->create(['slug' => 'alpha', 'en_name' => 'Alpha Fee', 'es_name' => 'Tarifa Alpha']);
    FeeType::factory()->create(['slug' => 'beta', 'en_name' => 'Beta Fee', 'es_name' => 'Tarifa Beta']);

    expect(FeeType::query()->search('%')->count())->toBe(0)
        ->and(FeeType::query()->search('_')->count())->toBe(0);
});

it('returns en_name for en locale with localizedName', function () {
    app()->setLocale('en');

    $feeType = FeeType::factory()->create([
        'en_name' => 'Cleaning Fee',
        'es_name' => 'Tarifa de Limpieza',
    ]);

    expect($feeType->localizedName())->toBe('Cleaning Fee');
});

it('returns es_name for es locale with localizedName', function () {
    app()->setLocale('es');

    $feeType = FeeType::factory()->create([
        'en_name' => 'Cleaning Fee',
        'es_name' => 'Tarifa de Limpieza',
    ]);

    expect($feeType->localizedName())->toBe('Tarifa de Limpieza');
});

it('returns correct column per locale with localizedNameColumn', function () {
    app()->setLocale('en');
    expect(FeeType::localizedNameColumn())->toBe('en_name');

    app()->setLocale('es');
    expect(FeeType::localizedNameColumn())->toBe('es_name');
});

it('returns related charge bases through chargeBases relationship', function () {
    $feeType = FeeType::factory()->create();
    $basis = ChargeBasis::factory()->create();

    $feeType->chargeBases()->attach($basis->id, ['sort_order' => 1]);

    $related = $feeType->chargeBases()->get();

    expect($related)->toHaveCount(1)
        ->and($related->first()->id)->toBe($basis->id);
});

it('orders chargeBases relationship by pivot sort_order', function () {
    $feeType = FeeType::factory()->create();
    $basisA = ChargeBasis::factory()->create(['order' => 1]);
    $basisB = ChargeBasis::factory()->create(['order' => 2]);

    $feeType->chargeBases()->attach($basisA->id, ['sort_order' => 20]);
    $feeType->chargeBases()->attach($basisB->id, ['sort_order' => 10]);

    $related = $feeType->chargeBases()->get();

    expect($related->first()->id)->toBe($basisB->id)
        ->and($related->last()->id)->toBe($basisA->id);
});

it('filters only active fee types with the active scope', function () {
    FeeType::factory()->create(['is_active' => true, 'slug' => 'active-fee']);
    FeeType::factory()->create(['is_active' => false, 'slug' => 'inactive-fee']);

    $results = FeeType::query()->active()->pluck('slug')->all();

    expect($results)->toBe(['active-fee']);
});

it('resolves the localized name attribute for fee types', function () {
    $feeType = FeeType::factory()->create(['en_name' => 'Cleaning Fee', 'es_name' => 'Tarifa de Limpieza']);

    expect($feeType->localized_name_attribute)->toBeString()->not->toBeEmpty();
});
