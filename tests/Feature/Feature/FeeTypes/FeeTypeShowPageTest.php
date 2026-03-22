<?php

use App\Actions\FeeTypes\UpdateFeeType;
use App\Models\ChargeBasis;
use App\Models\FeeType;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\ChargeBasisSeeder;
use Database\Seeders\FeeTypeChargeBasisSeeder;
use Database\Seeders\FeeTypeSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->seed([RolesAndPermissionsSeeder::class, ChargeBasisSeeder::class, FeeTypeSeeder::class, FeeTypeChargeBasisSeeder::class]);

    $this->actingAs(makeAdmin());
});

test('renders show page with fee type details', function () {
    FeeType::query()->where('name', 'cleaning-fee')->delete();

    $feeType = FeeType::factory()->create([
        'name' => 'cleaning-fee',
        'en_name' => 'Cleaning fee',
        'es_name' => 'Tarifa de limpieza',
        'order' => 10,
    ]);

    Livewire::test('pages::fee-types.show', ['feeType' => (string) $feeType->id])
        ->assertOk()
        ->assertSee('Cleaning fee')
        ->assertSee('cleaning-fee')
        ->assertSee('10');
});

test('autosaves field changes', function () {
    $feeType = FeeType::factory()->create([
        'en_name' => 'Old name',
    ]);

    Livewire::test('pages::fee-types.show', ['feeType' => (string) $feeType->id])
        ->call('startEditingSection', 'details')
        ->set('en_name', 'New name')
        ->assertDispatched('toast-show', function (string $event, array $params) {
            return ($params['dataset']['variant'] ?? null) === 'success';
        });

    expect($feeType->fresh()->en_name)->toBe('New name');
});

test('autosave normalizes slug to lowercase', function () {
    FeeType::query()->where('name', 'cleaning-fee')->delete();

    $feeType = FeeType::factory()->create(['name' => 'old-fee']);

    Livewire::test('pages::fee-types.show', ['feeType' => (string) $feeType->id])
        ->call('startEditingSection', 'details')
        ->set('name', 'CLEANING-FEE')
        ->assertDispatched('toast-show');

    expect($feeType->fresh()->name)->toBe('cleaning-fee');
});

test('show page renders charge bases section', function () {
    $feeType = FeeType::query()->where('name', 'pet-fee')->firstOrFail();
    $perPet = ChargeBasis::query()->where('name', 'per_pet')->firstOrFail();
    $perPetPerNight = ChargeBasis::query()->where('name', 'per_pet_per_night')->firstOrFail();

    Livewire::test('pages::fee-types.show', ['feeType' => (string) $feeType->id])
        ->assertSee(__('fee_types.show.sections.charge_bases'))
        ->assertSee($perPet->localizedName())
        ->assertSee($perPetPerNight->localizedName());
});

test('validates unique slug on autosave', function () {
    FeeType::query()->where('name', 'cleaning-fee')->delete();

    FeeType::factory()->create(['name' => 'cleaning-fee']);

    $feeType = FeeType::factory()->create(['name' => 'late-fee']);

    Livewire::test('pages::fee-types.show', ['feeType' => (string) $feeType->id])
        ->call('startEditingSection', 'details')
        ->set('name', 'cleaning-fee')
        ->assertHasErrors(['name']);

    expect($feeType->fresh()->name)->toBe('late-fee');
});

test('validates order on autosave', function () {
    $feeType = FeeType::factory()->create(['order' => 10]);

    Livewire::test('pages::fee-types.show', ['feeType' => (string) $feeType->id])
        ->call('startEditingSection', 'details')
        ->set('order', -1)
        ->assertHasErrors(['order']);

    expect($feeType->fresh()->order)->toBe(10);
});

test('validates localized labels on autosave', function (string $field) {
    $feeType = FeeType::factory()->create([
        'en_name' => 'Original EN',
        'es_name' => 'Original ES',
    ]);

    Livewire::test('pages::fee-types.show', ['feeType' => (string) $feeType->id])
        ->call('startEditingSection', 'details')
        ->set($field, '')
        ->assertHasErrors([$field]);

    expect($feeType->fresh()->{$field})->not->toBe('');
})->with(['en_name', 'es_name']);

test('delete confirmation and redirect', function () {
    $feeType = FeeType::factory()->create([
        'en_name' => 'Delete me',
    ]);

    Livewire::test('pages::fee-types.show', ['feeType' => (string) $feeType->id])
        ->call('confirmFeeTypeDeletion')
        ->assertSet('feeTypeIdPendingDeletion', $feeType->id)
        ->dispatch('modal-confirmed')
        ->assertRedirect(route('fee-types.index'));

    expect(FeeType::query()->find($feeType->id))->toBeNull();
});

test('non-admin cannot view show page', function () {
    $feeType = FeeType::factory()->create();

    $this->actingAs(makeGuest());

    Livewire::test('pages::fee-types.show', ['feeType' => (string) $feeType->id])
        ->assertForbidden();
});

test('cancel editing section restores original values and clears validation', function () {
    FeeType::query()->where('name', 'cleaning-fee')->delete();

    FeeType::factory()->create(['name' => 'cleaning-fee']);

    $feeType = FeeType::factory()->create([
        'name' => 'late-fee',
    ]);

    Livewire::test('pages::fee-types.show', ['feeType' => (string) $feeType->id])
        ->call('startEditingSection', 'details')
        ->set('name', 'cleaning-fee')
        ->assertHasErrors(['name'])
        ->call('cancelEditingSection')
        ->assertSet('name', 'late-fee')
        ->assertSet('editingSection', null)
        ->assertHasNoErrors();
});

