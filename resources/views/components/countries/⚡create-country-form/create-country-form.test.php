<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

it('renders successfully', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin);

    Livewire::test('countries.create-country-form')
        ->assertOk()
        ->assertSee(__('countries.create.fields.en_name'))
        ->assertSee(__('countries.create.submit'));
});
