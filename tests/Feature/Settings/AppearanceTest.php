<?php

use App\Livewire\Settings\Appearance;
use App\Models\User;
use Livewire\Livewire;

test('appearance page is displayed', function () {
    $this->actingAs($user = User::factory()->create());

    $this->get(route('appearance.edit'))
        ->assertOk()
        ->assertSee(__('Appearance'))
        ->assertSee(__('Light'))
        ->assertSee(__('Dark'))
        ->assertSee(__('System'));
});

test('appearance component exposes its title', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Appearance::class)
        ->assertSee(__('Appearance settings'));

    expect(app(Appearance::class)->title())->toBe(__('Appearance settings'));
});
