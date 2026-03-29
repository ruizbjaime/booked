<?php

use App\Actions\BathRoomTypes\UpdateBathRoomType;
use App\Models\BathRoomType;
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

test('renders show page with bathroom type details', function () {
    $bathRoomType = BathRoomType::factory()->create([
        'name' => 'private-bathroom',
        'en_name' => 'Private Bathroom',
        'es_name' => 'Bano privado',
        'description' => 'Exclusive bathroom inside the room.',
        'sort_order' => 10,
    ]);

    Livewire::test('pages::bath-room-types.show', ['bathRoomType' => (string) $bathRoomType->id])
        ->assertOk()
        ->assertSee('Private Bathroom')
        ->assertSee('private-bathroom')
        ->assertSee('Exclusive bathroom inside the room.');
});

test('autosaves field changes', function () {
    $bathRoomType = BathRoomType::factory()->create([
        'en_name' => 'Old Name',
    ]);

    Livewire::test('pages::bath-room-types.show', ['bathRoomType' => (string) $bathRoomType->id])
        ->call('startEditingSection', 'details')
        ->set('en_name', 'New Name')
        ->assertDispatched('toast-show', function (string $event, array $params) {
            return ($params['dataset']['variant'] ?? null) === 'success';
        });

    expect($bathRoomType->fresh()->en_name)->toBe('New Name');
});

test('autosave normalizes slug to lowercase', function () {
    $bathRoomType = BathRoomType::factory()->create(['name' => 'old-bathroom']);

    Livewire::test('pages::bath-room-types.show', ['bathRoomType' => (string) $bathRoomType->id])
        ->call('startEditingSection', 'details')
        ->set('name', 'PRIVATE-BATHROOM')
        ->assertDispatched('toast-show');

    expect($bathRoomType->fresh()->name)->toBe('private-bathroom');
});

test('validates unique slug on autosave', function () {
    BathRoomType::factory()->create(['name' => 'private-bathroom']);

    $bathRoomType = BathRoomType::factory()->create(['name' => 'shared-bathroom']);

    Livewire::test('pages::bath-room-types.show', ['bathRoomType' => (string) $bathRoomType->id])
        ->call('startEditingSection', 'details')
        ->set('name', 'private-bathroom')
        ->assertHasErrors(['name']);

    expect($bathRoomType->fresh()->name)->toBe('shared-bathroom');
});

test('validates sort order on autosave', function () {
    $bathRoomType = BathRoomType::factory()->create(['sort_order' => 10]);

    Livewire::test('pages::bath-room-types.show', ['bathRoomType' => (string) $bathRoomType->id])
        ->call('startEditingSection', 'details')
        ->set('sort_order', -1)
        ->assertHasErrors(['sort_order']);

    expect($bathRoomType->fresh()->sort_order)->toBe(10);
});

test('validates localized labels on autosave', function (string $field) {
    $bathRoomType = BathRoomType::factory()->create([
        'en_name' => 'Original EN',
        'es_name' => 'Original ES',
    ]);

    Livewire::test('pages::bath-room-types.show', ['bathRoomType' => (string) $bathRoomType->id])
        ->call('startEditingSection', 'details')
        ->set($field, '')
        ->assertHasErrors([$field]);

    expect($bathRoomType->fresh()->{$field})->not->toBe('');
})->with(['en_name', 'es_name']);

test('validates description on autosave', function () {
    $bathRoomType = BathRoomType::factory()->create(['description' => 'Original description']);

    Livewire::test('pages::bath-room-types.show', ['bathRoomType' => (string) $bathRoomType->id])
        ->call('startEditingSection', 'details')
        ->set('description', '')
        ->assertHasErrors(['description']);

    expect($bathRoomType->fresh()->description)->toBe('Original description');
});

test('delete confirmation and redirect', function () {
    $bathRoomType = BathRoomType::factory()->create([
        'en_name' => 'Delete Me',
    ]);

    Livewire::test('pages::bath-room-types.show', ['bathRoomType' => (string) $bathRoomType->id])
        ->call('confirmBathRoomTypeDeletion')
        ->assertSet('bathRoomTypeIdPendingDeletion', $bathRoomType->id)
        ->dispatch('modal-confirmed')
        ->assertRedirect(route('bath-room-types.index'));

    expect(BathRoomType::query()->find($bathRoomType->id))->toBeNull();
});

test('non-admin cannot view show page', function () {
    $bathRoomType = BathRoomType::factory()->create();

    $this->actingAs(makeGuest());

    Livewire::test('pages::bath-room-types.show', ['bathRoomType' => (string) $bathRoomType->id])
        ->assertForbidden();
});

test('cancel editing section restores original values and clears validation', function () {
    BathRoomType::factory()->create(['name' => 'private-bathroom']);

    $bathRoomType = BathRoomType::factory()->create([
        'name' => 'shared-bathroom',
    ]);

    Livewire::test('pages::bath-room-types.show', ['bathRoomType' => (string) $bathRoomType->id])
        ->call('startEditingSection', 'details')
        ->set('name', 'private-bathroom')
        ->assertHasErrors(['name'])
        ->call('cancelEditingSection')
        ->assertSet('name', 'shared-bathroom')
        ->assertSet('editingSection', null)
        ->assertHasNoErrors();
});

test('autosave does not trigger without active editing section', function () {
    $bathRoomType = BathRoomType::factory()->create([
        'en_name' => 'Unchanged',
    ]);

    Livewire::test('pages::bath-room-types.show', ['bathRoomType' => (string) $bathRoomType->id])
        ->assertSet('editingSection', null)
        ->set('en_name', 'Should Not Save')
        ->assertNotDispatched('toast-show');

    expect($bathRoomType->fresh()->en_name)->toBe('Unchanged');
});

