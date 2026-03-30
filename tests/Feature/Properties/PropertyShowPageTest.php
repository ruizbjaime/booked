<?php

use App\Models\BathRoomType;
use App\Models\Bedroom;
use App\Models\BedType;
use App\Models\Country;
use App\Models\Property;
use App\Models\Role;
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

test('renders accommodation section with bedrooms', function () {
    $property = Property::factory()->forUser($this->host)->create();
    $bedroom = Bedroom::factory()->create([
        'property_id' => $property->id,
        'en_name' => 'Main Bedroom',
        'es_name' => 'Habitación principal',
    ]);
    $bedType = BedType::factory()->create(['is_active' => true, 'en_name' => 'Queen Bed', 'es_name' => 'Cama Queen']);
    $bathRoomType = BathRoomType::factory()->create(['en_name' => 'Private Bathroom', 'es_name' => 'Baño privado']);
    $sharedBathRoomType = BathRoomType::factory()->create(['en_name' => 'Shared Bathroom', 'es_name' => 'Baño compartido']);
    $bedroom->bedTypes()->attach($bedType->id, ['quantity' => 2]);
    $bedroom->bathRoomTypes()->attach($bathRoomType->id, ['quantity' => 1]);
    $property->bathRoomTypes()->attach($sharedBathRoomType->id, ['quantity' => 3]);

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->assertOk()
        ->assertSee(__('properties.show.sections.accommodation'))
        ->assertSee('Main Bedroom')
        ->assertSee('Habitación principal')
        ->assertSee(__('properties.show.accommodation.bed_types.form.trigger'))
        ->assertSee(__('properties.show.accommodation.bath_room_types.form.trigger'))
        ->assertSee(__('properties.show.accommodation.shared_bath_room_types.form.trigger'))
        ->assertSee('Cama Queen')
        ->assertSee('Baño privado')
        ->assertSee('Baño compartido')
        ->assertSeeHtml('wire:key="property-bedroom-summary-bed-type-'.$bedroom->id.'-'.$bedType->id.'"')
        ->assertSeeHtml('wire:key="property-bedroom-summary-bath-room-type-'.$bedroom->id.'-'.$bathRoomType->id.'"')
        ->assertSeeHtml('wire:key="property-summary-shared-bath-room-type-'.$sharedBathRoomType->id.'"')
        ->assertSee(__('properties.show.accommodation.bed_types.quantity_badge', ['quantity' => 2]));

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

test('opens create bedroom modal from accommodation section', function () {
    $property = Property::factory()->forUser($this->host)->create();

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->call('openCreateBedroomModal')
        ->assertDispatched('open-form-modal', function (string $event, array $params) use ($property) {
            return $event === 'open-form-modal'
                && ($params['name'] ?? null) === 'properties.create-bedroom'
                && ($params['context']['property_id'] ?? null) === $property->id;
        });
});

test('host can add a bedroom via the create bedroom modal', function () {
    $property = Property::factory()->forUser($this->host)->create();

    Livewire::test('properties.create-bedroom-form', ['context' => ['property_id' => $property->id]])
        ->set('en_name', 'Main Bedroom')
        ->set('es_name', 'Habitación principal')
        ->set('en_description', 'Ocean view.')
        ->set('es_description', 'Vista al mar.')
        ->call('save')
        ->assertDispatched('close-form-modal')
        ->assertDispatched('bedroom-created');

    $bedroom = Bedroom::query()->whereBelongsTo($property)->first();

    expect($bedroom)->not->toBeNull()
        ->and($bedroom?->en_name)->toBe('Main Bedroom')
        ->and($bedroom?->slug)->toBe('main-bedroom');
});

test('opens attach bed type modal for a bedroom from accommodation section', function () {
    $property = Property::factory()->forUser($this->host)->create();
    $bedroom = Bedroom::factory()->create(['property_id' => $property->id]);

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->call('openAttachBedTypeModal', $bedroom->id)
        ->assertDispatched('open-form-modal', function (string $event, array $params) use ($bedroom) {
            return $event === 'open-form-modal'
                && ($params['name'] ?? null) === 'properties.attach-bed-type'
                && ($params['context']['bedroom_id'] ?? null) === $bedroom->id;
        });
});

test('opens attach bathroom type modal for a bedroom from accommodation section', function () {
    $property = Property::factory()->forUser($this->host)->create();
    $bedroom = Bedroom::factory()->create(['property_id' => $property->id]);

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->call('openAttachBathRoomTypeModal', $bedroom->id)
        ->assertDispatched('open-form-modal', function (string $event, array $params) use ($bedroom) {
            return $event === 'open-form-modal'
                && ($params['name'] ?? null) === 'properties.attach-bath-room-type'
                && ($params['context']['bedroom_id'] ?? null) === $bedroom->id;
        });
});

test('opens attach shared bathroom type modal from accommodation section', function () {
    $property = Property::factory()->forUser($this->host)->create();

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->call('openAttachSharedBathRoomTypeModal')
        ->assertDispatched('open-form-modal', function (string $event, array $params) use ($property) {
            return $event === 'open-form-modal'
                && ($params['name'] ?? null) === 'properties.attach-shared-bath-room-type'
                && ($params['context']['property_id'] ?? null) === $property->id;
        });
});

test('opens bed type removal confirmation for a bedroom from accommodation section', function () {
    $property = Property::factory()->forUser($this->host)->create();
    $bedroom = Bedroom::factory()->create(['property_id' => $property->id]);
    $bedType = BedType::factory()->create(['is_active' => true, 'en_name' => 'Queen Bed', 'es_name' => 'Cama Queen']);

    $bedroom->bedTypes()->attach($bedType->id, ['quantity' => 2]);

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->call('confirmBedTypeRemoval', $bedroom->id, $bedType->id)
        ->assertDispatched('open-confirm-modal', function (string $event, array $params) use ($bedroom, $bedType) {
            return $event === 'open-confirm-modal'
                && ($params['title'] ?? null) === __('properties.show.accommodation.bed_types.delete.title')
                && str_contains((string) ($params['message'] ?? ''), sprintf('"%s" (#%d)', $bedType->localizedName(), $bedType->id))
                && str_contains((string) ($params['message'] ?? ''), sprintf('"%s" (#%d)', $bedroom->localizedName(), $bedroom->id));
        });
});

test('opens bathroom type removal confirmation for a bedroom from accommodation section', function () {
    $property = Property::factory()->forUser($this->host)->create();
    $bedroom = Bedroom::factory()->create(['property_id' => $property->id]);
    $bathRoomType = BathRoomType::factory()->create(['en_name' => 'Private Bathroom', 'es_name' => 'Baño privado']);

    $bedroom->bathRoomTypes()->attach($bathRoomType->id, ['quantity' => 2]);

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->call('confirmBathRoomTypeRemoval', $bedroom->id, $bathRoomType->id)
        ->assertDispatched('open-confirm-modal', function (string $event, array $params) use ($bedroom, $bathRoomType) {
            return $event === 'open-confirm-modal'
                && ($params['title'] ?? null) === __('properties.show.accommodation.bath_room_types.delete.title')
                && str_contains((string) ($params['message'] ?? ''), sprintf('"%s" (#%d)', $bathRoomType->localizedName(), $bathRoomType->id))
                && str_contains((string) ($params['message'] ?? ''), sprintf('"%s" (#%d)', $bedroom->localizedName(), $bedroom->id));
        });
});

test('opens shared bathroom type removal confirmation from accommodation section', function () {
    $property = Property::factory()->forUser($this->host)->create();
    $bathRoomType = BathRoomType::factory()->create(['en_name' => 'Shared Bathroom', 'es_name' => 'Baño compartido']);

    $property->bathRoomTypes()->attach($bathRoomType->id, ['quantity' => 2]);

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->call('confirmSharedBathRoomTypeRemoval', $bathRoomType->id)
        ->assertDispatched('open-confirm-modal', function (string $event, array $params) use ($property, $bathRoomType) {
            return $event === 'open-confirm-modal'
                && ($params['title'] ?? null) === __('properties.show.accommodation.shared_bath_room_types.delete.title')
                && str_contains((string) ($params['message'] ?? ''), sprintf('"%s" (#%d)', $bathRoomType->localizedName(), $bathRoomType->id))
                && str_contains((string) ($params['message'] ?? ''), $property->label());
        });
});

test('refreshes accommodation when a bed type is attached', function () {
    $property = Property::factory()->forUser($this->host)->create();
    $bedroom = Bedroom::factory()->create([
        'property_id' => $property->id,
        'en_name' => 'Main Bedroom',
    ]);
    $bedType = BedType::factory()->create(['is_active' => true, 'en_name' => 'Queen Bed', 'es_name' => 'Cama Queen']);

    $component = Livewire::test('pages::properties.show', ['property' => (string) $property->id]);

    $bedroom->bedTypes()->attach($bedType->id, ['quantity' => 2]);

    $component->call('refreshAccommodation');

    $refreshedBedroom = $component->instance()->accommodationBedrooms->firstWhere('id', $bedroom->id);

    expect($refreshedBedroom)->not->toBeNull()
        ->and($refreshedBedroom?->bedTypes)->toHaveCount(1)
        ->and($refreshedBedroom?->bedTypes->first()?->es_name)->toBe('Cama Queen')
        ->and($refreshedBedroom?->bedTypes->first()?->pivot->quantity)->toBe(2);
});

test('refreshes accommodation when a bathroom type is attached', function () {
    $property = Property::factory()->forUser($this->host)->create();
    $bedroom = Bedroom::factory()->create([
        'property_id' => $property->id,
        'en_name' => 'Main Bedroom',
    ]);
    $bathRoomType = BathRoomType::factory()->create(['en_name' => 'Private Bathroom', 'es_name' => 'Baño privado']);

    $component = Livewire::test('pages::properties.show', ['property' => (string) $property->id]);

    $bedroom->bathRoomTypes()->attach($bathRoomType->id, ['quantity' => 2]);

    $component->call('refreshAccommodation');

    $refreshedBedroom = $component->instance()->accommodationBedrooms->firstWhere('id', $bedroom->id);

    expect($refreshedBedroom)->not->toBeNull()
        ->and($refreshedBedroom?->bathRoomTypes)->toHaveCount(1)
        ->and($refreshedBedroom?->bathRoomTypes->first()?->es_name)->toBe('Baño privado')
        ->and($refreshedBedroom?->bathRoomTypes->first()?->pivot->quantity)->toBe(2);
});

test('refreshes accommodation when a shared bathroom type is attached', function () {
    $property = Property::factory()->forUser($this->host)->create();
    $bathRoomType = BathRoomType::factory()->create(['en_name' => 'Shared Bathroom', 'es_name' => 'Baño compartido']);

    $component = Livewire::test('pages::properties.show', ['property' => (string) $property->id]);

    $property->bathRoomTypes()->attach($bathRoomType->id, ['quantity' => 2]);

    $component->call('refreshAccommodation');

    expect($component->instance()->sharedBathRoomTypes)->toHaveCount(1)
        ->and($component->instance()->sharedBathRoomTypes->first()?->es_name)->toBe('Baño compartido')
        ->and($component->instance()->sharedBathRoomTypes->first()?->pivot->quantity)->toBe(2);
});

test('host can remove a bed type from the accommodation section', function () {
    $property = Property::factory()->forUser($this->host)->create();
    $bedroom = Bedroom::factory()->create([
        'property_id' => $property->id,
        'en_name' => 'Main Bedroom',
        'es_name' => 'Habitación principal',
    ]);
    $bedType = BedType::factory()->create(['is_active' => true, 'en_name' => 'Queen Bed', 'es_name' => 'Cama Queen']);

    $bedroom->bedTypes()->attach($bedType->id, ['quantity' => 2]);

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->call('confirmBedTypeRemoval', $bedroom->id, $bedType->id)
        ->dispatch('modal-confirmed')
        ->assertSet('bedroomIdPendingBedTypeRemoval', null)
        ->assertSet('bedTypeIdPendingRemoval', null);

    expect($bedroom->fresh()->bedTypes)->toBeEmpty();
});

test('host can remove a bathroom type from the accommodation section', function () {
    $property = Property::factory()->forUser($this->host)->create();
    $bedroom = Bedroom::factory()->create([
        'property_id' => $property->id,
        'en_name' => 'Main Bedroom',
        'es_name' => 'Habitación principal',
    ]);
    $bathRoomType = BathRoomType::factory()->create(['en_name' => 'Private Bathroom', 'es_name' => 'Baño privado']);

    $bedroom->bathRoomTypes()->attach($bathRoomType->id, ['quantity' => 2]);

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->call('confirmBathRoomTypeRemoval', $bedroom->id, $bathRoomType->id)
        ->dispatch('modal-confirmed')
        ->assertSet('bedroomIdPendingBathRoomTypeRemoval', null)
        ->assertSet('bathRoomTypeIdPendingRemoval', null);

    expect($bedroom->fresh()->bathRoomTypes)->toBeEmpty();
});

test('host can remove a shared bathroom type from the accommodation section', function () {
    $property = Property::factory()->forUser($this->host)->create();
    $bedroom = Bedroom::factory()->create(['property_id' => $property->id]);
    $bathRoomType = BathRoomType::factory()->create(['en_name' => 'Shared Bathroom', 'es_name' => 'Baño compartido']);
    $bedroomBathRoomType = BathRoomType::factory()->create(['en_name' => 'Private Bathroom', 'es_name' => 'Baño privado']);

    $property->bathRoomTypes()->attach($bathRoomType->id, ['quantity' => 2]);
    $bedroom->bathRoomTypes()->attach($bedroomBathRoomType->id, ['quantity' => 1]);

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->call('confirmSharedBathRoomTypeRemoval', $bathRoomType->id)
        ->dispatch('modal-confirmed')
        ->assertSet('sharedBathRoomTypeIdPendingRemoval', null);

    expect($property->fresh()->bathRoomTypes)->toBeEmpty()
        ->and($bedroom->fresh()->bathRoomTypes)->toHaveCount(1);
});

test('show page keeps accommodation separate from the base property payload', function () {
    $property = Property::factory()->forUser($this->host)->create();
    $bedroom = Bedroom::factory()->create(['property_id' => $property->id]);
    $bedType = BedType::factory()->create(['is_active' => true]);
    $bathRoomType = BathRoomType::factory()->create();
    $sharedBathRoomType = BathRoomType::factory()->create();

    $bedroom->bedTypes()->attach($bedType->id, ['quantity' => 2]);
    $bedroom->bathRoomTypes()->attach($bathRoomType->id, ['quantity' => 1]);
    $property->bathRoomTypes()->attach($sharedBathRoomType->id, ['quantity' => 2]);

    $component = Livewire::test('pages::properties.show', ['property' => (string) $property->id]);

    expect($component->instance()->property()->relationLoaded('bedrooms'))->toBeFalse()
        ->and($component->instance()->accommodationBedrooms)->toHaveCount(1)
        ->and($component->instance()->accommodationBedrooms->first()?->bedTypes)->toHaveCount(1)
        ->and($component->instance()->accommodationBedrooms->first()?->bathRoomTypes)->toHaveCount(1)
        ->and($component->instance()->sharedBathRoomTypes)->toHaveCount(1);
});

test('read-only accommodation summary does not render add bed type action', function () {
    $property = Property::factory()->forUser($this->host)->create();
    Bedroom::factory()->create([
        'property_id' => $property->id,
        'en_name' => 'Main Bedroom',
        'es_name' => 'Habitación principal',
    ]);

    $hostRole = Role::query()->where('name', 'host')->firstOrFail();
    $hostRole->revokePermissionTo('property.update');

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->assertDontSeeHtml('wire:click="openAttachBedTypeModal')
        ->assertDontSeeHtml('wire:click="openAttachBathRoomTypeModal')
        ->assertDontSeeHtml('wire:click="openAttachSharedBathRoomTypeModal');
});

test('bedroom creation modal validates required names', function (string $field) {
    $property = Property::factory()->forUser($this->host)->create();

    Livewire::test('properties.create-bedroom-form', ['context' => ['property_id' => $property->id]])
        ->set('en_name', 'Main Bedroom')
        ->set('es_name', 'Habitación principal')
        ->set($field, '')
        ->call('save')
        ->assertHasErrors([$field]);

    expect(Bedroom::query()->whereBelongsTo($property)->exists())->toBeFalse();
})->with(['en_name', 'es_name']);

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
