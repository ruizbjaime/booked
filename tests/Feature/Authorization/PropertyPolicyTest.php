<?php

use App\Models\Property;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('host can perform every property policy ability', function () {
    $host = makeHost();
    $property = Property::factory()->forUser($host)->create();

    expect($host->can('viewAny', Property::class))->toBeTrue()
        ->and($host->can('view', $property))->toBeTrue()
        ->and($host->can('create', Property::class))->toBeTrue()
        ->and($host->can('update', $property))->toBeTrue()
        ->and($host->can('delete', $property))->toBeTrue();
});

test('host cannot perform instance abilities on another host property', function () {
    $hostA = makeHost();
    $hostB = makeHost();
    $property = Property::factory()->forUser($hostB)->create();

    expect($hostA->can('viewAny', Property::class))->toBeTrue()
        ->and($hostA->can('create', Property::class))->toBeTrue()
        ->and($hostA->can('view', $property))->toBeFalse()
        ->and($hostA->can('update', $property))->toBeFalse()
        ->and($hostA->can('delete', $property))->toBeFalse();
});

test('host can check instance abilities on an unpersisted property', function () {
    $host = makeHost();

    expect($host->can('view', new Property))->toBeTrue()
        ->and($host->can('update', new Property))->toBeTrue()
        ->and($host->can('delete', new Property))->toBeTrue();
});

test('admin cannot perform any property policy ability', function () {
    $admin = makeAdmin();
    $property = Property::factory()->create();

    expect($admin->can('viewAny', Property::class))->toBeFalse()
        ->and($admin->can('view', $property))->toBeFalse()
        ->and($admin->can('create', Property::class))->toBeFalse()
        ->and($admin->can('update', $property))->toBeFalse()
        ->and($admin->can('delete', $property))->toBeFalse();
});

test('guest cannot perform any property policy ability', function () {
    $guest = makeGuest();
    $property = Property::factory()->create();

    expect($guest->can('viewAny', Property::class))->toBeFalse()
        ->and($guest->can('view', $property))->toBeFalse()
        ->and($guest->can('create', Property::class))->toBeFalse()
        ->and($guest->can('update', $property))->toBeFalse()
        ->and($guest->can('delete', $property))->toBeFalse();
});

test('non host with property permissions is still denied', function () {
    $role = Role::factory()->create(['name' => 'property-manager']);
    $role->givePermissionTo('property.viewAny', 'property.view');

    $user = User::factory()->create();
    $user->assignRole($role);
    $property = Property::factory()->create();

    expect($user->can('viewAny', Property::class))->toBeFalse()
        ->and($user->can('view', $property))->toBeFalse();
});
