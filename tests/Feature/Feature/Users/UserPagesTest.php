<?php

use App\Domain\Users\RoleConfig;
use App\Infrastructure\UiFeedback\ModalService;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->actingAs(makeAdmin());
});

function usersIndexComponent(?bool $mobileViewport = false): Testable
{
    $component = Livewire::test('pages::users.index');

    if ($mobileViewport !== null) {
        $component->call('syncTableViewport', $mobileViewport);
    }

    return $component;
}

test('admins can visit the users index page', function () {
    $this->get(route('users.index'))
        ->assertOk()
        ->assertSeeText(__('users.index.title'));
});

test('users create page no longer exists', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/users/create')
        ->assertNotFound();
});

test('admins can visit the users show page', function () {
    $target = User::factory()->create([
        'name' => 'Show Target',
        'email' => 'show-target@example.com',
        'email_verified_at' => now(),
        'is_active' => true,
    ]);
    $target->assignRole(RoleConfig::defaultRole());

    $this->get(route('users.show', $target))
        ->assertOk()
        ->assertSeeText(__('users.show.placeholder_title'))
        ->assertSeeText('Show Target')
        ->assertSeeText('show-target@example.com')
        ->assertSeeText(__('users.show.status.active'))
        ->assertSeeText(RoleConfig::label(RoleConfig::defaultRole()))
        ->assertSee((string) $target->getRouteKey());
});

test('non admins cannot visit the users index page', function () {
    $this->actingAs(makeGuest());

    $this->get(route('users.index'))->assertForbidden();
});

test('non admins cannot visit the users show page', function () {
    $target = User::factory()->create();

    $this->actingAs(makeGuest());

    $this->get(route('users.show', $target))->assertForbidden();
});

test('sidebar hides the users navigation item for non admins', function () {
    $this->actingAs(makeGuest());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertDontSeeText(__('users.navigation.label'));
});

test('sidebar shows the users navigation item for admins', function () {
    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSeeText(__('users.navigation.label'));
});

test('users index shows verified badge for verified users', function () {
    User::factory()->create(['email_verified_at' => now()]);

    usersIndexComponent()
        ->assertOk()
        ->assertSee(__('users.index.verification.verified'));
});

test('users index shows pending badge for unverified users', function () {
    User::factory()->create(['email_verified_at' => null]);

    usersIndexComponent()
        ->assertOk()
        ->assertSee(__('users.index.verification.pending'));
});

test('users index renders a created at tooltip trigger', function () {
    $createdAt = Carbon::parse('2026-03-14 09:30:00');
    $expectedDate = $createdAt->copy()->locale(app()->getLocale())->isoFormat('ll');
    $expectedTooltip = $createdAt->copy()->locale(app()->getLocale())->isoFormat('llll');

    User::factory()->create(['created_at' => $createdAt]);

    usersIndexComponent()
        ->assertOk()
        ->assertSee($expectedDate)
        ->assertSee($expectedTooltip)
        ->assertSeeHtml('cursor-help');
});

test('users index localizes date tooltips in Spanish', function () {
    $originalLocale = app()->getLocale();
    $createdAt = Carbon::parse('2026-03-14 09:30:00');
    $updatedAt = Carbon::parse('2026-03-15 14:45:00');

    app()->setLocale('es');

    try {
        User::factory()->create([
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ]);

        usersIndexComponent()
            ->assertOk()
            ->assertSee($createdAt->copy()->locale('es')->isoFormat('ll'))
            ->assertSee($createdAt->copy()->locale('es')->isoFormat('llll'))
            ->assertSee($updatedAt->copy()->locale('es')->isoFormat('ll'))
            ->assertSee($updatedAt->copy()->locale('es')->isoFormat('llll'));
    } finally {
        app()->setLocale($originalLocale);
    }
});

test('users index preserves filters when switching to the mobile card layout', function () {
    $visibleUser = makeAdmin([
        'name' => 'Visible Admin',
        'email' => 'visible-admin@example.com',
    ]);
    makeGuest([
        'name' => 'Hidden Guest',
        'email' => 'hidden-guest@example.com',
    ]);

    usersIndexComponent(true)
        ->set('roleFilter', [RoleConfig::adminRole()])
        ->assertSeeHtml('data-table-viewport-mobile')
        ->assertDontSeeHtml('data-table-viewport-desktop')
        ->assertSee('Visible Admin')
        ->assertDontSee('Hidden Guest')
        ->assertSee(route('users.show', $visibleUser))
        ->call('syncTableViewport', false)
        ->assertSeeHtml('data-table-viewport-desktop')
        ->assertDontSee('Hidden Guest');
});

