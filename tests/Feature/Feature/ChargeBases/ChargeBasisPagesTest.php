<?php

use App\Infrastructure\UiFeedback\ModalService;
use App\Models\ChargeBasis;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed(RolesAndPermissionsSeeder::class);

    actingAs(makeAdmin());
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
    get(route('charge-bases.index'))
        ->assertOk()
        ->assertSeeText(__('charge_bases.index.title'));
});

test('admins can visit the charge bases show page', function () {
    $chargeBasis = ChargeBasis::factory()->create([
        'name' => 'per_child',
        'en_name' => 'Per Child',
        'es_name' => 'Por menor',
    ]);

    get(route('charge-bases.show', $chargeBasis))
        ->assertOk()
        ->assertSeeText(__('charge_bases.show.placeholder_title'))
        ->assertSeeText('Per Child');
});

test('non admins cannot visit the charge bases index page', function () {
    actingAs(makeGuest());

    get(route('charge-bases.index'))->assertForbidden();
});

test('sidebar shows the charge bases navigation item for admins', function () {
    get(route('dashboard'))
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
        ->assertSee(__('charge_bases.create.fields.name'));
});

test('admin can create a charge basis from the create modal', function () {
    Livewire::test('charge-bases.create-charge-basis-form')
        ->assertSet('order', 999)
        ->set('name', 'PER_CHILD')
        ->set('en_name', 'Per Child')
        ->set('es_name', 'Por menor')
        ->set('description', 'Applied per child.')
        ->set('requires_quantity', true)
        ->set('quantity_subject', 'guest')
        ->call('save')
        ->assertDispatched('close-form-modal')
        ->assertDispatched('charge-basis-created');

    $created = ChargeBasis::query()->where('name', 'per_child')->first();

    expect($created)->not->toBeNull()
        ->and($created?->metadata['requires_quantity'])->toBeTrue()
        ->and($created?->metadata['quantity_subject'])->toBe('guest');
});

test('admin can toggle charge basis active status', function () {
    $chargeBasis = ChargeBasis::factory()->create(['is_active' => false]);

    chargeBasesIndexComponent()
        ->call('toggleChargeBasisActiveStatus', $chargeBasis->id, true)
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
        ->set('name', 'rate_limited')
        ->set('en_name', 'Rate Limited')
        ->set('es_name', 'Limitado')
        ->call('save')
        ->assertStatus(429);
});

test('non admins cannot visit the charge bases show page', function () {
    $chargeBasis = ChargeBasis::factory()->create();

    actingAs(makeGuest());

    get(route('charge-bases.show', $chargeBasis))->assertForbidden();
});

test('index confirmChargeBasisDeletion throws on non-existent id', function () {
    chargeBasesIndexComponent()
        ->call('confirmChargeBasisDeletion', 999999);
})->throws(ModelNotFoundException::class);
