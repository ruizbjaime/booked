<?php

use App\Domain\Table\CardZone;
use App\Models\BathRoomType;
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

function bathRoomTypesIndexComponent(bool $mobileViewport = false): Testable
{
    return Livewire::test('pages::bath-room-types.index')
        ->call('syncTableViewport', $mobileViewport);
}

test('admins can visit the bathroom types index page', function () {
    $this->get(route('bath-room-types.index'))
        ->assertOk()
        ->assertSeeText(__('bath_room_types.index.title'));
});

test('admins can visit the bathroom types show page', function () {
    $bathRoomType = BathRoomType::factory()->create([
        'name' => 'private-bathroom',
        'en_name' => 'Private Bathroom',
        'es_name' => 'Bano privado',
    ]);

    $this->get(route('bath-room-types.show', $bathRoomType))
        ->assertOk()
        ->assertSeeText(__('bath_room_types.show.placeholder_title'))
        ->assertSeeText('Private Bathroom')
        ->assertSeeText('private-bathroom');
});

test('non admins cannot visit the bathroom types index page', function () {
    $this->actingAs(makeGuest());

    $this->get(route('bath-room-types.index'))->assertForbidden();
});

test('non admins cannot visit the bathroom types show page', function () {
    $bathRoomType = BathRoomType::factory()->create();

    $this->actingAs(makeGuest());

    $this->get(route('bath-room-types.show', $bathRoomType))->assertForbidden();
});

test('sidebar hides the bathroom types navigation item for non admins', function () {
    $this->actingAs(makeGuest());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertDontSeeText(__('bath_room_types.navigation.label'));
});

test('sidebar shows the bathroom types navigation item for admins', function () {
    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSeeText(__('bath_room_types.navigation.label'));
});

test('bathroom types index sorts by sort_order asc by default', function () {
    BathRoomType::factory()->create([
        'en_name' => 'Zulu Bathroom',
        'es_name' => 'Zulu Bathroom',
        'sort_order' => 200,
    ]);

    BathRoomType::factory()->create([
        'en_name' => 'Alpha Bathroom',
        'es_name' => 'Alpha Bathroom',
        'sort_order' => 100,
    ]);

    bathRoomTypesIndexComponent()
        ->assertSeeInOrder(['Alpha Bathroom', 'Zulu Bathroom'])
        ->assertSet('sortBy', 'sort_order')
        ->assertSet('sortDirection', 'asc');
});

test('bathroom types index can sort by localized name', function () {
    $nameColumn = BathRoomType::localizedNameColumn();

    BathRoomType::factory()->create([$nameColumn => 'Zulu Bathroom']);
    BathRoomType::factory()->create([$nameColumn => 'Alpha Bathroom']);

    bathRoomTypesIndexComponent()
        ->call('sort', $nameColumn)
        ->assertSeeInOrder(['Alpha Bathroom', 'Zulu Bathroom'])
        ->assertSet('sortBy', $nameColumn)
        ->assertSet('sortDirection', 'asc')
        ->call('sort', $nameColumn)
        ->assertSeeInOrder(['Zulu Bathroom', 'Alpha Bathroom'])
        ->assertSet('sortDirection', 'desc');
});

test('bathroom types index can sort by created_at', function () {
    $nameColumn = BathRoomType::localizedNameColumn();

    BathRoomType::factory()->create([
        $nameColumn => 'Older Bathroom',
        'created_at' => Carbon::parse('2026-03-10 09:00:00'),
    ]);

    BathRoomType::factory()->create([
        $nameColumn => 'Newest Bathroom',
        'created_at' => Carbon::parse('2026-03-15 09:00:00'),
    ]);

    bathRoomTypesIndexComponent()
        ->call('sort', 'created_at')
        ->assertSeeInOrder(['Newest Bathroom', 'Older Bathroom'])
        ->assertSet('sortBy', 'created_at')
        ->assertSet('sortDirection', 'desc');
});

test('bathroom types index search filters by slug and description', function () {
    $private = BathRoomType::factory()->create([
        'name' => 'private-bathroom',
        'en_name' => 'Private Bathroom',
        'es_name' => 'Bano privado',
        'description' => 'Exclusive bathroom',
    ]);

    $shared = BathRoomType::factory()->create([
        'name' => 'shared-bathroom',
        'en_name' => 'Shared Bathroom',
        'es_name' => 'Bano compartido',
        'description' => 'Shared bathroom',
    ]);

    bathRoomTypesIndexComponent()
        ->set('search', 'Exclusive')
        ->assertSee($private->localizedName())
        ->assertDontSee($shared->localizedName());
});