test('users index rows use a darker hover background', function () {
    User::factory()->create();

    usersIndexComponent()
        ->assertOk()
        ->assertSeeHtml('transition-colors')
        ->assertSeeHtml('hover:bg-zinc-200/80')
        ->assertSeeHtml('dark:hover:bg-white/[0.06]');
});

test('users index can filter users by the global search term', function () {
    User::factory()->create([
        'name' => 'Alice Johnson',
        'email' => 'alice@example.com',
    ]);
    User::factory()->create([
        'name' => 'Bob Stone',
        'email' => 'bob@example.com',
    ]);

    usersIndexComponent()
        ->set('search', 'alice')
        ->assertSee('Alice Johnson')
        ->assertDontSee('Bob Stone');
});

test('users index sorts by newest created users by default', function () {
    User::factory()->create([
        'name' => 'Older User',
        'created_at' => Carbon::parse('2026-03-10 09:00:00'),
    ]);

    User::factory()->create([
        'name' => 'Newest User',
        'created_at' => Carbon::parse('2026-03-15 09:00:00'),
    ]);

    usersIndexComponent()
        ->assertSeeInOrder(['Newest User', 'Older User'])
        ->assertSet('sortBy', 'created_at')
        ->assertSet('sortDirection', 'desc');
});

test('users index can sort users by name', function () {
    User::factory()->create(['name' => 'Zulu User']);
    User::factory()->create(['name' => 'Alpha User']);

    usersIndexComponent()
        ->call('sort', 'name')
        ->assertSeeInOrder(['Alpha User', 'Zulu User'])
        ->assertSet('sortBy', 'name')
        ->assertSet('sortDirection', 'asc')
        ->call('sort', 'name')
        ->assertSeeInOrder(['Zulu User', 'Alpha User'])
        ->assertSet('sortDirection', 'desc');
});

test('users index can sort users by email', function () {
    User::factory()->create([
        'name' => 'Bravo User',
        'email' => 'zulu@example.com',
    ]);

    User::factory()->create([
        'name' => 'Charlie User',
        'email' => 'alpha@example.com',
    ]);

    usersIndexComponent()
        ->call('sort', 'email')
        ->assertSeeInOrder(['alpha@example.com', 'zulu@example.com'])
        ->assertSet('sortBy', 'email')
        ->assertSet('sortDirection', 'asc');
});

test('users index can sort users by verified status', function () {
    User::factory()->create([
        'name' => 'Pending User',
        'email_verified_at' => null,
    ]);

    User::factory()->create([
        'name' => 'Verified User',
        'email_verified_at' => Carbon::parse('2026-03-15 12:00:00'),
    ]);

    usersIndexComponent()
        ->call('sort', 'email_verified_at')
        ->assertSeeInOrder(['Verified User', 'Pending User'])
        ->assertSet('sortBy', 'email_verified_at')
        ->assertSet('sortDirection', 'desc');
});

test('users index can sort users by last modification date', function () {
    User::factory()->create([
        'name' => 'Less Recent Update',
        'updated_at' => Carbon::parse('2026-03-11 08:00:00'),
    ]);

    User::factory()->create([
        'name' => 'Most Recent Update',
        'updated_at' => Carbon::parse('2026-03-16 08:00:00'),
    ]);

    usersIndexComponent()
        ->call('sort', 'updated_at')
        ->assertSeeInOrder(['Most Recent Update', 'Less Recent Update'])
        ->assertSet('sortBy', 'updated_at')
        ->assertSet('sortDirection', 'desc');
});

test('users index renders row actions for viewing users', function () {
    $user = User::factory()->create();

    usersIndexComponent()
        ->assertSee(route('users.show', $user))
        ->assertSee(__('actions.view'));
});

test('admin can open the user create modal from the users index', function () {
    $component = usersIndexComponent()
        ->call('openCreateUserModal');

    expect(collect(data_get($component->effects, 'dispatches', []))->contains(function (array $dispatch): bool {
        return ($dispatch['name'] ?? null) === 'open-form-modal'
            && ($dispatch['params']['name'] ?? null) === 'users.create'
            && ($dispatch['params']['title'] ?? null) === __('users.create.title')
            && ($dispatch['params']['description'] ?? null) === __('users.create.description');
    }))->toBeTrue();
});

