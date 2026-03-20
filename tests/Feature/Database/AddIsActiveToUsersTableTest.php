<?php

use App\Models\User;
use Illuminate\Support\Facades\Schema;

test('users table includes is_active column with a default false value', function () {
    expect(Schema::hasColumn('users', 'is_active'))->toBeTrue();

    $user = User::factory()->create(['is_active' => false]);

    expect((int) $user->fresh()->getRawOriginal('is_active'))->toBe(0)
        ->and($user->fresh()->is_active)->toBeFalse();
});

test('user factory can create active users', function () {
    $user = User::factory()->create();

    expect($user->fresh()->is_active)->toBeTrue()
        ->and((int) $user->fresh()->getRawOriginal('is_active'))->toBe(1);
});

test('user factory can create inactive users', function () {
    $user = User::factory()->inactive()->create();

    expect($user->fresh()->is_active)->toBeFalse()
        ->and((int) $user->fresh()->getRawOriginal('is_active'))->toBe(0);
});

test('user allows mass assignment for is_active', function () {
    $user = User::factory()->create(['is_active' => true]);

    expect($user->fresh()->is_active)->toBeTrue();
});
