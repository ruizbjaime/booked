<?php

use App\Actions\Users\DeleteUser;
use App\Domain\Users\RoleConfig;
use App\Models\Property;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

test('delete user action allows an admin to delete another user', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $admin = User::factory()->create();
    $target = User::factory()->create();

    $admin->assignRole(RoleConfig::adminRole());

    app(DeleteUser::class)->handle($admin, $target);

    expect(User::query()->find($target->id))->toBeNull();
});

test('delete user action forbids deleting the acting user', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $admin = User::factory()->create();

    $admin->assignRole(RoleConfig::adminRole());

    expect(fn () => app(DeleteUser::class)->handle($admin, $admin))
        ->toThrow(HttpException::class);
});

test('delete user action requires authorization', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $guest = User::factory()->create();
    $target = User::factory()->create();

    $guest->assignRole(RoleConfig::defaultRole());

    expect(fn () => app(DeleteUser::class)->handle($guest, $target))
        ->toThrow(AuthorizationException::class);
});

test('delete user action rejects deletion when user owns properties', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $admin = User::factory()->create();
    $target = User::factory()->create();
    $admin->assignRole(RoleConfig::adminRole());

    Property::factory()->forUser($target)->create();

    expect(fn () => app(DeleteUser::class)->handle($admin, $target))
        ->toThrow(ValidationException::class);

    expect(User::query()->find($target->id))->not->toBeNull();
});
