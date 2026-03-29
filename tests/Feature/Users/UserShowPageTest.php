<?php

use App\Domain\Users\RoleConfig;
use App\Models\Country;
use App\Models\IdentificationDocumentType;
use App\Models\Property;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PragmaRX\Google2FA\Google2FA;

/**
 * Captures the query log during $callback execution and returns
 * a filtered collection of queries that load roles for the given user.
 */
function captureRoleQueries(User $user, Closure $callback): Collection
{
    DB::flushQueryLog();
    DB::enableQueryLog();

    $callback();

    $queries = collect(DB::getQueryLog());
    DB::disableQueryLog();

    return $queries->filter(function (array $query) use ($user): bool {
        $sql = (string) ($query['query'] ?? '');

        return str_contains($sql, 'from "roles" inner join "model_has_roles"')
            && str_contains($sql, '"model_has_roles"."model_id" in ('.$user->id.')');
    });
}

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->adminRole = RoleConfig::adminRole();
    $this->defaultRole = RoleConfig::defaultRole();

    $this->admin = User::factory()->create();
    $this->admin->assignRole($this->adminRole);

    $this->actingAs($this->admin);
});

it('renders the show page successfully with all sections visible', function () {
    $target = User::factory()->create([
        'name' => 'Taylor Otwell',
        'email' => 'taylor@example.com',
        'email_verified_at' => now(),
        'is_active' => true,
    ]);
    $target->assignRole($this->defaultRole);

    Livewire::test('pages::users.show', ['user' => (string) $target->id])
        ->assertOk()
        ->assertSee(__('users.show.placeholder_title'))
        ->assertSee(__('users.show.sections.account'))
        ->assertSee(__('users.show.sections.access'))
        ->assertSee(__('users.show.quick_actions.title'))
        ->assertSee(__('users.show.stats.title'))
        ->assertSee('Taylor Otwell')
        ->assertSee('taylor@example.com')
        ->assertSee(__('users.show.status.active'))
        ->assertSee(__('users.show.stats.security_score'))
        ->assertSee(RoleConfig::label($this->defaultRole))
        ->assertSee((string) $target->id)
        ->assertSee(__('actions.edit'));
});

it('loads the target user roles only once during the initial render', function () {
    $target = User::factory()->create();
    $target->assignRole($this->defaultRole);

    $roleQueries = captureRoleQueries($target, function () use ($target): void {
        Livewire::test('pages::users.show', ['user' => (string) $target->id])
            ->assertOk();
    });

    expect($roleQueries)->toHaveCount(1);
});

it('autosaves account changes from the show page', function () {
    $target = User::factory()->create([
        'name' => 'Original Name',
        'email' => 'original@example.com',
    ]);
    $target->assignRole($this->defaultRole);

    Livewire::test('pages::users.show', ['user' => (string) $target->id])
        ->call('startEditingSection', 'account')
        ->assertSet('editingSection', 'account')
        ->set('name', 'Updated Name')
        ->assertDispatched('toast-show', function (string $event, array $params) {
            return $event === 'toast-show'
                && ($params['slots']['text'] ?? null) === __('users.show.saved.account');
        })
        ->set('email', 'updated@example.com')
        ->assertDispatched('toast-show', function (string $event, array $params) {
            return $event === 'toast-show'
                && ($params['slots']['text'] ?? null) === __('users.show.saved.account');
        });

    expect($target->fresh()->name)->toBe('Updated Name')
        ->and($target->fresh()->email)->toBe('updated@example.com');
});

it('keeps invalid account input visible when autosave validation fails', function () {
    User::factory()->create([
        'email' => 'already-taken@example.com',
    ]);

    $target = User::factory()->create([
        'name' => 'Original Name',
        'email' => 'original@example.com',
    ]);
    $target->assignRole($this->defaultRole);

    Livewire::test('pages::users.show', ['user' => (string) $target->id])
        ->call('startEditingSection', 'account')
        ->set('email', 'already-taken@example.com')
        ->assertSet('email', 'original@example.com')
        ->assertHasErrors(['email']);

    expect($target->fresh()->email)->toBe('original@example.com');
});

