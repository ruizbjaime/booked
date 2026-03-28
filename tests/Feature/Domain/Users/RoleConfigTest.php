<?php

use App\Domain\Users\RoleConfig;
use App\Models\Role;
use Database\Seeders\RolesAndPermissionsSeeder;

it('returns the configured admin role', function () {
    expect(RoleConfig::adminRole())->toBe(config('roles.admin_role'));
});

it('returns the configured default role', function () {
    expect(RoleConfig::defaultRole())->toBe(config('roles.default_role'));
});

it('returns the configured default color', function () {
    expect(RoleConfig::defaultColor())->toBe(config('roles.default_color'));
});

it('returns all active role names from database ordered by sort_order', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $expected = Role::query()
        ->where('guard_name', 'web')
        ->where('is_active', true)
        ->orderBy('sort_order')
        ->pluck('name')
        ->all();

    expect(RoleConfig::names())->toBe($expected);
});

it('returns the color for a seeded role from database', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    expect(RoleConfig::color('admin'))->toBe('red')
        ->and(RoleConfig::color('guest'))->toBe('zinc');
});

it('returns default color for unknown roles', function () {
    expect(RoleConfig::color('nonexistent_role'))->toBe(config('roles.default_color'));
});

it('returns the localized label for a seeded role', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    app()->setLocale('en');

    expect(RoleConfig::label('admin'))->toBe('Administrator')
        ->and(RoleConfig::label('guest'))->toBe('Guest');
});

it('returns the localized label for a seeded role in Spanish', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    app()->setLocale('es');

    expect(RoleConfig::label('admin'))->toBe('Administrador')
        ->and(RoleConfig::label('guest'))->toBe('Huésped');
});

it('returns a headline fallback for an unknown role', function () {
    RoleConfig::clearCache();

    expect(RoleConfig::label('unknown_role'))->toBe('Unknown Role');
});

it('returns a translation for a role not in the database but present in translations', function () {
    RoleConfig::clearCache();
    app()->setLocale('en');

    expect(RoleConfig::label('owner'))->toBe('Owner');
});

it('identifies only admin as the admin role', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    expect(RoleConfig::isAdminRole('admin'))->toBeTrue()
        ->and(RoleConfig::isAdminRole('guest'))->toBeFalse();
});

it('excludes inactive roles from names()', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    Role::factory()->create(['name' => 'inactive-role', 'is_active' => false]);

    expect(RoleConfig::names())->not->toContain('inactive-role');
});

it('refreshes the default role after clearing the default role cache', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    expect(RoleConfig::defaultRole())->toBe('guest');

    Role::query()->where('name', 'guest')->update(['is_default' => false]);
    Role::query()->where('name', 'host')->update(['is_default' => true]);

    RoleConfig::clearDefaultRoleCache();

    expect(RoleConfig::defaultRole())->toBe('host');
});

it('identifies system roles correctly', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    expect(RoleConfig::isSystemRole('admin'))->toBeTrue()
        ->and(RoleConfig::isSystemRole('guest'))->toBeTrue()
        ->and(RoleConfig::isSystemRole('host'))->toBeFalse();
});
