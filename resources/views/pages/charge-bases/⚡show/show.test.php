<?php

use App\Models\ChargeBasis;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

it('renders successfully', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $chargeBasis = ChargeBasis::factory()->create();

    $this->actingAs(makeAdmin());

    Livewire::test('pages::charge-bases.show', ['chargeBasis' => (string) $chargeBasis->id])
        ->assertOk()
        ->assertSee($chargeBasis->localizedName());
});