it('saves role changes explicitly from the show page', function () {
    $target = User::factory()->create([
        'is_active' => true,
    ]);
    $target->assignRole($this->adminRole);

    Livewire::test('pages::users.show', ['user' => (string) $target->id])
        ->call('startEditingSection', 'access')
        ->assertSet('editingSection', 'access')
        ->set('roles', [$this->defaultRole])
        ->assertSet('roles', [$this->defaultRole])
        ->call('saveRoles')
        ->assertDispatched('toast-show', function (string $event, array $params) {
            return $event === 'toast-show'
                && ($params['slots']['text'] ?? null) === __('users.show.saved.roles');
        });

    expect($target->fresh()->roles->pluck('name')->all())->toBe([$this->defaultRole]);
});

it('keeps invalid role selections visible when saving roles fails', function () {
    $target = User::factory()->create([
        'is_active' => true,
    ]);
    $target->assignRole($this->defaultRole);

    Livewire::test('pages::users.show', ['user' => (string) $target->id])
        ->call('startEditingSection', 'access')
        ->set('roles', [])
        ->call('saveRoles')
        ->assertSet('roles', [])
        ->assertHasErrors(['roles']);

    expect($target->fresh()->roles->pluck('name')->all())->toBe([$this->defaultRole]);
});

it('autosaves active changes from the show page', function () {
    $target = User::factory()->create([
        'is_active' => true,
    ]);
    $target->assignRole($this->defaultRole);

    Livewire::test('pages::users.show', ['user' => (string) $target->id])
        ->call('startEditingSection', 'access')
        ->set('roles', [$this->adminRole])
        ->set('active', false)
        ->assertDispatched('toast-show', function (string $event, array $params) {
            return $event === 'toast-show'
                && ($params['slots']['text'] ?? null) === __('users.show.saved.active');
        });

    expect($target->fresh()->is_active)->toBeFalse()
        ->and($target->fresh()->roles->pluck('name')->all())->toBe([$this->defaultRole]);
});

it('disables active changes when the actor views their own profile', function () {
    $this->admin->update(['is_active' => true]);

    Livewire::test('pages::users.show', ['user' => (string) $this->admin->id])
        ->call('startEditingSection', 'access')
        ->assertSeeHtml('id="show-user-active"')
        ->assertSeeHtml('disabled');
});

it('prevents a user from deactivating themselves from the show page', function () {
    $this->admin->update(['is_active' => true]);

    Livewire::test('pages::users.show', ['user' => (string) $this->admin->id])
        ->call('startEditingSection', 'access')
        ->set('active', false)
        ->assertSet('active', true)
        ->assertHasErrors(['active' => [__('users.show.validation.cannot_deactivate_self')]]);

    expect($this->admin->fresh()->is_active)->toBeTrue();
});

it('prevents an admin from changing their own roles from the show page', function () {
    Livewire::test('pages::users.show', ['user' => (string) $this->admin->id])
        ->call('startEditingSection', 'access')
        ->set('roles', [$this->defaultRole])
        ->call('saveRoles')
        ->assertSet('roles', [$this->adminRole])
        ->assertHasErrors(['roles' => [__('users.show.validation.cannot_change_own_roles')]]);

    expect($this->admin->fresh()->hasRole($this->adminRole))->toBeTrue();
});

it('does not render save roles button when actor views their own profile', function () {
    Livewire::test('pages::users.show', ['user' => (string) $this->admin->id])
        ->call('startEditingSection', 'access')
        ->assertDontSeeHtml('wire:click="saveRoles"');
});

it('updates the password from the access section', function () {
    $target = User::factory()->create();
    $target->assignRole($this->defaultRole);

    $originalHash = $target->password;

    Livewire::test('pages::users.show', ['user' => (string) $target->id])
        ->call('startEditingSection', 'access')
        ->set('password', 'new-password-123')
        ->set('password_confirmation', 'new-password-123')
        ->call('updatePassword')
        ->assertSet('password', '')
        ->assertSet('password_confirmation', '')
        ->assertDispatched('toast-show', function (string $event, array $params) {
            return $event === 'toast-show'
                && ($params['slots']['text'] ?? null) === __('users.show.saved.password');
        });

    expect($target->fresh()->password)->not->toBe($originalHash);
});

