<?php

use App\Domain\Users\RoleConfig;
use App\Models\Country;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

test('admin has access with seeded permissions', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $admin = makeAdmin();
    $adminRole = Role::query()->where('name', RoleConfig::adminRole())->firstOrFail();

    expect($admin->can('viewAny', Role::class))->toBeTrue()
        ->and($admin->can('update', $adminRole))->toBeTrue();
});

test('admin is denied when permission is removed', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $admin = makeAdmin();
    $adminRole = Role::query()->where('name', RoleConfig::adminRole())->firstOrFail();
    $adminRole->revokePermissionTo('country.create');

    expect($admin->can('create', Country::class))->toBeFalse()
        ->and($admin->can('viewAny', Country::class))->toBeTrue();
});

test('non-admin is denied without seeded permissions', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create();
    $user->assignRole(RoleConfig::defaultRole());

    expect($user->can('viewAny', Role::class))->toBeFalse()
        ->and($user->can('create', Role::class))->toBeFalse();
});

test('non-admin with specific permission gets fine-grained access', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create();
    $defaultRole = Role::query()->where('name', RoleConfig::defaultRole())->firstOrFail();
    $user->assignRole($defaultRole);

    $defaultRole->givePermissionTo('country.viewAny');

    expect($user->can('viewAny', Country::class))->toBeTrue()
        ->and($user->can('create', Country::class))->toBeFalse();
});
