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

test('it adds new non-excluded permissions to admin without resetting existing', function () {
    $adminRole = Role::query()->where('name', RoleConfig::adminRole())->firstOrFail();

    // Remove a non-excluded permission via "UI"
    $adminRole->revokePermissionTo('country.viewAny');

    Cache::forget('permissions:discovered_hash');

    $this->artisan('permissions:sync', ['--force' => true])->assertSuccessful();

    $adminRole->refresh()->load('permissions');
    $permissionNames = $adminRole->permissions->pluck('name')->all();

    // The revoked permission should NOT be re-added (sync is additive)
    expect($permissionNames)->not->toContain('country.viewAny');

    // Excluded permissions should not be present
    foreach (PermissionRegistry::adminExcludedPermissions() as $excluded) {
        expect($permissionNames)->not->toContain($excluded);
    }
});

test('it removes orphaned permissions from the database', function () {
    Permission::findOrCreate('fake_model.fakeAbility', 'web');

    $adminRole = Role::query()->where('name', RoleConfig::adminRole())->firstOrFail();
    $adminRole->givePermissionTo('fake_model.fakeAbility');

    Cache::forget('permissions:discovered_hash');

    $this->artisan('permissions:sync', ['--force' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('Removed 1 orphaned permission(s)');

    expect(Permission::query()->where('name', 'fake_model.fakeAbility')->exists())->toBeFalse();
    expect($adminRole->fresh()->permissions->pluck('name')->all())->not->toContain('fake_model.fakeAbility');
});

test('it does not give admin excluded permissions', function () {
    $adminRole = Role::query()->where('name', RoleConfig::adminRole())->firstOrFail();
    $adminRole->syncPermissions([]);

    Permission::query()->where('guard_name', 'web')->delete();
    Cache::forget('permissions:discovered_hash');

    $this->artisan('permissions:sync', ['--force' => true])->assertSuccessful();

    $adminRole->refresh()->load('permissions');
    $permissionNames = $adminRole->permissions->pluck('name')->all();

    foreach (PermissionRegistry::adminExcludedPermissions() as $excluded) {
        expect($permissionNames)->not->toContain($excluded);
    }

    // Non-excluded permissions should be present
    expect($permissionNames)->not->toBeEmpty();
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
