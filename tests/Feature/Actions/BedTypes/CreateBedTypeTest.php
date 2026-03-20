<?php

use App\Actions\BedTypes\CreateBedType;
use App\Models\BedType;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

function validBedTypeInput(array $overrides = []): array
{
    return array_merge([
        'name' => 'queen-bed',
        'name_en' => 'Queen Bed',
        'name_es' => 'Cama Queen',
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
        ->and($bedType->name)->toBe('queen-bed')
        ->and($bedType->name_en)->toBe('Queen Bed')
        ->and($bedType->name_es)->toBe('Cama Queen')
        ->and($bedType->bed_capacity)->toBe(2)
        ->and($bedType->sort_order)->toBe(100);
});

it('normalizes the slug before creating a bed type', function () {
    $admin = makeAdmin();

    $bedType = app(CreateBedType::class)->handle($admin, validBedTypeInput([
        'name' => '  KING-BED  ',
    ]));

    expect($bedType->name)->toBe('king-bed');
});

it('rejects duplicate names', function () {
    $admin = makeAdmin();
    BedType::factory()->create(['name' => 'queen-bed']);

    app(CreateBedType::class)->handle($admin, validBedTypeInput());
})->throws(ValidationException::class);

it('rejects invalid slug formats', function (string $name) {
    $admin = makeAdmin();

    app(CreateBedType::class)->handle($admin, validBedTypeInput(['name' => $name]));
})->with(['123-bed', 'queen bed', 'queen@bed', 'queen.bed'])
    ->throws(ValidationException::class);

it('accepts valid slug formats', function (string $name) {
    $admin = makeAdmin();

    $bedType = app(CreateBedType::class)->handle($admin, validBedTypeInput([
        'name' => $name,
        'name_en' => "Label {$name}",
        'name_es' => "Etiqueta {$name}",
    ]));

    expect($bedType->name)->toBe($name);
})->with(['queen-bed', 'queen_bed', 'q123']);

it('rejects missing translated labels', function () {
    $admin = makeAdmin();

    app(CreateBedType::class)->handle($admin, validBedTypeInput([
        'name_en' => '',
        'name_es' => '',
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
