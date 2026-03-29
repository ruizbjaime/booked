<?php

use App\Actions\BedTypes\UpdateBedType;
use App\Models\BedType;
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

test('renders show page with bed type details', function () {
    $bedType = BedType::factory()->create([
        'name' => 'king-bed',
        'en_name' => 'King Bed',
        'es_name' => 'Cama King',
        'bed_capacity' => 2,
        'sort_order' => 10,
    ]);

    Livewire::test('pages::bed-types.show', ['bedType' => (string) $bedType->id])
        ->assertOk()
        ->assertSee('King Bed')
        ->assertSee('king-bed')
        ->assertSee('2');
});

test('autosaves field changes', function () {
    $bedType = BedType::factory()->create([
        'en_name' => 'Old Name',
    ]);

    Livewire::test('pages::bed-types.show', ['bedType' => (string) $bedType->id])
        ->call('startEditingSection', 'details')
        ->set('en_name', 'New Name')
        ->assertDispatched('toast-show', function (string $event, array $params) {
            return ($params['dataset']['variant'] ?? null) === 'success';
        });

    expect($bedType->fresh()->en_name)->toBe('New Name');
});

test('autosave normalizes slug to lowercase', function () {
    $bedType = BedType::factory()->create(['name' => 'old-bed']);

    Livewire::test('pages::bed-types.show', ['bedType' => (string) $bedType->id])
        ->call('startEditingSection', 'details')
        ->set('name', 'KING-BED')
        ->assertDispatched('toast-show');

    expect($bedType->fresh()->name)->toBe('king-bed');
});

test('validates unique slug on autosave', function () {
    BedType::factory()->create(['name' => 'king-bed']);

    $bedType = BedType::factory()->create(['name' => 'single-bed']);

    Livewire::test('pages::bed-types.show', ['bedType' => (string) $bedType->id])
        ->call('startEditingSection', 'details')
        ->set('name', 'king-bed')
        ->assertHasErrors(['name']);

    expect($bedType->fresh()->name)->toBe('single-bed');
});

test('validates bed capacity on autosave', function () {
    $bedType = BedType::factory()->create(['bed_capacity' => 2]);

    Livewire::test('pages::bed-types.show', ['bedType' => (string) $bedType->id])
        ->call('startEditingSection', 'details')
        ->set('bed_capacity', 0)
        ->assertHasErrors(['bed_capacity']);

    expect($bedType->fresh()->bed_capacity)->toBe(2);
});

test('validates sort order on autosave', function () {
    $bedType = BedType::factory()->create(['sort_order' => 10]);

    Livewire::test('pages::bed-types.show', ['bedType' => (string) $bedType->id])
        ->call('startEditingSection', 'details')
        ->set('sort_order', -1)
        ->assertHasErrors(['sort_order']);

    expect($bedType->fresh()->sort_order)->toBe(10);
});

test('validates localized labels on autosave', function (string $field) {
    $bedType = BedType::factory()->create([
        'en_name' => 'Original EN',
        'es_name' => 'Original ES',
    ]);

    Livewire::test('pages::bed-types.show', ['bedType' => (string) $bedType->id])
        ->call('startEditingSection', 'details')
        ->set($field, '')
        ->assertHasErrors([$field]);

    expect($bedType->fresh()->{$field})->not->toBe('');
})->with(['en_name', 'es_name']);

test('delete confirmation and redirect', function () {
    $bedType = BedType::factory()->create([
        'en_name' => 'Delete Me',
    ]);

    Livewire::test('pages::bed-types.show', ['bedType' => (string) $bedType->id])
        ->call('confirmBedTypeDeletion')
        ->assertSet('bedTypeIdPendingDeletion', $bedType->id)
        ->dispatch('modal-confirmed')
        ->assertRedirect(route('bed-types.index'));

    expect(BedType::query()->find($bedType->id))->toBeNull();
});

test('non-admin cannot view show page', function () {
    $bedType = BedType::factory()->create();

    $this->actingAs(makeGuest());

    Livewire::test('pages::bed-types.show', ['bedType' => (string) $bedType->id])
        ->assertForbidden();
});

test('cancel editing section restores original values and clears validation', function () {
    BedType::factory()->create(['name' => 'king-bed']);

    $bedType = BedType::factory()->create([
        'name' => 'single-bed',
    ]);

    Livewire::test('pages::bed-types.show', ['bedType' => (string) $bedType->id])
        ->call('startEditingSection', 'details')
        ->set('name', 'king-bed')
        ->assertHasErrors(['name'])
        ->call('cancelEditingSection')
        ->assertSet('name', 'single-bed')
        ->assertSet('editingSection', null)
        ->assertHasNoErrors();
});

test('autosave does not trigger without active editing section', function () {
    $bedType = BedType::factory()->create([
        'en_name' => 'Unchanged',
    ]);

    Livewire::test('pages::bed-types.show', ['bedType' => (string) $bedType->id])
        ->assertSet('editingSection', null)
        ->set('en_name', 'Should Not Save')
        ->assertNotDispatched('toast-show');

    expect($bedType->fresh()->en_name)->toBe('Unchanged');
});

