<?php

use App\Domain\Users\RoleConfig;
use App\Infrastructure\UiFeedback\ModalService;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->actingAs(makeAdmin());
});

function rolesIndexComponent(?bool $mobileViewport = false): Testable
{
    $component = Livewire::test('pages::roles.index');

    if ($mobileViewport !== null) {
        $component->call('syncTableViewport', $mobileViewport);
    }

    return $component;
}

test('admins can visit the roles index page', function () {
    $this->get(route('roles.index'))
        ->assertOk()
        ->assertSeeText(__('roles.index.title'));
});

test('admins can visit the roles show page', function () {
    $role = Role::factory()->create([
        'name' => 'test-role',
        'en_label' => 'Test Role',
        'es_label' => 'Rol de Prueba',
        'color' => 'blue',
    ]);

    $this->get(route('roles.show', $role))
        ->assertOk()
        ->assertSeeText(__('roles.show.placeholder_title'))
        ->assertSeeText('Test Role')
        ->assertSeeText('test-role');
});

test('non admins cannot visit the roles index page', function () {
    $this->actingAs(makeGuest());

    $this->get(route('roles.index'))->assertForbidden();
});

test('non admins cannot visit the roles show page', function () {
    $role = Role::factory()->create();

    $this->actingAs(makeGuest());

    $this->get(route('roles.show', $role))->assertForbidden();
});

test('sidebar hides the security navigation group for non admins', function () {
    $this->actingAs(makeGuest());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertDontSeeText(__('roles.navigation.label'));
});

test('sidebar shows the security navigation group for admins', function () {
    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSeeText(__('Security'))
        ->assertSeeText(__('roles.navigation.label'));
});

test('roles index sorts by sort_order asc by default', function () {
    Role::factory()->create(['name' => 'zulu-role', 'en_label' => 'Zulu Label', 'es_label' => 'Zulu Label', 'sort_order' => 200]);
    Role::factory()->create(['name' => 'alpha-role', 'en_label' => 'Alpha Label', 'es_label' => 'Alpha Label', 'sort_order' => 100]);

    rolesIndexComponent()
        ->assertSeeInOrder(['Alpha Label', 'Zulu Label'])
        ->assertSet('sortBy', 'sort_order')
        ->assertSet('sortDirection', 'asc');
});

test('roles index can sort by localized label', function () {
    $labelColumn = Role::localizedLabelColumn();

    Role::factory()->create(['name' => 'zulu-role', 'en_label' => 'Zulu Role', 'es_label' => 'Zulu Role']);
    Role::factory()->create(['name' => 'alpha-role', 'en_label' => 'Alpha Role', 'es_label' => 'Alpha Role']);

    rolesIndexComponent()
        ->call('sort', $labelColumn)
        ->assertSeeInOrder(['Alpha Role', 'Zulu Role'])
        ->assertSet('sortBy', $labelColumn)
        ->assertSet('sortDirection', 'asc')
        ->call('sort', $labelColumn)
        ->assertSeeInOrder(['Zulu Role', 'Alpha Role'])
        ->assertSet('sortDirection', 'desc');
});

test('roles index search filters by name', function () {
    Role::factory()->create(['name' => 'editor', 'en_label' => 'Editor Label', 'es_label' => 'Editor Label']);
    Role::factory()->create(['name' => 'viewer', 'en_label' => 'Viewer Label', 'es_label' => 'Viewer Label']);

    rolesIndexComponent()
        ->set('search', 'editor')
        ->assertSee('Editor Label')
        ->assertDontSee('Viewer Label');
});

test('admin can open the role create modal from the roles index', function () {
    $component = rolesIndexComponent()
        ->call('openCreateRoleModal');

    expect(collect(data_get($component->effects, 'dispatches', []))->contains(function (array $dispatch): bool {
        return ($dispatch['name'] ?? null) === 'open-form-modal'
            && ($dispatch['params']['name'] ?? null) === 'roles.create'
            && ($dispatch['params']['title'] ?? null) === __('roles.create.title')
            && ($dispatch['params']['description'] ?? null) === __('roles.create.description');
    }))->toBeTrue();
});