test('admin can create a user from the users index modal flow', function () {
    Livewire::test('users.create-user-form')
        ->assertSet('active', true)
        ->assertSet('roles', [RoleConfig::defaultRole()])
        ->set('name', 'Modal User')
        ->set('email', 'modal-user@example.com')
        ->set('roles', [RoleConfig::defaultRole()])
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('save')
        ->assertSet('name', '')
        ->assertSet('email', '')
        ->assertSet('password', '')
        ->assertSet('password_confirmation', '')
        ->assertSet('roles', [RoleConfig::defaultRole()])
        ->assertSet('active', true)
        ->assertDispatched('close-form-modal')
        ->assertDispatched('user-created')
        ->assertDispatched('toast-show', function (string $event, array $params) {
            return $event === 'toast-show'
                && ($params['duration'] ?? null) === 5000
                && ($params['slots']['heading'] ?? null) === __('toasts.headings.success')
                && str_contains((string) ($params['slots']['text'] ?? ''), 'Modal User')
                && ($params['dataset']['variant'] ?? null) === 'success';
        });

    $created = User::query()->where('email', 'modal-user@example.com')->first();

    expect($created)->not->toBeNull()
        ->and($created?->is_active)->toBeTrue()
        ->and($created?->hasRole(RoleConfig::defaultRole()))->toBeTrue();
});

test('admin can create an inactive user from the users index modal flow', function () {
    Livewire::test('users.create-user-form')
        ->set('roles', [RoleConfig::defaultRole()])
        ->set('name', 'Inactive Modal User')
        ->set('email', 'inactive-modal-user@example.com')
        ->set('active', false)
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('save')
        ->assertDispatched('close-form-modal')
        ->assertDispatched('user-created');

    $created = User::query()->where('email', 'inactive-modal-user@example.com')->first();

    expect($created)->not->toBeNull()
        ->and($created?->is_active)->toBeFalse()
        ->and($created?->hasRole(RoleConfig::defaultRole()))->toBeTrue();
});

test('admin must select at least one role in the create user form', function () {
    Livewire::test('users.create-user-form')
        ->set('name', 'Roleless User')
        ->set('email', 'roleless-user@example.com')
        ->set('roles', [])
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('save')
        ->assertHasErrors(['roles'])
        ->assertSee(__('validation.required', ['attribute' => 'roles']))
        ->set('roles', [RoleConfig::defaultRole()])
        ->assertHasNoErrors(['roles'])
        ->assertDontSee(__('validation.required', ['attribute' => 'roles']))
        ->assertNotDispatched('user-created');
});

test('admin role selection clears other roles in the create user form', function () {
    Livewire::test('users.create-user-form')
        ->set('roles', [RoleConfig::defaultRole()])
        ->set('roles', [RoleConfig::defaultRole(), RoleConfig::adminRole()])
        ->assertSet('roles', [RoleConfig::adminRole()]);
});

test('non admins cannot open the user create modal from the users index', function () {
    $this->actingAs(makeGuest());

    Livewire::test('pages::users.index')
        ->assertForbidden();
});

test('users index search input defines non-auth autofill metadata', function () {
    usersIndexComponent()
        ->assertSeeHtml('name="users_search"')
        ->assertSeeHtml('id="users-search"')
        ->assertSeeHtml('autocomplete="off"');
});

test('users index renders active column and disables current user switch', function () {
    $admin = makeAdmin(['is_active' => true]);
    $target = User::factory()->create();

    $this->actingAs($admin);

    usersIndexComponent()
        ->assertSee(__('users.index.columns.active'))
        ->assertSeeHtml('id="user-active-'.$admin->id.'"')
        ->assertSeeHtml('data-disabled="true"')
        ->assertSeeHtml('id="user-active-'.$target->id.'"')
        ->assertSeeHtml('data-disabled="false"');
});

