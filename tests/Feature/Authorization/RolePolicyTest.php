<?php

use App\Domain\Users\RoleConfig;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

test('admin can perform every role policy ability', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $admin = User::factory()->create();
    $role = Role::factory()->create();

    $admin->assignRole(RoleConfig::adminRole());

    expect($admin->can('viewAny', Role::class))->toBeTrue()
        ->and($admin->can('view', $role))->toBeTrue()
        ->and($admin->can('create', Role::class))->toBeTrue()
        ->and($admin->can('update', $role))->toBeTrue()
        ->and($admin->can('delete', $role))->toBeTrue();
});

test('non-admin roles cannot perform any role policy ability', function (string $roleName) {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create();
    $role = Role::factory()->create();

    $user->assignRole($roleName);

    expect($user->can('viewAny', Role::class))->toBeFalse()
        ->and($user->can('view', $role))->toBeFalse()
        ->and($user->can('create', Role::class))->toBeFalse()
        ->and($user->can('update', $role))->toBeFalse()
        ->and($user->can('delete', $role))->toBeFalse();
})->with(function () {
    return nonAdminRoleNames();
});
