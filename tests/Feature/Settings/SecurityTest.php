<?php

use App\Livewire\Settings\Security;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Features;
use Livewire\Livewire;
use Mockery\MockInterface;

use function Pest\Laravel\mock;

beforeEach(function () {
    $this->skipUnlessFortifyFeature(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);
});

test('security settings page can be rendered', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('security.edit'))
        ->assertOk()
        ->assertSee(__('Two-factor authentication'))
        ->assertSee(__('Enable 2FA'));
});

test('security settings page requires password confirmation when enabled', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('security.edit'))
        ->assertRedirect(route('password.confirm'));
});

test('security settings page renders without two factor when feature is disabled', function () {
    config(['fortify.features' => []]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('security.edit'))
        ->assertOk()
        ->assertSee(__('Update password'))
        ->assertDontSee(__('Two-factor authentication'));
});

test('security settings resumes pending two factor setup for the authenticated user', function () {
    $user = User::factory()->create();

    $user->forceFill([
        'two_factor_secret' => encrypt('test-secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
        'two_factor_confirmed_at' => null,
    ])->save();

    $this->actingAs($user);

    Livewire::test(Security::class)
        ->assertSet('twoFactorEnabled', false)
        ->assertSet('hasPendingTwoFactorSetup', true)
        ->assertSet('showModal', true);
});

test('password can be updated', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $this->actingAs($user);

    Livewire::test(Security::class)
        ->set('current_password', 'password')
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('updatePassword')
        ->assertHasNoErrors();

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
});

test('session is regenerated after password update', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $this->actingAs($user);

    $oldSessionId = session()->getId();

    Livewire::test(Security::class)
        ->set('current_password', 'password')
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('updatePassword')
        ->assertHasNoErrors();

    expect(session()->getId())->not->toBe($oldSessionId);
});

test('correct password must be provided to update password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $this->actingAs($user);

    Livewire::test(Security::class)
        ->set('current_password', 'wrong-password')
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('updatePassword')
        ->assertHasErrors(['current_password']);
});

test('password update resets entered passwords when validation fails', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $this->actingAs($user);

    Livewire::test(Security::class)
        ->set('current_password', 'wrong-password')
        ->set('password', 'short')
        ->set('password_confirmation', 'different')
        ->call('updatePassword')
        ->assertHasErrors(['current_password', 'password'])
        ->assertSet('current_password', '')
        ->assertSet('password', '')
        ->assertSet('password_confirmation', '');
});

test('security component exposes the expected modal configuration states', function () {
    $user = User::factory()->create();
    $user->forceFill([
        'two_factor_secret' => encrypt('resume-secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code-1'])),
        'two_factor_confirmed_at' => null,
    ])->save();

    $this->actingAs($user);

    Livewire::test(Security::class)
        ->assertSet('modalConfig.title', __('auth.two_factor.resume_title'))
        ->set('showVerificationStep', true)
        ->assertSet('modalConfig.title', __('auth.two_factor.resume_title'))
        ->call('resetVerification')
        ->assertSet('showVerificationStep', false)
        ->call('closeModal')
        ->assertSet('showModal', false);

    $freshUser = User::factory()->create();

    $this->actingAs($freshUser);

    Livewire::test(Security::class)
        ->set('showVerificationStep', true)
        ->assertSet('modalConfig.title', __('Verify authentication code'))
        ->set('showVerificationStep', false)
        ->assertSet('modalConfig.title', __('Enable two-factor authentication'));

    $enabledUser = User::factory()->withTwoFactor()->create();

    $this->actingAs($enabledUser);

    Livewire::test(Security::class)
        ->assertSet('modalConfig.title', __('Two-factor authentication enabled'));
});

test('security component can enable two factor without confirmation', function () {
    config(['fortify.features' => [Features::updatePasswords(), Features::twoFactorAuthentication(['confirm' => false, 'confirmPassword' => false])]]);

    $user = User::factory()->create();
    $this->actingAs($user);

    mock(EnableTwoFactorAuthentication::class, function (MockInterface $mock) use ($user): void {
        $mock->shouldReceive('__invoke')
            ->once()
            ->withArgs(fn (User $passedUser): bool => $passedUser->is($user))
            ->andReturnUsing(function (User $passedUser): void {
                $passedUser->forceFill([
                    'two_factor_secret' => encrypt('new-secret'),
                    'two_factor_recovery_codes' => encrypt(json_encode(['code-1'])),
                    'two_factor_confirmed_at' => now(),
                ])->save();
            });
    });

    Livewire::test(Security::class)
        ->call('enable')
        ->assertSet('twoFactorEnabled', true)
        ->assertSet('showModal', true)
        ->assertSet('hasPendingTwoFactorSetup', false);
});

