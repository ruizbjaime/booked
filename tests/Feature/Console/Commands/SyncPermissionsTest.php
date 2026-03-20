<?php

use App\Domain\Auth\PermissionRegistry;
use App\Domain\Users\RoleConfig;
use App\Models\Role;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('it creates missing permissions in the database', function () {
    $allPermissions = PermissionRegistry::allPermissionNames();
    $toDelete = $allPermissions[0];

    Permission::query()->where('name', $toDelete)->where('guard_name', 'web')->delete();
    Cache::forget('permissions:discovered_hash');

    $this->artisan('permissions:sync', ['--force' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('Created 1 new permission(s)');

    expect(Permission::query()->where('name', $toDelete)->where('guard_name', 'web')->exists())->toBeTrue();
});

test('it assigns all permissions to admin role', function () {
    Cache::forget('permissions:discovered_hash');

    $this->artisan('permissions:sync', ['--force' => true])
        ->assertSuccessful();

    $adminRole = Role::query()->where('name', RoleConfig::adminRole())->firstOrFail();
    $discovered = PermissionRegistry::allPermissionNames();

    expect($adminRole->permissions->pluck('name')->sort()->values()->all())
        ->toEqual(collect($discovered)->sort()->values()->all());
});

test('it is idempotent', function () {
    $countBefore = Permission::query()->where('guard_name', 'web')->count();

    $this->artisan('permissions:sync', ['--force' => true])->assertSuccessful();
    $this->artisan('permissions:sync', ['--force' => true])->assertSuccessful();

    expect(Permission::query()->where('guard_name', 'web')->count())->toBe($countBefore);
});

test('it updates the hash cache', function () {
    Cache::forget('permissions:discovered_hash');

    $this->artisan('permissions:sync', ['--force' => true])->assertSuccessful();

    expect(Cache::get('permissions:discovered_hash'))
        ->toBe(PermissionRegistry::computeHash());
});

test('it skips sync when hash matches and force is not used', function () {
    Cache::put('permissions:discovered_hash', PermissionRegistry::computeHash());

    $this->artisan('permissions:sync')
        ->assertSuccessful()
        ->expectsOutputToContain('up to date');
});
