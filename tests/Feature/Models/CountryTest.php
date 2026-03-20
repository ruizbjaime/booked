<?php

use App\Models\Country;
use App\Models\User;

it('has a users relationship', function () {
    $country = Country::factory()->create();
    $user = User::factory()->create(['country_id' => $country->id]);

    expect($country->users)
        ->toHaveCount(1)
        ->first()->id->toBe($user->id);
});

it('returns localized name in english by default', function () {
    app()->setLocale('en');

    $country = Country::factory()->create([
        'en_name' => 'Colombia',
        'es_name' => 'Colombia',
    ]);

    expect($country->localizedName())->toBe('Colombia');
});

it('returns localized name in spanish when locale is es', function () {
    app()->setLocale('es');

    $country = Country::factory()->create([
        'en_name' => 'United States',
        'es_name' => 'Estados Unidos',
    ]);

    expect($country->localizedName())->toBe('Estados Unidos');
});

it('filters active countries with scope', function () {
    Country::factory()->create();
    Country::factory()->inactive()->create();

    expect(Country::query()->active()->count())->toBe(1);
});

it('casts is_active to boolean', function () {
    $country = Country::factory()->create(['is_active' => true]);

    expect($country->is_active)->toBeTrue()->toBeBool();
});
