<?php

use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

it('renders successfully', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->actingAs(makeAdmin());

    Livewire::test('charge-bases.create-charge-basis-form')
        ->assertOk()
        ->assertSee(__('charge_bases.create.fields.en_name'))
        ->assertSee(__('charge_bases.create.submit'));
});