test('start editing section with invalid section returns 404', function () {
    $bathRoomType = BathRoomType::factory()->create();

    Livewire::test('pages::bath-room-types.show', ['bathRoomType' => (string) $bathRoomType->id])
        ->call('startEditingSection', 'nonexistent')
        ->assertNotFound();
});

test('show page autosave is rate limited', function () {
    $bathRoomType = BathRoomType::factory()->create();

    $component = Livewire::test('pages::bath-room-types.show', ['bathRoomType' => (string) $bathRoomType->id])
        ->call('startEditingSection', 'details');

    for ($i = 0; $i < 10; $i++) {
        RateLimiter::hit("bath-room-type-mgmt:autosave:{$this->app['auth']->id()}", 60);
    }

    $component->set('en_name', 'Rate Limited Name')
        ->assertDispatched('open-info-modal');
});

test('show page delete confirmation is rate limited', function () {
    $bathRoomType = BathRoomType::factory()->create();

    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit("bath-room-type-mgmt:delete:{$this->app['auth']->id()}", 60);
    }

    Livewire::test('pages::bath-room-types.show', ['bathRoomType' => (string) $bathRoomType->id])
        ->call('confirmBathRoomTypeDeletion')
        ->assertDispatched('open-info-modal');
});

test('show page modal-confirmed is rate limited', function () {
    $bathRoomType = BathRoomType::factory()->create();

    $component = Livewire::test('pages::bath-room-types.show', ['bathRoomType' => (string) $bathRoomType->id])
        ->call('confirmBathRoomTypeDeletion');

    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit("bath-room-type-mgmt:confirmed-action:{$this->app['auth']->id()}", 60);
    }

    $component->dispatch('modal-confirmed')
        ->assertDispatched('open-info-modal');

    expect(BathRoomType::query()->find($bathRoomType->id))->not->toBeNull();
});

test('show page canEdit and canDelete render actions for admin', function () {
    $bathRoomType = BathRoomType::factory()->create();

    Livewire::test('pages::bath-room-types.show', ['bathRoomType' => (string) $bathRoomType->id])
        ->assertSeeHtml('wire:click="startEditingSection')
        ->assertSeeHtml('wire:click="confirmBathRoomTypeDeletion');
});

test('show page reports edit and delete capabilities as false for users without update or delete permissions', function () {
    $role = Role::factory()->create(['name' => 'bath-room-type-viewer']);
    $role->givePermissionTo('bath_room_type.viewAny', 'bath_room_type.view');

    $user = User::factory()->create();
    $user->assignRole($role);

    $bathRoomType = BathRoomType::factory()->create();

    $this->actingAs($user);

    $component = Livewire::test('pages::bath-room-types.show', ['bathRoomType' => (string) $bathRoomType->id])
        ->assertOk();

    expect($component->instance()->canEdit())->toBeFalse()
        ->and($component->instance()->canDelete())->toBeFalse();
});

test('show page forbids editing for users without update permission', function () {
    $role = Role::factory()->create(['name' => 'bath-room-type-viewer-edit-blocked']);
    $role->givePermissionTo('bath_room_type.viewAny', 'bath_room_type.view');

    $user = User::factory()->create();
    $user->assignRole($role);

    $bathRoomType = BathRoomType::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::bath-room-types.show', ['bathRoomType' => (string) $bathRoomType->id])
        ->call('startEditingSection', 'details')
        ->assertForbidden();
});

test('show page forbids deleting for users without delete permission', function () {
    $role = Role::factory()->create(['name' => 'bath-room-type-viewer-delete-blocked']);
    $role->givePermissionTo('bath_room_type.viewAny', 'bath_room_type.view');

    $user = User::factory()->create();
    $user->assignRole($role);

    $bathRoomType = BathRoomType::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::bath-room-types.show', ['bathRoomType' => (string) $bathRoomType->id])
        ->call('confirmBathRoomTypeDeletion')
        ->assertForbidden();
});

test('show page clears pending deletion when confirm modal is cancelled', function () {
    $bathRoomType = BathRoomType::factory()->create();

    Livewire::test('pages::bath-room-types.show', ['bathRoomType' => (string) $bathRoomType->id])
        ->call('confirmBathRoomTypeDeletion')
        ->assertSet('bathRoomTypeIdPendingDeletion', $bathRoomType->id)
        ->dispatch('modal-confirm-cancelled')
        ->assertSet('bathRoomTypeIdPendingDeletion', null);
});

test('show page ignores modal-confirmed when no deletion is pending', function () {
    $bathRoomType = BathRoomType::factory()->create();

    Livewire::test('pages::bath-room-types.show', ['bathRoomType' => (string) $bathRoomType->id])
        ->dispatch('modal-confirmed')
        ->assertNoRedirect();

    expect(BathRoomType::query()->find($bathRoomType->id))->not->toBeNull();
});

test('show page mount returns 404 for non-existent bathroom type', function () {
    $this->get('/bath-room-types/999999')
        ->assertNotFound();
});

test('update action aborts 422 for unknown field name', function () {
    $admin = makeAdmin();
    $bathRoomType = BathRoomType::factory()->create();

    $action = app(UpdateBathRoomType::class);

    try {
        $action->handle($admin, $bathRoomType, 'nonexistent', 'value');
        $this->fail('Expected abort 422');
    } catch (HttpException $e) {
        expect($e->getStatusCode())->toBe(422);
    }
});
