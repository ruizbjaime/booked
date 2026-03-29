<?php

use App\Actions\BedTypes\CreateBedType;
use App\Models\BedType;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

function validBedTypeInput(array $overrides = []): array
{
    return array_merge([
        'en_name' => 'Queen Bed',
        'es_name' => 'Cama Queen',
        'bed_capacity' => 2,
        'sort_order' => 100,
    ], $overrides);
}

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('denies non-admin users from creating a bed type', function () {
    $guest = makeGuest();

    app(CreateBedType::class)->handle($guest, validBedTypeInput());
})->throws(AuthorizationException::class);

it('creates a bed type with valid input', function () {
    $admin = makeAdmin();

    $bedType = app(CreateBedType::class)->handle($admin, validBedTypeInput());

    expect($bedType)->toBeInstanceOf(BedType::class)
        ->and($bedType->slug)->toBe('queen-bed')
        ->and($bedType->en_name)->toBe('Queen Bed')
        ->and($bedType->es_name)->toBe('Cama Queen')
        ->and($bedType->bed_capacity)->toBe(2)
        ->and($bedType->sort_order)->toBe(100);
});

it('auto-generates slug from en_name on creation', function () {
    $admin = makeAdmin();

    $bedType = app(CreateBedType::class)->handle($admin, validBedTypeInput([
        'en_name' => 'King Size Bed',
        'es_name' => 'Cama King',
    ]));

    expect($bedType->slug)->toBe('king-size-bed');
});

it('rejects missing translated labels', function () {
    $admin = makeAdmin();

    app(CreateBedType::class)->handle($admin, validBedTypeInput([
        'en_name' => '',
        'es_name' => '',
    ]));
})->throws(ValidationException::class);

it('trims translated labels before creating a bed type', function () {
    $admin = makeAdmin();

    $bedType = app(CreateBedType::class)->handle($admin, validBedTypeInput([
        'en_name' => '  Queen Bed  ',
        'es_name' => '  Cama Queen  ',
    ]));

    expect($bedType->en_name)->toBe('Queen Bed')
        ->and($bedType->es_name)->toBe('Cama Queen');
});

it('rejects bed capacity above the allowed maximum', function () {
    $admin = makeAdmin();

    app(CreateBedType::class)->handle($admin, validBedTypeInput([
        'bed_capacity' => 21,
    ]));
})->throws(ValidationException::class);

it('rejects bed capacity below one', function () {
    $admin = makeAdmin();

    app(CreateBedType::class)->handle($admin, validBedTypeInput([
        'bed_capacity' => 0,
    ]));
})->throws(ValidationException::class);

it('rejects negative sort order', function () {
    $admin = makeAdmin();

    app(CreateBedType::class)->handle($admin, validBedTypeInput([
        'sort_order' => -1,
    ]));
})->throws(ValidationException::class);

it('rejects translated labels with invalid characters', function () {
    $admin = makeAdmin();

    app(CreateBedType::class)->handle($admin, validBedTypeInput([
        'en_name' => 'Queen Bed!',
        'es_name' => 'Cama Queen!',
    ]));
})->throws(ValidationException::class);

it('normalizes null fields to empty strings and fails validation', function (string $field) {
    $admin = makeAdmin();

    app(CreateBedType::class)->handle($admin, validBedTypeInput([
        $field => null,
    ]));
})->with(['en_name', 'es_name'])
    ->throws(ValidationException::class);

it('normalizes non-string fields to empty strings and fails validation', function (string $field) {
    $admin = makeAdmin();

    app(CreateBedType::class)->handle($admin, validBedTypeInput([
        $field => ['foo'],
    ]));
})->with(['en_name', 'es_name'])
    ->throws(ValidationException::class);
