<?php

use App\Models\Country;
use App\Models\Property;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->host = makeHost();
    $this->actingAs($this->host);
});

test('renders show page with property details', function () {
    $country = Country::factory()->create(['en_name' => 'Colombia', 'es_name' => 'Colombia']);

    $property = Property::factory()->forUser($this->host)->create([
        'name' => 'Beach House',
        'city' => 'Cartagena',
        'address' => 'Calle 123 #45-67',
        'country_id' => $country->id,
        'is_active' => true,
    ]);

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->assertOk()
        ->assertSee('Beach House')
        ->assertSee($property->slug)
        ->assertSee('Cartagena')
        ->assertSee('Calle 123 #45-67')
        ->assertSee('Colombia')
        ->assertSee(__('properties.show.status.active'));
});

test('autosaves property detail field changes', function () {
    $property = Property::factory()->forUser($this->host)->create([
        'name' => 'Old Name',
    ]);

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->call('startEditingSection', 'details')
        ->set('name', 'New Name')
        ->assertDispatched('toast-show', function (string $event, array $params) {
            return ($params['dataset']['variant'] ?? null) === 'success';
        });

    expect($property->fresh()->name)->toBe('New Name');
});

test('autosaving property name also updates the slug', function () {
    $property = Property::factory()->forUser($this->host)->create([
        'name' => 'Old Name',
        'slug' => 'old-name',
    ]);

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->call('startEditingSection', 'details')
        ->set('name', 'Casa de Playa')
        ->assertSee('casa-de-playa')
        ->assertDispatched('toast-show');

    expect($property->fresh()->name)->toBe('Casa de Playa')
        ->and($property->fresh()->slug)->toBe('casa-de-playa');
});

test('autosaving property name adds a suffix when the generated slug already exists', function () {
    Property::factory()->create([
        'name' => 'Casa de Playa Original',
        'slug' => 'casa-de-playa',
    ]);

    $property = Property::factory()->forUser($this->host)->create([
        'name' => 'Old Name',
        'slug' => 'old-name',
    ]);

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->call('startEditingSection', 'details')
        ->set('name', 'Casa de Playa')
        ->assertSee($property->fresh()->slug)
        ->assertDispatched('toast-show');

    expect($property->fresh()->name)->toBe('Casa de Playa')
        ->and($property->fresh()->slug)->toMatch('/^casa-de-playa-[a-z]{4}$/');
});

test('autosaves property country changes', function () {
    $originalCountry = Country::factory()->create(['en_name' => 'Colombia', 'es_name' => 'Colombia']);
    $newCountry = Country::factory()->create(['en_name' => 'Peru', 'es_name' => 'Perú']);

    $property = Property::factory()->forUser($this->host)->create([
        'country_id' => $originalCountry->id,
    ]);

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->call('startEditingSection', 'details')
        ->set('country_id', $newCountry->id)
        ->assertDispatched('toast-show');

    expect($property->fresh()->country_id)->toBe($newCountry->id);
});

test('active toggle autosaves on property show page', function () {
    $property = Property::factory()->forUser($this->host)->create(['is_active' => true]);

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->call('startEditingSection', 'details')
        ->set('is_active', false)
        ->assertDispatched('toast-show');

    expect($property->fresh()->is_active)->toBeFalse();
});

test('active toggle does not autosave outside the editing section', function () {
    $property = Property::factory()->forUser($this->host)->create(['is_active' => true]);

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->assertSet('editingSection', null)
        ->set('is_active', false)
        ->assertNotDispatched('toast-show')
        ->assertSet('is_active', false);

    expect($property->fresh()->is_active)->toBeTrue();
});

test('validates required fields on autosave', function (string $field) {
    $property = Property::factory()->forUser($this->host)->create();

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->call('startEditingSection', 'details')
        ->set($field, '')
        ->assertHasErrors([$field]);

    expect((string) $property->fresh()->{$field})->not->toBe('');
})->with(['name', 'city', 'address']);

