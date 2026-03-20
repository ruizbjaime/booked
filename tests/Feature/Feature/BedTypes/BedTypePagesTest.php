<?php

use App\Models\BedType;
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

function bedTypesIndexComponent(?bool $mobileViewport = false): Testable
{
    $component = Livewire::test('pages::bed-types.index');

    if ($mobileViewport !== null) {
        $component->call('syncTableViewport', $mobileViewport);
    }

    return $component;
}

test('admins can visit the bed types index page', function () {
    $this->get(route('bed-types.index'))
        ->assertOk()
        ->assertSeeText(__('bed_types.index.title'));
});

test('admins can visit the bed types show page', function () {
    $bedType = BedType::factory()->create([
        'name' => 'king-bed',
        'name_en' => 'King Bed',
        'name_es' => 'Cama King',
    ]);

    $this->get(route('bed-types.show', $bedType))
        ->assertOk()
        ->assertSeeText(__('bed_types.show.placeholder_title'))
        ->assertSeeText('King Bed')
        ->assertSeeText('king-bed');
});

test('non admins cannot visit the bed types index page', function () {
    $this->actingAs(makeGuest());

    $this->get(route('bed-types.index'))->assertForbidden();
});

test('non admins cannot visit the bed types show page', function () {
    $bedType = BedType::factory()->create();

    $this->actingAs(makeGuest());

    $this->get(route('bed-types.show', $bedType))->assertForbidden();
});

test('sidebar hides the bed types navigation item for non admins', function () {
    $this->actingAs(makeGuest());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertDontSeeText(__('bed_types.navigation.label'));
});

test('sidebar shows the bed types navigation item for admins', function () {
    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSeeText(__('bed_types.navigation.label'));
});

test('bed types index sorts by sort_order asc by default', function () {
    BedType::factory()->create([
        'name_en' => 'Zulu Bed',
        'name_es' => 'Zulu Bed',
        'sort_order' => 200,
    ]);

    BedType::factory()->create([
        'name_en' => 'Alpha Bed',
        'name_es' => 'Alpha Bed',
        'sort_order' => 100,
    ]);

    bedTypesIndexComponent()
        ->assertSeeInOrder(['Alpha Bed', 'Zulu Bed'])
        ->assertSet('sortBy', 'sort_order')
        ->assertSet('sortDirection', 'asc');
});

test('bed types index can sort by localized name', function () {
    $nameColumn = BedType::localizedNameColumn();

    BedType::factory()->create([$nameColumn => 'Zulu Bed']);
    BedType::factory()->create([$nameColumn => 'Alpha Bed']);

    bedTypesIndexComponent()
        ->call('sort', $nameColumn)
        ->assertSeeInOrder(['Alpha Bed', 'Zulu Bed'])
        ->assertSet('sortBy', $nameColumn)
        ->assertSet('sortDirection', 'asc')
        ->call('sort', $nameColumn)
        ->assertSeeInOrder(['Zulu Bed', 'Alpha Bed'])
        ->assertSet('sortDirection', 'desc');
});

test('bed types index can sort by created_at', function () {
    $nameColumn = BedType::localizedNameColumn();

    BedType::factory()->create([
        $nameColumn => 'Older Bed',
        'created_at' => Carbon::parse('2026-03-10 09:00:00'),
    ]);

    BedType::factory()->create([
        $nameColumn => 'Newest Bed',
        'created_at' => Carbon::parse('2026-03-15 09:00:00'),
    ]);

    bedTypesIndexComponent()
        ->call('sort', 'created_at')
        ->assertSeeInOrder(['Newest Bed', 'Older Bed'])
        ->assertSet('sortBy', 'created_at')
        ->assertSet('sortDirection', 'desc');
});

test('bed types index search filters by slug and label', function () {
    BedType::factory()->create([
        'name' => 'king-bed',
        'name_en' => 'King Bed',
        'name_es' => 'King Bed',
    ]);

    BedType::factory()->create([
        'name' => 'single-bed',
        'name_en' => 'Single Bed',
        'name_es' => 'Single Bed',
    ]);

    bedTypesIndexComponent()
        ->set('search', 'king-bed')
        ->assertSee('King Bed')
        ->assertDontSee('Single Bed');
});

