<?php

use App\Livewire\Settings\TwoFactor\RecoveryCodes;
use App\Models\User;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Livewire\Livewire;
use Mockery\MockInterface;

use function Pest\Laravel\mock;

test('recovery codes component loads codes for users with two factor enabled', function () {
    $user = User::factory()->withTwoFactor()->create();

    Livewire::actingAs($user)
        ->test(RecoveryCodes::class)
        ->assertSet('recoveryCodes', ['recovery-code-1'])
        ->assertSee(__('2FA recovery codes'));
});

test('recovery codes component reports malformed encrypted codes', function () {
    $user = User::factory()->create();

    $user->forceFill([
        'two_factor_secret' => encrypt('secret'),
        'two_factor_recovery_codes' => encrypt(['bad' => 'format']),
        'two_factor_confirmed_at' => now(),
    ])->save();

    Livewire::actingAs($user)
        ->test(RecoveryCodes::class)
        ->assertHasErrors('recoveryCodes')
        ->assertSet('recoveryCodes', []);
});

test('recovery codes can be regenerated', function () {
    $user = User::factory()->withTwoFactor()->create();

    mock(GenerateNewRecoveryCodes::class, function (MockInterface $mock) use ($user): void {
        $mock->shouldReceive('__invoke')
            ->once()
            ->withArgs(fn (User $passedUser): bool => $passedUser->is($user))
            ->andReturnUsing(function (User $passedUser): void {
                $passedUser->forceFill([
                    'two_factor_recovery_codes' => encrypt(json_encode(['new-code-1', 'new-code-2'])),
                ])->save();
            });
    });

    Livewire::actingAs($user)
        ->test(RecoveryCodes::class)
        ->call('regenerateRecoveryCodes')
        ->assertSet('recoveryCodes', ['new-code-1', 'new-code-2']);
});

test('recovery codes component requires an authenticated user', function () {
    Livewire::test(RecoveryCodes::class)
        ->assertForbidden();
});