test('users index does not reload actor roles for each row action', function () {
    $admin = makeAdmin();

    User::factory()->count(3)->create()->each(function (User $user): void {
        $user->assignRole(RoleConfig::defaultRole());
    });

    $this->actingAs($admin);

    DB::flushQueryLog();
    DB::enableQueryLog();

    usersIndexComponent()
        ->assertOk();

    $actorRoleQueries = collect(DB::getQueryLog())->filter(function (array $query) use ($admin): bool {
        return str_contains((string) ($query['query'] ?? ''), 'from "roles" inner join "model_has_roles"')
            && str_contains((string) ($query['query'] ?? ''), '"model_has_roles"."model_id" in ('.$admin->id.')');
    });

    expect($actorRoleQueries)->toHaveCount(1);
});

function userDeleteModalMessage(User $user): string
{
    return __('users.index.confirm_delete.message', [
        'user' => __('users.user_label', ['name' => $user->name, 'id' => $user->id]),
    ]);
}

test('admin sees delete for other users but not for themselves', function () {
    $admin = makeAdmin();
    $target = User::factory()->create();

    $this->actingAs($admin);

    usersIndexComponent()
        ->assertSeeHtml('confirmUserDeletion('.$target->id.')')
        ->assertDontSeeHtml('confirmUserDeletion('.$admin->id.')');
});

test('admin can delete another user from the users index', function () {
    $admin = makeAdmin();
    $target = User::factory()->create([
        'name' => 'Delete Me',
        'email' => 'delete-me@example.com',
    ]);

    $this->actingAs($admin);

    usersIndexComponent()
        ->call('confirmUserDeletion', $target->id)
        ->assertSet('userIdPendingDeletion', $target->id)
        ->assertDispatched('open-confirm-modal', function (string $event, array $params) use ($target) {
            return $event === 'open-confirm-modal'
                && ($params['title'] ?? null) === __('users.index.confirm_delete.title')
                && ($params['message'] ?? null) === userDeleteModalMessage($target)
                && ($params['confirmLabel'] ?? null) === __('users.index.confirm_delete.confirm_label')
                && ($params['variant'] ?? null) === ModalService::VARIANT_PASSWORD;
        })
        ->dispatch('modal-confirmed')
        ->assertSet('userIdPendingDeletion', null)
        ->assertDontSee('Delete Me');

    expect(User::query()->find($target->id))->toBeNull();
});

test('users index clears a pending deletion when the confirm modal is cancelled', function () {
    $target = User::factory()->create();

    usersIndexComponent()
        ->call('confirmUserDeletion', $target->id)
        ->assertSet('userIdPendingDeletion', $target->id)
        ->dispatch('modal-confirm-cancelled')
        ->assertSet('userIdPendingDeletion', null);
});

test('admin can activate another user from the users index', function () {
    $admin = makeAdmin(['is_active' => true]);
    $target = User::factory()->create([
        'name' => 'Toggle Me',
        'email' => 'toggle-me@example.com',
        'is_active' => false,
    ]);

    $this->actingAs($admin);

    usersIndexComponent()
        ->call('toggleUserActiveStatus', $target->id, true)
        ->assertDispatched('toast-show', function (string $event, array $params) use ($target) {
            return $event === 'toast-show'
                && ($params['duration'] ?? null) === 5000
                && ($params['slots']['heading'] ?? null) === __('toasts.headings.success')
                && ($params['slots']['text'] ?? null) === __('users.index.activated', [
                    'user' => __('users.user_label', ['name' => $target->name, 'id' => $target->id]),
                ])
                && ($params['dataset']['variant'] ?? null) === 'success';
        });

    expect($target->fresh()->is_active)->toBeTrue();
});

test('admin can deactivate another user from the users index', function () {
    $admin = makeAdmin(['is_active' => true]);
    $target = User::factory()->active()->create([
        'name' => 'Deactivate Me',
        'email' => 'deactivate-me@example.com',
    ]);

    $this->actingAs($admin);

    usersIndexComponent()
        ->call('toggleUserActiveStatus', $target->id, false)
        ->assertDispatched('toast-show', function (string $event, array $params) use ($target) {
            return $event === 'toast-show'
                && ($params['duration'] ?? null) === 5000
                && ($params['slots']['heading'] ?? null) === __('toasts.headings.success')
                && ($params['slots']['text'] ?? null) === __('users.index.deactivated', [
                    'user' => __('users.user_label', ['name' => $target->name, 'id' => $target->id]),
                ])
                && ($params['dataset']['variant'] ?? null) === 'success';
        });

    expect($target->fresh()->is_active)->toBeFalse();
});