test('admin can open the bed type create modal from the index', function () {
    $component = bedTypesIndexComponent()
        ->call('openCreateBedTypeModal');

    expect(collect(data_get($component->effects, 'dispatches', []))->contains(function (array $dispatch): bool {
        return ($dispatch['name'] ?? null) === 'open-form-modal'
            && ($dispatch['params']['name'] ?? null) === 'bed-types.create'
            && ($dispatch['params']['title'] ?? null) === __('bed_types.create.title')
            && ($dispatch['params']['description'] ?? null) === __('bed_types.create.description');
    }))->toBeTrue();
});

test('modal service resolves the bed type create form component', function () {
    Livewire::test('modal-service')
        ->dispatch('open-form-modal',
            name: 'bed-types.create',
            title: __('bed_types.create.title'),
            description: __('bed_types.create.description'),
        )
        ->assertSet('formModalName', 'bed-types.create')
        ->assertSee(__('bed_types.create.fields.name'));
});

test('admin can create a bed type from the create modal', function () {
    Livewire::test('bed-types.create-bed-type-form')
        ->assertSet('bed_capacity', 1)
        ->assertSet('sort_order', 999)
        ->set('name', 'KING-BED')
        ->set('name_en', 'King Bed')
        ->set('name_es', 'Cama King')
        ->set('bed_capacity', 2)
        ->set('sort_order', 100)
        ->call('save')
        ->assertSet('name', '')
        ->assertSet('name_en', '')
        ->assertSet('name_es', '')
        ->assertSet('bed_capacity', 1)
        ->assertSet('sort_order', 999)
        ->assertDispatched('close-form-modal')
        ->assertDispatched('bed-type-created');

    $created = BedType::query()->where('name', 'king-bed')->first();

    expect($created)->not->toBeNull()
        ->and($created?->name_en)->toBe('King Bed')
        ->and($created?->name_es)->toBe('Cama King')
        ->and($created?->bed_capacity)->toBe(2)
        ->and($created?->sort_order)->toBe(100);
});

test('create form validates duplicate slug', function () {
    BedType::factory()->create(['name' => 'king-bed']);

    Livewire::test('bed-types.create-bed-type-form')
        ->set('name', 'king-bed')
        ->set('name_en', 'Duplicate')
        ->set('name_es', 'Duplicado')
        ->set('bed_capacity', 2)
        ->set('sort_order', 1)
        ->call('save')
        ->assertHasErrors(['name'])
        ->assertNotDispatched('bed-type-created');
});

test('admin can delete a bed type from the index', function () {
    $bedType = BedType::factory()->create([
        'name_en' => 'Delete Me',
        'name_es' => 'Eliminar',
    ]);

    bedTypesIndexComponent()
        ->call('confirmBedTypeDeletion', $bedType->id)
        ->assertSet('bedTypeIdPendingDeletion', $bedType->id)
        ->assertDispatched('open-confirm-modal', function (string $event, array $params) {
            return $event === 'open-confirm-modal'
                && ($params['title'] ?? null) === __('bed_types.index.confirm_delete.title')
                && ($params['confirmLabel'] ?? null) === __('bed_types.index.confirm_delete.confirm_label');
        })
        ->dispatch('modal-confirmed')
        ->assertSet('bedTypeIdPendingDeletion', null)
        ->assertDontSee('Delete Me');

    expect(BedType::query()->find($bedType->id))->toBeNull();
});

test('bed types index clears a pending deletion when the confirm modal is cancelled', function () {
    $bedType = BedType::factory()->create();

    bedTypesIndexComponent()
        ->call('confirmBedTypeDeletion', $bedType->id)
        ->assertSet('bedTypeIdPendingDeletion', $bedType->id)
        ->dispatch('modal-confirm-cancelled')
        ->assertSet('bedTypeIdPendingDeletion', null);
});