test('autosave does not trigger without active editing section', function () {
    $feeType = FeeType::factory()->create([
        'en_name' => 'Unchanged',
    ]);

    Livewire::test('pages::fee-types.show', ['feeType' => (string) $feeType->id])
        ->assertSet('editingSection', null)
        ->set('en_name', 'Should not save')
        ->assertNotDispatched('toast-show');

    expect($feeType->fresh()->en_name)->toBe('Unchanged');
});

test('start editing section with invalid section returns 404', function () {
    $feeType = FeeType::factory()->create();

    Livewire::test('pages::fee-types.show', ['feeType' => (string) $feeType->id])
        ->call('startEditingSection', 'nonexistent')
        ->assertNotFound();
});

test('show page autosave is rate limited', function () {
    $feeType = FeeType::factory()->create();

    $component = Livewire::test('pages::fee-types.show', ['feeType' => (string) $feeType->id])
        ->call('startEditingSection', 'details');

    for ($i = 0; $i < 10; $i++) {
        RateLimiter::hit('fee-type-mgmt:autosave:'.app('auth')->id(), 60);
    }

    $component->set('en_name', 'Rate limited name')
        ->assertDispatched('open-info-modal');
});

test('show page delete confirmation is rate limited', function () {
    $feeType = FeeType::factory()->create();

    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit('fee-type-mgmt:delete:'.app('auth')->id(), 60);
    }

    Livewire::test('pages::fee-types.show', ['feeType' => (string) $feeType->id])
        ->call('confirmFeeTypeDeletion')
        ->assertDispatched('open-info-modal');
});

test('show page modal-confirmed is rate limited', function () {
    $feeType = FeeType::factory()->create();

    $component = Livewire::test('pages::fee-types.show', ['feeType' => (string) $feeType->id])
        ->call('confirmFeeTypeDeletion');

    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit('fee-type-mgmt:confirmed-action:'.app('auth')->id(), 60);
    }

    $component->dispatch('modal-confirmed')
        ->assertDispatched('open-info-modal');

    expect(FeeType::query()->find($feeType->id))->not->toBeNull();
});

test('show page canEdit and canDelete render actions for admin', function () {
    $feeType = FeeType::factory()->create();

    Livewire::test('pages::fee-types.show', ['feeType' => (string) $feeType->id])
        ->assertSeeHtml('wire:click="startEditingSection')
        ->assertSeeHtml('wire:click="confirmFeeTypeDeletion');
});

test('show page reports edit and delete capabilities as false for users without update or delete permissions', function () {
    $role = Role::factory()->create(['name' => 'fee-type-viewer']);
    $role->givePermissionTo('fee_type.viewAny', 'fee_type.view');

    $user = User::factory()->create();
    $user->assignRole($role);

    $feeType = FeeType::factory()->create();

    $this->actingAs($user);

    $component = Livewire::test('pages::fee-types.show', ['feeType' => (string) $feeType->id])
        ->assertOk();

    expect($component->instance()->canEdit())->toBeFalse()
        ->and($component->instance()->canDelete())->toBeFalse();
});

test('show page forbids editing for users without update permission', function () {
    $role = Role::factory()->create(['name' => 'fee-type-viewer-edit-blocked']);
    $role->givePermissionTo('fee_type.viewAny', 'fee_type.view');

    $user = User::factory()->create();
    $user->assignRole($role);

    $feeType = FeeType::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::fee-types.show', ['feeType' => (string) $feeType->id])
        ->call('startEditingSection', 'details')
        ->assertForbidden();
});

test('show page forbids deleting for users without delete permission', function () {
    $role = Role::factory()->create(['name' => 'fee-type-viewer-delete-blocked']);
    $role->givePermissionTo('fee_type.viewAny', 'fee_type.view');

    $user = User::factory()->create();
    $user->assignRole($role);

    $feeType = FeeType::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::fee-types.show', ['feeType' => (string) $feeType->id])
        ->call('confirmFeeTypeDeletion')
        ->assertForbidden();
});

test('show page clears pending deletion when confirm modal is cancelled', function () {
    $feeType = FeeType::factory()->create();

    Livewire::test('pages::fee-types.show', ['feeType' => (string) $feeType->id])
        ->call('confirmFeeTypeDeletion')
        ->assertSet('feeTypeIdPendingDeletion', $feeType->id)
        ->dispatch('modal-confirm-cancelled')
        ->assertSet('feeTypeIdPendingDeletion', null);
});

test('show page ignores modal-confirmed when no deletion is pending', function () {
    $feeType = FeeType::factory()->create();

    Livewire::test('pages::fee-types.show', ['feeType' => (string) $feeType->id])
        ->dispatch('modal-confirmed')
        ->assertNoRedirect();

    expect(FeeType::query()->find($feeType->id))->not->toBeNull();
});

test('show page mount returns 404 for non-existent fee type', function () {
    $this->get('/fee-types/999999')
        ->assertNotFound();
});

test('update action aborts 422 for unknown field name', function () {
    $admin = makeAdmin();
    $feeType = FeeType::factory()->create();

    $action = app(UpdateFeeType::class);

    try {
        $action->handle($admin, $feeType, 'nonexistent', 'value');
        $this->fail('Expected abort 422');
    } catch (HttpException $e) {
        expect($e->getStatusCode())->toBe(422);
    }
});
