<?php

use App\Listeners\RecordLastLogin;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Carbon;

it('updates last_login_at when a user logs in', function () {
    $user = User::factory()->createOne();

    expect($user->last_login_at)->toBeNull();

    Carbon::setTestNow($loginTime = now());

    $listener = new RecordLastLogin;
    $listener->handle(new Login('web', $user, false));

    $user->refresh();

    expect($user->last_login_at)->not->toBeNull()
        ->and($user->last_login_at->timestamp)->toBe($loginTime->timestamp);
});

it('updates last_login_at on subsequent logins', function () {
    $user = User::factory()->createOne([
        'last_login_at' => now()->subDays(5),
    ]);

    Carbon::setTestNow($newLoginTime = now());

    $listener = new RecordLastLogin;
    $listener->handle(new Login('web', $user, false));

    $user->refresh();

    expect($user->last_login_at->timestamp)->toBe($newLoginTime->timestamp);
});
