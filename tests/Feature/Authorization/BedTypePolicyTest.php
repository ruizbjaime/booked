<?php

use App\Domain\Users\RoleConfig;
use App\Models\BedType;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

test('admin can perform every bed type policy ability', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $admin = User::factory()->create();
    $bedType = BedType::factory()->create();

    $admin->assignRole(RoleConfig::adminRole());

    expect($admin->can('viewAny', BedType::class))->toBeTrue()
        ->and($admin->can('view', $bedType))->toBeTrue()
        ->and($admin->can('create', BedType::class))->toBeTrue()
        ->and($admin->can('update', $bedType))->toBeTrue()
        ->and($admin->can('delete', $bedType))->toBeTrue();
});

test('non-admin roles cannot perform any bed type policy ability', function (string $role) {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create();
    $bedType = BedType::factory()->create();

    $user->assignRole($role);

    expect($user->can('viewAny', BedType::class))->toBeFalse()
        ->and($user->can('view', $bedType))->toBeFalse()
        ->and($user->can('create', BedType::class))->toBeFalse()
        ->and($user->can('update', $bedType))->toBeFalse()
        ->and($user->can('delete', $bedType))->toBeFalse();
})->with(function () {
    return nonAdminRoleNames();
});

test('role with specific bed type permissions can perform only those abilities', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $role = Role::factory()->create(['name' => 'bed-type-viewer']);
    $role->givePermissionTo('bed_type.viewAny', 'bed_type.view');

    $user = User::factory()->create();
    $user->assignRole($role);
    $bedType = BedType::factory()->create();

    expect($user->can('viewAny', BedType::class))->toBeTrue()
        ->and($user->can('view', $bedType))->toBeTrue()
        ->and($user->can('create', BedType::class))->toBeFalse()
        ->and($user->can('update', $bedType))->toBeFalse()
        ->and($user->can('delete', $bedType))->toBeFalse();
});
