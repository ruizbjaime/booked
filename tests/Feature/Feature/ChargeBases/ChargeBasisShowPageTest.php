<?php

use App\Models\ChargeBasis;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->actingAs(makeAdmin());
});

test('renders show page with charge basis details', function () {
    $chargeBasis = ChargeBasis::factory()->create([
        'name' => 'per_child',
        'en_name' => 'Per Child',
        'es_name' => 'Por menor',
        'metadata' => ['requires_quantity' => true, 'quantity_subject' => 'guest'],
    ]);

    Livewire::test('pages::charge-bases.show', ['chargeBasis' => (string) $chargeBasis->id])
        ->assertOk()
        ->assertSee('Per Child')
        ->assertSee('per_child')
        ->assertSee(__('charge_bases.quantity_subjects.guest'));
});

test('autosaves charge basis field changes', function () {
    $chargeBasis = ChargeBasis::factory()->create(['en_name' => 'Old Name']);

    Livewire::test('pages::charge-bases.show', ['chargeBasis' => (string) $chargeBasis->id])
        ->call('startEditingSection', 'details')
        ->set('en_name', 'New Name')
        ->assertDispatched('toast-show');

    expect($chargeBasis->fresh()->en_name)->toBe('New Name');
});

test('autosaves quantity metadata changes', function () {
    $chargeBasis = ChargeBasis::factory()->create(['metadata' => ['requires_quantity' => false, 'quantity_subject' => null]]);

    $component = Livewire::test('pages::charge-bases.show', ['chargeBasis' => (string) $chargeBasis->id])
        ->call('startEditingSection', 'configuration');

    $component->set('requires_quantity', true);

    expect($chargeBasis->fresh()->metadata['requires_quantity'])->toBeTrue();

    $component->set('quantity_subject', 'pet')
        ->assertDispatched('toast-show');

    expect($chargeBasis->fresh()->metadata['requires_quantity'])->toBeTrue()
        ->and($chargeBasis->fresh()->metadata['quantity_subject'])->toBe('pet');
});

test('quantity subject resets when quantity requirement is disabled', function () {
    $chargeBasis = ChargeBasis::factory()->create(['metadata' => ['requires_quantity' => true, 'quantity_subject' => 'guest']]);

    $component = Livewire::test('pages::charge-bases.show', ['chargeBasis' => (string) $chargeBasis->id])
        ->call('startEditingSection', 'configuration')
        ->assertSet('quantity_subject', 'guest');

    $component->set('requires_quantity', false);

    expect($chargeBasis->fresh()->metadata['requires_quantity'])->toBeFalse()
        ->and($chargeBasis->fresh()->metadata['quantity_subject'])->toBeNull();
});

test('delete confirmation and redirect works', function () {
    $chargeBasis = ChargeBasis::factory()->create(['en_name' => 'Delete me']);

    Livewire::test('pages::charge-bases.show', ['chargeBasis' => (string) $chargeBasis->id])
        ->call('confirmChargeBasisDeletion')
        ->assertSet('chargeBasisIdPendingDeletion', $chargeBasis->id)
        ->dispatch('modal-confirmed')
        ->assertRedirect(route('charge-bases.index'));

    expect(ChargeBasis::query()->find($chargeBasis->id))->toBeNull();
});

test('non-admin cannot view charge basis show page', function () {
    $chargeBasis = ChargeBasis::factory()->create();

    $this->actingAs(makeGuest());

    Livewire::test('pages::charge-bases.show', ['chargeBasis' => (string) $chargeBasis->id])
        ->assertForbidden();
});

test('show page forbids editing for users without update permission', function () {
    $role = Role::factory()->create(['name' => 'charge-basis-viewer-edit-blocked']);
    $role->givePermissionTo('charge_basis.viewAny', 'charge_basis.view');

    $user = User::factory()->create();
    $user->assignRole($role);

    $chargeBasis = ChargeBasis::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::charge-bases.show', ['chargeBasis' => (string) $chargeBasis->id])
        ->call('startEditingSection', 'details')
        ->assertForbidden();
});

test('show page autosave is rate limited', function () {
    $chargeBasis = ChargeBasis::factory()->create();

    $component = Livewire::test('pages::charge-bases.show', ['chargeBasis' => (string) $chargeBasis->id])
        ->call('startEditingSection', 'details');

    for ($i = 0; $i < 10; $i++) {
        RateLimiter::hit('charge-basis-mgmt:autosave:'.app('auth')->id(), 60);
    }

    $component->set('en_name', 'Rate limited name')
        ->assertStatus(429);
});

test('cancel editing section restores original values and clears validation', function () {
    ChargeBasis::factory()->create(['name' => 'existing_slug']);

    $chargeBasis = ChargeBasis::factory()->create([
        'name' => 'original_slug',
    ]);

    Livewire::test('pages::charge-bases.show', ['chargeBasis' => (string) $chargeBasis->id])
        ->call('startEditingSection', 'details')
        ->set('name', 'existing_slug')
        ->assertHasErrors(['name'])
        ->call('cancelEditingSection')
        ->assertSet('name', 'original_slug')
        ->assertSet('editingSection', null)
        ->assertHasNoErrors();
});

test('autosave does not trigger without active editing section', function () {
    $chargeBasis = ChargeBasis::factory()->create([
        'en_name' => 'Unchanged',
    ]);

    Livewire::test('pages::charge-bases.show', ['chargeBasis' => (string) $chargeBasis->id])
        ->assertSet('editingSection', null)
        ->set('en_name', 'Should not save')
        ->assertNotDispatched('toast-show');

    expect($chargeBasis->fresh()->en_name)->toBe('Unchanged');
});

