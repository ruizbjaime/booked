<?php

use App\Actions\BathRoomTypes\CreateBathRoomType;
use App\Models\BathRoomType;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

function validBathRoomTypeInput(array $overrides = []): array
{
    return array_merge([
        'en_name' => 'Private Bathroom',
        'es_name' => 'Bano privado',
        'description' => 'Bathroom reserved for the room guests.',
        'sort_order' => 100,
    ], $overrides);
}

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('denies non-admin users from creating a bathroom type', function () {
    $guest = makeGuest();

    app(CreateBathRoomType::class)->handle($guest, validBathRoomTypeInput());
})->throws(AuthorizationException::class);

it('creates a bathroom type with valid input', function () {
    $admin = makeAdmin();

    $bathRoomType = app(CreateBathRoomType::class)->handle($admin, validBathRoomTypeInput());

    expect($bathRoomType)->toBeInstanceOf(BathRoomType::class)
        ->and($bathRoomType->slug)->toBe('private-bathroom')
        ->and($bathRoomType->en_name)->toBe('Private Bathroom')
        ->and($bathRoomType->es_name)->toBe('Bano privado')
        ->and($bathRoomType->description)->toBe('Bathroom reserved for the room guests.')
        ->and($bathRoomType->sort_order)->toBe(100);
});

it('auto-generates slug from en_name on creation', function () {
    $admin = makeAdmin();

    $bathRoomType = app(CreateBathRoomType::class)->handle($admin, validBathRoomTypeInput([
        'en_name' => 'Shared Bathroom',
        'es_name' => 'Bano compartido',
    ]));

    expect($bathRoomType->slug)->toBe('shared-bathroom');
});

it('rejects missing translated labels', function () {
    $admin = makeAdmin();

    app(CreateBathRoomType::class)->handle($admin, validBathRoomTypeInput([
        'en_name' => '',
        'es_name' => '',
    ]));
})->throws(ValidationException::class);

it('trims translated labels and description before creating a bathroom type', function () {
    $admin = makeAdmin();

    $bathRoomType = app(CreateBathRoomType::class)->handle($admin, validBathRoomTypeInput([
        'en_name' => '  Private Bathroom  ',
        'es_name' => '  Bano privado  ',
        'description' => '  Bathroom reserved for the room guests.  ',
    ]));

    expect($bathRoomType->en_name)->toBe('Private Bathroom')
        ->and($bathRoomType->es_name)->toBe('Bano privado')
        ->and($bathRoomType->description)->toBe('Bathroom reserved for the room guests.');
});

it('rejects descriptions longer than the allowed limit', function () {
    $admin = makeAdmin();

    app(CreateBathRoomType::class)->handle($admin, validBathRoomTypeInput([
        'description' => str_repeat('a', 1001),
    ]));
})->throws(ValidationException::class);

it('rejects missing description', function () {
    $admin = makeAdmin();

    app(CreateBathRoomType::class)->handle($admin, validBathRoomTypeInput([
        'description' => '',
    ]));
})->throws(ValidationException::class);

it('rejects negative sort order', function () {
    $admin = makeAdmin();

    app(CreateBathRoomType::class)->handle($admin, validBathRoomTypeInput([
        'sort_order' => -1,
    ]));
})->throws(ValidationException::class);

it('rejects translated labels with invalid characters', function () {
    $admin = makeAdmin();

    app(CreateBathRoomType::class)->handle($admin, validBathRoomTypeInput([
        'en_name' => 'Private Bathroom!',
        'es_name' => 'Bano privado!',
    ]));
})->throws(ValidationException::class);

it('normalizes null fields to empty strings and fails validation', function (string $field) {
    $admin = makeAdmin();

    app(CreateBathRoomType::class)->handle($admin, validBathRoomTypeInput([
        $field => null,
    ]));
})->with(['en_name', 'es_name', 'description'])
    ->throws(ValidationException::class);

it('normalizes non-string fields to empty strings and fails validation', function (string $field) {
    $admin = makeAdmin();

    app(CreateBathRoomType::class)->handle($admin, validBathRoomTypeInput([
        $field => ['foo'],
    ]));
})->with(['en_name', 'es_name', 'description'])
    ->throws(ValidationException::class);
