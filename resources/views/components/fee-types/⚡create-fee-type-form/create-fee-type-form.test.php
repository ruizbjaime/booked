<?php

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

    actingAs($admin);

    Livewire::test('fee-types.create-fee-type-form')
        ->assertOk()
        ->assertSee(__('fee_types.create.fields.en_name'))
        ->assertSee(__('fee_types.create.submit'));
});
