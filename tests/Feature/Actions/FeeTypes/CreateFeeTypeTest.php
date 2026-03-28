<?php

use App\Actions\FeeTypes\CreateFeeType;
use App\Models\FeeType;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

function validFeeTypeInput(array $overrides = []): array
{
    return array_merge([
        'name' => 'cleaning-fee',
        'en_name' => 'Cleaning Fee',
        'es_name' => 'Tarifa de Limpieza',
        'order' => 100,
    ], $overrides);
}

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('denies non-admin users from creating a fee type', function () {
    $guest = makeGuest();

    app(CreateFeeType::class)->handle($guest, validFeeTypeInput());
})->throws(AuthorizationException::class);

it('creates a fee type with valid input', function () {
    $admin = makeAdmin();

    $feeType = app(CreateFeeType::class)->handle($admin, validFeeTypeInput());

    expect($feeType)->toBeInstanceOf(FeeType::class)
        ->and($feeType->name)->toBe('cleaning-fee')
        ->and($feeType->en_name)->toBe('Cleaning Fee')
        ->and($feeType->es_name)->toBe('Tarifa de Limpieza')
        ->and($feeType->order)->toBe(100);
});

it('normalizes the slug before creating a fee type', function () {
    $admin = makeAdmin();

    $feeType = app(CreateFeeType::class)->handle($admin, validFeeTypeInput([
        'name' => '  SERVICE-FEE  ',
    ]));

    expect($feeType->name)->toBe('service-fee');
});

it('rejects duplicate slugs', function () {
    $admin = makeAdmin();
    FeeType::factory()->create(['name' => 'cleaning-fee']);

    app(CreateFeeType::class)->handle($admin, validFeeTypeInput());
})->throws(ValidationException::class);

it('rejects invalid slug formats', function (string $name) {
    $admin = makeAdmin();

    app(CreateFeeType::class)->handle($admin, validFeeTypeInput(['name' => $name]));
})->with(['123-fee', 'cleaning fee', 'cleaning@fee', 'cleaning.fee'])
    ->throws(ValidationException::class);

it('accepts valid slug formats', function (string $name) {
    $admin = makeAdmin();

    $feeType = app(CreateFeeType::class)->handle($admin, validFeeTypeInput([
        'name' => $name,
        'en_name' => "Label {$name}",
        'es_name' => "Etiqueta {$name}",
    ]));

    expect($feeType->name)->toBe($name);
})->with(['cleaning-fee', 'cleaning_fee', 'c123']);

it('rejects missing translated labels', function () {
    $admin = makeAdmin();

    app(CreateFeeType::class)->handle($admin, validFeeTypeInput([
        'en_name' => '',
        'es_name' => '',
    ]));
})->throws(ValidationException::class);

it('trims translated labels before creating a fee type', function () {
    $admin = makeAdmin();

    $feeType = app(CreateFeeType::class)->handle($admin, validFeeTypeInput([
        'en_name' => '  Cleaning Fee  ',
        'es_name' => '  Tarifa de Limpieza  ',
    ]));

    expect($feeType->en_name)->toBe('Cleaning Fee')
        ->and($feeType->es_name)->toBe('Tarifa de Limpieza');
});

it('rejects order values above the allowed maximum', function () {
    $admin = makeAdmin();

    app(CreateFeeType::class)->handle($admin, validFeeTypeInput([
        'order' => 10000,
    ]));
})->throws(ValidationException::class);

it('rejects negative order', function () {
    $admin = makeAdmin();

    app(CreateFeeType::class)->handle($admin, validFeeTypeInput([
        'order' => -1,
    ]));
})->throws(ValidationException::class);

it('rejects translated labels with invalid characters', function () {
    $admin = makeAdmin();

    app(CreateFeeType::class)->handle($admin, validFeeTypeInput([
        'en_name' => 'Cleaning Fee!',
        'es_name' => 'Tarifa de Limpieza!',
    ]));
})->throws(ValidationException::class);

it('normalizes null fields to empty strings and fails validation', function (string $field) {
    $admin = makeAdmin();

    app(CreateFeeType::class)->handle($admin, validFeeTypeInput([
        $field => null,
    ]));
})->with(['name', 'en_name', 'es_name'])
    ->throws(ValidationException::class);

it('normalizes non-string fields to empty strings and fails validation', function (string $field) {
    $admin = makeAdmin();

    app(CreateFeeType::class)->handle($admin, validFeeTypeInput([
        $field => ['foo'],
    ]));
})->with(['name', 'en_name', 'es_name'])
    ->throws(ValidationException::class);
