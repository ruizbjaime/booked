<?php

use App\Models\Platform;

it('returns localized name in english by default', function () {
    app()->setLocale('en');

    $platform = Platform::factory()->create([
        'en_name' => 'Booking.com',
        'es_name' => 'Booking.com',
    ]);

    expect($platform->localizedName())->toBe('Booking.com');
});

it('returns localized name in spanish when locale is es', function () {
    app()->setLocale('es');

    $platform = Platform::factory()->create([
        'en_name' => 'Direct Booking',
        'es_name' => 'Reserva Directa',
    ]);

    expect($platform->localizedName())->toBe('Reserva Directa');
});

it('filters active platforms with scope', function () {
    Platform::factory()->create();
    Platform::factory()->inactive()->create();

    expect(Platform::query()->active()->count())->toBe(1);
});

it('casts is_active to boolean', function () {
    $platform = Platform::factory()->create(['is_active' => true]);

    expect($platform->is_active)->toBeTrue()->toBeBool();
});

it('casts commission and commission_tax as decimal with 4 places', function () {
    $platform = Platform::factory()->create([
        'commission' => 0.155,
        'commission_tax' => 0.0325,
    ]);

    expect($platform->commission)->toBe('0.1550')
        ->and($platform->commission_tax)->toBe('0.0325');
});

it('searches by en_name and es_name', function () {
    Platform::factory()->create(['en_name' => 'Booking.com', 'es_name' => 'Booking.com']);
    Platform::factory()->create(['en_name' => 'Airbnb', 'es_name' => 'Airbnb']);

    expect(Platform::query()->search('Booking')->pluck('en_name')->all())->toBe(['Booking.com']);
});
