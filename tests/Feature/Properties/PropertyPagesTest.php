<?php

use App\Infrastructure\UiFeedback\ModalService;
use App\Models\Country;
use App\Models\Property;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Carbon;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->host = makeHost();
    $this->actingAs($this->host);
});

function propertiesIndexComponent(?bool $mobileViewport = false): Testable
{
    $component = Livewire::test('pages::properties.index');

    if ($mobileViewport !== null) {
        $component->call('syncTableViewport', $mobileViewport);
    }

    return $component;
}

function propertyDeleteModalMessage(Property $property): string
{
    return __('properties.index.confirm_delete.message', [
        'property' => $property->label(),
    ]);
}

function propertyShowDeleteModalMessage(Property $property): string
{
    return __('properties.show.quick_actions.delete.message', [
        'property' => $property->label(),
    ]);
}

test('hosts can visit the properties index page', function () {
    $this->get(route('properties.index'))
        ->assertOk()
        ->assertSeeText(__('properties.index.title'));
});

test('hosts can visit the properties show page', function () {
    $country = Country::factory()->create(['en_name' => 'Colombia', 'es_name' => 'Colombia']);

    $property = Property::factory()->forUser($this->host)->create([
        'slug' => 'beach_house',
        'name' => 'Beach House',
        'city' => 'Cartagena',
        'address' => 'Calle 123 #45-67',
        'country_id' => $country->id,
        'is_active' => true,
    ]);

    $this->get(route('properties.show', $property))
        ->assertOk()
        ->assertSeeText(__('properties.show.placeholder_title'))
        ->assertSeeText('Beach House')
        ->assertSeeText('Cartagena')
        ->assertSeeText('Calle 123 #45-67')
        ->assertSeeText('Colombia');
});

test('admins cannot visit the properties index page', function () {
    $admin = makeAdmin();

    $this->actingAs($admin);

    $this->get(route('properties.index'))->assertForbidden();
});

test('admins cannot visit the properties show page', function () {
    $admin = makeAdmin();
    $property = Property::factory()->create();

    $this->actingAs($admin);

    $this->get(route('properties.show', $property))->assertNotFound();
});

test('guests cannot visit the properties index page', function () {
    $this->actingAs(makeGuest());

    $this->get(route('properties.index'))->assertForbidden();
});

test('guests cannot visit the properties show page', function () {
    $property = Property::factory()->create();

    $this->actingAs(makeGuest());

    $this->get(route('properties.show', $property))->assertNotFound();
});

test('sidebar shows the properties navigation item for hosts', function () {
    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSeeText(__('properties.navigation.label'));
});

test('sidebar hides the properties navigation item for admins', function () {
    $this->actingAs(makeAdmin());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertDontSeeText(__('properties.navigation.label'));
});

test('sidebar hides the properties navigation item for guests', function () {
    $this->actingAs(makeGuest());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertDontSeeText(__('properties.navigation.label'));
});

test('properties index sorts by localized name asc by default', function () {
    Property::factory()->forUser($this->host)->create(['name' => 'Zulu Property']);
    Property::factory()->forUser($this->host)->create(['name' => 'Alpha Property']);

    propertiesIndexComponent()
        ->assertSeeInOrder(['Alpha Property', 'Zulu Property'])
        ->assertSet('sortBy', 'name')
        ->assertSet('sortDirection', 'asc');
});

test('properties index can sort by city', function () {
    Property::factory()->forUser($this->host)->create(['name' => 'Beach House', 'city' => 'Zurich']);
    Property::factory()->forUser($this->host)->create(['name' => 'Lake Cabin', 'city' => 'Amsterdam']);

    propertiesIndexComponent()
        ->call('sort', 'city')
        ->assertSeeInOrder(['Lake Cabin', 'Beach House'])
        ->assertSet('sortBy', 'city')
        ->assertSet('sortDirection', 'asc');
});

