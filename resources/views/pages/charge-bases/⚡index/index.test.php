<?php

use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

it('renders successfully', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->actingAs(makeAdmin());

    Livewire::test('pages::charge-bases.index')
        ->assertOk()
        ->assertSee(__('charge_bases.index.title'));
});
