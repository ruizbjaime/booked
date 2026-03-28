<?php

use App\Models\Country;
use App\Models\Property;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $host = User::factory()->create();
    $host->assignRole('host');

    $this->actingAs($host);
});

test('renders show page with property details', function () {
    $country = Country::factory()->create(['en_name' => 'Colombia', 'es_name' => 'Colombia']);

    $property = Property::factory()->create([
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
    $property = Property::factory()->create([
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

test('autosaves property country changes', function () {
    $originalCountry = Country::factory()->create(['en_name' => 'Colombia', 'es_name' => 'Colombia']);
    $newCountry = Country::factory()->create(['en_name' => 'Peru', 'es_name' => 'Perú']);

    $property = Property::factory()->create([
        'country_id' => $originalCountry->id,
    ]);

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->call('startEditingSection', 'details')
        ->set('country_id', $newCountry->id)
        ->assertDispatched('toast-show');

    expect($property->fresh()->country_id)->toBe($newCountry->id);
});

test('active toggle autosaves on property show page', function () {
    $property = Property::factory()->create(['is_active' => true]);

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->call('startEditingSection', 'details')
        ->set('is_active', false)
        ->assertDispatched('toast-show');

    expect($property->fresh()->is_active)->toBeFalse();
});

test('validates required fields on autosave', function (string $field) {
    $property = Property::factory()->create();

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->call('startEditingSection', 'details')
        ->set($field, '')
        ->assertHasErrors([$field]);

    expect((string) $property->fresh()->{$field})->not->toBe('');
})->with(['name', 'city', 'address']);

test('validates inactive countries cannot be selected on autosave', function () {
    $property = Property::factory()->create();
    $inactiveCountry = Country::factory()->inactive()->create();

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->call('startEditingSection', 'details')
        ->set('country_id', $inactiveCountry->id)
        ->assertHasErrors(['country_id']);
});

test('cancel editing section restores original values and clears validation', function () {
    $property = Property::factory()->create([
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

test('autosave does not trigger without active editing section', function () {
    $property = Property::factory()->create([
        'name' => 'Unchanged',
    ]);

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->assertSet('editingSection', null)
        ->set('name', 'Should Not Save')
        ->assertNotDispatched('toast-show');

    expect($property->fresh()->name)->toBe('Unchanged');
});

test('start editing section with invalid section returns 404', function () {
    $property = Property::factory()->create();

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->call('startEditingSection', 'nonexistent')
        ->assertNotFound();
});

test('show page autosave is rate limited for property name', function () {
    $property = Property::factory()->create();

    $component = Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->call('startEditingSection', 'details');

    for ($i = 0; $i < 10; $i++) {
        RateLimiter::hit("property-mgmt:autosave:{$this->app['auth']->id()}", 60);
    }

    $component->set('name', 'Rate Limited Name')
        ->assertDispatched('open-info-modal');
});

test('show page active toggle is rate limited', function () {
    $property = Property::factory()->create(['is_active' => true]);

    $component = Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->call('startEditingSection', 'details');

    for ($i = 0; $i < 10; $i++) {
        RateLimiter::hit("property-mgmt:toggle-active:{$this->app['auth']->id()}", 60);
    }

    $component->set('is_active', false)
        ->assertDispatched('open-info-modal');
});

test('show page delete confirmation is rate limited', function () {
    $property = Property::factory()->create();

    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit("property-mgmt:delete:{$this->app['auth']->id()}", 60);
    }

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->call('confirmPropertyDeletion')
        ->assertDispatched('open-info-modal');
});

test('show page modal-confirmed is rate limited', function () {
    $property = Property::factory()->create();

    $component = Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->call('confirmPropertyDeletion');

    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit("property-mgmt:confirmed-action:{$this->app['auth']->id()}", 60);
    }

    $component->dispatch('modal-confirmed')
        ->assertDispatched('open-info-modal');

    expect(Property::query()->find($property->id))->not->toBeNull();
});

test('show page renders edit and delete controls for hosts', function () {
    $property = Property::factory()->create();

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->assertSeeHtml('wire:click="startEditingSection')
        ->assertSeeHtml('wire:click="confirmPropertyDeletion');
});

test('non-host cannot view property show page', function () {
    $property = Property::factory()->create();

    $this->actingAs(makeGuest());

    Livewire::test('pages::properties.show', ['property' => (string) $property->id])
        ->assertForbidden();
});
