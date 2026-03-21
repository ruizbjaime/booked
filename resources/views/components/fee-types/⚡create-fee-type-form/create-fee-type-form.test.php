<?php

use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

it('renders successfully', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->actingAs(makeAdmin());

    Livewire::test('fee-types.create-fee-type-form')
        ->assertOk()
        ->assertSee(__('fee_types.create.fields.en_name'))
        ->assertSee(__('fee_types.create.submit'));
});
