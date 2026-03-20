<?php

use App\Domain\Users\RoleConfig;
use App\Models\Country;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

test('admin has access even without seeded permissions', function () {
    $adminRole = Role::query()->updateOrCreate(
        ['name' => RoleConfig::adminRole(), 'guard_name' => 'web'],
        ['en_label' => 'Administrator', 'es_label' => 'Administrador', 'color' => 'red', 'sort_order' => 1],
    );

    $admin = User::factory()->create();
    $admin->assignRole($adminRole);

    expect($admin->can('viewAny', Role::class))->toBeTrue()
        ->and($admin->can('update', $adminRole))->toBeTrue();
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
