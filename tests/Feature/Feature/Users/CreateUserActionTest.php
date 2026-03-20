<?php

use App\Actions\Users\CreateUser;
use App\Domain\Users\RoleConfig;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

test('create user action allows an admin to create an active user with a role', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole(RoleConfig::adminRole());

    $created = app(CreateUser::class)->handle($admin, [
        'name' => 'Created User',
        'email' => 'created-user@example.com',
        'is_active' => true,
        'password' => 'password',
        'password_confirmation' => 'password',
        'roles' => [RoleConfig::defaultRole()],
    ]);

    expect($created->name)->toBe('Created User')
        ->and($created->email)->toBe('created-user@example.com')
        ->and($created->is_active)->toBeTrue()
        ->and($created->hasRole(RoleConfig::defaultRole()))->toBeTrue();
});

test('create user action assigns only non admin roles', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole(RoleConfig::adminRole());

    $created = app(CreateUser::class)->handle($admin, [
        'name' => 'Created User',
        'email' => 'non-admin-role-user@example.com',
        'is_active' => true,
        'password' => 'password',
        'password_confirmation' => 'password',
        'roles' => [RoleConfig::defaultRole()],
    ]);

    expect($created->hasRole(RoleConfig::defaultRole()))->toBeTrue()
        ->and($created->hasRole(RoleConfig::adminRole()))->toBeFalse();
});

test('create user action keeps admin as the only role when combined with others', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole(RoleConfig::adminRole());

    $created = app(CreateUser::class)->handle($admin, [
        'name' => 'Created User',
        'email' => 'exclusive-admin-user@example.com',
        'is_active' => true,
        'password' => 'password',
        'password_confirmation' => 'password',
        'roles' => [RoleConfig::adminRole(), RoleConfig::defaultRole()],
    ]);

    expect($created->hasRole(RoleConfig::adminRole()))->toBeTrue()
        ->and($created->hasRole(RoleConfig::defaultRole()))->toBeFalse();
});

test('create user action requires authorization', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $guest = User::factory()->create();
    $guest->assignRole(RoleConfig::defaultRole());

    expect(fn () => app(CreateUser::class)->handle($guest, [
        'name' => 'Created User',
        'email' => 'created-user@example.com',
        'is_active' => true,
        'password' => 'password',
        'password_confirmation' => 'password',
        'roles' => [RoleConfig::defaultRole()],
    ]))->toThrow(AuthorizationException::class);
});

test('create user action rejects duplicate emails', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole(RoleConfig::adminRole());

    User::factory()->create([
        'email' => 'duplicate@example.com',
    ]);

    expect(fn () => app(CreateUser::class)->handle($admin, [
        'name' => 'Created User',
        'email' => 'duplicate@example.com',
        'is_active' => true,
        'password' => 'password',
        'password_confirmation' => 'password',
        'roles' => [RoleConfig::defaultRole()],
    ]))->toThrow(ValidationException::class);
});

test('create user action requires password confirmation', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole(RoleConfig::adminRole());

    expect(fn () => app(CreateUser::class)->handle($admin, [
        'name' => 'Created User',
        'email' => 'created-user@example.com',
        'is_active' => true,
        'password' => 'password',
        'password_confirmation' => 'different-password',
        'roles' => [RoleConfig::defaultRole()],
    ]))->toThrow(ValidationException::class);
});

test('create user action requires valid roles', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole(RoleConfig::adminRole());

    expect(fn () => app(CreateUser::class)->handle($admin, [
        'name' => 'Created User',
        'email' => 'created-user@example.com',
        'is_active' => true,
        'password' => 'password',
        'password_confirmation' => 'password',
        'roles' => ['invalid-role'],
    ]))->toThrow(ValidationException::class);
});

test('create user action requires at least one role', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole(RoleConfig::adminRole());

    expect(fn () => app(CreateUser::class)->handle($admin, [
        'name' => 'Created User',
        'email' => 'created-user@example.com',
        'is_active' => true,
        'password' => 'password',
        'password_confirmation' => 'password',
        'roles' => [],
    ]))->toThrow(ValidationException::class);
});

test('create user action allows an admin to create an inactive user with a role', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole(RoleConfig::adminRole());

    $created = app(CreateUser::class)->handle($admin, [
        'name' => 'Inactive User',
        'email' => 'inactive-user@example.com',
        'is_active' => false,
        'password' => 'password',
        'password_confirmation' => 'password',
        'roles' => [RoleConfig::defaultRole()],
    ]);

    expect($created->is_active)->toBeFalse()
        ->and($created->hasRole(RoleConfig::defaultRole()))->toBeTrue();
});