test('validates inactive countries cannot be selected on autosave', function () {
    $property = Property::factory()->forUser($this->host)->create();
    $inactiveCountry = Country::factory()->inactive()->create();

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->call('startEditingSection', 'details')
        ->set('country_id', $inactiveCountry->id)
        ->assertHasErrors(['country_id']);
});

test('cancel editing section restores original values and clears validation', function () {
    $property = Property::factory()->forUser($this->host)->create([
        'name' => 'Original Name',
    ]);

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->call('startEditingSection', 'details')
        ->set('name', '')
        ->assertHasErrors(['name'])
        ->call('cancelEditingSection')
        ->assertSet('name', 'Original Name')
        ->assertSet('editingSection', null)
        ->assertHasNoErrors();
});

test('show page filters available countries by country search while editing', function () {
    Country::factory()->create(['en_name' => 'Colombia', 'es_name' => 'Colombia']);
    Country::factory()->create(['en_name' => 'Peru', 'es_name' => 'Peru']);

    $property = Property::factory()->forUser($this->host)->create();

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->call('startEditingSection', 'details')
        ->set('countrySearch', 'Peru')
        ->assertSee('Peru')
        ->assertDontSee('Colombia');
});

test('autosave does not trigger without active editing section', function () {
    $property = Property::factory()->forUser($this->host)->create([
        'name' => 'Unchanged',
    ]);

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->assertSet('editingSection', null)
        ->set('name', 'Should Not Save')
        ->assertNotDispatched('toast-show');

    expect($property->fresh()->name)->toBe('Unchanged');
});

test('start editing section with invalid section returns 404', function () {
    $property = Property::factory()->forUser($this->host)->create();

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->call('startEditingSection', 'nonexistent')
        ->assertNotFound();
});

test('show page autosave is rate limited for property name', function () {
    $property = Property::factory()->forUser($this->host)->create();

    $component = Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->call('startEditingSection', 'details');

    for ($i = 0; $i < 10; $i++) {
        RateLimiter::hit("property-mgmt:autosave:{$this->app['auth']->id()}", 60);
    }

    $component->set('name', 'Rate Limited Name')
        ->assertDispatched('open-info-modal');
});

test('show page active toggle is rate limited', function () {
    $property = Property::factory()->forUser($this->host)->create(['is_active' => true]);

    $component = Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->call('startEditingSection', 'details');

    for ($i = 0; $i < 10; $i++) {
        RateLimiter::hit("property-mgmt:toggle-active:{$this->app['auth']->id()}", 60);
    }

    $component->set('is_active', false)
        ->assertDispatched('open-info-modal');
});

test('show page delete confirmation is rate limited', function () {
    $property = Property::factory()->forUser($this->host)->create();

    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit("property-mgmt:delete:{$this->app['auth']->id()}", 60);
    }

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->call('confirmPropertyDeletion')
        ->assertDispatched('open-info-modal');
});

test('show page modal-confirmed is rate limited', function () {
    $property = Property::factory()->forUser($this->host)->create();

    $component = Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->call('confirmPropertyDeletion');

    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit("property-mgmt:confirmed-action:{$this->app['auth']->id()}", 60);
    }

    $component->dispatch('modal-confirmed')
        ->assertDispatched('open-info-modal');

    expect(Property::query()->find($property->id))->not->toBeNull();
});

test('show page modal-confirmed does nothing when no property is pending deletion', function () {
    $property = Property::factory()->forUser($this->host)->create();

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->assertSet('propertyIdPendingDeletion', null)
        ->dispatch('modal-confirmed')
        ->assertSet('propertyIdPendingDeletion', null)
        ->assertNoRedirect()
        ->assertNotDispatched('toast-show');

    expect(Property::query()->find($property->id))->not->toBeNull();
});

test('show page renders edit and delete controls for hosts', function () {
    $property = Property::factory()->forUser($this->host)->create();

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->assertSeeHtml('wire:click="startEditingSection')
        ->assertSeeHtml('wire:click="confirmPropertyDeletion');
});

test('renders show page with capacity values', function () {
    $property = Property::factory()->forUser($this->host)->withCapacity(2, 6)->create();

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->assertOk()
        ->assertSee(2)
        ->assertSee(6);
});

