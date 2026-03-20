<?php

use App\Actions\Users\ToggleUserActiveStatus;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('admin can deactivate another user', function () {
    $admin = makeAdmin();
    $target = makeGuest(['is_active' => true]);

    app(ToggleUserActiveStatus::class)->handle($admin, $target, false);

    expect($target->fresh()->is_active)->toBeFalse();
});

test('admin can activate another user', function () {
    $admin = makeAdmin();
    $target = makeGuest(['is_active' => false]);

    app(ToggleUserActiveStatus::class)->handle($admin, $target, true);

    expect($target->fresh()->is_active)->toBeTrue();
});

test('self toggle is forbidden', function () {
    $admin = makeAdmin();

    expect(fn () => app(ToggleUserActiveStatus::class)->handle($admin, $admin, false))
        ->toThrow(HttpException::class);
});

test('non admin cannot toggle another user status', function () {
    $guest = makeGuest();
    $target = makeGuest();

    expect(fn () => app(ToggleUserActiveStatus::class)->handle($guest, $target, false))
        ->toThrow(AuthorizationException::class);
});

test('toggling preserves other user attributes', function () {
    $admin = makeAdmin();
    $target = makeGuest(['is_active' => true]);
    $originalName = $target->name;
    $originalEmail = $target->email;

    app(ToggleUserActiveStatus::class)->handle($admin, $target, false);

    $fresh = $target->fresh();

    expect($fresh->is_active)->toBeFalse()
        ->and($fresh->name)->toBe($originalName)
        ->and($fresh->email)->toBe($originalEmail);
});
