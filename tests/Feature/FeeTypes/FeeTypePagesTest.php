<?php

use App\Domain\Table\CardZone;
use App\Models\FeeType;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->actingAs(makeAdmin());
});

function feeTypesIndexComponent(?bool $mobileViewport = false): Testable
{
    $component = Livewire::test('pages::fee-types.index');

    if ($mobileViewport !== null) {
        $component->call('syncTableViewport', $mobileViewport);
    }

    return $component;
}

test('admins can visit the fee types index page', function () {
    $this->get(route('fee-types.index'))
        ->assertOk()
        ->assertSeeText(__('fee_types.index.title'));
});

test('admins can visit the fee types show page', function () {
    $feeType = FeeType::factory()->create([
        'slug' => 'cleaning-fee',
        'en_name' => 'Cleaning fee',
        'es_name' => 'Tarifa de limpieza',
    ]);

    $this->get(route('fee-types.show', $feeType))
        ->assertOk()
        ->assertSeeText(__('fee_types.show.placeholder_title'))
        ->assertSeeText('Cleaning fee')
        ->assertSeeText('cleaning-fee');
});

test('non admins cannot visit the fee types index page', function () {
    $this->actingAs(makeGuest());

    $this->get(route('fee-types.index'))->assertForbidden();
});

test('non admins cannot visit the fee types show page', function () {
    $feeType = FeeType::factory()->create();

    $this->actingAs(makeGuest());

    $this->get(route('fee-types.show', $feeType))->assertForbidden();
});

test('sidebar hides the fee types navigation item for non admins', function () {
    $this->actingAs(makeGuest());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertDontSeeText(__('fee_types.navigation.label'));
});

test('sidebar shows the fee types navigation item for admins', function () {
    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSeeText(__('fee_types.navigation.label'));
});

test('fee types index sorts by order asc by default', function () {
    FeeType::factory()->create([
        'en_name' => 'Late fee',
        'es_name' => 'Late fee',
        'order' => 200,
    ]);

    FeeType::factory()->create([
        'en_name' => 'Cleaning fee',
        'es_name' => 'Cleaning fee',
        'order' => 100,
    ]);

    feeTypesIndexComponent()
        ->assertSeeInOrder(['Cleaning fee', 'Late fee'])
        ->assertSet('sortBy', 'order')
        ->assertSet('sortDirection', 'asc');
});

test('fee types index can sort by localized name', function () {
    $nameColumn = FeeType::localizedNameColumn();

    FeeType::factory()->create([$nameColumn => 'Zulu fee']);
    FeeType::factory()->create([$nameColumn => 'Alpha fee']);

    feeTypesIndexComponent()
        ->call('sort', $nameColumn)
        ->assertSeeInOrder(['Alpha fee', 'Zulu fee'])
        ->assertSet('sortBy', $nameColumn)
        ->assertSet('sortDirection', 'asc')
        ->call('sort', $nameColumn)
        ->assertSeeInOrder(['Zulu fee', 'Alpha fee'])
        ->assertSet('sortDirection', 'desc');
});

test('fee types index can sort by created_at', function () {
    $nameColumn = FeeType::localizedNameColumn();

    FeeType::factory()->create([
        $nameColumn => 'Older fee',
        'created_at' => Carbon::parse('2026-03-10 09:00:00'),
    ]);

    FeeType::factory()->create([
        $nameColumn => 'Newest fee',
        'created_at' => Carbon::parse('2026-03-15 09:00:00'),
    ]);

    feeTypesIndexComponent()
        ->call('sort', 'created_at')
        ->assertSeeInOrder(['Newest fee', 'Older fee'])
        ->assertSet('sortBy', 'created_at')
        ->assertSet('sortDirection', 'desc');
});

test('fee types index search filters by slug and label', function () {
    FeeType::factory()->create([
        'slug' => 'cleaning-fee',
        'en_name' => 'Cleaning fee',
        'es_name' => 'Cleaning fee',
    ]);

    FeeType::factory()->create([
        'slug' => 'late-fee',
        'en_name' => 'Late fee',
        'es_name' => 'Late fee',
    ]);

    feeTypesIndexComponent()
        ->set('search', 'cleaning-fee')
        ->assertSee('Cleaning fee')
        ->assertDontSee('Late fee');
});

test('fee type label is rendered in the mobile card header', function () {
    $labelColumn = collect(feeTypesIndexComponent(true)->instance()->tableColumns())
        ->first(fn ($column) => $column->name() === FeeType::localizedNameColumn());

    expect($labelColumn)->not->toBeNull()
        ->and($labelColumn?->cardZone())->toBe(CardZone::Header);
});

test('admin can open the fee type create modal from the index', function () {
    $component = feeTypesIndexComponent()
        ->call('openCreateFeeTypeModal');

    expect(collect(data_get($component->effects, 'dispatches', []))->contains(function (array $dispatch): bool {
        return ($dispatch['name'] ?? null) === 'open-form-modal'
            && ($dispatch['params']['name'] ?? null) === 'fee-types.create'
            && ($dispatch['params']['title'] ?? null) === __('fee_types.create.title')
            && ($dispatch['params']['description'] ?? null) === __('fee_types.create.description');
    }))->toBeTrue();
});

