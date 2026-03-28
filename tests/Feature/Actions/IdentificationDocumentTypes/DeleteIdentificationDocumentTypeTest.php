<?php

use App\Actions\IdentificationDocumentTypes\DeleteIdentificationDocumentType;
use App\Models\IdentificationDocumentType;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('deletes a document type without associated users', function () {
    $admin = makeAdmin();
    $type = IdentificationDocumentType::factory()->create();

    $result = app(DeleteIdentificationDocumentType::class)->handle($admin, $type);

    expect($result)->toBeTrue()
        ->and(IdentificationDocumentType::query()->find($type->id))->toBeNull();
});

it('deactivates a document type with associated users instead of deleting', function () {
    $admin = makeAdmin();
    $type = IdentificationDocumentType::factory()->create(['is_active' => true]);
    User::factory()->create(['document_type_id' => $type->id]);

    $result = app(DeleteIdentificationDocumentType::class)->handle($admin, $type);

    expect($result)->toBeFalse()
        ->and($type->fresh()->is_active)->toBeFalse()
        ->and(IdentificationDocumentType::query()->find($type->id))->not->toBeNull();
});

it('throws authorization exception when non-admin deletes a document type', function () {
    $guest = makeGuest();
    $type = IdentificationDocumentType::factory()->create();

    app(DeleteIdentificationDocumentType::class)->handle($guest, $type);
})->throws(AuthorizationException::class);
