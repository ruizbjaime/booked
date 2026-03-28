<?php

use App\Actions\IdentificationDocumentTypes\CreateIdentificationDocumentType;
use App\Models\IdentificationDocumentType;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('creates a document type with valid input', function () {
    $admin = makeAdmin();

    $type = app(CreateIdentificationDocumentType::class)->handle($admin, [
        'code' => 'cc',
        'en_name' => 'Citizenship Card',
        'es_name' => 'Cédula de Ciudadanía',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    expect($type->code)->toBe('CC')
        ->and($type->en_name)->toBe('Citizenship Card')
        ->and($type->is_active)->toBeTrue();
});

it('normalizes code to uppercase', function () {
    $admin = makeAdmin();

    $type = app(CreateIdentificationDocumentType::class)->handle($admin, [
        'code' => 'nit',
        'en_name' => 'Tax ID',
        'es_name' => 'NIT',
        'sort_order' => 2,
        'is_active' => true,
    ]);

    expect($type->code)->toBe('NIT');
});

it('rejects duplicate codes', function () {
    $admin = makeAdmin();
    IdentificationDocumentType::factory()->create(['code' => 'CC']);

    app(CreateIdentificationDocumentType::class)->handle($admin, [
        'code' => 'CC',
        'en_name' => 'Duplicate',
        'es_name' => 'Duplicado',
        'sort_order' => 1,
        'is_active' => true,
    ]);
})->throws(ValidationException::class);

it('rejects missing required fields', function () {
    $admin = makeAdmin();

    app(CreateIdentificationDocumentType::class)->handle($admin, [
        'code' => 'PP',
    ]);
})->throws(ValidationException::class);

it('throws authorization exception when non-admin creates a document type', function () {
    $guest = makeGuest();

    app(CreateIdentificationDocumentType::class)->handle($guest, [
        'code' => 'PP',
        'en_name' => 'Passport',
        'es_name' => 'Pasaporte',
        'sort_order' => 1,
        'is_active' => true,
    ]);
})->throws(AuthorizationException::class);
