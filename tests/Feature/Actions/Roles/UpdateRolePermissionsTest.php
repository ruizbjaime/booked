<?php

use App\Actions\Roles\UpdateRolePermissions;
use App\Domain\Auth\PermissionRegistry;
use App\Domain\Users\RoleConfig;
use App\Models\Role;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('syncs permissions for a regular role', function () {
    $admin = makeAdmin();
    $role = Role::factory()->create(['name' => 'editor']);

    app(UpdateRolePermissions::class)->handle($admin, $role, [
        'country.viewAny',
        'country.view',
        'country.create',
    ]);

    $permissionNames = $role->fresh()->permissions->pluck('name')->sort()->values()->all();

    expect($permissionNames)->toBe(['country.create', 'country.view', 'country.viewAny']);
});

it('replaces existing permissions on sync', function () {
    $admin = makeAdmin();
    $role = Role::factory()->create(['name' => 'editor']);
    $role->givePermissionTo('country.viewAny', 'country.view');

    app(UpdateRolePermissions::class)->handle($admin, $role, ['country.create']);

    $permissionNames = $role->fresh()->permissions->pluck('name')->all();

    expect($permissionNames)->toBe(['country.create']);
});

it('enforces admin protection by keeping user and role permissions', function () {
    $admin = makeAdmin();
    $adminRole = Role::query()->where('name', RoleConfig::adminRole())->first();

    app(UpdateRolePermissions::class)->handle($admin, $adminRole, [
        'country.viewAny',
    ]);

    $adminRole->refresh()->load('permissions');
    $permissionNames = $adminRole->permissions->pluck('name')->all();

    foreach (PermissionRegistry::adminProtectedPermissions() as $protectedPerm) {
        expect($permissionNames)->toContain($protectedPerm);
    }

    expect($permissionNames)->toContain('country.viewAny');
});

it('validates permission names exist in registry', function () {
    $admin = makeAdmin();
    $role = Role::factory()->create(['name' => 'test-role']);

    app(UpdateRolePermissions::class)->handle($admin, $role, [
        'invalid.permission',
    ]);
})->throws(ValidationException::class);

it('requires authorization', function () {
    $guest = makeGuest();
    $role = Role::factory()->create(['name' => 'test-role']);

    app(UpdateRolePermissions::class)->handle($guest, $role, [
        'country.viewAny',
    ]);
})->throws(AuthorizationException::class);

it('allows clearing all permissions for a non-admin role', function () {
    $admin = makeAdmin();
    $role = Role::factory()->create(['name' => 'empty-role']);
    $role->givePermissionTo('country.viewAny', 'country.view');

    app(UpdateRolePermissions::class)->handle($admin, $role, []);

    expect($role->fresh()->permissions)->toBeEmpty();
});

it('allows admin to edit non-protected permissions on admin role', function () {
    $admin = makeAdmin();
    $adminRole = Role::query()->where('name', RoleConfig::adminRole())->first();

    $protectedOnly = PermissionRegistry::adminProtectedPermissions();

    app(UpdateRolePermissions::class)->handle($admin, $adminRole, $protectedOnly);

    $adminRole->refresh()->load('permissions');
    $permissionNames = $adminRole->permissions->pluck('name')->sort()->values()->all();

    expect($permissionNames)->toBe(collect($protectedOnly)->sort()->values()->all());
    expect($permissionNames)->not->toContain('country.viewAny');
});