it('opens password confirmation before changing two factor state', function () {
    $target = User::factory()->create();
    $target->assignRole($this->defaultRole);

    Livewire::test('pages::users.show', ['user' => (string) $target->id])
        ->call('startEditingSection', 'access')
        ->set('twoFactorValue', true)
        ->assertSet('twoFactorValue', false)
        ->assertDispatched('open-confirm-modal', function (string $event, array $params) use ($target) {
            return $event === 'open-confirm-modal'
                && ($params['variant'] ?? null) === 'password'
                && ($params['message'] ?? null) === __('users.show.two_factor.confirm_message', [
                    'action' => __('users.show.two_factor.enable_action'),
                    'user' => __('users.user_label', ['name' => $target->name, 'id' => $target->id]),
                ]);
        });
});

it('shows the setup modal when a user enables two factor for their own account', function () {
    Livewire::test('pages::users.show', ['user' => (string) $this->admin->id])
        ->call('startEditingSection', 'access')
        ->set('twoFactorValue', true)
        ->call('handleModalConfirmed')
        ->assertSet('showTwoFactorModal', true);

    expect($this->admin->fresh()->two_factor_secret)->not->toBeNull()
        ->and($this->admin->fresh()->two_factor_confirmed_at)->toBeNull();
});

it('does not reveal setup data when an admin enables two factor for another user', function () {
    $target = User::factory()->create();
    $target->assignRole($this->defaultRole);

    Livewire::test('pages::users.show', ['user' => (string) $target->id])
        ->call('startEditingSection', 'access')
        ->set('twoFactorValue', true)
        ->call('handleModalConfirmed')
        ->assertSet('showTwoFactorModal', false)
        ->assertSet('twoFactorValue', true)
        ->assertDispatched('toast-show', function (string $event, array $params) {
            return $event === 'toast-show'
                && ($params['slots']['text'] ?? null) === __('users.show.saved.two_factor_pending_owner_setup');
        });

    expect($target->fresh()->two_factor_secret)->not->toBeNull()
        ->and($target->fresh()->two_factor_confirmed_at)->toBeNull();
});

it('opens delete confirmation from quick actions', function () {
    $target = User::factory()->create([
        'name' => 'Delete Sidebar',
        'email' => 'delete-sidebar@example.com',
    ]);
    $target->assignRole($this->defaultRole);

    Livewire::test('pages::users.show', ['user' => (string) $target->id])
        ->call('confirmUserDeletion')
        ->assertSet('userIdPendingDeletion', $target->id)
        ->assertDispatched('open-confirm-modal', function (string $event, array $params) use ($target) {
            return $event === 'open-confirm-modal'
                && ($params['title'] ?? null) === __('users.show.quick_actions.delete.title')
                && ($params['message'] ?? null) === __('users.show.quick_actions.delete.message', [
                    'user' => __('users.user_label', ['name' => $target->name, 'id' => $target->id]),
                ])
                && ($params['confirmLabel'] ?? null) === __('users.show.quick_actions.delete.confirm_label');
        });
});

it('clears pending sensitive actions when the confirm modal is cancelled', function () {
    $target = User::factory()->create();
    $target->assignRole($this->defaultRole);

    Livewire::test('pages::users.show', ['user' => (string) $target->id])
        ->call('startEditingSection', 'access')
        ->set('twoFactorValue', true)
        ->assertSet('twoFactorPendingValue', true)
        ->dispatch('modal-confirm-cancelled')
        ->assertSet('twoFactorPendingValue', null)
        ->call('confirmUserDeletion')
        ->assertSet('userIdPendingDeletion', $target->id)
        ->assertSet('pendingAction', 'delete')
        ->dispatch('modal-confirm-cancelled')
        ->assertSet('userIdPendingDeletion', null)
        ->assertSet('pendingAction', null);
});

it('keeps pending role selections until roles are explicitly saved', function () {
    $target = User::factory()->create();
    $target->assignRole($this->adminRole);

    Livewire::test('pages::users.show', ['user' => (string) $target->id])
        ->call('startEditingSection', 'access')
        ->set('roles', [])
        ->assertSet('roles', [])
        ->set('roles', [$this->defaultRole])
        ->assertSet('roles', [$this->defaultRole])
        ->call('saveRoles');

    expect($target->fresh()->roles->pluck('name')->sort()->values()->all())->toBe([$this->defaultRole]);
});

