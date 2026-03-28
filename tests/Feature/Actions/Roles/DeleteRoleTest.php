<?php

use App\Actions\Roles\DeleteRole;
use App\Domain\Users\RoleConfig;
use App\Models\Role;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('deletes a custom role without users', function () {
    $admin = makeAdmin();
    $role = Role::factory()->create();

    app(DeleteRole::class)->handle($admin, $role);

    expect(Role::query()->find($role->id))->toBeNull();
});

it('aborts with 403 when trying to delete a system role', function () {
    $admin = makeAdmin();
    $role = Role::query()->where('name', RoleConfig::adminRole())->first();

    try {
        app(DeleteRole::class)->handle($admin, $role);
        $this->fail('Expected HttpException was not thrown');
    } catch (HttpException $exception) {
        expect($exception->getStatusCode())->toBe(403);
    }
});

it('aborts with 409 when trying to delete a role with assigned users', function () {
    $admin = makeAdmin();
    $role = Role::factory()->create();
    $user = makeGuest();
    $user->syncRoles([$role->name]);

    try {
        app(DeleteRole::class)->handle($admin, $role);
        $this->fail('Expected HttpException was not thrown');
    } catch (HttpException $exception) {
        expect($exception->getStatusCode())->toBe(409);
    }
});

it('throws authorization exception when non-admin deletes a role', function () {
    $guest = makeGuest();
    $role = Role::factory()->create();

    app(DeleteRole::class)->handle($guest, $role);
})->throws(AuthorizationException::class);
