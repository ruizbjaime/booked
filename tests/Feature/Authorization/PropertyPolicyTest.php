<?php

use App\Models\Property;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

test('host can perform every property policy ability', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $host = User::factory()->create();
    $property = Property::factory()->create();

    $host->assignRole('host');

    expect($host->can('viewAny', Property::class))->toBeTrue()
        ->and($host->can('view', $property))->toBeTrue()
        ->and($host->can('create', Property::class))->toBeTrue()
        ->and($host->can('update', $property))->toBeTrue()
        ->and($host->can('delete', $property))->toBeTrue()
        ->and($host->can('restore', $property))->toBeTrue()
        ->and($host->can('forceDelete', $property))->toBeTrue();
});

test('admin cannot perform any property policy ability', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $admin = User::factory()->create();
    $property = Property::factory()->create();

    $admin->assignRole('admin');

    expect($admin->can('viewAny', Property::class))->toBeFalse()
        ->and($admin->can('view', $property))->toBeFalse()
        ->and($admin->can('create', Property::class))->toBeFalse()
        ->and($admin->can('update', $property))->toBeFalse()
        ->and($admin->can('delete', $property))->toBeFalse()
        ->and($admin->can('restore', $property))->toBeFalse()
        ->and($admin->can('forceDelete', $property))->toBeFalse();
});

test('guest cannot perform any property policy ability', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $guest = User::factory()->create();
    $property = Property::factory()->create();

    $guest->assignRole('guest');

    expect($guest->can('viewAny', Property::class))->toBeFalse()
        ->and($guest->can('view', $property))->toBeFalse()
        ->and($guest->can('create', Property::class))->toBeFalse()
        ->and($guest->can('update', $property))->toBeFalse()
        ->and($guest->can('delete', $property))->toBeFalse()
        ->and($guest->can('restore', $property))->toBeFalse()
        ->and($guest->can('forceDelete', $property))->toBeFalse();
});

test('non host with property permissions is still denied', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $role = Role::factory()->create(['name' => 'property-manager']);
    $role->givePermissionTo('property.viewAny', 'property.view');

    $user = User::factory()->create();
    $user->assignRole($role);
    $property = Property::factory()->create();

    expect($user->can('viewAny', Property::class))->toBeFalse()
        ->and($user->can('view', $property))->toBeFalse();
});
