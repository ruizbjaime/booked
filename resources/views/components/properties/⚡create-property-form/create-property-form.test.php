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
