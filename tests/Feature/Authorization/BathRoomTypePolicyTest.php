<?php

use App\Domain\Users\RoleConfig;
use App\Models\BathRoomType;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

use function Pest\Laravel\seed;

test('admin can perform every bathroom type policy ability', function () {
    seed(RolesAndPermissionsSeeder::class);

    $admin = User::factory()->create();
    $bathRoomType = BathRoomType::factory()->create();

    $admin->assignRole(RoleConfig::adminRole());

    expect($admin->can('viewAny', BathRoomType::class))->toBeTrue()
        ->and($admin->can('view', $bathRoomType))->toBeTrue()
        ->and($admin->can('create', BathRoomType::class))->toBeTrue()
        ->and($admin->can('update', $bathRoomType))->toBeTrue()
        ->and($admin->can('delete', $bathRoomType))->toBeTrue();
});

test('non-admin roles cannot perform any bathroom type policy ability', function (string $role) {
    seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create();
    $bathRoomType = BathRoomType::factory()->create();

    $user->assignRole($role);

    expect($user->can('viewAny', BathRoomType::class))->toBeFalse()
        ->and($user->can('view', $bathRoomType))->toBeFalse()
        ->and($user->can('create', BathRoomType::class))->toBeFalse()
        ->and($user->can('update', $bathRoomType))->toBeFalse()
        ->and($user->can('delete', $bathRoomType))->toBeFalse();
})->with(function () {
    return nonAdminRoleNames();
});

test('role with specific bathroom type permissions can perform only those abilities', function () {
    seed(RolesAndPermissionsSeeder::class);

    $role = Role::factory()->create(['name' => 'bath-room-type-viewer']);
    $role->givePermissionTo('bath_room_type.viewAny', 'bath_room_type.view');

    $user = User::factory()->create();
    $user->assignRole($role);
    $bathRoomType = BathRoomType::factory()->create();

    expect($user->can('viewAny', BathRoomType::class))->toBeTrue()
        ->and($user->can('view', $bathRoomType))->toBeTrue()
        ->and($user->can('create', BathRoomType::class))->toBeFalse()
        ->and($user->can('update', $bathRoomType))->toBeFalse()
        ->and($user->can('delete', $bathRoomType))->toBeFalse();
});