test('properties index can sort by created_at', function () {
    Property::factory()->forUser($this->host)->create([
        'name' => 'Older Property',
        'created_at' => Carbon::parse('2026-03-10 09:00:00'),
    ]);

    Property::factory()->forUser($this->host)->create([
        'name' => 'Newest Property',
        'created_at' => Carbon::parse('2026-03-15 09:00:00'),
    ]);

    propertiesIndexComponent()
        ->call('sort', 'created_at')
        ->assertSeeInOrder(['Newest Property', 'Older Property'])
        ->assertSet('sortBy', 'created_at')
        ->assertSet('sortDirection', 'desc');
});

test('properties index search filters by property, city, and country', function () {
    $colombia = Country::factory()->create(['en_name' => 'Colombia', 'es_name' => 'Colombia']);
    $peru = Country::factory()->create(['en_name' => 'Peru', 'es_name' => 'Perú']);

    Property::factory()->forUser($this->host)->create([
        'name' => 'Beach House',
        'city' => 'Cartagena',
        'address' => 'Centro Historico 101',
        'country_id' => $colombia->id,
    ]);

    Property::factory()->forUser($this->host)->create([
        'name' => 'Mountain Cabin',
        'city' => 'Cusco',
        'address' => 'Valle Sagrado 202',
        'country_id' => $peru->id,
    ]);

    propertiesIndexComponent()
        ->set('search', 'Cartagena')
        ->assertSee('Beach House')
        ->assertDontSee('Mountain Cabin')
        ->set('search', 'Sagrado')
        ->assertSee('Mountain Cabin')
        ->assertDontSee('Beach House')
        ->set('search', 'Peru')
        ->assertSee('Mountain Cabin')
        ->assertDontSee('Beach House');
});

test('properties index shows active and inactive badges', function () {
    Property::factory()->forUser($this->host)->create(['name' => 'Active Home', 'is_active' => true]);
    Property::factory()->forUser($this->host)->create(['name' => 'Inactive Home', 'is_active' => false]);

    propertiesIndexComponent()
        ->assertSee(__('properties.index.status.active'))
        ->assertSee(__('properties.index.status.inactive'));
});

test('host can open the property create modal from the index', function () {
    $component = propertiesIndexComponent()
        ->call('openCreatePropertyModal');

    expect(collect(data_get($component->effects, 'dispatches', []))->contains(function (array $dispatch): bool {
        return ($dispatch['name'] ?? null) === 'open-form-modal'
            && ($dispatch['params']['name'] ?? null) === 'properties.create'
            && ($dispatch['params']['title'] ?? null) === __('properties.create.title')
            && ($dispatch['params']['description'] ?? null) === __('properties.create.description');
    }))->toBeTrue();
});

test('host can delete a property from the properties index', function () {
    $property = Property::factory()->forUser($this->host)->create([
        'name' => 'Delete Me',
    ]);

    propertiesIndexComponent()
        ->call('confirmPropertyDeletion', $property->id)
        ->assertSet('propertyIdPendingDeletion', $property->id)
        ->assertDispatched('open-confirm-modal', function (string $event, array $params) use ($property) {
            return $event === 'open-confirm-modal'
                && ($params['title'] ?? null) === __('properties.index.confirm_delete.title')
                && ($params['message'] ?? null) === propertyDeleteModalMessage($property)
                && ($params['confirmLabel'] ?? null) === __('properties.index.confirm_delete.confirm_label')
                && ($params['variant'] ?? null) === ModalService::VARIANT_PASSWORD;
        })
        ->dispatch('modal-confirmed')
        ->assertSet('propertyIdPendingDeletion', null)
        ->assertDontSee('Delete Me');

    expect(Property::query()->find($property->id))->toBeNull();
});

test('properties index clears a pending deletion when the confirm modal is cancelled', function () {
    $property = Property::factory()->forUser($this->host)->create();

    propertiesIndexComponent()
        ->call('confirmPropertyDeletion', $property->id)
        ->assertSet('propertyIdPendingDeletion', $property->id)
        ->dispatch('modal-confirm-cancelled')
        ->assertSet('propertyIdPendingDeletion', null);
});

