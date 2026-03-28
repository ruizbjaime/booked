<?php

use App\Actions\IdentificationDocumentTypes\ToggleIdentificationDocumentTypeActiveStatus;
use App\Models\IdentificationDocumentType;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('activates a document type', function () {
    $admin = makeAdmin();
    $type = IdentificationDocumentType::factory()->create(['is_active' => false]);

    app(ToggleIdentificationDocumentTypeActiveStatus::class)->handle($admin, $type, true);

    expect($type->fresh()->is_active)->toBeTrue();
});

it('deactivates a document type', function () {
    $admin = makeAdmin();
    $type = IdentificationDocumentType::factory()->create(['is_active' => true]);

    app(ToggleIdentificationDocumentTypeActiveStatus::class)->handle($admin, $type, false);

    expect($type->fresh()->is_active)->toBeFalse();
});

it('throws authorization exception when non-admin toggles a document type', function () {
    $guest = makeGuest();
    $type = IdentificationDocumentType::factory()->create();

    app(ToggleIdentificationDocumentTypeActiveStatus::class)->handle($guest, $type, true);
})->throws(AuthorizationException::class);