test('start editing section with invalid section returns 404', function () {
    $bedType = BedType::factory()->create();

    Livewire::test('pages::bed-types.show', ['bedType' => (string) $bedType->id])
        ->call('startEditingSection', 'nonexistent')
        ->assertNotFound();
});

test('show page autosave is rate limited', function () {
    $bedType = BedType::factory()->create();

    $component = Livewire::test('pages::bed-types.show', ['bedType' => (string) $bedType->id])
        ->call('startEditingSection', 'details');

    for ($i = 0; $i < 10; $i++) {
        RateLimiter::hit("bed-type-mgmt:autosave:{$this->app['auth']->id()}", 60);
    }

    $component->set('en_name', 'Rate Limited Name')
        ->assertDispatched('open-info-modal');
});

test('show page delete confirmation is rate limited', function () {
    $bedType = BedType::factory()->create();

    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit("bed-type-mgmt:delete:{$this->app['auth']->id()}", 60);
    }

    Livewire::test('pages::bed-types.show', ['bedType' => (string) $bedType->id])
        ->call('confirmBedTypeDeletion')
        ->assertDispatched('open-info-modal');
});

test('show page modal-confirmed is rate limited', function () {
    $bedType = BedType::factory()->create();

    $component = Livewire::test('pages::bed-types.show', ['bedType' => (string) $bedType->id])
        ->call('confirmBedTypeDeletion');

    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit("bed-type-mgmt:confirmed-action:{$this->app['auth']->id()}", 60);
    }

    $component->dispatch('modal-confirmed')
        ->assertDispatched('open-info-modal');

    expect(BedType::query()->find($bedType->id))->not->toBeNull();
});

test('show page canEdit and canDelete render actions for admin', function () {
    $bedType = BedType::factory()->create();

    Livewire::test('pages::bed-types.show', ['bedType' => (string) $bedType->id])
        ->assertSeeHtml('wire:click="startEditingSection')
        ->assertSeeHtml('wire:click="confirmBedTypeDeletion');
});

test('show page reports edit and delete capabilities as false for users without update or delete permissions', function () {
    $role = Role::factory()->create(['name' => 'bed-type-viewer']);
    $role->givePermissionTo('bed_type.viewAny', 'bed_type.view');

    $user = User::factory()->create();
    $user->assignRole($role);

    $bedType = BedType::factory()->create();

    $this->actingAs($user);

    $component = Livewire::test('pages::bed-types.show', ['bedType' => (string) $bedType->id])
        ->assertOk();

    expect($component->instance()->canEdit())->toBeFalse()
        ->and($component->instance()->canDelete())->toBeFalse();
});

test('show page forbids editing for users without update permission', function () {
    $role = Role::factory()->create(['name' => 'bed-type-viewer-edit-blocked']);
    $role->givePermissionTo('bed_type.viewAny', 'bed_type.view');

    $user = User::factory()->create();
    $user->assignRole($role);

    $bedType = BedType::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::bed-types.show', ['bedType' => (string) $bedType->id])
        ->call('startEditingSection', 'details')
        ->assertForbidden();
});

test('show page forbids deleting for users without delete permission', function () {
    $role = Role::factory()->create(['name' => 'bed-type-viewer-delete-blocked']);
    $role->givePermissionTo('bed_type.viewAny', 'bed_type.view');

    $user = User::factory()->create();
    $user->assignRole($role);

    $bedType = BedType::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::bed-types.show', ['bedType' => (string) $bedType->id])
        ->call('confirmBedTypeDeletion')
        ->assertForbidden();
});

test('show page clears pending deletion when confirm modal is cancelled', function () {
    $bedType = BedType::factory()->create();

    Livewire::test('pages::bed-types.show', ['bedType' => (string) $bedType->id])
        ->call('confirmBedTypeDeletion')
        ->assertSet('bedTypeIdPendingDeletion', $bedType->id)
        ->dispatch('modal-confirm-cancelled')
        ->assertSet('bedTypeIdPendingDeletion', null);
});

test('show page ignores modal-confirmed when no deletion is pending', function () {
    $bedType = BedType::factory()->create();

    Livewire::test('pages::bed-types.show', ['bedType' => (string) $bedType->id])
        ->dispatch('modal-confirmed')
        ->assertNoRedirect();

    expect(BedType::query()->find($bedType->id))->not->toBeNull();
});

test('show page mount returns 404 for non-existent bed type', function () {
    $this->get('/bed-types/999999')
        ->assertNotFound();
});

test('update action aborts 422 for unknown field name', function () {
    $admin = makeAdmin();
    $bedType = BedType::factory()->create();

    $action = app(UpdateBedType::class);

    try {
        $action->handle($admin, $bedType, 'nonexistent', 'value');
        $this->fail('Expected abort 422');
    } catch (HttpException $e) {
        expect($e->getStatusCode())->toBe(422);
    }
});