test('admin can create a role from the create modal', function () {
    Livewire::test('roles.create-role-form')
        ->assertSet('is_active', true)
        ->assertSet('sort_order', 999)
        ->set('name', 'new-role')
        ->set('en_label', 'New Role')
        ->set('es_label', 'Nuevo Rol')
        ->set('color', 'blue')
        ->set('sort_order', 100)
        ->call('save')
        ->assertSet('name', '')
        ->assertSet('en_label', '')
        ->assertSet('es_label', '')
        ->assertSet('color', 'zinc')
        ->assertSet('sort_order', 999)
        ->assertSet('is_active', true)
        ->assertDispatched('close-form-modal')
        ->assertDispatched('role-created');

    $created = Role::query()->where('name', 'new-role')->first();

    expect($created)->not->toBeNull()
        ->and($created?->en_label)->toBe('New Role')
        ->and($created?->es_label)->toBe('Nuevo Rol')
        ->and($created?->color)->toBe('blue')
        ->and($created?->sort_order)->toBe(100)
        ->and($created?->guard_name)->toBe('web')
        ->and($created?->is_active)->toBeTrue();
});

test('create form validates slug format for name', function () {
    Livewire::test('roles.create-role-form')
        ->set('name', 'Invalid Name!')
        ->set('en_label', 'Test')
        ->set('es_label', 'Test')
        ->set('color', 'blue')
        ->set('sort_order', 1)
        ->call('save')
        ->assertHasErrors(['name'])
        ->assertNotDispatched('role-created');
});

test('create form validates duplicate name', function () {
    Role::factory()->create(['name' => 'existing-role']);

    Livewire::test('roles.create-role-form')
        ->set('name', 'existing-role')
        ->set('en_label', 'Test')
        ->set('es_label', 'Test')
        ->set('color', 'blue')
        ->set('sort_order', 1)
        ->call('save')
        ->assertHasErrors(['name'])
        ->assertNotDispatched('role-created');
});

test('create form validates required fields', function () {
    Livewire::test('roles.create-role-form')
        ->set('name', '')
        ->set('en_label', '')
        ->set('es_label', '')
        ->call('save')
        ->assertHasErrors(['name', 'en_label', 'es_label'])
        ->assertNotDispatched('role-created');
});

test('admin can toggle role active status', function () {
    $role = Role::factory()->create([
        'name' => 'toggle-me',
        'is_active' => false,
    ]);

    rolesIndexComponent()
        ->call('toggleRoleActiveStatus', $role->id, true)
        ->assertDispatched('toast-show', function (string $event, array $params) {
            return $event === 'toast-show'
                && ($params['dataset']['variant'] ?? null) === 'success';
        });

    expect($role->fresh()->is_active)->toBeTrue();
});

test('admin can delete a role from the roles index', function () {
    $role = Role::factory()->create(['name' => 'delete-me']);

    rolesIndexComponent()
        ->call('confirmRoleDeletion', $role->id)
        ->assertSet('roleIdPendingDeletion', $role->id)
        ->assertDispatched('open-confirm-modal', function (string $event, array $params) {
            return ($params['title'] ?? null) === __('roles.index.confirm_delete.title')
                && ($params['variant'] ?? null) === ModalService::VARIANT_PASSWORD;
        })
        ->dispatch('modal-confirmed')
        ->assertSet('roleIdPendingDeletion', null);

    expect(Role::query()->find($role->id))->toBeNull();
});

test('role with assigned users cannot be deleted', function () {
    $role = Role::factory()->create(['name' => 'has-users', 'is_active' => true]);

    $user = User::factory()->create();
    $user->assignRole($role);

    rolesIndexComponent()
        ->call('confirmRoleDeletion', $role->id)
        ->dispatch('modal-confirmed')
        ->assertStatus(409);

    expect(Role::query()->find($role->id))->not->toBeNull();
});

test('role with assigned users cannot be deactivated via toggle', function () {
    $role = Role::factory()->create(['name' => 'active-with-users', 'is_active' => true]);

    $user = User::factory()->create();
    $user->assignRole($role);

    rolesIndexComponent()
        ->call('toggleRoleActiveStatus', $role->id, false)
        ->assertStatus(409);

    expect($role->fresh()->is_active)->toBeTrue();
});

test('role with assigned users can be activated via toggle', function () {
    $role = Role::factory()->create(['name' => 'inactive-with-users', 'is_active' => false]);

    $user = User::factory()->create();
    $user->assignRole($role);

    rolesIndexComponent()
        ->call('toggleRoleActiveStatus', $role->id, true)
        ->assertDispatched('toast-show');

    expect($role->fresh()->is_active)->toBeTrue();
});

test('delete action is hidden for roles with assigned users', function () {
    $role = Role::factory()->create(['name' => 'has-users']);

    $user = User::factory()->create();
    $user->assignRole($role);

    rolesIndexComponent()
        ->assertDontSeeHtml('wire:click="confirmRoleDeletion('.$role->id.')');
});

