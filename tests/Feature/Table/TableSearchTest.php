<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;
use Tests\Fixtures\Table\Components\SearchableTableComponent;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed(RolesAndPermissionsSeeder::class);
    actingAs(makeAdmin());
});

test('search filters results by searchable fields', function () {
    User::factory()->create(['name' => 'Alice Johnson', 'email' => 'alice@example.com']);
    User::factory()->create(['name' => 'Bob Stone', 'email' => 'bob@example.com']);

    Livewire::test(SearchableTableComponent::class)
        ->set('search', 'alice')
        ->assertSee('Alice Johnson')
        ->assertDontSee('Bob Stone');
});

test('search by email filters correctly', function () {
    User::factory()->create(['name' => 'Alice', 'email' => 'alice@unique.com']);
    User::factory()->create(['name' => 'Bob', 'email' => 'bob@other.com']);

    Livewire::test(SearchableTableComponent::class)
        ->set('search', 'unique')
        ->assertSee('Alice')
        ->assertDontSee('Bob');
});

test('empty search shows all results', function () {
    User::factory()->create(['name' => 'Alice']);
    User::factory()->create(['name' => 'Bob']);

    Livewire::test(SearchableTableComponent::class)
        ->set('search', '')
        ->assertSee('Alice')
        ->assertSee('Bob');
});

test('search treats percent as a literal character', function () {
    User::factory()->create(['name' => '100% Real']);
    User::factory()->create(['name' => '1000 Real']);

    Livewire::test(SearchableTableComponent::class)
        ->set('search', '100%')
        ->assertSee('100% Real')
        ->assertDontSee('1000 Real');
});

test('search treats underscore as a literal character', function () {
    User::factory()->create(['name' => 'foo_bar']);
    User::factory()->create(['name' => 'fooxbar']);

    Livewire::test(SearchableTableComponent::class)
        ->set('search', 'foo_')
        ->assertSee('foo_bar')
        ->assertDontSee('fooxbar');
});

test('search trims query string values before filtering', function () {
    User::factory()->create(['name' => 'Alice Johnson', 'email' => 'alice@example.com']);
    User::factory()->create(['name' => 'Bob Stone', 'email' => 'bob@example.com']);

    Livewire::withQueryParams(['search' => ' alice '])
        ->test(SearchableTableComponent::class)
        ->assertSee('Alice Johnson')
        ->assertDontSee('Bob Stone');
});

test('search is case-insensitive', function () {
    User::factory()->create(['name' => 'Alice Johnson', 'email' => 'alice@example.com']);
    User::factory()->create(['name' => 'Bob Stone', 'email' => 'bob@example.com']);

    Livewire::test(SearchableTableComponent::class)
        ->set('search', 'ALICE')
        ->assertSee('Alice Johnson')
        ->assertDontSee('Bob Stone');
});

test('search resets pagination to first page', function () {
    User::factory()->count(15)->create();

    $component = Livewire::test(SearchableTableComponent::class)
        ->set('perPage', 10)
        ->call('nextPage');

    expect($component->instance()->getPage())->toBeGreaterThan(1);

    $component->set('search', 'test');

    expect($component->instance()->getPage())->toBe(1);
});