test('bathroom type label is rendered in the mobile card header', function () {
    $labelColumn = collect(bathRoomTypesIndexComponent(true)->instance()->tableColumns())
        ->first(fn ($column) => $column->name() === BathRoomType::localizedNameColumn());

    expect($labelColumn)->not->toBeNull()
        ->and($labelColumn?->cardZone())->toBe(CardZone::Header);
});

test('admin can open the bathroom type create modal from the index', function () {
    $component = bathRoomTypesIndexComponent()
        ->call('openCreateBathRoomTypeModal');

    expect(collect(data_get($component->effects, 'dispatches', []))->contains(function (array $dispatch): bool {
        return ($dispatch['name'] ?? null) === 'open-form-modal'
            && ($dispatch['params']['name'] ?? null) === 'bath-room-types.create'
            && ($dispatch['params']['title'] ?? null) === __('bath_room_types.create.title')
            && ($dispatch['params']['description'] ?? null) === __('bath_room_types.create.description');
    }))->toBeTrue();
});

test('modal service resolves the bathroom type create form component', function () {
    Livewire::test('modal-service')
        ->dispatch('open-form-modal',
            name: 'bath-room-types.create',
            title: __('bath_room_types.create.title'),
            description: __('bath_room_types.create.description'),
        )
        ->assertSet('formModalName', 'bath-room-types.create')
        ->assertSee(__('bath_room_types.create.fields.name'));
});

test('admin can create a bathroom type from the create modal', function () {
    Livewire::test('bath-room-types.create-bath-room-type-form')
        ->assertSet('sort_order', 999)
        ->set('name', 'PRIVATE-BATHROOM')
        ->set('en_name', 'Private Bathroom')
        ->set('es_name', 'Bano privado')
        ->set('description', 'Bathroom reserved for the room.')
        ->set('sort_order', 100)
        ->call('save')
        ->assertSet('name', '')
        ->assertSet('en_name', '')
        ->assertSet('es_name', '')
        ->assertSet('description', '')
        ->assertSet('sort_order', 999)
        ->assertDispatched('close-form-modal')
        ->assertDispatched('bath-room-type-created');

    $created = BathRoomType::query()->where('name', 'private-bathroom')->first();

    expect($created)->not->toBeNull()
        ->and($created?->en_name)->toBe('Private Bathroom')
        ->and($created?->es_name)->toBe('Bano privado')
        ->and($created?->description)->toBe('Bathroom reserved for the room.')
        ->and($created?->sort_order)->toBe(100);
});

test('create form validates duplicate slug', function () {
    BathRoomType::factory()->create(['name' => 'private-bathroom']);

    Livewire::test('bath-room-types.create-bath-room-type-form')
        ->set('name', 'private-bathroom')
        ->set('en_name', 'Duplicate')
        ->set('es_name', 'Duplicado')
        ->set('description', 'Duplicated description')
        ->set('sort_order', 1)
        ->call('save')
        ->assertHasErrors(['name'])
        ->assertNotDispatched('bath-room-type-created');
});

test('admin can delete a bathroom type from the index', function () {
    $bathRoomType = BathRoomType::factory()->create([
        'en_name' => 'Delete Me',
        'es_name' => 'Eliminar',
    ]);

    bathRoomTypesIndexComponent()
        ->call('confirmBathRoomTypeDeletion', $bathRoomType->id)
        ->assertSet('bathRoomTypeIdPendingDeletion', $bathRoomType->id)
        ->assertDispatched('open-confirm-modal', function (string $event, array $params) {
            return $event === 'open-confirm-modal'
                && ($params['title'] ?? null) === __('bath_room_types.index.confirm_delete.title')
                && ($params['confirmLabel'] ?? null) === __('bath_room_types.index.confirm_delete.confirm_label');
        })
        ->dispatch('modal-confirmed')
        ->assertSet('bathRoomTypeIdPendingDeletion', null)
        ->assertDontSee('Delete Me');

    expect(BathRoomType::query()->find($bathRoomType->id))->toBeNull();
});

