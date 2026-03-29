<?php

use App\Actions\Users\UpdateUserProfile;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('admin can update another user profile', function () {
    $admin = makeAdmin();
    $target = makeGuest();

    $updated = app(UpdateUserProfile::class)->handle($admin, $target, [
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
    ]);

    expect($updated->name)->toBe('Updated Name')
        ->and($updated->email)->toBe('updated@example.com');
});

test('user can update own profile', function () {
    $user = makeGuest();

    $updated = app(UpdateUserProfile::class)->handle($user, $user, [
        'name' => 'Self Updated',
        'email' => 'self-updated@example.com',
    ]);

    expect($updated->name)->toBe('Self Updated')
        ->and($updated->email)->toBe('self-updated@example.com');
});

test('updates only name and email', function () {
    $admin = makeAdmin();
    $target = makeGuest(['is_active' => true]);
    $originalActive = $target->is_active;

    $updated = app(UpdateUserProfile::class)->handle($admin, $target, [
        'name' => 'New Name',
        'email' => 'new-email@example.com',
    ]);

    expect($updated->name)->toBe('New Name')
        ->and($updated->email)->toBe('new-email@example.com')
        ->and($updated->is_active)->toBe($originalActive);
});

test('rejects duplicate email', function () {
    $admin = makeAdmin();
    $target = makeGuest();

    User::factory()->create(['email' => 'taken@example.com']);

    expect(fn () => app(UpdateUserProfile::class)->handle($admin, $target, [
        'name' => $target->name,
        'email' => 'taken@example.com',
    ]))->toThrow(ValidationException::class);
});

test('allows keeping own email on update', function () {
    $admin = makeAdmin();
    $target = makeGuest();

    $updated = app(UpdateUserProfile::class)->handle($admin, $target, [
        'name' => 'Changed Name Only',
        'email' => $target->email,
    ]);

    expect($updated->name)->toBe('Changed Name Only')
        ->and($updated->email)->toBe($target->email);
});

test('rejects empty name', function () {
    $admin = makeAdmin();
    $target = makeGuest();

    expect(fn () => app(UpdateUserProfile::class)->handle($admin, $target, [
        'name' => '',
        'email' => $target->email,
    ]))->toThrow(ValidationException::class);
});

test('rejects invalid email format', function () {
    $admin = makeAdmin();
    $target = makeGuest();

    expect(fn () => app(UpdateUserProfile::class)->handle($admin, $target, [
        'name' => $target->name,
        'email' => 'not-an-email',
    ]))->toThrow(ValidationException::class);
});

test('non admin cannot update another user profile', function () {
    $guest = makeGuest();
    $target = makeGuest();

    expect(fn () => app(UpdateUserProfile::class)->handle($guest, $target, [
        'name' => 'Hacked',
        'email' => 'hacked@example.com',
    ]))->toThrow(AuthorizationException::class);
});
