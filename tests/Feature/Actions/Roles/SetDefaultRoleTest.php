<?php

use App\Actions\Roles\SetDefaultRole;
use App\Domain\Users\RoleConfig;
use App\Models\Role;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('sets a regular active role as the default role', function () {
    $admin = makeAdmin();
    $targetRole = Role::factory()->create([
        'name' => 'concierge',
        'is_active' => true,
        'is_default' => false,
    ]);

    $currentDefault = Role::query()->where('name', RoleConfig::defaultRole())->firstOrFail();

    app(SetDefaultRole::class)->handle($admin, $targetRole);

    expect($targetRole->fresh()->is_default)->toBeTrue()
        ->and($currentDefault->fresh()->is_default)->toBeFalse();

    RoleConfig::clearCache();

    expect(RoleConfig::defaultRole())->toBe('concierge');
});

it('rejects inactive roles as default', function () {
    $admin = makeAdmin();
    $inactiveRole = Role::factory()->inactive()->create(['name' => 'inactive-role']);

    expect(fn () => app(SetDefaultRole::class)->handle($admin, $inactiveRole))
        ->toThrow(HttpException::class, __('roles.errors.only_active_as_default'));
});

it('rejects the admin role as the default role', function () {
    $admin = makeAdmin();
    $adminRole = Role::query()->where('name', RoleConfig::adminRole())->firstOrFail();

    expect(fn () => app(SetDefaultRole::class)->handle($admin, $adminRole))
        ->toThrow(HttpException::class, __('roles.errors.admin_cannot_be_default'));
});

it('requires authorization to set the default role', function () {
    $guest = makeGuest();
    $role = Role::factory()->create(['name' => 'editor']);

    expect(fn () => app(SetDefaultRole::class)->handle($guest, $role))
        ->toThrow(AuthorizationException::class);
});
