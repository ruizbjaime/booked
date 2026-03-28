<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;
use Tests\Fixtures\Table\Components\PaginatedTableComponent;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed(RolesAndPermissionsSeeder::class);
    actingAs(makeAdmin());
});

test('pagination defaults to 10 per page', function () {
    Livewire::test(PaginatedTableComponent::class)
        ->assertSet('perPage', 10);
});

test('per page can be changed to valid options', function () {
    Livewire::test(PaginatedTableComponent::class)
        ->set('perPage', 25)
        ->assertSet('perPage', 25);
});

test('invalid per page falls back to first option', function () {
    Livewire::test(PaginatedTableComponent::class)
        ->set('perPage', 0)
        ->assertSet('perPage', 10)
        ->set('perPage', 999)
        ->assertSet('perPage', 10);
});

test('changing per page resets pagination to first page', function () {
    User::factory()->count(30)->create();

    $component = Livewire::test(PaginatedTableComponent::class)
        ->set('perPage', 10)
        ->call('nextPage');

    expect($component->instance()->getPage())->toBeGreaterThan(1);

    $component->set('perPage', 25);

    expect($component->instance()->getPage())->toBe(1);
});

test('per page options returns the configured options', function () {
    $component = Livewire::test(PaginatedTableComponent::class);

    expect($component->instance()->perPageOptions())->toBe([10, 15, 25, 50, 100]);
});

test('syncCurrentPage clamps page to last page when current exceeds total', function () {
    User::factory()->count(5)->create();

    $component = Livewire::test(PaginatedTableComponent::class)
        ->set('perPage', 10);

    // Manually navigate to a page that doesn't exist (only 1 page of 5 records)
    $component->instance()->setPage(3);

    // Invoke syncCurrentPage through a computed property re-evaluation
    // by calling the method directly via reflection
    $reflection = new ReflectionMethod($component->instance(), 'syncCurrentPage');
    $reflection->invoke($component->instance(), User::query());

    expect($component->instance()->getPage())->toBe(1);
});
