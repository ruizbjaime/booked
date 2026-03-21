<?php

use App\Domain\Auth\PermissionRegistry;
use App\Domain\Users\RoleConfig;
use App\Models\Role;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Permission;

it('creates the expected base roles with display columns', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $admin = Role::query()->where('name', RoleConfig::adminRole())->first();
    $host = Role::query()->where('name', 'host')->first();
    $guest = Role::query()->where('name', RoleConfig::defaultRole())->first();

    expect($admin)->not->toBeNull()
        ->and($admin->en_label)->toBe('Administrator')
        ->and($admin->es_label)->toBe('Administrador')
        ->and($admin->color)->toBe('red')
        ->and($admin->sort_order)->toBe(1)
        ->and($host)->not->toBeNull()
        ->and($host->en_label)->toBe('Host')
        ->and($host->es_label)->toBe('Anfitrión')
        ->and($host->color)->toBe('blue')
        ->and($host->sort_order)->toBe(2)
        ->and($guest)->not->toBeNull()
        ->and($guest->en_label)->toBe('Guest')
        ->and($guest->es_label)->toBe('Huésped')
        ->and($guest->color)->toBe('zinc')
        ->and($guest->sort_order)->toBe(3);
});

it('does not remove extra roles', function () {
    Role::findOrCreate('custom_role', 'web');

    $this->seed(RolesAndPermissionsSeeder::class);

    expect(Role::query()->where('name', 'custom_role')->exists())->toBeTrue();
});

it('is idempotent', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $firstRoleCount = Role::query()->where('guard_name', 'web')->count();
    $firstPermCount = Permission::query()->where('guard_name', 'web')->count();

    $this->seed(RolesAndPermissionsSeeder::class);
    $secondRoleCount = Role::query()->where('guard_name', 'web')->count();
    $secondPermCount = Permission::query()->where('guard_name', 'web')->count();

    expect($firstRoleCount)->toBe($secondRoleCount)
        ->and($firstPermCount)->toBe($secondPermCount);
});

it('creates all expected permissions', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $expectedNames = PermissionRegistry::allPermissionNames();
    $dbNames = Permission::query()->where('guard_name', 'web')->pluck('name')->sort()->values()->all();

    expect($dbNames)->toBe(collect($expectedNames)->sort()->values()->all());
});

it('assigns all permissions to admin role', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $admin = Role::query()->where('name', RoleConfig::adminRole())->first();
    $adminPermissions = $admin->permissions->pluck('name')->sort()->values()->all();

    expect($adminPermissions)->toBe(collect(PermissionRegistry::allPermissionNames())->sort()->values()->all());
});

it('assigns no permissions to guest role', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $guest = Role::query()->where('name', RoleConfig::defaultRole())->first();

    expect($guest->permissions)->toBeEmpty();
});