test('users delete modal message keeps concise continuous copy', function () {
    $user = User::factory()->make([
        'name' => 'Delete Me',
        'id' => 148,
    ]);

    expect(userDeleteModalMessage($user))
        ->not->toContain("\n")
        ->not->toContain(__('Current password'))
        ->toContain(__('users.user_label', ['name' => $user->name, 'id' => $user->id]));
});

test('users index success toast identifies the deleted user by name and id', function () {
    $admin = makeAdmin();
    $target = User::factory()->create([
        'name' => 'Toast Target',
        'email' => 'toast-target@example.com',
    ]);

    $this->actingAs($admin);

    usersIndexComponent()
        ->call('confirmUserDeletion', $target->id)
        ->dispatch('modal-confirmed')
        ->assertDispatched('toast-show', function (string $event, array $params) use ($target) {
            return $event === 'toast-show'
                && ($params['duration'] ?? null) === 5000
                && ($params['slots']['heading'] ?? null) === __('toasts.headings.success')
                && ($params['slots']['text'] ?? null) === __('users.index.deleted', [
                    'user' => __('users.user_label', ['name' => $target->name, 'id' => $target->id]),
                ])
                && ($params['dataset']['variant'] ?? null) === 'success';
        });
});

test('users pages render Spanish translations in the sidebar and module screens', function () {
    $originalLocale = app()->getLocale();

    app()->setLocale('es');

    try {
        $this->actingAs(makeAdmin());

        $this->get(route('users.index'))
            ->assertOk()
            ->assertSeeText(__('users.navigation.label'))
            ->assertSeeText(__('users.index.description'))
            ->assertSeeText(__('users.index.create_action'));

        $user = User::factory()->create();
        $user->assignRole(RoleConfig::defaultRole());

        $this->get(route('users.show', $user))
            ->assertOk()
            ->assertSeeText(__('users.show.placeholder_title'));
    } finally {
        app()->setLocale($originalLocale);
    }
});

test('admin can delete another user when per page is tampered with an invalid value', function () {
    $admin = makeAdmin();
    $target = User::factory()->create([
        'name' => 'Invalid Per Page Target',
        'email' => 'invalid-per-page-target@example.com',
    ]);

    $this->actingAs($admin);

    usersIndexComponent()
        ->call('confirmUserDeletion', $target->id)
        ->set('perPage', 0)
        ->dispatch('modal-confirmed')
        ->assertSet('perPage', 10)
        ->assertSet('userIdPendingDeletion', null);

    expect(User::query()->find($target->id))->toBeNull();
});

test('non admins cannot trigger user deletion from the users index', function () {
    $target = User::factory()->create();

    $this->actingAs(makeGuest());

    Livewire::test('pages::users.index')
        ->assertForbidden();
});

test('non admins cannot toggle another user active status from the users index', function () {
    $target = User::factory()->create();

    $this->actingAs(makeGuest());

    Livewire::test('pages::users.index')
        ->assertForbidden();
});

test('admin cannot delete themselves from the users index', function () {
    $admin = makeAdmin();

    $this->actingAs($admin);

    Livewire::test('pages::users.index')
        ->call('confirmUserDeletion', $admin->id)
        ->assertForbidden();
});

test('admin cannot deactivate themselves from the users index', function () {
    $admin = makeAdmin(['is_active' => true]);

    $this->actingAs($admin);

    Livewire::test('pages::users.index')
        ->call('toggleUserActiveStatus', $admin->id, false)
        ->assertForbidden();
});

test('users pages render successfully as livewire components', function () {
    usersIndexComponent()
        ->assertOk()
        ->assertSee(__('users.index.title'));

    Livewire::test('users.create-user-form')
        ->assertOk()
        ->assertSee('grid items-start gap-4 md:grid-cols-2', false)
        ->assertSee('rounded-2xl border border-white/8 bg-white/3 px-4 py-3.5', false)
        ->assertSee(__('users.create.fields.roles'))
        ->assertSee(__('users.create.roles_help'))
        ->assertSeeHtml('name="create_user_email"')
        ->assertSeeHtml('autocomplete="section-create-user email"')
        ->assertSeeHtml('name="new_password"')
        ->assertSeeHtml('autocomplete="section-create-user new-password"')
        ->assertSeeHtml('name="new_password_confirmation"')
        ->assertSee(__('users.create.fields.active'))
        ->assertSee(__('users.create.active_help'))
        ->assertSee(__('users.create.active_enabled'))
        ->assertSee(__('users.create.fields.name'));

    $showUser = User::factory()->create(['id' => 123]);

    $showUser->assignRole(RoleConfig::defaultRole());

    Livewire::test('pages::users.show', ['user' => (string) $showUser->id])
        ->assertOk()
        ->assertSee(__('users.show.placeholder_title'))
        ->assertSee($showUser->name)
        ->assertSee($showUser->email);
});