it('resets form and editing state when cancelling a section', function () {
    $target = User::factory()->create([
        'is_active' => true,
    ]);
    $target->assignRole($this->defaultRole);

    Livewire::test('pages::users.show', ['user' => (string) $target->id])
        ->call('startEditingSection', 'access')
        ->assertSet('editingSection', 'access')
        ->set('roles', [$this->adminRole])
        ->call('cancelEditingSection')
        ->assertSet('editingSection', null)
        ->assertSet('roles', [$this->defaultRole])
        ->assertHasNoErrors();
});

it('deletes a user via the modal confirmed handler and redirects to index', function () {
    $target = User::factory()->create([
        'name' => 'Delete Target',
        'email' => 'delete-target@example.com',
    ]);
    $target->assignRole($this->defaultRole);

    Livewire::test('pages::users.show', ['user' => (string) $target->id])
        ->call('confirmUserDeletion')
        ->assertSet('userIdPendingDeletion', $target->id)
        ->dispatch('modal-confirmed')
        ->assertSet('userIdPendingDeletion', null)
        ->assertRedirect(route('users.index'))
        ->assertDispatched('toast-show', function (string $event, array $params) {
            return $event === 'toast-show'
                && str_contains((string) ($params['slots']['text'] ?? ''), 'Delete Target');
        });

    expect(User::query()->find($target->id))->toBeNull();
});

it('opens deactivation modal when deleting a user with properties from quick actions', function () {
    $target = User::factory()->create([
        'name' => 'Property Owner',
        'email' => 'property-owner@example.com',
    ]);
    $target->assignRole($this->defaultRole);

    Property::factory()->forUser($target)->create();

    Livewire::test('pages::users.show', ['user' => (string) $target->id])
        ->call('confirmUserDeletion')
        ->assertSet('userIdPendingDeletion', $target->id)
        ->assertSet('pendingAction', 'deactivate')
        ->assertDispatched('open-confirm-modal', function (string $event, array $params) use ($target) {
            return $event === 'open-confirm-modal'
                && ($params['title'] ?? null) === __('users.show.quick_actions.deactivate.title')
                && ($params['message'] ?? null) === __('users.show.quick_actions.deactivate.message', [
                    'user' => __('users.user_label', ['name' => $target->name, 'id' => $target->id]),
                ])
                && ($params['confirmLabel'] ?? null) === __('users.show.quick_actions.deactivate.confirm_label');
        });
});

it('deactivates a user with properties instead of deleting from quick actions', function () {
    $target = User::factory()->active()->create([
        'name' => 'Deactivate Via Delete',
        'email' => 'deactivate-via-delete@example.com',
    ]);
    $target->assignRole($this->defaultRole);

    Property::factory()->forUser($target)->create();

    Livewire::test('pages::users.show', ['user' => (string) $target->id])
        ->call('confirmUserDeletion')
        ->assertSet('pendingAction', 'deactivate')
        ->dispatch('modal-confirmed')
        ->assertSet('userIdPendingDeletion', null)
        ->assertSet('pendingAction', null)
        ->assertNoRedirect()
        ->assertDispatched('toast-show', function (string $event, array $params) use ($target) {
            return $event === 'toast-show'
                && ($params['slots']['text'] ?? null) === __('users.show.quick_actions.deactivate.deactivated', [
                    'user' => __('users.user_label', ['name' => $target->name, 'id' => $target->id]),
                ]);
        });

    expect(User::query()->find($target->id))->not->toBeNull()
        ->and($target->fresh()->is_active)->toBeFalse();
});

it('normalizes admin role when selected alongside other roles', function () {
    $target = User::factory()->create();
    $target->assignRole($this->defaultRole);

    Livewire::test('pages::users.show', ['user' => (string) $target->id])
        ->call('startEditingSection', 'access')
        ->set('roles', [$this->defaultRole, $this->adminRole])
        ->assertSet('roles', [$this->adminRole]);
});

it('shows validation errors when password confirmation does not match', function () {
    $target = User::factory()->create();
    $target->assignRole($this->defaultRole);

    Livewire::test('pages::users.show', ['user' => (string) $target->id])
        ->call('startEditingSection', 'access')
        ->set('password', 'new-password-123')
        ->set('password_confirmation', 'wrong-confirmation')
        ->call('updatePassword')
        ->assertHasErrors(['password'])
        ->assertSet('password', '')
        ->assertSet('password_confirmation', '');
});

