<?php

use App\Domain\Users\RoleConfig;
use App\Models\IdentificationDocumentType;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

test('admin can perform every identification document type policy ability', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $admin = User::factory()->create();
    $docType = IdentificationDocumentType::factory()->create();

    $admin->assignRole(RoleConfig::adminRole());

    expect($admin->can('viewAny', IdentificationDocumentType::class))->toBeTrue()
        ->and($admin->can('view', $docType))->toBeTrue()
        ->and($admin->can('create', IdentificationDocumentType::class))->toBeTrue()
        ->and($admin->can('update', $docType))->toBeTrue()
        ->and($admin->can('delete', $docType))->toBeTrue();
});

test('non-admin roles cannot perform any identification document type policy ability', function (string $role) {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create();
    $docType = IdentificationDocumentType::factory()->create();

    $user->assignRole($role);

    expect($user->can('viewAny', IdentificationDocumentType::class))->toBeFalse()
        ->and($user->can('view', $docType))->toBeFalse()
        ->and($user->can('create', IdentificationDocumentType::class))->toBeFalse()
        ->and($user->can('update', $docType))->toBeFalse()
        ->and($user->can('delete', $docType))->toBeFalse();
})->with(function () {
    return nonAdminRoleNames();
});
