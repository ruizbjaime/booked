<?php

use App\Actions\Users\UpdateUserPassword;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('admin can update another user password', function () {
    $admin = makeAdmin();
    $target = makeGuest();

    app(UpdateUserPassword::class)->handle($admin, $target, [
        'password' => 'new-secure-password',
        'password_confirmation' => 'new-secure-password',
    ]);

    expect(Hash::check('new-secure-password', $target->fresh()->password))->toBeTrue();
});

test('user can update own password', function () {
    $user = makeGuest();

    app(UpdateUserPassword::class)->handle($user, $user, [
        'password' => 'my-new-password',
        'password_confirmation' => 'my-new-password',
    ]);

    expect(Hash::check('my-new-password', $user->fresh()->password))->toBeTrue();
});

test('rejects mismatched password confirmation', function () {
    $admin = makeAdmin();
    $target = makeGuest();

    expect(fn () => app(UpdateUserPassword::class)->handle($admin, $target, [
        'password' => 'new-password',
        'password_confirmation' => 'different-password',
    ]))->toThrow(ValidationException::class);
});

test('rejects empty password', function () {
    $admin = makeAdmin();
    $target = makeGuest();

    expect(fn () => app(UpdateUserPassword::class)->handle($admin, $target, [
        'password' => '',
        'password_confirmation' => '',
    ]))->toThrow(ValidationException::class);
});

test('non admin cannot update another user password', function () {
    $guest = makeGuest();
    $target = makeGuest();

    expect(fn () => app(UpdateUserPassword::class)->handle($guest, $target, [
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]))->toThrow(AuthorizationException::class);
});

test('session is regenerated when user changes own password', function () {
    $user = makeGuest();

    $this->actingAs($user);

    $oldSessionId = session()->getId();

    app(UpdateUserPassword::class)->handle($user, $user, [
        'password' => 'my-new-password',
        'password_confirmation' => 'my-new-password',
    ]);

    expect(session()->getId())->not->toBe($oldSessionId);
});

test('session is not regenerated when admin changes another user password', function () {
    $admin = makeAdmin();
    $target = makeGuest();

    $this->actingAs($admin);

    $oldSessionId = session()->getId();

    app(UpdateUserPassword::class)->handle($admin, $target, [
        'password' => 'new-secure-password',
        'password_confirmation' => 'new-secure-password',
    ]);

    expect(session()->getId())->toBe($oldSessionId);
});

test('password is hashed before storage', function () {
    $admin = makeAdmin();
    $target = makeGuest();

    app(UpdateUserPassword::class)->handle($admin, $target, [
        'password' => 'plaintext-password',
        'password_confirmation' => 'plaintext-password',
    ]);

    $stored = $target->fresh()->getRawOriginal('password');

    expect($stored)->not->toBe('plaintext-password')
        ->and(Hash::check('plaintext-password', $stored))->toBeTrue();
});
