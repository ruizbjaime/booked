<?php

use App\Actions\Roles\UpdateRole;
use App\Domain\Auth\PermissionRegistry;
use App\Domain\Users\RoleConfig;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->actingAs(makeAdmin());
});

test('renders show page with role details', function () {
    $role = Role::factory()->create([
        'name' => 'test-role',
        'en_label' => 'Test Role',
        'es_label' => 'Rol de Prueba',
        'color' => 'blue',
        'sort_order' => 10,
        'is_active' => true,
    ]);

    Livewire::test('pages::roles.show', ['role' => (string) $role->id])
        ->assertOk()
        ->assertSee('Test Role')
        ->assertSee('test-role')
        ->assertSee(__('roles.show.status.active'));
});

test('autosaves field changes', function () {
    $role = Role::factory()->create([
        'en_label' => 'Old Label',
    ]);

    Livewire::test('pages::roles.show', ['role' => (string) $role->id])
        ->call('startEditingSection', 'details')
        ->set('en_label', 'New Label')
        ->assertDispatched('toast-show', function (string $event, array $params) {
            return ($params['dataset']['variant'] ?? null) === 'success';
        });

    expect($role->fresh()->en_label)->toBe('New Label');
});

test('active toggle autosaves', function () {
    $role = Role::factory()->create(['is_active' => true]);

    Livewire::test('pages::roles.show', ['role' => (string) $role->id])
        ->call('startEditingSection', 'details')
        ->set('is_active', false)
        ->assertDispatched('toast-show');

    expect($role->fresh()->is_active)->toBeFalse();
});

test('delete confirmation and redirect', function () {
    $role = Role::factory()->create(['name' => 'delete-me']);

    Livewire::test('pages::roles.show', ['role' => (string) $role->id])
        ->call('confirmRoleDeletion')
        ->assertSet('roleIdPendingDeletion', $role->id)
        ->dispatch('modal-confirmed')
        ->assertRedirect(route('roles.index'));

    expect(Role::query()->find($role->id))->toBeNull();
});

test('role with assigned users cannot be deleted from show page', function () {
    $role = Role::factory()->create(['is_active' => true]);

    $user = User::factory()->create();
    $user->assignRole($role);

    Livewire::test('pages::roles.show', ['role' => (string) $role->id])
        ->call('confirmRoleDeletion')
        ->dispatch('modal-confirmed')
        ->assertStatus(409);

    expect(Role::query()->find($role->id))->not->toBeNull();
});

test('delete button is hidden for roles with assigned users', function () {
    $role = Role::factory()->create(['is_active' => true]);

    $user = User::factory()->create();
    $user->assignRole($role);

    Livewire::test('pages::roles.show', ['role' => (string) $role->id])
        ->assertDontSeeHtml('wire:click="confirmRoleDeletion"');
});

test('active toggle is disabled for roles with assigned users', function () {
    $role = Role::factory()->create(['is_active' => true]);

    $user = User::factory()->create();
    $user->assignRole($role);

    Livewire::test('pages::roles.show', ['role' => (string) $role->id])
        ->call('startEditingSection', 'details')
        ->set('is_active', false)
        ->assertStatus(409);

    expect($role->fresh()->is_active)->toBeTrue();
});

test('system role delete button is hidden', function () {
    $adminRole = Role::query()->where('name', RoleConfig::adminRole())->first();

    Livewire::test('pages::roles.show', ['role' => (string) $adminRole->id])
        ->assertDontSeeHtml('wire:click="confirmRoleDeletion"');
});

test('non-admin cannot view show page', function () {
    $role = Role::factory()->create();

    $this->actingAs(makeGuest());

    Livewire::test('pages::roles.show', ['role' => (string) $role->id])
        ->assertForbidden();
});

test('show page displays assigned users count', function () {
    $role = Role::factory()->create();

    $users = User::factory()->count(3)->create();
    $users->each(fn (User $u) => $u->assignRole($role));

    Livewire::test('pages::roles.show', ['role' => (string) $role->id])
        ->assertOk()
        ->assertSee('3');
});

