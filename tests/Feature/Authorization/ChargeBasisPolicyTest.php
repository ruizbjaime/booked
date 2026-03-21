<?php

use App\Domain\Users\RoleConfig;
use App\Models\ChargeBasis;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

test('admin can perform every charge basis policy ability', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $admin = User::factory()->create();
    $chargeBasis = ChargeBasis::factory()->create();

    $admin->assignRole(RoleConfig::adminRole());

    expect($admin->can('viewAny', ChargeBasis::class))->toBeTrue()
        ->and($admin->can('view', $chargeBasis))->toBeTrue()
        ->and($admin->can('create', ChargeBasis::class))->toBeTrue()
        ->and($admin->can('update', $chargeBasis))->toBeTrue()
        ->and($admin->can('delete', $chargeBasis))->toBeTrue();
});

test('non-admin roles cannot perform any charge basis policy ability', function (string $role) {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create();
    $chargeBasis = ChargeBasis::factory()->create();

    $user->assignRole($role);

    expect($user->can('viewAny', ChargeBasis::class))->toBeFalse()
        ->and($user->can('view', $chargeBasis))->toBeFalse()
        ->and($user->can('create', ChargeBasis::class))->toBeFalse()
        ->and($user->can('update', $chargeBasis))->toBeFalse()
        ->and($user->can('delete', $chargeBasis))->toBeFalse();
})->with(function () {
    return nonAdminRoleNames();
});

test('role with specific charge basis permissions can perform only those abilities', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $role = Role::factory()->create(['name' => 'charge-basis-viewer']);
    $role->givePermissionTo('charge_basis.viewAny', 'charge_basis.view');

    $user = User::factory()->create();
    $user->assignRole($role);
    $chargeBasis = ChargeBasis::factory()->create();

    expect($user->can('viewAny', ChargeBasis::class))->toBeTrue()
        ->and($user->can('view', $chargeBasis))->toBeTrue()
        ->and($user->can('create', ChargeBasis::class))->toBeFalse()
        ->and($user->can('update', $chargeBasis))->toBeFalse()
        ->and($user->can('delete', $chargeBasis))->toBeFalse();
});