test('bathroom types index clears a pending deletion when the confirm modal is cancelled', function () {
    $bathRoomType = BathRoomType::factory()->create();

    bathRoomTypesIndexComponent()
        ->call('confirmBathRoomTypeDeletion', $bathRoomType->id)
        ->assertSet('bathRoomTypeIdPendingDeletion', $bathRoomType->id)
        ->dispatch('modal-confirm-cancelled')
        ->assertSet('bathRoomTypeIdPendingDeletion', null);
});

test('non admins cannot trigger bathroom type deletion from the index', function () {
    $this->actingAs(makeGuest());

    Livewire::test('pages::bath-room-types.index')
        ->assertForbidden();
});

test('create form validates required fields', function () {
    Livewire::test('bath-room-types.create-bath-room-type-form')
        ->set('name', '')
        ->set('en_name', '')
        ->set('es_name', '')
        ->set('description', '')
        ->call('save')
        ->assertHasErrors(['name', 'en_name', 'es_name', 'description'])
        ->assertNotDispatched('bath-room-type-created');
});

test('create form rejects invalid slug formats', function (string $name) {
    Livewire::test('bath-room-types.create-bath-room-type-form')
        ->set('name', $name)
        ->set('en_name', 'Invalid Slug')
        ->set('es_name', 'Slug Invalido')
        ->set('description', 'Description')
        ->set('sort_order', 1)
        ->call('save')
        ->assertHasErrors(['name'])
        ->assertNotDispatched('bath-room-type-created');
})->with(['123-bathroom', 'private bathroom', 'private@bathroom', 'private.bathroom']);

test('create form rejects negative sort order', function () {
    Livewire::test('bath-room-types.create-bath-room-type-form')
        ->set('name', 'negative-order')
        ->set('en_name', 'Negative Order')
        ->set('es_name', 'Orden Negativo')
        ->set('description', 'Description')
        ->set('sort_order', -1)
        ->call('save')
        ->assertHasErrors(['sort_order'])
        ->assertNotDispatched('bath-room-type-created');
});

test('create form clears field validation error when user corrects the field', function () {
    Livewire::test('bath-room-types.create-bath-room-type-form')
        ->set('name', '')
        ->set('en_name', '')
        ->set('es_name', 'Algo')
        ->set('description', 'Description')
        ->call('save')
        ->assertHasErrors(['name', 'en_name'])
        ->set('name', 'fixed-bathroom')
        ->assertHasNoErrors(['name'])
        ->set('en_name', 'Fixed Bathroom')
        ->assertHasNoErrors(['en_name']);
});

test('create form save is rate limited', function () {
    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit("bath-room-type-mgmt:create:{$this->app['auth']->id()}", 60);
    }

    Livewire::test('bath-room-types.create-bath-room-type-form')
        ->set('name', 'rate-limited-bathroom')
        ->set('en_name', 'Rate Limited Bathroom')
        ->set('es_name', 'Bano limitado')
        ->set('description', 'Description')
        ->set('sort_order', 1)
        ->call('save')
        ->assertDispatched('open-info-modal')
        ->assertNotDispatched('bath-room-type-created');

    expect(BathRoomType::query()->where('name', 'rate-limited-bathroom')->exists())->toBeFalse();
});

test('index delete confirmation is rate limited', function () {
    $bathRoomType = BathRoomType::factory()->create();

    $component = bathRoomTypesIndexComponent();

    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit("bath-room-type-mgmt:delete:{$this->app['auth']->id()}", 60);
    }

    $component->call('confirmBathRoomTypeDeletion', $bathRoomType->id)
        ->assertDispatched('open-info-modal');
});

test('index modal-confirmed delete is rate limited', function () {
    $bathRoomType = BathRoomType::factory()->create();

    $component = bathRoomTypesIndexComponent()
        ->call('confirmBathRoomTypeDeletion', $bathRoomType->id);

    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit("bath-room-type-mgmt:delete:{$this->app['auth']->id()}", 60);
    }

    $component->dispatch('modal-confirmed')
        ->assertDispatched('open-info-modal');

    expect(BathRoomType::query()->find($bathRoomType->id))->not->toBeNull();
});

test('index deleteBathRoomType aborts 404 when no pending deletion exists', function () {
    bathRoomTypesIndexComponent()
        ->dispatch('modal-confirmed')
        ->assertNotFound();
});

test('index confirmBathRoomTypeDeletion throws on non-existent id', function () {
    bathRoomTypesIndexComponent()
        ->call('confirmBathRoomTypeDeletion', 999999);
})->throws(ModelNotFoundException::class);
