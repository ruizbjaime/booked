<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

it('renders successfully', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin);

    Livewire::test('users.create-user-form')
        ->assertOk()
        ->assertSee(__('users.create.fields.name'))
        ->assertSee(__('users.create.submit'));
});
