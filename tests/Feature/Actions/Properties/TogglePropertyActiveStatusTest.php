<?php

use App\Actions\Properties\TogglePropertyActiveStatus;
use App\Models\Property;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('allows a host to deactivate a property', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create(['is_active' => true]);

    app(TogglePropertyActiveStatus::class)->handle($host, $property, false);

    expect($property->fresh()->is_active)->toBeFalse();
});

it('allows a host to activate a property', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create(['is_active' => false]);

    app(TogglePropertyActiveStatus::class)->handle($host, $property, true);

    expect($property->fresh()->is_active)->toBeTrue();
});

it('forbids non-host users from toggling a property', function () {
    $guest = makeGuest();
    $property = Property::factory()->create(['is_active' => true]);

    expect(fn () => app(TogglePropertyActiveStatus::class)->handle($guest, $property, false))
        ->toThrow(AuthorizationException::class);
});