test('modal service resolves the property create form component', function () {
    Livewire::test('modal-service')
        ->dispatch('open-form-modal',
            name: 'properties.create',
            title: __('properties.create.title'),
            description: __('properties.create.description'),
        )
        ->assertSet('formModalName', 'properties.create')
        ->assertSee(__('properties.create.fields.name'));
});

test('host can create a property from the create modal', function () {
    $country = Country::factory()->create(['en_name' => 'Colombia', 'es_name' => 'Colombia']);

    Livewire::test('properties.create-property-form')
        ->assertSet('is_active', true)
        ->assertSet('country_id', $country->id)
        ->set('country_id', $country->id)
        ->set('name', 'Beach House')
        ->set('city', 'Cartagena')
        ->set('address', 'Calle 123 #45-67')
        ->set('is_active', false)
        ->call('save')
        ->assertSet('name', '')
        ->assertSet('city', '')
        ->assertSet('address', '')
        ->assertSet('country_id', $country->id)
        ->assertSet('is_active', true)
        ->assertDispatched('close-form-modal')
        ->assertDispatched('property-created');

    $created = Property::query()->where('slug', 'beach_house')->first();

    expect($created)->not->toBeNull()
        ->and($created?->name)->toBe('Beach House')
        ->and($created?->city)->toBe('Cartagena')
        ->and($created?->address)->toBe('Calle 123 #45-67')
        ->and($created?->country?->en_name)->toBe('Colombia')
        ->and($created?->is_active)->toBeFalse();
});

test('property create form generates an underscored slug from the name', function () {
    $country = Country::factory()->create(['en_name' => 'Colombia', 'es_name' => 'Colombia']);

    Livewire::test('properties.create-property-form')
        ->set('name', 'Casa de Playa')
        ->set('city', 'Cartagena')
        ->set('address', 'Bocagrande 300')
        ->set('country_id', $country->id)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('property-created');

    $created = Property::query()->where('name', 'Casa de Playa')->first();

    expect($created)->not->toBeNull()
        ->and($created?->slug)->toBe('casa_de_playa');
});

test('property create form adds a four letter suffix when generated slug already exists', function () {
    $country = Country::factory()->create(['en_name' => 'Colombia', 'es_name' => 'Colombia']);

    Property::factory()->create([
        'slug' => 'casa_de_playa',
        'name' => 'Casa de Playa Original',
    ]);

    Livewire::test('properties.create-property-form')
        ->set('name', 'Casa de Playa')
        ->set('city', 'Cartagena')
        ->set('address', 'Bocagrande 301')
        ->set('country_id', $country->id)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('property-created');

    $created = Property::query()->where('name', 'Casa de Playa')->first();

    expect($created)->not->toBeNull()
        ->and($created?->slug)->toMatch('/^casa_de_playa_[a-z]{4}$/');
});

test('property create form falls back to the default slug when the generated slug is empty', function () {
    $country = Country::factory()->create(['en_name' => 'Colombia', 'es_name' => 'Colombia']);

    Livewire::test('properties.create-property-form')
        ->set('name', '!!!')
        ->set('city', 'Cartagena')
        ->set('address', 'Bocagrande 302')
        ->set('country_id', $country->id)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('property-created');

    $created = Property::query()->where('name', '!!!')->first();

    expect($created)->not->toBeNull()
        ->and($created?->slug)->toBe('property');
});

test('property create form validates required fields', function () {
    Livewire::test('properties.create-property-form')
        ->set('name', '')
        ->set('city', '')
        ->set('address', '')
        ->set('country_id', null)
        ->call('save')
        ->assertHasErrors(['name', 'city', 'address', 'country_id'])
        ->assertNotDispatched('property-created');
});