test('cancel editing section restores original values and clears validation', function () {
    $role = Role::factory()->create([
        'en_label' => 'Original Label',
    ]);

    Livewire::test('pages::roles.show', ['role' => (string) $role->id])
        ->call('startEditingSection', 'details')
        ->set('en_label', '')
        ->call('cancelEditingSection')
        ->assertSet('en_label', 'Original Label')
        ->assertSet('editingSection', null)
        ->assertHasNoErrors();
});

test('autosave does not trigger without active editing section', function () {
    $role = Role::factory()->create([
        'en_label' => 'Unchanged',
    ]);

    Livewire::test('pages::roles.show', ['role' => (string) $role->id])
        ->assertSet('editingSection', null)
        ->set('en_label', 'Should Not Save')
        ->assertNotDispatched('toast-show');

    expect($role->fresh()->en_label)->toBe('Unchanged');
});

test('start editing section with invalid section returns 404', function () {
    $role = Role::factory()->create();

    Livewire::test('pages::roles.show', ['role' => (string) $role->id])
        ->call('startEditingSection', 'nonexistent')
        ->assertNotFound();
});

test('name field is displayed read-only in edit mode', function () {
    $role = Role::factory()->create(['name' => 'my-role']);

    Livewire::test('pages::roles.show', ['role' => (string) $role->id])
        ->call('startEditingSection', 'details')
        ->assertSeeHtml('disabled');
});

// --- Rate limiting tests ---

test('show page autosave is rate limited', function () {
    $role = Role::factory()->create();

    $component = Livewire::test('pages::roles.show', ['role' => (string) $role->id])
        ->call('startEditingSection', 'details');

    for ($i = 0; $i < 10; $i++) {
        RateLimiter::hit("role-mgmt:autosave:{$this->app['auth']->id()}", 60);
    }

    $component->set('en_label', 'Rate Limited Label')
        ->assertDispatched('open-info-modal');
});

test('show page active toggle is rate limited', function () {
    $role = Role::factory()->create(['is_active' => true]);

    $component = Livewire::test('pages::roles.show', ['role' => (string) $role->id])
        ->call('startEditingSection', 'details');

    for ($i = 0; $i < 10; $i++) {
        RateLimiter::hit("role-mgmt:toggle-active:{$this->app['auth']->id()}", 60);
    }

    $component->set('is_active', false)
        ->assertDispatched('open-info-modal');
});

test('show page delete confirmation is rate limited', function () {
    $role = Role::factory()->create();

    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit("role-mgmt:delete:{$this->app['auth']->id()}", 60);
    }

    Livewire::test('pages::roles.show', ['role' => (string) $role->id])
        ->call('confirmRoleDeletion')
        ->assertDispatched('open-info-modal');
});

// --- canEdit / canDelete ---

test('show page canEdit returns true for admin', function () {
    $role = Role::factory()->create();

    Livewire::test('pages::roles.show', ['role' => (string) $role->id])
        ->assertSeeHtml('wire:click="startEditingSection');
});

test('show page canDelete returns true for non-system role', function () {
    $role = Role::factory()->create();

    Livewire::test('pages::roles.show', ['role' => (string) $role->id])
        ->assertSeeHtml('wire:click="confirmRoleDeletion');
});

// --- Modal cancel on show page ---

test('show page clears pending deletion when confirm modal is cancelled', function () {
    $role = Role::factory()->create();

    Livewire::test('pages::roles.show', ['role' => (string) $role->id])
        ->call('confirmRoleDeletion')
        ->assertSet('roleIdPendingDeletion', $role->id)
        ->dispatch('modal-confirm-cancelled')
        ->assertSet('roleIdPendingDeletion', null);
});

// --- Mount 404 ---

test('show page mount returns 404 for non-existent role', function () {
    $this->get('/roles/999999')
        ->assertNotFound();
});

// --- Update action aborts 422 for unknown field ---

