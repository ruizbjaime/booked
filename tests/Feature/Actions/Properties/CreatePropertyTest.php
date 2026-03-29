<?php

use App\Actions\Properties\CreateProperty;
use App\Models\Country;
use App\Models\Property;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

function validPropertyInput(array $overrides = []): array
{
    return array_merge([
        'name' => 'Beach House',
        'city' => 'Cartagena',
        'address' => 'Calle 123 #45-67',
        'country_id' => Country::factory()->create()->id,
        'is_active' => true,
    ], $overrides);
}

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('denies non-host users from creating a property', function () {
    $guest = makeGuest();

    app(CreateProperty::class)->handle($guest, validPropertyInput());
})->throws(AuthorizationException::class);

it('creates a property with valid input', function () {
    $host = makeHost();

    $property = app(CreateProperty::class)->handle($host, validPropertyInput());

    expect($property)->toBeInstanceOf(Property::class)
        ->and($property->user_id)->toBe($host->id)
        ->and($property->name)->toBe('Beach House')
        ->and($property->city)->toBe('Cartagena')
        ->and($property->address)->toBe('Calle 123 #45-67')
        ->and($property->slug)->toContain('beach-house')
        ->and($property->is_active)->toBeTrue();
});

it('trims whitespace from string fields', function () {
    $host = makeHost();

    $property = app(CreateProperty::class)->handle($host, validPropertyInput([
        'name' => '  Beach House  ',
        'city' => '  Cartagena  ',
        'address' => '  Calle 123  ',
    ]));

    expect($property->name)->toBe('Beach House')
        ->and($property->city)->toBe('Cartagena')
        ->and($property->address)->toBe('Calle 123');
});

it('rejects an inactive country', function () {
    $host = makeHost();
    $inactiveCountry = Country::factory()->inactive()->create();

    app(CreateProperty::class)->handle($host, validPropertyInput([
        'country_id' => $inactiveCountry->id,
    ]));
})->throws(ValidationException::class);

it('rejects missing required fields', function (string $field) {
    $host = makeHost();

    app(CreateProperty::class)->handle($host, validPropertyInput([
        $field => '',
    ]));
})->throws(ValidationException::class)->with(['name', 'city', 'address']);

it('assigns the actor as the property owner', function () {
    $host = makeHost();

    $property = app(CreateProperty::class)->handle($host, validPropertyInput());

    expect($property->user_id)->toBe($host->id);
});

it('generates a unique slug on name collision', function () {
    $host = makeHost();
    $country = Country::factory()->create();

    $first = app(CreateProperty::class)->handle($host, validPropertyInput([
        'name' => 'Beach House',
        'country_id' => $country->id,
    ]));

    $second = app(CreateProperty::class)->handle($host, validPropertyInput([
        'name' => 'Beach House',
        'country_id' => $country->id,
    ]));

    expect($first->slug)->toBe('beach-house')
        ->and($second->slug)->toStartWith('beach-house-')
        ->and($second->slug)->not->toBe($first->slug);
});
