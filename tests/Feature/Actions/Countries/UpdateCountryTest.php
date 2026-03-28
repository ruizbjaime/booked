<?php

use App\Actions\Countries\UpdateCountry;
use App\Models\Country;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('throws authorization exception when non-admin user updates a country', function () {
    $guest = makeGuest();
    $country = Country::factory()->create();

    app(UpdateCountry::class)->handle($guest, $country, 'en_name', 'Updated Country');
})->throws(AuthorizationException::class);

it('updates localized names and phone codes', function () {
    $admin = makeAdmin();
    $country = Country::factory()->create([
        'en_name' => 'Old Name',
        'es_name' => 'Nombre Viejo',
        'phone_code' => '+57',
    ]);

    app(UpdateCountry::class)->handle($admin, $country, 'en_name', 'New Name');
    app(UpdateCountry::class)->handle($admin, $country, 'es_name', 'Nombre Nuevo');
    app(UpdateCountry::class)->handle($admin, $country, 'phone_code', '+3491');

    expect($country->fresh()->en_name)->toBe('New Name')
        ->and($country->fresh()->es_name)->toBe('Nombre Nuevo')
        ->and($country->fresh()->phone_code)->toBe('+3491');
});

it('normalizes country iso codes to uppercase', function () {
    $admin = makeAdmin();
    $country = Country::factory()->create([
        'iso_alpha2' => 'CO',
        'iso_alpha3' => 'COL',
    ]);

    app(UpdateCountry::class)->handle($admin, $country, 'iso_alpha2', 'us');
    app(UpdateCountry::class)->handle($admin, $country, 'iso_alpha3', 'mex');

    expect($country->fresh()->iso_alpha2)->toBe('US')
        ->and($country->fresh()->iso_alpha3)->toBe('MEX');
});

it('updates sort order and active state', function () {
    $admin = makeAdmin();
    $country = Country::factory()->inactive()->create([
        'sort_order' => 3,
    ]);

    app(UpdateCountry::class)->handle($admin, $country, 'sort_order', 10);
    app(UpdateCountry::class)->handle($admin, $country, 'is_active', true);

    expect($country->fresh()->sort_order)->toBe(10)
        ->and($country->fresh()->is_active)->toBeTrue();
});

it('rejects duplicate iso codes from another country', function () {
    $admin = makeAdmin();
    Country::factory()->create(['iso_alpha2' => 'US', 'iso_alpha3' => 'USA']);
    $country = Country::factory()->create(['iso_alpha2' => 'CO', 'iso_alpha3' => 'COL']);

    app(UpdateCountry::class)->handle($admin, $country, 'iso_alpha2', 'us');
})->throws(ValidationException::class);

it('rejects invalid phone codes', function () {
    $admin = makeAdmin();
    $country = Country::factory()->create();

    app(UpdateCountry::class)->handle($admin, $country, 'phone_code', 'abc');
})->throws(ValidationException::class);

it('aborts with 422 for an unknown country field', function () {
    $admin = makeAdmin();
    $country = Country::factory()->create();

    try {
        app(UpdateCountry::class)->handle($admin, $country, 'unknown_field', 'value');
        $this->fail('Expected HttpException was not thrown');
    } catch (HttpException $exception) {
        expect($exception->getStatusCode())->toBe(422);
    }
});
