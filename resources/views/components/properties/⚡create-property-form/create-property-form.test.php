<?php

use App\Models\Country;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

it('renders successfully', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $host = User::factory()->create();
    $host->assignRole('host');

    $this->actingAs($host);

    Country::factory()->create(['en_name' => 'Colombia', 'es_name' => 'Colombia']);

    Livewire::test('properties.create-property-form')
        ->assertOk()
        ->assertSee(__('properties.create.fields.name'))
        ->assertSee(__('properties.create.fields.country'))
        ->assertSee(__('properties.create.submit'));
});

it('forbids non-host users from rendering the create property form', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $guest = User::factory()->create();
    $guest->assignRole('guest');

    $this->actingAs($guest);

    Livewire::test('properties.create-property-form')
        ->assertForbidden();
});

it('filters countries by the search term', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $host = User::factory()->create();
    $host->assignRole('host');

    $this->actingAs($host);

    Country::factory()->create(['en_name' => 'Colombia', 'es_name' => 'Colombia']);
    Country::factory()->create(['en_name' => 'Peru', 'es_name' => 'Peru']);

    Livewire::test('properties.create-property-form')
        ->set('countrySearch', 'Peru')
        ->assertSee('Peru')
        ->assertDontSee('Colombia');
});

it('does not default the country when colombia is unavailable', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $host = User::factory()->create();
    $host->assignRole('host');

    $this->actingAs($host);

    Country::factory()->create(['en_name' => 'Peru', 'es_name' => 'Peru']);
    Country::factory()->inactive()->create(['en_name' => 'Colombia', 'es_name' => 'Colombia']);

    Livewire::test('properties.create-property-form')
        ->assertSet('country_id', null);
});

it('rejects inactive countries during creation', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $host = User::factory()->create();
    $host->assignRole('host');

    $this->actingAs($host);

    $inactiveCountry = Country::factory()->inactive()->create();

    Livewire::test('properties.create-property-form')
        ->set('name', 'Beach House')
        ->set('city', 'Cartagena')
        ->set('address', 'Calle 123 #45-67')
        ->set('country_id', $inactiveCountry->id)
        ->call('save')
        ->assertHasErrors(['country_id'])
        ->assertNotDispatched('property-created');
});

it('clears field validation errors when a property input is updated', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $host = User::factory()->create();
    $host->assignRole('host');

    $this->actingAs($host);

    Livewire::test('properties.create-property-form')
        ->call('save')
        ->assertHasErrors(['name'])
        ->set('name', 'Beach House')
        ->assertHasNoErrors(['name']);
});
