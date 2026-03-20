<?php

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

function bathRoomTypesIndexComponent(?bool $mobileViewport = false): Testable
{
    $component = Livewire::test('pages::bath-room-types.index');

    if ($mobileViewport !== null) {
        $component->call('syncTableViewport', $mobileViewport);
    }

    return $component;
}

test('admins can visit the bathroom types index page', function () {
    $this->get(route('bath-room-types.index'))
        ->assertOk()
        ->assertSeeText(__('bath_room_types.index.title'));
});

test('admins can visit the bathroom types show page', function () {
    $bathRoomType = BathRoomType::factory()->create([
        'name' => 'private-bathroom',
        'name_en' => 'Private Bathroom',
        'name_es' => 'Bano privado',
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
        'name_en' => 'Zulu Bathroom',
        'name_es' => 'Zulu Bathroom',
        'sort_order' => 200,
    ]);

    BathRoomType::factory()->create([
        'name_en' => 'Alpha Bathroom',
        'name_es' => 'Alpha Bathroom',
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
    BathRoomType::factory()->create([
        'name' => 'private-bathroom',
        'name_en' => 'Private Bathroom',
        'name_es' => 'Bano privado',
        'description' => 'Exclusive bathroom',
    ]);

    BathRoomType::factory()->create([
        'name' => 'shared-bathroom',
        'name_en' => 'Shared Bathroom',
        'name_es' => 'Bano compartido',
        'description' => 'Shared bathroom',
    ]);

    bathRoomTypesIndexComponent()
        ->set('search', 'Exclusive')
        ->assertSee('Bano privado')
        ->assertDontSee('Bano compartido');
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
        ->set('name_en', 'Private Bathroom')
        ->set('name_es', 'Bano privado')
        ->set('description', 'Bathroom reserved for the room.')
        ->set('sort_order', 100)
        ->call('save')
        ->assertSet('name', '')
        ->assertSet('name_en', '')
        ->assertSet('name_es', '')
        ->assertSet('description', '')
        ->assertSet('sort_order', 999)
        ->assertDispatched('close-form-modal')
        ->assertDispatched('bath-room-type-created');

    $created = BathRoomType::query()->where('name', 'private-bathroom')->first();

    expect($created)->not->toBeNull()
        ->and($created?->name_en)->toBe('Private Bathroom')
        ->and($created?->name_es)->toBe('Bano privado')
        ->and($created?->description)->toBe('Bathroom reserved for the room.')
        ->and($created?->sort_order)->toBe(100);
});

test('create form validates duplicate slug', function () {
    BathRoomType::factory()->create(['name' => 'private-bathroom']);

    Livewire::test('bath-room-types.create-bath-room-type-form')
        ->set('name', 'private-bathroom')
        ->set('name_en', 'Duplicate')
        ->set('name_es', 'Duplicado')
        ->set('description', 'Duplicated description')
        ->set('sort_order', 1)
        ->call('save')
        ->assertHasErrors(['name'])
        ->assertNotDispatched('bath-room-type-created');
});

test('admin can delete a bathroom type from the index', function () {
    $bathRoomType = BathRoomType::factory()->create([
        'name_en' => 'Delete Me',
        'name_es' => 'Eliminar',
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
        ->set('name_en', '')
        ->set('name_es', '')
        ->set('description', '')
        ->call('save')
        ->assertHasErrors(['name', 'name_en', 'name_es', 'description'])
        ->assertNotDispatched('bath-room-type-created');
});

test('create form rejects invalid slug formats', function (string $name) {
    Livewire::test('bath-room-types.create-bath-room-type-form')
        ->set('name', $name)
        ->set('name_en', 'Invalid Slug')
        ->set('name_es', 'Slug Invalido')
        ->set('description', 'Description')
        ->set('sort_order', 1)
        ->call('save')
        ->assertHasErrors(['name'])
        ->assertNotDispatched('bath-room-type-created');
})->with(['123-bathroom', 'private bathroom', 'private@bathroom', 'private.bathroom']);

test('create form rejects negative sort order', function () {
    Livewire::test('bath-room-types.create-bath-room-type-form')
        ->set('name', 'negative-order')
        ->set('name_en', 'Negative Order')
        ->set('name_es', 'Orden Negativo')
        ->set('description', 'Description')
        ->set('sort_order', -1)
        ->call('save')
        ->assertHasErrors(['sort_order'])
        ->assertNotDispatched('bath-room-type-created');
});

test('create form clears field validation error when user corrects the field', function () {
    Livewire::test('bath-room-types.create-bath-room-type-form')
        ->set('name', '')
        ->set('name_en', '')
        ->set('name_es', 'Algo')
        ->set('description', 'Description')
        ->call('save')
        ->assertHasErrors(['name', 'name_en'])
        ->set('name', 'fixed-bathroom')
        ->assertHasNoErrors(['name'])
        ->set('name_en', 'Fixed Bathroom')
        ->assertHasNoErrors(['name_en']);
});

test('create form save is rate limited', function () {
    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit("bath-room-type-mgmt:create:{$this->app['auth']->id()}", 60);
    }

    Livewire::test('bath-room-types.create-bath-room-type-form')
        ->set('name', 'rate-limited-bathroom')
        ->set('name_en', 'Rate Limited Bathroom')
        ->set('name_es', 'Bano limitado')
        ->set('description', 'Description')
        ->set('sort_order', 1)
        ->call('save')
        ->assertStatus(429);

    expect(BathRoomType::query()->where('name', 'rate-limited-bathroom')->exists())->toBeFalse();
});

test('index delete confirmation is rate limited', function () {
    $bathRoomType = BathRoomType::factory()->create();

    $component = bathRoomTypesIndexComponent();

    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit("bath-room-type-mgmt:delete:{$this->app['auth']->id()}", 60);
    }

    $component->call('confirmBathRoomTypeDeletion', $bathRoomType->id)
        ->assertStatus(429);
});

test('index modal-confirmed delete is rate limited', function () {
    $bathRoomType = BathRoomType::factory()->create();

    $component = bathRoomTypesIndexComponent()
        ->call('confirmBathRoomTypeDeletion', $bathRoomType->id);

    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit("bath-room-type-mgmt:delete:{$this->app['auth']->id()}", 60);
    }

    $component->dispatch('modal-confirmed')
        ->assertStatus(429);

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