test('users index filter renders available role options', function () {
    $component = usersIndexComponent();

    foreach (RoleConfig::names() as $role) {
        $component->assertSee(RoleConfig::label($role));
    }
});

test('users index filters by role', function (string $filterRole, string $visibleName, string $hiddenName) {
    makeAdmin(['name' => 'Filter Admin']);
    makeGuest(['name' => 'Filter Guest']);

    usersIndexComponent()
        ->set('roleFilter', [$filterRole])
        ->assertSee($visibleName)
        ->assertDontSee($hiddenName);
})->with([
    'admin role' => [fn () => RoleConfig::adminRole(), 'Filter Admin', 'Filter Guest'],
    'guest role' => [fn () => RoleConfig::defaultRole(), 'Filter Guest', 'Filter Admin'],
]);

test('users index clearing role filter shows all users', function () {
    makeAdmin(['name' => 'Visible Admin']);
    makeGuest(['name' => 'Visible Guest']);

    usersIndexComponent()
        ->set('roleFilter', [RoleConfig::adminRole()])
        ->assertDontSee('Visible Guest')
        ->set('roleFilter', [])
        ->assertSee('Visible Admin')
        ->assertSee('Visible Guest');
});

test('users index resets pagination when role filter changes', function () {
    User::factory()->count(15)->create()->each(fn (User $u) => $u->assignRole(RoleConfig::adminRole()));

    usersIndexComponent()
        ->set('perPage', 10)
        ->call('gotoPage', 2)
        ->assertSet('paginators.page', 2)
        ->set('roleFilter', [RoleConfig::adminRole()])
        ->assertSet('paginators.page', 1);
});

test('users index combines search and role filter', function () {
    makeAdmin(['name' => 'Alice Admin']);
    makeGuest(['name' => 'Alice Guest']);
    makeAdmin(['name' => 'Bob Admin']);

    usersIndexComponent()
        ->set('search', 'Alice')
        ->set('roleFilter', [RoleConfig::adminRole()])
        ->assertSee('Alice Admin')
        ->assertDontSee('Alice Guest')
        ->assertDontSee('Bob Admin');
});

test('create user form shows validation error for duplicate email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    Livewire::test('users.create-user-form')
        ->set('name', 'Duplicate Email User')
        ->set('email', 'taken@example.com')
        ->set('roles', [RoleConfig::defaultRole()])
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('save')
        ->assertHasErrors(['email'])
        ->assertNotDispatched('user-created');

    expect(User::query()->where('name', 'Duplicate Email User')->exists())->toBeFalse();
});

test('create user form shows validation error for password mismatch', function () {
    Livewire::test('users.create-user-form')
        ->set('name', 'Mismatch User')
        ->set('email', 'mismatch@example.com')
        ->set('roles', [RoleConfig::defaultRole()])
        ->set('password', 'password-one')
        ->set('password_confirmation', 'password-two')
        ->call('save')
        ->assertHasErrors(['password'])
        ->assertNotDispatched('user-created');

    expect(User::query()->where('email', 'mismatch@example.com')->exists())->toBeFalse();
});

test('users index strips invalid role names from URL-synced role filter', function () {
    makeAdmin(['name' => 'Valid Admin']);
    makeGuest(['name' => 'Valid Guest']);

    usersIndexComponent()
        ->set('roleFilter', ['nonexistent-role', RoleConfig::adminRole()])
        ->assertSet('roleFilter', [RoleConfig::adminRole()])
        ->assertSee('Valid Admin')
        ->assertDontSee('Valid Guest');
});

test('users index ignores entirely invalid role filter values', function () {
    makeAdmin(['name' => 'Still Visible Admin']);
    makeGuest(['name' => 'Still Visible Guest']);

    usersIndexComponent()
        ->set('roleFilter', ['fake-role', 'another-fake'])
        ->assertSet('roleFilter', [])
        ->assertSee('Still Visible Admin')
        ->assertSee('Still Visible Guest');
});
