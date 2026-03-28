<?php

use App\Actions\Roles\ToggleRoleActiveStatus;
use App\Domain\Users\RoleConfig;
use App\Models\Role;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('activates a role', function () {
    $admin = makeAdmin();
    $role = Role::factory()->create(['is_active' => false]);

    app(ToggleRoleActiveStatus::class)->handle($admin, $role, true);

    expect($role->fresh()->is_active)->toBeTrue();
});

it('deactivates a custom role without users', function () {
    $admin = makeAdmin();
    $role = Role::factory()->create(['is_active' => true]);

    app(ToggleRoleActiveStatus::class)->handle($admin, $role, false);

    expect($role->fresh()->is_active)->toBeFalse();
});

it('aborts with 403 when deactivating a system role', function () {
    $admin = makeAdmin();
    $role = Role::query()->where('name', RoleConfig::adminRole())->first();

    try {
        app(ToggleRoleActiveStatus::class)->handle($admin, $role, false);
        $this->fail('Expected HttpException was not thrown');
    } catch (HttpException $exception) {
        expect($exception->getStatusCode())->toBe(403);
    }
});

it('aborts with 409 when deactivating a role with assigned users', function () {
    $admin = makeAdmin();
    $role = Role::factory()->create(['is_active' => true]);
    $user = makeGuest();
    $user->syncRoles([$role->name]);

    try {
        app(ToggleRoleActiveStatus::class)->handle($admin, $role, false);
        $this->fail('Expected HttpException was not thrown');
    } catch (HttpException $exception) {
        expect($exception->getStatusCode())->toBe(409);
    }
});

it('allows activating a system role without error', function () {
    $admin = makeAdmin();
    $role = Role::query()->where('name', RoleConfig::adminRole())->first();
    $role->update(['is_active' => false]);

    app(ToggleRoleActiveStatus::class)->handle($admin, $role, true);

    expect($role->fresh()->is_active)->toBeTrue();
});

it('throws authorization exception when non-admin toggles a role', function () {
    $guest = makeGuest();
    $role = Role::factory()->create();

    app(ToggleRoleActiveStatus::class)->handle($guest, $role, false);
})->throws(AuthorizationException::class);
