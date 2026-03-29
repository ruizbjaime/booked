<?php

use App\Domain\Table\CardZone;
use App\Infrastructure\UiFeedback\ModalService;
use App\Models\ChargeBasis;
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

function chargeBasesIndexComponent(?bool $mobileViewport = false): Testable
{
    $component = Livewire::test('pages::charge-bases.index');

    if ($mobileViewport !== null) {
        $component->call('syncTableViewport', $mobileViewport);
    }

    return $component;
}

test('admins can visit the charge bases index page', function () {
    $this->get(route('charge-bases.index'))
        ->assertOk()
        ->assertSeeText(__('charge_bases.index.title'));
});

test('admins can visit the charge bases show page', function () {
    $chargeBasis = ChargeBasis::factory()->create([
        'slug' => 'per-child',
        'en_name' => 'Per Child',
        'es_name' => 'Por menor',
    ]);

    $this->get(route('charge-bases.show', $chargeBasis))
        ->assertOk()
        ->assertSeeText(__('charge_bases.show.placeholder_title'))
        ->assertSeeText('Per Child');
});

test('non admins cannot visit the charge bases index page', function () {
    $this->actingAs(makeGuest());

    $this->get(route('charge-bases.index'))->assertForbidden();
});

test('sidebar shows the charge bases navigation item for admins', function () {
    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSeeText(__('charge_bases.navigation.label'));
});

test('admin can open the charge basis create modal from the index', function () {
    $component = chargeBasesIndexComponent()
        ->call('openCreateChargeBasisModal');

    expect(collect(data_get($component->effects, 'dispatches', []))->contains(function (array $dispatch): bool {
        return ($dispatch['name'] ?? null) === 'open-form-modal'
            && ($dispatch['params']['name'] ?? null) === 'charge-bases.create'
            && ($dispatch['params']['title'] ?? null) === __('charge_bases.create.title');
    }))->toBeTrue();
});

test('modal service resolves the charge basis create form component', function () {
    Livewire::test('modal-service')
        ->dispatch('open-form-modal',
            name: 'charge-bases.create',
            title: __('charge_bases.create.title'),
            description: __('charge_bases.create.description'),
        )
        ->assertSet('formModalName', 'charge-bases.create')
        ->assertSee(__('charge_bases.create.fields.en_name'));
});

test('admin can create a charge basis from the create modal', function () {
    Livewire::test('charge-bases.create-charge-basis-form')
        ->assertSet('order', 999)
        ->set('en_name', 'Per Child')
        ->set('es_name', 'Por menor')
        ->set('en_description', 'Applied per child.')
        ->set('es_description', 'Aplicado por menor.')
        ->set('requires_quantity', true)
        ->set('quantity_subject', 'guest')
        ->call('save')
        ->assertDispatched('close-form-modal')
        ->assertDispatched('charge-basis-created');

    $created = ChargeBasis::query()->where('en_name', 'Per Child')->first();

    expect($created)->not->toBeNull()
        ->and($created?->metadata['requires_quantity'])->toBeTrue()
        ->and($created?->metadata['quantity_subject'])->toBe('guest');
});

test('admin can toggle charge basis active status', function () {
    $chargeBasis = ChargeBasis::factory()->create(['is_active' => false]);

    chargeBasesIndexComponent()
        ->call('toggleChargeBasisActiveStatus', $chargeBasis->id, 'is_active', true)
        ->assertDispatched('toast-show', function (string $event, array $params) {
            return $event === 'toast-show'
                && ($params['dataset']['variant'] ?? null) === 'success';
        });

    expect($chargeBasis->fresh()->is_active)->toBeTrue();
});

test('admin can delete a charge basis from the index', function () {
    $chargeBasis = ChargeBasis::factory()->create(['en_name' => 'Delete me']);

    chargeBasesIndexComponent()
        ->call('confirmChargeBasisDeletion', $chargeBasis->id)
        ->assertSet('chargeBasisIdPendingDeletion', $chargeBasis->id)
        ->assertDispatched('open-confirm-modal', function (string $event, array $params) {
            return $event === 'open-confirm-modal'
                && ($params['title'] ?? null) === __('charge_bases.index.confirm_delete.title')
                && ($params['variant'] ?? null) === ModalService::VARIANT_PASSWORD;
        })
        ->dispatch('modal-confirmed')
        ->assertSet('chargeBasisIdPendingDeletion', null);

    expect(ChargeBasis::query()->find($chargeBasis->id))->toBeNull();
});

