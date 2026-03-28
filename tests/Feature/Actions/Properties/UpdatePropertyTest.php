<?php

use App\Actions\Properties\UpdateProperty;
use App\Models\Property;
use App\Models\User;
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

it('updates the active state', function () {
    $host = User::factory()->create();
    $host->assignRole('host');

    $property = Property::factory()->create(['is_active' => true]);

    app(UpdateProperty::class)->handle($host, $property, 'is_active', false);

    expect($property->fresh()->is_active)->toBeFalse();
});

it('rejects invalid active state values', function () {
    $host = User::factory()->create();
    $host->assignRole('host');

    $property = Property::factory()->create(['is_active' => true]);

    app(UpdateProperty::class)->handle($host, $property, 'is_active', 'not-a-bool');
})->throws(ValidationException::class);

it('aborts with 422 for an unknown property field', function () {
    $host = User::factory()->create();
    $host->assignRole('host');

    $property = Property::factory()->create();

    try {
        app(UpdateProperty::class)->handle($host, $property, 'unknown_field', 'value');
        $this->fail('Expected HttpException was not thrown');
    } catch (HttpException $exception) {
        expect($exception->getStatusCode())->toBe(422);
    }
});
