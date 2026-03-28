<?php

use App\Actions\Countries\ToggleCountryActiveStatus;
use App\Models\Country;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('activates a country', function () {
    $admin = makeAdmin();
    $country = Country::factory()->create(['is_active' => false]);

    app(ToggleCountryActiveStatus::class)->handle($admin, $country, true);

    expect($country->fresh()->is_active)->toBeTrue();
});

it('deactivates a country', function () {
    $admin = makeAdmin();
    $country = Country::factory()->create(['is_active' => true]);

    app(ToggleCountryActiveStatus::class)->handle($admin, $country, false);

    expect($country->fresh()->is_active)->toBeFalse();
});

it('throws authorization exception when non-admin toggles a country', function () {
    $guest = makeGuest();
    $country = Country::factory()->create();

    app(ToggleCountryActiveStatus::class)->handle($guest, $country, true);
})->throws(AuthorizationException::class);
