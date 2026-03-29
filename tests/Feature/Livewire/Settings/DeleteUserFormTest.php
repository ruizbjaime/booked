<?php

use App\Livewire\Settings\DeleteUserForm;
use App\Models\Property;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('prevents unverified users from deleting their account', function () {
    $user = User::factory()->unverified()->create(['password' => 'password']);

    Livewire::actingAs($user)
        ->test(DeleteUserForm::class)
        ->set('password', 'password')
        ->call('deleteUser')
        ->assertForbidden();
});

it('prevents self-deletion when user owns properties', function () {
    $user = User::factory()->create(['password' => 'password']);
    Property::factory()->forUser($user)->create();

    Livewire::actingAs($user)
        ->test(DeleteUserForm::class)
        ->set('password', 'password')
        ->call('deleteUser')
        ->assertHasErrors('password');

    expect(User::query()->find($user->id))->not->toBeNull();
});

it('allows verified user without properties to delete their account', function () {
    $user = User::factory()->create(['password' => 'password']);

    Livewire::actingAs($user)
        ->test(DeleteUserForm::class)
        ->set('password', 'password')
        ->call('deleteUser')
        ->assertRedirect('/');

    expect(User::query()->find($user->id))->toBeNull();
});
