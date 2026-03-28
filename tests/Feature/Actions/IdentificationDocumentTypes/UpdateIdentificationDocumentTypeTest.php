<?php

use App\Actions\IdentificationDocumentTypes\UpdateIdentificationDocumentType;
use App\Models\IdentificationDocumentType;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('throws authorization exception when non-admin user updates an identification document type', function () {
    $guest = makeGuest();
    $type = IdentificationDocumentType::factory()->create();

    app(UpdateIdentificationDocumentType::class)->handle($guest, $type, 'en_name', 'Passport');
})->throws(AuthorizationException::class);

it('updates labels, sort order, and active state', function () {
    $admin = makeAdmin();
    $type = IdentificationDocumentType::factory()->inactive()->create([
        'en_name' => 'Old Name',
        'es_name' => 'Nombre Viejo',
        'sort_order' => 1,
    ]);

    app(UpdateIdentificationDocumentType::class)->handle($admin, $type, 'en_name', 'Passport');
    app(UpdateIdentificationDocumentType::class)->handle($admin, $type, 'es_name', 'Pasaporte');
    app(UpdateIdentificationDocumentType::class)->handle($admin, $type, 'sort_order', 9);
    app(UpdateIdentificationDocumentType::class)->handle($admin, $type, 'is_active', true);

    expect($type->fresh()->en_name)->toBe('Passport')
        ->and($type->fresh()->es_name)->toBe('Pasaporte')
        ->and($type->fresh()->sort_order)->toBe(9)
        ->and($type->fresh()->is_active)->toBeTrue();
});

it('normalizes the code to uppercase', function () {
    $admin = makeAdmin();
    $type = IdentificationDocumentType::factory()->create(['code' => 'CC']);

    app(UpdateIdentificationDocumentType::class)->handle($admin, $type, 'code', 'ti');

    expect($type->fresh()->code)->toBe('TI');
});

it('rejects duplicate codes from another identification document type', function () {
    $admin = makeAdmin();
    IdentificationDocumentType::factory()->create(['code' => 'PP']);
    $type = IdentificationDocumentType::factory()->create(['code' => 'CC']);

    app(UpdateIdentificationDocumentType::class)->handle($admin, $type, 'code', 'pp');
})->throws(ValidationException::class);

it('aborts with 422 for an unknown identification document field', function () {
    $admin = makeAdmin();
    $type = IdentificationDocumentType::factory()->create();

    try {
        app(UpdateIdentificationDocumentType::class)->handle($admin, $type, 'unknown_field', 'value');
        $this->fail('Expected HttpException was not thrown');
    } catch (HttpException $exception) {
        expect($exception->getStatusCode())->toBe(422);
    }
});
