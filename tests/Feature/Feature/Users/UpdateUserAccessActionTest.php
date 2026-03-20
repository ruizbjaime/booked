<?php

use App\Actions\Users\UpdateUserAccess;
use App\Domain\Users\RoleConfig;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('admin can update roles of another user', function () {
    $admin = makeAdmin();
    $target = makeGuest();

    $updated = app(UpdateUserAccess::class)->handle($admin, $target, [
        'is_active' => true,
        'roles' => [RoleConfig::adminRole()],
    ]);

    expect($updated->hasRole(RoleConfig::adminRole()))->toBeTrue()
        ->and($updated->hasRole(RoleConfig::defaultRole()))->toBeFalse();
});

test('admin can activate another user', function () {
    $admin = makeAdmin();
    $target = makeGuest(['is_active' => false]);

    $updated = app(UpdateUserAccess::class)->handle($admin, $target, [
        'is_active' => true,
        'roles' => [RoleConfig::defaultRole()],
    ]);

    expect($updated->is_active)->toBeTrue();
});

test('admin can deactivate another user', function () {
    $admin = makeAdmin();
    $target = makeGuest(['is_active' => true]);

    $updated = app(UpdateUserAccess::class)->handle($admin, $target, [
        'is_active' => false,
        'roles' => [RoleConfig::defaultRole()],
    ]);

    expect($updated->is_active)->toBeFalse();
});

test('prevents a user from deactivating themselves', function () {
    $admin = makeAdmin();

    try {
        app(UpdateUserAccess::class)->handle($admin, $admin, [
            'is_active' => false,
            'roles' => [RoleConfig::adminRole()],
        ]);

        $this->fail('Expected validation exception was not thrown.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey('is_active')
            ->and($exception->errors()['is_active'][0])->toBe(__('users.show.validation.cannot_deactivate_self'));
    }

    expect($admin->fresh()->is_active)->toBeTrue();
});

test('allows user to keep themselves active', function () {
    $admin = makeAdmin();

    $updated = app(UpdateUserAccess::class)->handle($admin, $admin, [
        'is_active' => true,
        'roles' => [RoleConfig::adminRole()],
    ]);

    expect($updated->is_active)->toBeTrue();
});

test('rejects invalid roles', function () {
    $admin = makeAdmin();
    $target = makeGuest();

    expect(fn () => app(UpdateUserAccess::class)->handle($admin, $target, [
        'is_active' => true,
        'roles' => ['nonexistent-role'],
    ]))->toThrow(ValidationException::class);
});

test('requires at least one role', function () {
    $admin = makeAdmin();
    $target = makeGuest();

    expect(fn () => app(UpdateUserAccess::class)->handle($admin, $target, [
        'is_active' => true,
        'roles' => [],
    ]))->toThrow(ValidationException::class);
});

test('admin role normalizes out other roles', function () {
    $admin = makeAdmin();
    $target = makeGuest();

    $updated = app(UpdateUserAccess::class)->handle($admin, $target, [
        'is_active' => true,
        'roles' => [RoleConfig::adminRole(), RoleConfig::defaultRole()],
    ]);

    expect($updated->hasRole(RoleConfig::adminRole()))->toBeTrue()
        ->and($updated->hasRole(RoleConfig::defaultRole()))->toBeFalse();
});

test('non admin cannot update user access', function () {
    $guest = makeGuest();
    $target = makeGuest();

    expect(fn () => app(UpdateUserAccess::class)->handle($guest, $target, [
        'is_active' => true,
        'roles' => [RoleConfig::defaultRole()],
    ]))->toThrow(AuthorizationException::class);
});

test('returns refreshed user with loaded roles', function () {
    $admin = makeAdmin();
    $target = makeGuest();

    $updated = app(UpdateUserAccess::class)->handle($admin, $target, [
        'is_active' => true,
        'roles' => [RoleConfig::defaultRole()],
    ]);

    expect($updated->relationLoaded('roles'))->toBeTrue()
        ->and($updated->roles)->toHaveCount(1);
});
