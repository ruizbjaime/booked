<?php

use App\Actions\Countries\DeleteCountry;
use App\Models\Country;
use App\Models\Property;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('deletes a country without associated users', function () {
    $admin = makeAdmin();
    $country = Country::factory()->create();

    $result = app(DeleteCountry::class)->handle($admin, $country);

    expect($result)->toBeTrue()
        ->and(Country::query()->find($country->id))->toBeNull();
});

it('deactivates a country with associated users instead of deleting', function () {
    $admin = makeAdmin();
    $country = Country::factory()->create(['is_active' => true]);
    User::factory()->create(['country_id' => $country->id]);

    $result = app(DeleteCountry::class)->handle($admin, $country);

    expect($result)->toBeFalse()
        ->and($country->fresh()->is_active)->toBeFalse()
        ->and(Country::query()->find($country->id))->not->toBeNull();
});

it('deactivates a country with associated properties instead of deleting', function () {
    $admin = makeAdmin();
    $country = Country::factory()->create(['is_active' => true]);
    Property::factory()->create(['country_id' => $country->id]);

    $result = app(DeleteCountry::class)->handle($admin, $country);

    expect($result)->toBeFalse()
        ->and($country->fresh()->is_active)->toBeFalse()
        ->and(Country::query()->find($country->id))->not->toBeNull();
});

it('throws authorization exception when non-admin deletes a country', function () {
    $guest = makeGuest();
    $country = Country::factory()->create();

    app(DeleteCountry::class)->handle($guest, $country);
})->throws(AuthorizationException::class);
