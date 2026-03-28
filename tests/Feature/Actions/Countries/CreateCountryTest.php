<?php

use App\Actions\Countries\CreateCountry;
use App\Models\Country;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('creates a country with valid input', function () {
    $admin = makeAdmin();

    $country = app(CreateCountry::class)->handle($admin, [
        'en_name' => 'Colombia',
        'es_name' => 'Colombia',
        'iso_alpha2' => 'co',
        'iso_alpha3' => 'col',
        'phone_code' => '+57',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    expect($country->en_name)->toBe('Colombia')
        ->and($country->iso_alpha2)->toBe('CO')
        ->and($country->iso_alpha3)->toBe('COL')
        ->and($country->is_active)->toBeTrue();
});

it('normalizes ISO codes to uppercase', function () {
    $admin = makeAdmin();

    $country = app(CreateCountry::class)->handle($admin, [
        'en_name' => 'Mexico',
        'es_name' => 'México',
        'iso_alpha2' => 'mx',
        'iso_alpha3' => 'mex',
        'phone_code' => '+52',
        'sort_order' => 2,
        'is_active' => true,
    ]);

    expect($country->iso_alpha2)->toBe('MX')
        ->and($country->iso_alpha3)->toBe('MEX');
});

it('rejects duplicate iso_alpha2 codes', function () {
    $admin = makeAdmin();
    Country::factory()->create(['iso_alpha2' => 'CO']);

    app(CreateCountry::class)->handle($admin, [
        'en_name' => 'Colombia Copy',
        'es_name' => 'Colombia Copia',
        'iso_alpha2' => 'CO',
        'iso_alpha3' => 'CCC',
        'phone_code' => '+57',
        'sort_order' => 1,
        'is_active' => true,
    ]);
})->throws(ValidationException::class);

it('rejects invalid phone code format', function () {
    $admin = makeAdmin();

    app(CreateCountry::class)->handle($admin, [
        'en_name' => 'Test',
        'es_name' => 'Test',
        'iso_alpha2' => 'TT',
        'iso_alpha3' => 'TST',
        'phone_code' => 'abc',
        'sort_order' => 1,
        'is_active' => true,
    ]);
})->throws(ValidationException::class);

it('throws authorization exception when non-admin creates a country', function () {
    $guest = makeGuest();

    app(CreateCountry::class)->handle($guest, [
        'en_name' => 'Test',
        'es_name' => 'Test',
        'iso_alpha2' => 'TT',
        'iso_alpha3' => 'TST',
        'phone_code' => '+1',
        'sort_order' => 1,
        'is_active' => true,
    ]);
})->throws(AuthorizationException::class);