it('confirms two factor with a valid OTP code', function () {
    $component = Livewire::test('pages::users.show', ['user' => (string) $this->admin->id])
        ->call('startEditingSection', 'access')
        ->set('twoFactorValue', true)
        ->call('handleModalConfirmed')
        ->assertSet('showTwoFactorModal', true);

    $this->admin->refresh();
    $secret = decrypt((string) $this->admin->two_factor_secret);

    $google2fa = new Google2FA;
    $validCode = $google2fa->getCurrentOtp($secret);

    $component
        ->call('advanceTwoFactorSetup')
        ->assertSet('showTwoFactorVerificationStep', true)
        ->set('twoFactorCode', $validCode)
        ->call('confirmTwoFactor')
        ->assertSet('showTwoFactorModal', false)
        ->assertDispatched('toast-show', function (string $event, array $params) {
            return $event === 'toast-show'
                && ($params['slots']['text'] ?? null) === __('users.show.saved.access');
        });

    expect($this->admin->fresh()->two_factor_confirmed_at)->not->toBeNull();
});

it('reverts pending two factor enrollment when closing the setup modal', function () {
    Livewire::test('pages::users.show', ['user' => (string) $this->admin->id])
        ->call('startEditingSection', 'access')
        ->set('twoFactorValue', true)
        ->call('handleModalConfirmed')
        ->assertSet('showTwoFactorModal', true)
        ->call('closeTwoFactorModal')
        ->assertSet('showTwoFactorModal', false)
        ->assertSet('twoFactorValue', false);

    expect($this->admin->fresh()->two_factor_secret)->toBeNull();
});

it('resets the verification step when going back from OTP input', function () {
    Livewire::test('pages::users.show', ['user' => (string) $this->admin->id])
        ->call('startEditingSection', 'access')
        ->set('twoFactorValue', true)
        ->call('handleModalConfirmed')
        ->call('advanceTwoFactorSetup')
        ->assertSet('showTwoFactorVerificationStep', true)
        ->set('twoFactorCode', '123456')
        ->call('resetTwoFactorVerification')
        ->assertSet('twoFactorCode', '')
        ->assertSet('showTwoFactorVerificationStep', false);
});

it('computes profile completion percentage based on user attributes', function () {
    $target = User::factory()->create([
        'name' => 'Complete User',
        'email' => 'complete@example.com',
        'email_verified_at' => now(),
        'is_active' => true,
    ]);
    $target->assignRole($this->defaultRole);

    // 5 of 10 criteria met (name, email, verified, role, active — missing 2FA, avatar, phone, country, document)
    Livewire::test('pages::users.show', ['user' => (string) $target->id])
        ->assertSee('50%');

    $minimal = User::factory()->create([
        'name' => 'Minimal',
        'email' => 'minimal@example.com',
        'email_verified_at' => null,
        'is_active' => false,
    ]);

    // 2 of 10 criteria met (name, email)
    Livewire::test('pages::users.show', ['user' => (string) $minimal->id])
        ->assertSee('20%');
});

it('aborts with 404 when starting an invalid editing section', function () {
    $target = User::factory()->create();
    $target->assignRole($this->defaultRole);

    Livewire::test('pages::users.show', ['user' => (string) $target->id])
        ->call('startEditingSection', 'invalid-section')
        ->assertNotFound();
});

it('computes security score text based on user security criteria', function () {
    $strong = User::factory()->create([
        'email_verified_at' => now(),
        'is_active' => true,
        'two_factor_confirmed_at' => now(),
    ]);
    $strong->assignRole('guest');

    Livewire::test('pages::users.show', ['user' => (string) $strong->id])
        ->assertSee(__('users.show.stats.security_strong'));

    $weak = User::factory()->create([
        'email_verified_at' => null,
        'is_active' => false,
    ]);

    Livewire::test('pages::users.show', ['user' => (string) $weak->id])
        ->assertSee(__('users.show.stats.security_low'));
});

it('shows humanized last access text when user has logged in', function () {
    $target = User::factory()->create([
        'last_login_at' => now()->subMinutes(30),
    ]);
    $target->assignRole($this->defaultRole);

    Livewire::test('pages::users.show', ['user' => (string) $target->id])
        ->assertSee(__('users.show.stats.last_access'))
        ->assertDontSee(__('users.show.stats.not_available'));
});