test('system roles cannot be deleted', function () {
    $adminRole = Role::query()->where('name', RoleConfig::adminRole())->first();

    rolesIndexComponent()
        ->call('confirmRoleDeletion', $adminRole->id)
        ->dispatch('modal-confirmed')
        ->assertForbidden();
});

test('roles index clears pending deletion when confirm modal is cancelled', function () {
    $role = Role::factory()->create();

    rolesIndexComponent()
        ->call('confirmRoleDeletion', $role->id)
        ->assertSet('roleIdPendingDeletion', $role->id)
        ->dispatch('modal-confirm-cancelled')
        ->assertSet('roleIdPendingDeletion', null);
});

test('roles index resets page when a role is created', function () {
    Role::factory()->count(15)->create();

    rolesIndexComponent()
        ->set('perPage', 10)
        ->call('gotoPage', 2)
        ->assertSet('paginators.page', 2)
        ->dispatch('role-created')
        ->assertSet('paginators.page', 1);
});

// --- Rate limiting tests ---

test('index toggle active status is rate limited', function () {
    $role = Role::factory()->create(['is_active' => false]);

    $component = rolesIndexComponent();

    for ($i = 0; $i < 10; $i++) {
        RateLimiter::hit("role-mgmt:toggle-active:{$this->app['auth']->id()}", 60);
    }

    $component->call('toggleRoleActiveStatus', $role->id, true)
        ->assertStatus(429);
});

test('index delete confirmation is rate limited', function () {
    $role = Role::factory()->create();

    $component = rolesIndexComponent();

    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit("role-mgmt:delete:{$this->app['auth']->id()}", 60);
    }

    $component->call('confirmRoleDeletion', $role->id)
        ->assertStatus(429);
});

test('create form save is rate limited', function () {
    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit("role-mgmt:create:{$this->app['auth']->id()}", 60);
    }

    Livewire::test('roles.create-role-form')
        ->set('name', 'rate-limited')
        ->set('en_label', 'Rate Limited')
        ->set('es_label', 'Limitado')
        ->set('color', 'blue')
        ->set('sort_order', 1)
        ->call('save')
        ->assertStatus(429);

    expect(Role::query()->where('name', 'rate-limited')->exists())->toBeFalse();
});

// --- Validation boundary tests ---

test('create form rejects negative sort_order', function () {
    Livewire::test('roles.create-role-form')
        ->set('name', 'negative-order')
        ->set('en_label', 'Negative Order')
        ->set('es_label', 'Orden Negativo')
        ->set('color', 'blue')
        ->set('sort_order', -1)
        ->call('save')
        ->assertHasErrors(['sort_order'])
        ->assertNotDispatched('role-created');
});

test('create form clears field validation error when user corrects the field', function () {
    Livewire::test('roles.create-role-form')
        ->set('name', '')
        ->set('en_label', '')
        ->set('es_label', '')
        ->call('save')
        ->assertHasErrors(['name', 'en_label'])
        ->set('name', 'fixed-name')
        ->assertHasNoErrors(['name'])
        ->set('en_label', 'Fixed')
        ->assertHasNoErrors(['en_label']);
});

// --- Abort path tests ---

test('index deleteRole aborts 404 when no pending deletion exists', function () {
    rolesIndexComponent()
        ->dispatch('modal-confirmed')
        ->assertNotFound();
});

test('index toggleRoleActiveStatus throws on non-existent ID', function () {
    rolesIndexComponent()
        ->call('toggleRoleActiveStatus', 999999, true);
})->throws(ModelNotFoundException::class);

test('roles pages render successfully as livewire components', function () {
    rolesIndexComponent()
        ->assertOk()
        ->assertSee(__('roles.index.title'));

    Livewire::test('roles.create-role-form')
        ->assertOk()
        ->assertSee(__('roles.create.fields.name'))
        ->assertSee(__('roles.create.fields.en_label'))
        ->assertSee(__('roles.create.fields.es_label'))
        ->assertSee(__('roles.create.fields.color'))
        ->assertSee(__('roles.create.fields.sort_order'))
        ->assertSee(__('roles.create.submit'));

    $showRole = Role::factory()->create();

    Livewire::test('pages::roles.show', ['role' => (string) $showRole->id])
        ->assertOk()
        ->assertSee(__('roles.show.placeholder_title'))
        ->assertSee($showRole->en_label);
});

test('roles index search input defines non-auth autofill metadata', function () {
    rolesIndexComponent()
        ->assertSeeHtml('name="roles_search"')
        ->assertSeeHtml('id="roles-search"')
        ->assertSeeHtml('autocomplete="off"');
});
