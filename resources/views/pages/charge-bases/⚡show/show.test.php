<?php

use App\Models\ChargeBasis;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

it('renders successfully', function () {
    seed(RolesAndPermissionsSeeder::class);

    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $chargeBasis = ChargeBasis::factory()->create();

    actingAs($admin);

    Livewire::test('pages::charge-bases.show', ['chargeBasis' => (string) $chargeBasis->id])
        ->assertOk()
        ->assertSee($chargeBasis->localizedName());
});
