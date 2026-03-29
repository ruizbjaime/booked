<?php

use App\Domain\Users\RoleConfig;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;

test('roles seeder creates the expected base roles', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $roleNames = Role::query()
        ->where('guard_name', 'web')
        ->pluck('name')
        ->sort()
        ->values()
        ->all();

    expect($roleNames)->toContain(RoleConfig::adminRole())
        ->and($roleNames)->toContain(RoleConfig::defaultRole());
});

test('admin user seeder creates the admin user with admin role', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(AdminUserSeeder::class);

    $admin = User::query()->where('email', 'admin@localhost')->first();

    expect($admin)->not->toBeNull()
        ->and($admin?->name)->toBe('Administrator')
        ->and($admin?->hasRole(RoleConfig::adminRole()))->toBeTrue();
});

test('admin can perform every user policy ability', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $admin = User::factory()->create();
    $target = User::factory()->create();

    $admin->assignRole(RoleConfig::adminRole());

    expect($admin->can('viewAny', User::class))->toBeTrue()
        ->and($admin->can('view', $target))->toBeTrue()
        ->and($admin->can('create', User::class))->toBeTrue()
        ->and($admin->can('update', $target))->toBeTrue()
        ->and($admin->can('delete', $target))->toBeTrue();
});

test('non-admin roles cannot perform any user policy ability', function (string $role) {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create();
    $target = User::factory()->create();

    $user->assignRole($role);

    expect($user->can('viewAny', User::class))->toBeFalse()
        ->and($user->can('view', $target))->toBeFalse()
        ->and($user->can('create', User::class))->toBeFalse()
        ->and($user->can('update', $target))->toBeFalse()
        ->and($user->can('delete', $target))->toBeFalse();
})->with(function () {
    return nonAdminRoleNames();
});

test('user can update their own profile without permission', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create();
    $user->assignRole(RoleConfig::defaultRole());

    expect($user->can('update', $user))->toBeTrue();
});

test('user without permission cannot update another user', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create();
    $target = User::factory()->create();
    $user->assignRole(RoleConfig::defaultRole());

    expect($user->can('update', $target))->toBeFalse();
});