test('update action aborts 422 for unknown field name', function () {
    $admin = makeAdmin();
    $role = Role::factory()->create();

    $action = app(UpdateRole::class);

    try {
        $action->handle($admin, $role, 'nonexistent', 'value');
        $this->fail('Expected abort 422');
    } catch (HttpException $e) {
        expect($e->getStatusCode())->toBe(422);
    }
});

// --- Permissions section tests ---

test('permissions section renders in view mode with badges', function () {
    $role = Role::factory()->create();
    $role->givePermissionTo('country.viewAny', 'country.view');

    Livewire::test('pages::roles.show', ['role' => (string) $role->id])
        ->assertOk()
        ->assertSee(__('roles.show.sections.permissions'))
        ->assertSee(__('roles.show.permissions.models.country'));
});

test('permissions section edit mode shows checkboxes', function () {
    $role = Role::factory()->create();

    Livewire::test('pages::roles.show', ['role' => (string) $role->id])
        ->call('startEditingSection', 'permissions')
        ->assertSet('editingSection', 'permissions')
        ->assertSee(__('roles.show.permissions.save'))
        ->assertSee(__('roles.show.permissions.models.user'))
        ->assertSee(__('roles.show.permissions.models.country'));
});

test('saving permissions updates role', function () {
    $role = Role::factory()->create();

    Livewire::test('pages::roles.show', ['role' => (string) $role->id])
        ->call('startEditingSection', 'permissions')
        ->set('selectedPermissions', ['country.viewAny', 'country.view'])
        ->call('savePermissions')
        ->assertDispatched('toast-show', function (string $event, array $params) {
            return ($params['dataset']['variant'] ?? null) === 'success';
        });

    $permissions = $role->fresh()->permissions->pluck('name')->sort()->values()->all();

    expect($permissions)->toBe(['country.view', 'country.viewAny']);
});

test('admin role has protected permission checkboxes', function () {
    $adminRole = Role::query()->where('name', RoleConfig::adminRole())->first();

    $component = Livewire::test('pages::roles.show', ['role' => (string) $adminRole->id])
        ->call('startEditingSection', 'permissions');

    expect($component->instance()->isProtectedPermission('user.viewAny'))->toBeTrue()
        ->and($component->instance()->isProtectedPermission('role.delete'))->toBeTrue()
        ->and($component->instance()->isProtectedPermission('country.viewAny'))->toBeFalse();
});

test('admin role cannot lose user or role permissions via save', function () {
    $adminRole = Role::query()->where('name', RoleConfig::adminRole())->first();

    Livewire::test('pages::roles.show', ['role' => (string) $adminRole->id])
        ->call('startEditingSection', 'permissions')
        ->set('selectedPermissions', ['country.viewAny'])
        ->call('savePermissions');

    $adminRole->refresh()->load('permissions');
    $permissionNames = $adminRole->permissions->pluck('name')->all();

    foreach (PermissionRegistry::adminProtectedPermissions() as $perm) {
        expect($permissionNames)->toContain($perm);
    }

    expect($permissionNames)->toContain('country.viewAny');
});

test('cancel editing permissions restores original values', function () {
    $role = Role::factory()->create();
    $role->givePermissionTo('country.viewAny');

    Livewire::test('pages::roles.show', ['role' => (string) $role->id])
        ->call('startEditingSection', 'permissions')
        ->assertSet('selectedPermissions', ['country.viewAny'])
        ->set('selectedPermissions', ['country.viewAny', 'country.view', 'country.create'])
        ->call('cancelEditingSection')
        ->assertSet('selectedPermissions', ['country.viewAny'])
        ->assertSet('editingSection', null);
});

test('save permissions is rate limited', function () {
    $role = Role::factory()->create();

    $component = Livewire::test('pages::roles.show', ['role' => (string) $role->id])
        ->call('startEditingSection', 'permissions');

    for ($i = 0; $i < 10; $i++) {
        RateLimiter::hit("role-mgmt:save-permissions:{$this->app['auth']->id()}", 60);
    }

    $component->call('savePermissions')
        ->assertDispatched('open-info-modal');
});