test('non admins cannot trigger bed type deletion from the index', function () {
    $this->actingAs(makeGuest());

    Livewire::test('pages::bed-types.index')
        ->assertForbidden();
});

test('create form validates required fields', function () {
    Livewire::test('bed-types.create-bed-type-form')
        ->set('name', '')
        ->set('name_en', '')
        ->set('name_es', '')
        ->call('save')
        ->assertHasErrors(['name', 'name_en', 'name_es'])
        ->assertNotDispatched('bed-type-created');
});

test('create form rejects invalid slug formats', function (string $name) {
    Livewire::test('bed-types.create-bed-type-form')
        ->set('name', $name)
        ->set('name_en', 'Invalid Slug')
        ->set('name_es', 'Slug Invalido')
        ->set('bed_capacity', 1)
        ->set('sort_order', 1)
        ->call('save')
        ->assertHasErrors(['name'])
        ->assertNotDispatched('bed-type-created');
})->with(['123-bed', 'king bed', 'king@bed', 'king.bed']);

test('create form rejects bed capacity below one', function () {
    Livewire::test('bed-types.create-bed-type-form')
        ->set('name', 'invalid-capacity')
        ->set('name_en', 'Invalid Capacity')
        ->set('name_es', 'Capacidad Invalida')
        ->set('bed_capacity', 0)
        ->set('sort_order', 1)
        ->call('save')
        ->assertHasErrors(['bed_capacity'])
        ->assertNotDispatched('bed-type-created');
});

test('create form rejects negative sort order', function () {
    Livewire::test('bed-types.create-bed-type-form')
        ->set('name', 'negative-order')
        ->set('name_en', 'Negative Order')
        ->set('name_es', 'Orden Negativo')
        ->set('bed_capacity', 1)
        ->set('sort_order', -1)
        ->call('save')
        ->assertHasErrors(['sort_order'])
        ->assertNotDispatched('bed-type-created');
});

test('create form clears field validation error when user corrects the field', function () {
    Livewire::test('bed-types.create-bed-type-form')
        ->set('name', '')
        ->set('name_en', '')
        ->set('name_es', 'Algo')
        ->call('save')
        ->assertHasErrors(['name', 'name_en'])
        ->set('name', 'fixed-bed')
        ->assertHasNoErrors(['name'])
        ->set('name_en', 'Fixed Bed')
        ->assertHasNoErrors(['name_en']);
});

test('create form save is rate limited', function () {
    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit("bed-type-mgmt:create:{$this->app['auth']->id()}", 60);
    }

    Livewire::test('bed-types.create-bed-type-form')
        ->set('name', 'rate-limited-bed')
        ->set('name_en', 'Rate Limited Bed')
        ->set('name_es', 'Cama Limitada')
        ->set('bed_capacity', 1)
        ->set('sort_order', 1)
        ->call('save')
        ->assertStatus(429);

    expect(BedType::query()->where('name', 'rate-limited-bed')->exists())->toBeFalse();
});

test('index delete confirmation is rate limited', function () {
    $bedType = BedType::factory()->create();

    $component = bedTypesIndexComponent();

    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit("bed-type-mgmt:delete:{$this->app['auth']->id()}", 60);
    }

    $component->call('confirmBedTypeDeletion', $bedType->id)
        ->assertStatus(429);
});

test('index modal-confirmed delete is rate limited', function () {
    $bedType = BedType::factory()->create();

    $component = bedTypesIndexComponent()
        ->call('confirmBedTypeDeletion', $bedType->id);

    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit("bed-type-mgmt:delete:{$this->app['auth']->id()}", 60);
    }

    $component->dispatch('modal-confirmed')
        ->assertStatus(429);

    expect(BedType::query()->find($bedType->id))->not->toBeNull();
});

test('index deleteBedType aborts 404 when no pending deletion exists', function () {
    bedTypesIndexComponent()
        ->dispatch('modal-confirmed')
        ->assertNotFound();
});

test('index confirmBedTypeDeletion throws on non-existent id', function () {
    bedTypesIndexComponent()
        ->call('confirmBedTypeDeletion', 999999);
})->throws(ModelNotFoundException::class);
