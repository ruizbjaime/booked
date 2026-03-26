<?php

use App\Models\SeasonBlock;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->actingAs(makeAdmin());
});

it('renders successfully', function () {
    Livewire::test('calendar.season-block-form', ['context' => ['mode' => 'create']])
        ->assertStatus(200);
});

it('shows existing values in edit mode', function () {
    $seasonBlock = SeasonBlock::factory()->fixedRange(6, 1, 6, 30)->create([
        'name' => 'mid_year_break',
        'en_name' => 'Mid-year Break',
        'es_name' => 'Receso de Mitad de Año',
    ]);

    Livewire::test('calendar.season-block-form', ['context' => ['mode' => 'edit', 'seasonBlockId' => $seasonBlock->id]])
        ->assertSet('name', 'mid_year_break')
        ->assertSet('fixed_start_month', 6)
        ->assertSet('fixed_end_day', 30);
});