test('charge bases index search input defines non-auth autofill metadata', function () {
    chargeBasesIndexComponent()
        ->assertSeeHtml('name="charge_bases_search"')
        ->assertSeeHtml('autocomplete="off"');
});

test('charge basis create form save is rate limited', function () {
    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit('charge-basis-mgmt:create:'.app('auth')->id(), 60);
    }

    Livewire::test('charge-bases.create-charge-basis-form')
        ->set('en_name', 'Rate Limited')
        ->set('es_name', 'Limitado')
        ->call('save')
        ->assertDispatched('open-info-modal')
        ->assertNotDispatched('charge-basis-created');
});

test('non admins cannot visit the charge bases show page', function () {
    $chargeBasis = ChargeBasis::factory()->create();

    $this->actingAs(makeGuest());

    $this->get(route('charge-bases.show', $chargeBasis))->assertForbidden();
});

test('sidebar hides the charge bases navigation item for non admins', function () {
    $this->actingAs(makeGuest());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertDontSeeText(__('charge_bases.navigation.label'));
});

test('charge bases index sorts by order asc by default', function () {
    ChargeBasis::factory()->create([
        'en_name' => 'Late Basis',
        'es_name' => 'Late Basis',
        'order' => 200,
    ]);

    ChargeBasis::factory()->create([
        'en_name' => 'Early Basis',
        'es_name' => 'Early Basis',
        'order' => 100,
    ]);

    chargeBasesIndexComponent()
        ->assertSeeInOrder(['Early Basis', 'Late Basis'])
        ->assertSet('sortBy', 'order')
        ->assertSet('sortDirection', 'asc');
});

test('charge bases index can sort by localized name', function () {
    $nameColumn = ChargeBasis::localizedNameColumn();

    ChargeBasis::factory()->create([$nameColumn => 'Zulu Basis']);
    ChargeBasis::factory()->create([$nameColumn => 'Alpha Basis']);

    chargeBasesIndexComponent()
        ->call('sort', $nameColumn)
        ->assertSeeInOrder(['Alpha Basis', 'Zulu Basis'])
        ->assertSet('sortBy', $nameColumn)
        ->assertSet('sortDirection', 'asc')
        ->call('sort', $nameColumn)
        ->assertSeeInOrder(['Zulu Basis', 'Alpha Basis'])
        ->assertSet('sortDirection', 'desc');
});

test('charge bases index can sort by created_at', function () {
    $nameColumn = ChargeBasis::localizedNameColumn();

    ChargeBasis::factory()->create([
        $nameColumn => 'Older Basis',
        'created_at' => Carbon::parse('2026-03-10 09:00:00'),
    ]);

    ChargeBasis::factory()->create([
        $nameColumn => 'Newest Basis',
        'created_at' => Carbon::parse('2026-03-15 09:00:00'),
    ]);

    chargeBasesIndexComponent()
        ->call('sort', 'created_at')
        ->assertSeeInOrder(['Newest Basis', 'Older Basis'])
        ->assertSet('sortBy', 'created_at')
        ->assertSet('sortDirection', 'desc');
});

test('charge bases index search filters by slug and label', function () {
    ChargeBasis::factory()->create([
        'slug' => 'per-child',
        'en_name' => 'Per Child',
        'es_name' => 'Per Child',
    ]);

    ChargeBasis::factory()->create([
        'slug' => 'per-night',
        'en_name' => 'Per Night',
        'es_name' => 'Per Night',
    ]);

    chargeBasesIndexComponent()
        ->set('search', 'per-child')
        ->assertSee('Per Child')
        ->assertDontSee('Per Night');
});

test('charge basis label is rendered in the mobile card header', function () {
    $labelColumn = collect(chargeBasesIndexComponent(true)->instance()->tableColumns())
        ->first(fn ($column) => $column->name() === ChargeBasis::localizedNameColumn());

    expect($labelColumn)->not->toBeNull()
        ->and($labelColumn?->cardZone())->toBe(CardZone::Header);
});