test('property create form renders successfully for hosts', function () {
    $country = Country::factory()->create(['en_name' => 'Colombia', 'es_name' => 'Colombia']);

    Livewire::test('properties.create-property-form')
        ->assertOk()
        ->assertSet('country_id', $country->id)
        ->assertSee(__('properties.create.fields.name'))
        ->assertSee(__('properties.create.fields.address'))
        ->assertSee(__('properties.create.submit'));
});

test('property create form renders countries as a Flux select', function () {
    $country = Country::factory()->create(['en_name' => 'Colombia', 'es_name' => 'Colombia']);

    Livewire::test('properties.create-property-form')
        ->assertSee($country->localizedName())
        ->assertSeeHtml('name="country_id"')
        ->assertSeeHtml('id="create-property-country"');
});

test('properties index resets pagination when a property is created', function () {
    Property::factory()->forUser($this->host)->count(15)->create();

    propertiesIndexComponent()
        ->set('perPage', 10)
        ->call('gotoPage', 2)
        ->assertSet('paginators.page', 2)
        ->dispatch('property-created')
        ->assertSet('paginators.page', 1);
});

test('properties index search input defines non-auth autofill metadata', function () {
    propertiesIndexComponent()
        ->assertSeeHtml('name="properties_search"')
        ->assertSeeHtml('id="properties-search"')
        ->assertSeeHtml('autocomplete="off"');
});

test('properties pages render successfully as livewire components', function () {
    propertiesIndexComponent()
        ->assertOk()
        ->assertSee(__('properties.index.title'));

    $property = Property::factory()->forUser($this->host)->create();

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->assertOk()
        ->assertSee(__('properties.show.placeholder_title'))
        ->assertSee($property->name);
});

test('show page opens delete confirmation from quick actions', function () {
    $property = Property::factory()->forUser($this->host)->create([
        'name' => 'Delete Sidebar',
    ]);

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->call('confirmPropertyDeletion')
        ->assertSet('propertyIdPendingDeletion', $property->id)
        ->assertDispatched('open-confirm-modal', function (string $event, array $params) use ($property) {
            return $event === 'open-confirm-modal'
                && ($params['title'] ?? null) === __('properties.show.quick_actions.delete.title')
                && ($params['message'] ?? null) === propertyShowDeleteModalMessage($property)
                && ($params['confirmLabel'] ?? null) === __('properties.show.quick_actions.delete.confirm_label');
        });
});

test('show page deletes a property via the modal confirmed handler and redirects to index', function () {
    $property = Property::factory()->forUser($this->host)->create([
        'name' => 'Delete Target',
    ]);

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->call('confirmPropertyDeletion')
        ->assertSet('propertyIdPendingDeletion', $property->id)
        ->dispatch('modal-confirmed')
        ->assertSet('propertyIdPendingDeletion', null)
        ->assertRedirect(route('properties.index'))
        ->assertDispatched('toast-show', function (string $event, array $params) {
            return $event === 'toast-show'
                && str_contains((string) ($params['slots']['text'] ?? ''), 'Delete Target');
        });

    expect(Property::query()->find($property->id))->toBeNull();
});

test('show page clears pending deletion when confirm modal is cancelled', function () {
    $property = Property::factory()->forUser($this->host)->create();

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->call('confirmPropertyDeletion')
        ->assertSet('propertyIdPendingDeletion', $property->id)
        ->dispatch('modal-confirm-cancelled')
        ->assertSet('propertyIdPendingDeletion', null);
});

test('host cannot see properties owned by another host on the index', function () {
    Property::factory()->forUser($this->host)->create(['name' => 'My Property']);

    $otherHost = makeHost();
    Property::factory()->forUser($otherHost)->create(['name' => 'Other Property']);

    propertiesIndexComponent()
        ->assertSee('My Property')
        ->assertDontSee('Other Property');
});

test('host cannot access a property owned by another host on the show page', function () {
    $otherHost = makeHost();
    $property = Property::factory()->forUser($otherHost)->create();

    $this->get(route('properties.show', $property))->assertNotFound();
});
