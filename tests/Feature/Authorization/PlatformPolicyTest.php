<?php

use App\Domain\Users\RoleConfig;
use App\Models\Platform;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

test('admin can perform every platform policy ability', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $admin = User::factory()->create();
    $platform = Platform::factory()->create();

    $admin->assignRole(RoleConfig::adminRole());

    expect($admin->can('viewAny', Platform::class))->toBeTrue()
        ->and($admin->can('view', $platform))->toBeTrue()
        ->and($admin->can('create', Platform::class))->toBeTrue()
        ->and($admin->can('update', $platform))->toBeTrue()
        ->and($admin->can('delete', $platform))->toBeTrue();
});

test('non-admin roles cannot perform any platform policy ability', function (string $role) {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create();
    $platform = Platform::factory()->create();

    $user->assignRole($role);

    expect($user->can('viewAny', Platform::class))->toBeFalse()
        ->and($user->can('view', $platform))->toBeFalse()
        ->and($user->can('create', Platform::class))->toBeFalse()
        ->and($user->can('update', $platform))->toBeFalse()
        ->and($user->can('delete', $platform))->toBeFalse();
})->with(function () {
    return nonAdminRoleNames();
});

test('role with specific platform permissions can perform only those abilities', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $role = Role::factory()->create(['name' => 'viewer']);
    $role->givePermissionTo('platform.viewAny', 'platform.view');

    $user = User::factory()->create();
    $user->assignRole($role);
    $platform = Platform::factory()->create();

    expect($user->can('viewAny', Platform::class))->toBeTrue()
        ->and($user->can('view', $platform))->toBeTrue()
        ->and($user->can('create', Platform::class))->toBeFalse()
        ->and($user->can('update', $platform))->toBeFalse()
        ->and($user->can('delete', $platform))->toBeFalse();
});
