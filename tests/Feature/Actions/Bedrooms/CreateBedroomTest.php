<?php

use App\Actions\Bedrooms\CreateBedroom;
use App\Models\Bedroom;
use App\Models\Property;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

function validBedroomInput(array $overrides = []): array
{
    return array_merge([
        'en_name' => 'Main Bedroom',
        'es_name' => 'Habitación principal',
        'en_description' => 'Ocean view.',
        'es_description' => 'Vista al mar.',
    ], $overrides);
}

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('denies users who cannot update the property', function () {
    $guest = makeGuest();
    $property = Property::factory()->create();

    app(CreateBedroom::class)->handle($guest, $property, validBedroomInput());
})->throws(AuthorizationException::class);

it('creates a bedroom for the given property', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create();

    $bedroom = app(CreateBedroom::class)->handle($host, $property, validBedroomInput());

    expect($bedroom)->toBeInstanceOf(Bedroom::class)
        ->and($bedroom->property_id)->toBe($property->id)
        ->and($bedroom->en_name)->toBe('Main Bedroom')
        ->and($bedroom->es_name)->toBe('Habitación principal')
        ->and($bedroom->slug)->toBe('main-bedroom');
});

it('trims whitespace and normalizes blank descriptions to null', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create();

    $bedroom = app(CreateBedroom::class)->handle($host, $property, validBedroomInput([
        'en_name' => '  Main Bedroom  ',
        'es_name' => '  Habitación principal  ',
        'en_description' => '   ',
        'es_description' => '  Vista al mar.  ',
    ]));

    expect($bedroom->en_name)->toBe('Main Bedroom')
        ->and($bedroom->es_name)->toBe('Habitación principal')
        ->and($bedroom->en_description)->toBeNull()
        ->and($bedroom->es_description)->toBe('Vista al mar.');
});

it('rejects missing required names', function (string $field) {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create();

    app(CreateBedroom::class)->handle($host, $property, validBedroomInput([
        $field => '',
    ]));
})->throws(ValidationException::class)->with(['en_name', 'es_name']);

it('generates a unique slug on bedroom name collision', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create();

    $first = app(CreateBedroom::class)->handle($host, $property, validBedroomInput([
        'en_name' => 'Ocean View',
        'es_name' => 'Vista al mar',
    ]));

    $second = app(CreateBedroom::class)->handle($host, $property, validBedroomInput([
        'en_name' => 'Ocean View',
        'es_name' => 'Vista al océano',
    ]));

    expect($first->slug)->toBe('ocean-view')
        ->and($second->slug)->toStartWith('ocean-view-')
        ->and($second->slug)->not->toBe($first->slug);
});
