<?php

use App\Models\ChargeBasis;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed(RolesAndPermissionsSeeder::class);

    actingAs(makeAdmin());
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
        ->call('startEditingSection', 'details');

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
        ->call('startEditingSection', 'details')
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

    actingAs(makeGuest());

    Livewire::test('pages::charge-bases.show', ['chargeBasis' => (string) $chargeBasis->id])
        ->assertForbidden();
});

test('show page forbids editing for users without update permission', function () {
    $role = Role::factory()->create(['name' => 'charge-basis-viewer-edit-blocked']);
    $role->givePermissionTo('charge_basis.viewAny', 'charge_basis.view');

    /** @var User $user */
    $user = User::factory()->create();
    $user->assignRole($role);

    $chargeBasis = ChargeBasis::factory()->create();

    actingAs($user);

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