it('shows not available when user has never logged in', function () {
    $target = User::factory()->create([
        'last_login_at' => null,
    ]);
    $target->assignRole($this->defaultRole);

    Livewire::test('pages::users.show', ['user' => (string) $target->id])
        ->assertSee(__('users.show.stats.not_available'));
});

it('prevents a non-admin from viewing the show page', function () {
    $guest = User::factory()->create();
    $guest->assignRole($this->defaultRole);

    $target = User::factory()->create();
    $target->assignRole($this->defaultRole);

    $this->actingAs($guest);

    Livewire::test('pages::users.show', ['user' => (string) $target->id])
        ->assertForbidden();
});

it('uploads a profile photo successfully', function () {
    Storage::fake('public');

    $target = User::factory()->create([
        'email_verified_at' => now(),
        'is_active' => true,
    ]);
    $target->assignRole($this->defaultRole);

    $photo = UploadedFile::fake()->image('avatar.jpg', 600, 600);

    $component = Livewire::test('pages::users.show', ['user' => (string) $target->id]);

    $roleQueries = captureRoleQueries($target, function () use ($component, $photo): void {
        $component
            ->set('photo', $photo)
            ->assertHasNoErrors();
    });

    expect($roleQueries)->toHaveCount(0)
        ->and($component->instance()->userAvatarUrl)->not->toBeNull()
        ->and($component->instance()->profileCompletionPercentage())->toBe(60)
        ->and($target->refresh()->getFirstMedia('avatar'))->not->toBeNull();
});

it('rejects a non-image file for profile photo', function () {
    Storage::fake('public');

    $target = User::factory()->create();
    $target->assignRole($this->defaultRole);

    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    Livewire::test('pages::users.show', ['user' => (string) $target->id])
        ->set('photo', $file)
        ->assertHasErrors('photo');
});

it('replaces the previous avatar when uploading a new one', function () {
    Storage::fake('public');

    $target = User::factory()->create();
    $target->assignRole($this->defaultRole);

    $firstPhoto = UploadedFile::fake()->image('first.jpg', 400, 400);
    $secondPhoto = UploadedFile::fake()->image('second.jpg', 400, 400);

    Livewire::test('pages::users.show', ['user' => (string) $target->id])
        ->set('photo', $firstPhoto)
        ->assertHasNoErrors();

    Livewire::test('pages::users.show', ['user' => (string) $target->id])
        ->set('photo', $secondPhoto)
        ->assertHasNoErrors();

    expect($target->refresh()->getMedia('avatar'))->toHaveCount(1);
});

it('deletes the profile photo', function () {
    Storage::fake('public');

    $target = User::factory()->create([
        'email_verified_at' => now(),
        'is_active' => true,
    ]);
    $target->assignRole($this->defaultRole);

    $photo = UploadedFile::fake()->image('avatar.jpg', 300, 300);

    $component = Livewire::test('pages::users.show', ['user' => (string) $target->id])
        ->set('photo', $photo)
        ->assertHasNoErrors();

    expect($component->instance()->userAvatarUrl)->not->toBeNull()
        ->and($component->instance()->profileCompletionPercentage())->toBe(60)
        ->and($target->refresh()->getFirstMedia('avatar'))->not->toBeNull();

    $roleQueries = captureRoleQueries($target, function () use ($component): void {
        $component
            ->call('deleteAvatar')
            ->assertHasNoErrors();
    });

    expect($roleQueries)->toHaveCount(0)
        ->and($component->instance()->userAvatarUrl)->toBeNull()
        ->and($component->instance()->profileCompletionPercentage())->toBe(50)
        ->and($target->refresh()->getFirstMedia('avatar'))->toBeNull();
});

// --- Personal section: document_type_id and country_id ---

it('autosaves document_type_id from the personal section', function () {
    $docType = IdentificationDocumentType::factory()->create();

    $target = User::factory()->create(['document_type_id' => null]);
    $target->assignRole($this->defaultRole);

    Livewire::test('pages::users.show', ['user' => (string) $target->id])
        ->call('startEditingSection', 'personal')
        ->set('document_type_id', $docType->id)
        ->assertDispatched('toast-show');

    expect($target->fresh()->document_type_id)->toBe($docType->id);
});

