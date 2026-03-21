<?php

use App\Domain\Users\RoleConfig;
use App\Models\FeeType;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

test('admin can perform every fee type policy ability', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $admin = User::factory()->create();
    $feeType = FeeType::factory()->create();

    $admin->assignRole(RoleConfig::adminRole());

    expect($admin->can('viewAny', FeeType::class))->toBeTrue()
        ->and($admin->can('view', $feeType))->toBeTrue()
        ->and($admin->can('create', FeeType::class))->toBeTrue()
        ->and($admin->can('update', $feeType))->toBeTrue()
        ->and($admin->can('delete', $feeType))->toBeTrue();
});

test('non-admin roles cannot perform any fee type policy ability', function (string $role) {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create();
    $feeType = FeeType::factory()->create();

    $user->assignRole($role);

    expect($user->can('viewAny', FeeType::class))->toBeFalse()
        ->and($user->can('view', $feeType))->toBeFalse()
        ->and($user->can('create', FeeType::class))->toBeFalse()
        ->and($user->can('update', $feeType))->toBeFalse()
        ->and($user->can('delete', $feeType))->toBeFalse();
})->with(function () {
    return nonAdminRoleNames();
});

test('role with specific fee type permissions can perform only those abilities', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $role = Role::factory()->create(['name' => 'fee-type-viewer']);
    $role->givePermissionTo('fee_type.viewAny', 'fee_type.view');

    $user = User::factory()->create();
    $user->assignRole($role);
    $feeType = FeeType::factory()->create();

    expect($user->can('viewAny', FeeType::class))->toBeTrue()
        ->and($user->can('view', $feeType))->toBeTrue()
        ->and($user->can('create', FeeType::class))->toBeFalse()
        ->and($user->can('update', $feeType))->toBeFalse()
        ->and($user->can('delete', $feeType))->toBeFalse();
});
