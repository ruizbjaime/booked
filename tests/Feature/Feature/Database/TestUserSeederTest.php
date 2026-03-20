<?php

use App\Domain\Users\RoleConfig;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\TestUserSeeder;

test('test user seeder can run on a clean database', function () {
    $this->seed(TestUserSeeder::class);

    $expectedRoles = RoleConfig::names();

    expect(User::query()->count())->toBe(50)
        ->and(User::query()->has('roles')->count())->toBe(50);

    foreach ($expectedRoles as $role) {
        expect(Role::query()->where('name', $role)->exists())->toBeTrue("Role '{$role}' should exist");
    }
});

test('test user seeder only assigns non-admin roles', function () {
    $this->seed(TestUserSeeder::class);

    $adminRole = RoleConfig::adminRole();

    expect(User::role($adminRole)->count())->toBe(0);
});
