<?php

use App\Actions\BathRoomTypes\CreateBathRoomType;
use App\Models\BathRoomType;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

function validBathRoomTypeInput(array $overrides = []): array
{
    return array_merge([
        'name' => 'private-bathroom',
        'name_en' => 'Private Bathroom',
        'name_es' => 'Bano privado',
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
        ->and($bathRoomType->name)->toBe('private-bathroom')
        ->and($bathRoomType->name_en)->toBe('Private Bathroom')
        ->and($bathRoomType->name_es)->toBe('Bano privado')
        ->and($bathRoomType->description)->toBe('Bathroom reserved for the room guests.')
        ->and($bathRoomType->sort_order)->toBe(100);
});

it('normalizes the slug before creating a bathroom type', function () {
    $admin = makeAdmin();

    $bathRoomType = app(CreateBathRoomType::class)->handle($admin, validBathRoomTypeInput([
        'name' => '  PRIVATE-BATHROOM  ',
    ]));

    expect($bathRoomType->name)->toBe('private-bathroom');
});

it('rejects duplicate names', function () {
    $admin = makeAdmin();
    BathRoomType::factory()->create(['name' => 'private-bathroom']);

    app(CreateBathRoomType::class)->handle($admin, validBathRoomTypeInput());
})->throws(ValidationException::class);

it('rejects invalid slug formats', function (string $name) {
    $admin = makeAdmin();

    app(CreateBathRoomType::class)->handle($admin, validBathRoomTypeInput(['name' => $name]));
})->with(['123-bathroom', 'private bathroom', 'private@bathroom', 'private.bathroom'])
    ->throws(ValidationException::class);

it('accepts valid slug formats', function (string $name) {
    $admin = makeAdmin();

    $bathRoomType = app(CreateBathRoomType::class)->handle($admin, validBathRoomTypeInput([
        'name' => $name,
        'name_en' => "Label {$name}",
        'name_es' => "Etiqueta {$name}",
    ]));

    expect($bathRoomType->name)->toBe($name);
})->with(['private-bathroom', 'private_bathroom', 'p123']);

it('rejects missing translated labels', function () {
    $admin = makeAdmin();

    app(CreateBathRoomType::class)->handle($admin, validBathRoomTypeInput([
        'name_en' => '',
        'name_es' => '',
    ]));
})->throws(ValidationException::class);

it('trims translated labels and description before creating a bathroom type', function () {
    $admin = makeAdmin();

    $bathRoomType = app(CreateBathRoomType::class)->handle($admin, validBathRoomTypeInput([
        'name_en' => '  Private Bathroom  ',
        'name_es' => '  Bano privado  ',
        'description' => '  Bathroom reserved for the room guests.  ',
    ]));

    expect($bathRoomType->name_en)->toBe('Private Bathroom')
        ->and($bathRoomType->name_es)->toBe('Bano privado')
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
        'name_en' => 'Private Bathroom!',
        'name_es' => 'Bano privado!',
    ]));
})->throws(ValidationException::class);