test('modal service resolves the fee type create form component', function () {
    Livewire::test('modal-service')
        ->dispatch('open-form-modal',
            name: 'fee-types.create',
            title: __('fee_types.create.title'),
            description: __('fee_types.create.description'),
        )
        ->assertSet('formModalName', 'fee-types.create')
        ->assertSee(__('fee_types.create.fields.en_name'));
});

test('admin can create a fee type from the create modal', function () {
    Livewire::test('fee-types.create-fee-type-form')
        ->assertSet('order', 999)
        ->set('en_name', 'Cleaning fee')
        ->set('es_name', 'Tarifa de limpieza')
        ->set('order', 100)
        ->call('save')
        ->assertSet('en_name', '')
        ->assertSet('es_name', '')
        ->assertSet('order', 999)
        ->assertDispatched('close-form-modal')
        ->assertDispatched('fee-type-created');

    $created = FeeType::query()->where('en_name', 'Cleaning fee')->first();

    expect($created)->not->toBeNull()
        ->and($created?->en_name)->toBe('Cleaning fee')
        ->and($created?->es_name)->toBe('Tarifa de limpieza')
        ->and($created?->order)->toBe(100);
});

test('admin can delete a fee type from the index', function () {
    $feeType = FeeType::factory()->create([
        'en_name' => 'Delete me',
        'es_name' => 'Eliminar',
    ]);

    feeTypesIndexComponent()
        ->call('confirmFeeTypeDeletion', $feeType->id)
        ->assertSet('feeTypeIdPendingDeletion', $feeType->id)
        ->assertDispatched('open-confirm-modal', function (string $event, array $params) {
            return $event === 'open-confirm-modal'
                && ($params['title'] ?? null) === __('fee_types.index.confirm_delete.title')
                && ($params['confirmLabel'] ?? null) === __('fee_types.index.confirm_delete.confirm_label');
        })
        ->dispatch('modal-confirmed')
        ->assertSet('feeTypeIdPendingDeletion', null)
        ->assertDontSee('Delete me');

    expect(FeeType::query()->find($feeType->id))->toBeNull();
});

test('fee types index clears a pending deletion when the confirm modal is cancelled', function () {
    $feeType = FeeType::factory()->create();

    feeTypesIndexComponent()
        ->call('confirmFeeTypeDeletion', $feeType->id)
        ->assertSet('feeTypeIdPendingDeletion', $feeType->id)
        ->dispatch('modal-confirm-cancelled')
        ->assertSet('feeTypeIdPendingDeletion', null);
});

test('non admins cannot trigger fee type deletion from the index', function () {
    $this->actingAs(makeGuest());

    Livewire::test('pages::fee-types.index')
        ->assertForbidden();
});

test('create form validates required fields', function () {
    Livewire::test('fee-types.create-fee-type-form')
        ->set('en_name', '')
        ->set('es_name', '')
        ->call('save')
        ->assertHasErrors(['en_name', 'es_name'])
        ->assertNotDispatched('fee-type-created');
});

test('create form rejects negative order', function () {
    Livewire::test('fee-types.create-fee-type-form')
        ->set('en_name', 'Negative order')
        ->set('es_name', 'Orden negativa')
        ->set('order', -1)
        ->call('save')
        ->assertHasErrors(['order'])
        ->assertNotDispatched('fee-type-created');
});

test('create form clears field validation error when user corrects the field', function () {
    Livewire::test('fee-types.create-fee-type-form')
        ->set('en_name', '')
        ->set('es_name', 'Algo')
        ->call('save')
        ->assertHasErrors(['en_name'])
        ->set('en_name', 'Fixed fee')
        ->assertHasNoErrors(['en_name']);
});

test('create form save is rate limited', function () {
    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit('fee-type-mgmt:create:'.app('auth')->id(), 60);
    }

    Livewire::test('fee-types.create-fee-type-form')
        ->set('en_name', 'Rate limited fee')
        ->set('es_name', 'Tarifa limitada')
        ->set('order', 1)
        ->call('save')
        ->assertDispatched('open-info-modal')
        ->assertNotDispatched('fee-type-created');

    expect(FeeType::query()->where('en_name', 'Rate limited fee')->exists())->toBeFalse();
});

test('index delete confirmation is rate limited', function () {
    $feeType = FeeType::factory()->create();

    $component = feeTypesIndexComponent();

    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit('fee-type-mgmt:delete:'.app('auth')->id(), 60);
    }

    $component->call('confirmFeeTypeDeletion', $feeType->id)
        ->assertDispatched('open-info-modal');
});

test('index modal-confirmed delete is rate limited', function () {
    $feeType = FeeType::factory()->create();

    $component = feeTypesIndexComponent()
        ->call('confirmFeeTypeDeletion', $feeType->id);

    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit('fee-type-mgmt:delete:'.app('auth')->id(), 60);
    }

    $component->dispatch('modal-confirmed')
        ->assertDispatched('open-info-modal');

    expect(FeeType::query()->find($feeType->id))->not->toBeNull();
});

test('index deleteFeeType aborts 404 when no pending deletion exists', function () {
    feeTypesIndexComponent()
        ->dispatch('modal-confirmed')
        ->assertNotFound();
});

test('index confirmFeeTypeDeletion throws on non-existent id', function () {
    feeTypesIndexComponent()
        ->call('confirmFeeTypeDeletion', 999999);
})->throws(ModelNotFoundException::class);