test('renders show page with null capacity as em-dash', function () {
    $property = Property::factory()->forUser($this->host)->create(['base_capacity' => null, 'max_capacity' => null]);

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->assertOk()
        ->assertSee('—');
});

test('autosaves base_capacity field changes', function () {
    $property = Property::factory()->forUser($this->host)->create(['base_capacity' => null, 'max_capacity' => 6]);

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->call('startEditingSection', 'capacity')
        ->set('base_capacity', 2)
        ->assertDispatched('toast-show', function (string $event, array $params) {
            return ($params['dataset']['variant'] ?? null) === 'success';
        });

    expect($property->fresh()->base_capacity)->toBe(2);
});

test('autosaves max_capacity field changes', function () {
    $property = Property::factory()->forUser($this->host)->create(['base_capacity' => 2, 'max_capacity' => null]);

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->call('startEditingSection', 'capacity')
        ->set('max_capacity', 8)
        ->assertDispatched('toast-show', function (string $event, array $params) {
            return ($params['dataset']['variant'] ?? null) === 'success';
        });

    expect($property->fresh()->max_capacity)->toBe(8);
});

test('capacity autosave does not trigger outside editing section', function () {
    $property = Property::factory()->forUser($this->host)->create(['base_capacity' => 3, 'max_capacity' => 6]);

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->assertSet('editingSection', null)
        ->set('base_capacity', 1)
        ->assertNotDispatched('toast-show');

    expect($property->fresh()->base_capacity)->toBe(3);
});

test('capacity autosave does not trigger when editing details section', function () {
    $property = Property::factory()->forUser($this->host)->create(['base_capacity' => 3, 'max_capacity' => 6]);

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->call('startEditingSection', 'details')
        ->set('base_capacity', 1)
        ->assertNotDispatched('toast-show');

    expect($property->fresh()->base_capacity)->toBe(3);
});

test('start editing capacity section is accepted', function () {
    $property = Property::factory()->forUser($this->host)->create();

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->call('startEditingSection', 'capacity')
        ->assertSet('editingSection', 'capacity');
});

test('validates base_capacity cannot exceed max_capacity on show page', function () {
    $property = Property::factory()->forUser($this->host)->create(['base_capacity' => null, 'max_capacity' => 4]);

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->call('startEditingSection', 'capacity')
        ->set('base_capacity', 5)
        ->assertHasErrors(['base_capacity']);

    expect($property->fresh()->base_capacity)->toBeNull();
});

test('cancel editing capacity section restores original values', function () {
    $property = Property::factory()->forUser($this->host)->withCapacity(2, 6)->create();

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->call('startEditingSection', 'capacity')
        ->set('base_capacity', 99)
        ->call('cancelEditingSection')
        ->assertSet('base_capacity', 2)
        ->assertSet('max_capacity', 6)
        ->assertSet('editingSection', null)
        ->assertHasNoErrors();
});

test('renders show page with description', function () {
    $property = Property::factory()->forUser($this->host)->create([
        'description' => '<p>A lovely property.</p>',
    ]);

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->assertOk()
        ->assertSee('A lovely property.');
});

test('renders show page with null description as em-dash', function () {
    $property = Property::factory()->forUser($this->host)->create(['description' => null]);

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->assertOk()
        ->assertSee('—');
});

test('saves description on button click', function () {
    $property = Property::factory()->forUser($this->host)->create(['description' => null]);

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->call('startEditingSection', 'details')
        ->set('description', '<p>Updated description</p>')
        ->call('saveDescription')
        ->assertDispatched('toast-show', function (string $event, array $params) {
            return ($params['dataset']['variant'] ?? null) === 'success';
        });

    expect($property->fresh()->description)->toBe('<p>Updated description</p>');
});

test('non-host cannot view property show page', function () {
    $property = Property::factory()->create();

    $this->actingAs(makeGuest());

    $this->get(route('properties.show', $property))
        ->assertNotFound();
});

test('host cannot view show page of a property owned by another host', function () {
    $otherHost = makeHost();
    $property = Property::factory()->forUser($otherHost)->create();

    $this->get(route('properties.show', $property))
        ->assertNotFound();
});
