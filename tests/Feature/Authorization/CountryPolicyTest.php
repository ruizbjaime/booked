<?php

use App\Domain\Users\RoleConfig;
use App\Models\Country;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

test('admin can perform every country policy ability', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $admin = User::factory()->create();
    $country = Country::factory()->create();

    $admin->assignRole(RoleConfig::adminRole());

    expect($admin->can('viewAny', Country::class))->toBeTrue()
        ->and($admin->can('view', $country))->toBeTrue()
        ->and($admin->can('create', Country::class))->toBeTrue()
        ->and($admin->can('update', $country))->toBeTrue()
        ->and($admin->can('delete', $country))->toBeTrue();
});

test('non-admin roles cannot perform any country policy ability', function (string $role) {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create();
    $country = Country::factory()->create();

    $user->assignRole($role);

    expect($user->can('viewAny', Country::class))->toBeFalse()
        ->and($user->can('view', $country))->toBeFalse()
        ->and($user->can('create', Country::class))->toBeFalse()
        ->and($user->can('update', $country))->toBeFalse()
        ->and($user->can('delete', $country))->toBeFalse();
})->with(function () {
    return nonAdminRoleNames();
});

test('role with specific country permissions can perform only those abilities', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $role = Role::factory()->create(['name' => 'viewer']);
    $role->givePermissionTo('country.viewAny', 'country.view');

    $user = User::factory()->create();
    $user->assignRole($role);
    $country = Country::factory()->create();

    expect($user->can('viewAny', Country::class))->toBeTrue()
        ->and($user->can('view', $country))->toBeTrue()
        ->and($user->can('create', Country::class))->toBeFalse()
        ->and($user->can('update', $country))->toBeFalse()
        ->and($user->can('delete', $country))->toBeFalse();
});