it('autosaves country_id from the personal section', function () {
    $country = Country::factory()->create(['is_active' => true]);

    $target = User::factory()->create(['country_id' => null]);
    $target->assignRole($this->defaultRole);

    Livewire::test('pages::users.show', ['user' => (string) $target->id])
        ->call('startEditingSection', 'personal')
        ->set('country_id', $country->id)
        ->assertDispatched('toast-show');

    expect($target->fresh()->country_id)->toBe($country->id);
});

it('shows cross-validation error when document_type is set without document_number', function () {
    $docType = IdentificationDocumentType::factory()->create();

    $target = User::factory()->create([
        'document_type_id' => null,
        'document_number' => null,
    ]);
    $target->assignRole($this->defaultRole);

    Livewire::test('pages::users.show', ['user' => (string) $target->id])
        ->call('startEditingSection', 'personal')
        ->set('document_type_id', $docType->id)
        ->assertHasErrors(['document_number']);
});

it('shows cross-validation error when document_number is set without document_type', function () {
    $target = User::factory()->create([
        'document_type_id' => null,
        'document_number' => null,
    ]);
    $target->assignRole($this->defaultRole);

    Livewire::test('pages::users.show', ['user' => (string) $target->id])
        ->call('startEditingSection', 'personal')
        ->set('document_number', '12345678')
        ->assertHasErrors(['document_type_id']);
});

it('accepts both document fields filled together', function () {
    $docType = IdentificationDocumentType::factory()->create();

    $target = User::factory()->create([
        'document_type_id' => null,
        'document_number' => null,
    ]);
    $target->assignRole($this->defaultRole);

    Livewire::test('pages::users.show', ['user' => (string) $target->id])
        ->call('startEditingSection', 'personal')
        ->set('document_type_id', $docType->id)
        ->set('document_number', '12345678')
        ->assertHasNoErrors(['document_type_id', 'document_number']);

    expect($target->fresh()->document_type_id)->toBe($docType->id)
        ->and($target->fresh()->document_number)->toBe('12345678');
});

it('rejects an inactive document type in admin personal section', function () {
    $inactiveDocType = IdentificationDocumentType::factory()->inactive()->create();

    $target = User::factory()->create([
        'document_type_id' => null,
        'document_number' => null,
    ]);
    $target->assignRole($this->defaultRole);

    Livewire::test('pages::users.show', ['user' => (string) $target->id])
        ->call('startEditingSection', 'personal')
        ->set('document_type_id', $inactiveDocType->id)
        ->assertHasErrors(['document_type_id']);

    expect($target->fresh()->document_type_id)->toBeNull();
});

it('rejects an inactive country in admin personal section', function () {
    $inactiveCountry = Country::factory()->create(['is_active' => false]);

    $target = User::factory()->create(['country_id' => null]);
    $target->assignRole($this->defaultRole);

    Livewire::test('pages::users.show', ['user' => (string) $target->id])
        ->call('startEditingSection', 'personal')
        ->set('country_id', $inactiveCountry->id)
        ->assertHasErrors(['country_id']);
});

it('only shows active document types in the personal section dropdown', function () {
    IdentificationDocumentType::factory()->create(['code' => 'ACT', 'is_active' => true]);
    IdentificationDocumentType::factory()->create(['code' => 'INA', 'is_active' => false]);

    $target = User::factory()->create();
    $target->assignRole($this->defaultRole);

    $component = Livewire::test('pages::users.show', ['user' => (string) $target->id]);

    $docTypeCodes = $component->instance()->documentTypes->pluck('code')->all();

    expect($docTypeCodes)->toContain('ACT')
        ->and($docTypeCodes)->not->toContain('INA');
});

it('only shows active countries in the personal section dropdown', function () {
    Country::factory()->create(['iso_alpha2' => 'AC', 'is_active' => true]);
    Country::factory()->create(['iso_alpha2' => 'IN', 'is_active' => false]);

    $target = User::factory()->create();
    $target->assignRole($this->defaultRole);

    $component = Livewire::test('pages::users.show', ['user' => (string) $target->id]);

    $countryCodes = $component->instance()->countries->pluck('iso_alpha2')->all();

    expect($countryCodes)->toContain('AC')
        ->and($countryCodes)->not->toContain('IN');
});
