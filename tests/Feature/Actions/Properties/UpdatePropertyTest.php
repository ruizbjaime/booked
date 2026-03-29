<?php

use App\Actions\Properties\UpdateProperty;
use App\Models\Country;
use App\Models\Property;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('throws authorization exception when non-host updates a property', function () {
    $guest = makeGuest();
    $property = Property::factory()->create();

    app(UpdateProperty::class)->handle($guest, $property, 'name', 'Updated Property');
})->throws(AuthorizationException::class);

it('updates the name and regenerates slug', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create(['name' => 'Old Name']);

    app(UpdateProperty::class)->handle($host, $property, 'name', 'New Name');

    $fresh = $property->fresh();

    expect($fresh->name)->toBe('New Name')
        ->and($fresh->slug)->toContain('new_name');
});

it('trims whitespace from name', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create();

    app(UpdateProperty::class)->handle($host, $property, 'name', '  Beach House  ');

    expect($property->fresh()->name)->toBe('Beach House');
});

it('updates the city', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create(['city' => 'Bogotá']);

    app(UpdateProperty::class)->handle($host, $property, 'city', 'Medellín');

    expect($property->fresh()->city)->toBe('Medellín');
});

it('updates the address', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create(['address' => 'Calle 1']);

    app(UpdateProperty::class)->handle($host, $property, 'address', 'Calle 2 #10-20');

    expect($property->fresh()->address)->toBe('Calle 2 #10-20');
});

it('updates the country', function () {
    $host = makeHost();
    $newCountry = Country::factory()->create();
    $property = Property::factory()->forUser($host)->create();

    app(UpdateProperty::class)->handle($host, $property, 'country_id', $newCountry->id);

    expect($property->fresh()->country_id)->toBe($newCountry->id);
});

it('rejects an inactive country', function () {
    $host = makeHost();
    $inactiveCountry = Country::factory()->inactive()->create();
    $property = Property::factory()->forUser($host)->create();

    app(UpdateProperty::class)->handle($host, $property, 'country_id', $inactiveCountry->id);
})->throws(ValidationException::class);

it('updates the active state', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create(['is_active' => true]);

    app(UpdateProperty::class)->handle($host, $property, 'is_active', false);

    expect($property->fresh()->is_active)->toBeFalse();
});

it('rejects invalid active state values', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create(['is_active' => true]);

    app(UpdateProperty::class)->handle($host, $property, 'is_active', 'not-a-bool');
})->throws(ValidationException::class);

it('aborts with 422 for an unknown property field', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create();

    try {
        app(UpdateProperty::class)->handle($host, $property, 'unknown_field', 'value');
        $this->fail('Expected HttpException was not thrown');
    } catch (HttpException $exception) {
        expect($exception->getStatusCode())->toBe(422);
    }
});