test('security component can advance to and reset the verification step', function () {
    $user = User::factory()->create();
    $user->forceFill([
        'two_factor_secret' => encrypt('setup-secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code-1'])),
        'two_factor_confirmed_at' => null,
    ])->save();

    $this->actingAs($user);

    Livewire::test(Security::class)
        ->call('showVerificationIfNecessary')
        ->assertSet('showVerificationStep', true)
        ->set('code', '123456')
        ->call('resetVerification')
        ->assertSet('code', '')
        ->assertSet('showVerificationStep', false);
});

test('security component closes the modal immediately when confirmation is not required', function () {
    config(['fortify.features' => [Features::updatePasswords(), Features::twoFactorAuthentication(['confirm' => false, 'confirmPassword' => false])]]);

    $user = User::factory()->withTwoFactor()->create();
    $this->actingAs($user);

    Livewire::test(Security::class)
        ->set('showModal', true)
        ->call('showVerificationIfNecessary')
        ->assertSet('showModal', false)
        ->assertSet('showVerificationStep', false);
});

test('security component confirms two factor setup', function () {
    $user = User::factory()->create();
    $user->forceFill([
        'two_factor_secret' => encrypt('setup-secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code-1'])),
        'two_factor_confirmed_at' => null,
    ])->save();

    $this->actingAs($user);

    mock(ConfirmTwoFactorAuthentication::class, function (MockInterface $mock) use ($user): void {
        $mock->shouldReceive('__invoke')
            ->once()
            ->withArgs(fn (User $passedUser, string $code): bool => $passedUser->is($user) && $code === '123456')
            ->andReturnUsing(function (User $passedUser): void {
                $passedUser->forceFill([
                    'two_factor_confirmed_at' => now(),
                ])->save();
            });
    });

    Livewire::test(Security::class)
        ->set('showModal', true)
        ->set('showVerificationStep', true)
        ->set('code', '123456')
        ->call('confirmTwoFactor')
        ->assertSet('twoFactorEnabled', true)
        ->assertSet('hasPendingTwoFactorSetup', false)
        ->assertSet('showModal', false)
        ->assertSet('code', '');
});

test('security component disables two factor', function () {
    $user = User::factory()->withTwoFactor()->create();
    $this->actingAs($user);

    mock(DisableTwoFactorAuthentication::class, function (MockInterface $mock) use ($user): void {
        $mock->shouldReceive('__invoke')
            ->once()
            ->withArgs(fn (User $passedUser): bool => $passedUser->is($user))
            ->andReturnUsing(function (User $passedUser): void {
                $passedUser->forceFill([
                    'two_factor_secret' => null,
                    'two_factor_recovery_codes' => null,
                    'two_factor_confirmed_at' => null,
                ])->save();
            });
    });

    Livewire::test(Security::class)
        ->call('disable')
        ->assertSet('twoFactorEnabled', false)
        ->assertSet('hasPendingTwoFactorSetup', false);
});

test('security component reports setup data errors when the secret cannot be decrypted', function () {
    $user = User::factory()->create();
    $user->forceFill([
        'two_factor_secret' => 'invalid-secret-payload',
        'two_factor_recovery_codes' => encrypt(json_encode(['code-1'])),
        'two_factor_confirmed_at' => null,
    ])->save();

    $this->actingAs($user);

    Livewire::test(Security::class)
        ->assertHasErrors('setupData')
        ->assertSet('qrCodeSvg', '')
        ->assertSet('manualSetupKey', '');
});

test('security component has the correct title', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()]);

    $component = Livewire::test(Security::class);

    expect($component->instance()->title())->toBe(__('Security settings'));
});

test('two factor setup handles missing secret gracefully', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    mock(EnableTwoFactorAuthentication::class, function (MockInterface $mock): void {
        $mock->shouldReceive('__invoke')->once();
    });

    Livewire::test(Security::class)
        ->call('enable')
        ->assertHasErrors('setupData');
});