test('start editing section with invalid section returns 404', function () {
    $chargeBasis = ChargeBasis::factory()->create();

    Livewire::test('pages::charge-bases.show', ['chargeBasis' => (string) $chargeBasis->id])
        ->call('startEditingSection', 'nonexistent')
        ->assertNotFound();
});

test('show page canEdit and canDelete render actions for admin', function () {
    $chargeBasis = ChargeBasis::factory()->create();

    Livewire::test('pages::charge-bases.show', ['chargeBasis' => (string) $chargeBasis->id])
        ->assertSeeHtml('wire:click="startEditingSection')
        ->assertSeeHtml('wire:click="confirmChargeBasisDeletion');
});

test('show page reports edit and delete capabilities as false for users without update or delete permissions', function () {
    $role = Role::factory()->create(['name' => 'charge-basis-viewer']);
    $role->givePermissionTo('charge_basis.viewAny', 'charge_basis.view');

    $user = User::factory()->create();
    $user->assignRole($role);

    $chargeBasis = ChargeBasis::factory()->create();

    $this->actingAs($user);

    $component = Livewire::test('pages::charge-bases.show', ['chargeBasis' => (string) $chargeBasis->id])
        ->assertOk();

    expect($component->instance()->canEdit())->toBeFalse()
        ->and($component->instance()->canDelete())->toBeFalse();
});

test('show page forbids deleting for users without delete permission', function () {
    $role = Role::factory()->create(['name' => 'charge-basis-viewer-delete-blocked']);
    $role->givePermissionTo('charge_basis.viewAny', 'charge_basis.view');

    $user = User::factory()->create();
    $user->assignRole($role);

    $chargeBasis = ChargeBasis::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::charge-bases.show', ['chargeBasis' => (string) $chargeBasis->id])
        ->call('confirmChargeBasisDeletion')
        ->assertForbidden();
});

test('show page clears pending deletion when confirm modal is cancelled', function () {
    $chargeBasis = ChargeBasis::factory()->create();

    Livewire::test('pages::charge-bases.show', ['chargeBasis' => (string) $chargeBasis->id])
        ->call('confirmChargeBasisDeletion')
        ->assertSet('chargeBasisIdPendingDeletion', $chargeBasis->id)
        ->dispatch('modal-confirm-cancelled')
        ->assertSet('chargeBasisIdPendingDeletion', null);
});

test('show page ignores modal-confirmed when no deletion is pending', function () {
    $chargeBasis = ChargeBasis::factory()->create();

    Livewire::test('pages::charge-bases.show', ['chargeBasis' => (string) $chargeBasis->id])
        ->dispatch('modal-confirmed')
        ->assertNoRedirect();

    expect(ChargeBasis::query()->find($chargeBasis->id))->not->toBeNull();
});

test('show page delete confirmation is rate limited', function () {
    $chargeBasis = ChargeBasis::factory()->create();

    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit('charge-basis-mgmt:delete:'.app('auth')->id(), 60);
    }

    Livewire::test('pages::charge-bases.show', ['chargeBasis' => (string) $chargeBasis->id])
        ->call('confirmChargeBasisDeletion')
        ->assertStatus(429);
});

test('show page modal-confirmed is rate limited', function () {
    $chargeBasis = ChargeBasis::factory()->create();

    $component = Livewire::test('pages::charge-bases.show', ['chargeBasis' => (string) $chargeBasis->id])
        ->call('confirmChargeBasisDeletion');

    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit('charge-basis-mgmt:confirmed-action:'.app('auth')->id(), 60);
    }

    $component->dispatch('modal-confirmed')
        ->assertStatus(429);

    expect(ChargeBasis::query()->find($chargeBasis->id))->not->toBeNull();
});

test('show page mount returns 404 for non-existent charge basis', function () {
    $this->get('/charge-bases/999999')
        ->assertNotFound();
});

test('validates unique slug on autosave', function () {
    ChargeBasis::factory()->create(['name' => 'existing_slug']);

    $chargeBasis = ChargeBasis::factory()->create(['name' => 'original_slug']);

    Livewire::test('pages::charge-bases.show', ['chargeBasis' => (string) $chargeBasis->id])
        ->call('startEditingSection', 'details')
        ->set('name', 'existing_slug')
        ->assertHasErrors(['name']);

    expect($chargeBasis->fresh()->name)->toBe('original_slug');
});

test('validates order on autosave', function () {
    $chargeBasis = ChargeBasis::factory()->create(['order' => 10]);

    Livewire::test('pages::charge-bases.show', ['chargeBasis' => (string) $chargeBasis->id])
        ->call('startEditingSection', 'details')
        ->set('order', -1)
        ->assertHasErrors(['order']);

    expect($chargeBasis->fresh()->order)->toBe(10);
});

test('validates localized labels on autosave', function (string $field) {
    $chargeBasis = ChargeBasis::factory()->create([
        'en_name' => 'Original EN',
        'es_name' => 'Original ES',
    ]);

    Livewire::test('pages::charge-bases.show', ['chargeBasis' => (string) $chargeBasis->id])
        ->call('startEditingSection', 'details')
        ->set($field, '')
        ->assertHasErrors([$field]);

    expect($chargeBasis->fresh()->{$field})->not->toBe('');
})->with(['en_name', 'es_name']);
