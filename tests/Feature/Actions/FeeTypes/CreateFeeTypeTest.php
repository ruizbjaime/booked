<?php

use App\Actions\FeeTypes\CreateFeeType;
use App\Models\FeeType;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

function validFeeTypeInput(array $overrides = []): array
{
    return array_merge([
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
        ->and($feeType->slug)->toBe('cleaning-fee')
        ->and($feeType->en_name)->toBe('Cleaning Fee')
        ->and($feeType->es_name)->toBe('Tarifa de Limpieza')
        ->and($feeType->order)->toBe(100);
});

it('auto-generates slug from en_name on creation', function () {
    $admin = makeAdmin();

    $feeType = app(CreateFeeType::class)->handle($admin, validFeeTypeInput([
        'en_name' => 'Service Fee',
        'es_name' => 'Tarifa de Servicio',
    ]));

    expect($feeType->slug)->toBe('service-fee');
});

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
})->with(['en_name', 'es_name'])
    ->throws(ValidationException::class);

it('normalizes non-string fields to empty strings and fails validation', function (string $field) {
    $admin = makeAdmin();

    app(CreateFeeType::class)->handle($admin, validFeeTypeInput([
        $field => ['foo'],
    ]));
})->with(['en_name', 'es_name'])
    ->throws(ValidationException::class);