test('create form validates required fields', function () {
    Livewire::test('charge-bases.create-charge-basis-form')
        ->set('en_name', '')
        ->set('es_name', '')
        ->call('save')
        ->assertHasErrors(['en_name', 'es_name'])
        ->assertNotDispatched('charge-basis-created');
});

test('create form rejects negative order', function () {
    Livewire::test('charge-bases.create-charge-basis-form')
        ->set('en_name', 'Negative order')
        ->set('es_name', 'Orden negativa')
        ->set('order', -1)
        ->call('save')
        ->assertHasErrors(['order'])
        ->assertNotDispatched('charge-basis-created');
});

test('create form clears field validation error when user corrects the field', function () {
    Livewire::test('charge-bases.create-charge-basis-form')
        ->set('en_name', '')
        ->set('es_name', 'Algo')
        ->call('save')
        ->assertHasErrors(['en_name'])
        ->set('en_name', 'Fixed basis')
        ->assertHasNoErrors(['en_name']);
});

test('index clears pending deletion when confirm modal is cancelled', function () {
    $chargeBasis = ChargeBasis::factory()->create();

    chargeBasesIndexComponent()
        ->call('confirmChargeBasisDeletion', $chargeBasis->id)
        ->assertSet('chargeBasisIdPendingDeletion', $chargeBasis->id)
        ->dispatch('modal-confirm-cancelled')
        ->assertSet('chargeBasisIdPendingDeletion', null);
});

test('index delete confirmation is rate limited', function () {
    $chargeBasis = ChargeBasis::factory()->create();

    $component = chargeBasesIndexComponent();

    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit('charge-basis-mgmt:delete:'.app('auth')->id(), 60);
    }

    $component->call('confirmChargeBasisDeletion', $chargeBasis->id)
        ->assertDispatched('open-info-modal');
});

test('index modal-confirmed delete is rate limited', function () {
    $chargeBasis = ChargeBasis::factory()->create();

    $component = chargeBasesIndexComponent()
        ->call('confirmChargeBasisDeletion', $chargeBasis->id);

    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit('charge-basis-mgmt:delete:'.app('auth')->id(), 60);
    }

    $component->dispatch('modal-confirmed')
        ->assertDispatched('open-info-modal');

    expect(ChargeBasis::query()->find($chargeBasis->id))->not->toBeNull();
});

test('index deleteChargeBasis aborts 404 when no pending deletion exists', function () {
    chargeBasesIndexComponent()
        ->dispatch('modal-confirmed')
        ->assertNotFound();
});

test('index confirmChargeBasisDeletion throws on non-existent id', function () {
    chargeBasesIndexComponent()
        ->call('confirmChargeBasisDeletion', 999999);
})->throws(ModelNotFoundException::class);

test('charge bases index shows sortable active when sorted by order ascending', function () {
    $component = chargeBasesIndexComponent()
        ->assertSet('sortBy', 'order')
        ->assertSet('sortDirection', 'asc');

    expect($component->instance()->isSortableActive())->toBeTrue();
});

test('charge bases index reorderRows updates record order', function () {
    $a = ChargeBasis::factory()->create(['order' => 1, 'slug' => 'basis-a', 'en_name' => 'A']);
    $b = ChargeBasis::factory()->create(['order' => 2, 'slug' => 'basis-b', 'en_name' => 'B']);
    $c = ChargeBasis::factory()->create(['order' => 3, 'slug' => 'basis-c', 'en_name' => 'C']);

    chargeBasesIndexComponent()
        ->call('reorderRows', $c->id, 0)
        ->assertDispatched('toast-show', function (string $event, array $params) {
            return ($params['dataset']['variant'] ?? null) === 'success';
        });

    expect($c->fresh()->order)->toBe(1)
        ->and($a->fresh()->order)->toBe(2)
        ->and($b->fresh()->order)->toBe(3);
});

test('charge bases index sortable is not active when search is present', function () {
    ChargeBasis::factory()->create(['en_name' => 'Basis Test']);

    $component = chargeBasesIndexComponent()
        ->set('search', 'basis');

    expect($component->instance()->isSortableActive())->toBeFalse();
});

test('charge bases index sortable is not active when sorted by different column', function () {
    $component = chargeBasesIndexComponent()
        ->call('sort', ChargeBasis::localizedNameColumn());

    expect($component->instance()->isSortableActive())->toBeFalse();
});
