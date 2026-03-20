<?php

use App\Domain\Users\RoleConfig;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;
use Tests\Fixtures\Table\Components\DummyTableComponent;
use Tests\Fixtures\Table\Components\JoinedTableComponent;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed(RolesAndPermissionsSeeder::class);
    actingAs(makeAdmin());
});

test('sorting defaults to the configured default column and direction', function () {
    Livewire::test(DummyTableComponent::class)
        ->assertSet('sortBy', 'created_at')
        ->assertSet('sortDirection', 'desc');
});

test('sort toggles direction when clicking the same column', function () {
    Livewire::test(DummyTableComponent::class)
        ->call('sort', 'name')
        ->assertSet('sortBy', 'name')
        ->assertSet('sortDirection', 'asc')
        ->call('sort', 'name')
        ->assertSet('sortDirection', 'desc');
});

test('sort changes to a new column with its default direction', function () {
    Livewire::test(DummyTableComponent::class)
        ->call('sort', 'name')
        ->assertSet('sortBy', 'name')
        ->assertSet('sortDirection', 'asc')
        ->call('sort', 'email')
        ->assertSet('sortBy', 'email')
        ->assertSet('sortDirection', 'asc');
});

test('sort ignores non-sortable columns', function () {
    Livewire::test(DummyTableComponent::class)
        ->call('sort', 'id')
        ->assertSet('sortBy', 'created_at')
        ->assertSet('sortDirection', 'desc');
});

test('sort with invalid column name is ignored', function () {
    Livewire::test(DummyTableComponent::class)
        ->call('sort', 'nonexistent')
        ->assertSet('sortBy', 'created_at')
        ->assertSet('sortDirection', 'desc');
});

test('sort uses column default direction when switching columns', function () {
    Livewire::test(DummyTableComponent::class)
        ->call('sort', 'name')
        ->assertSet('sortBy', 'name')
        ->assertSet('sortDirection', 'asc')
        ->call('sort', 'created_at')
        ->assertSet('sortBy', 'created_at')
        ->assertSet('sortDirection', 'desc');
});

test('invalid sort direction falls back to the active columns default direction', function () {
    User::factory()->create(['name' => 'Descending Result', 'email' => 'zeta@example.com']);
    User::factory()->create(['name' => 'Ascending Result', 'email' => 'alpha@example.com']);

    Livewire::test(DummyTableComponent::class)
        ->set('sortBy', 'email')
        ->set('sortDirection', '')
        ->assertSeeInOrder(['Ascending Result', 'Descending Result']);
});

test('sorting resets pagination to first page', function () {
    User::factory()->count(15)->create();

    $component = Livewire::test(DummyTableComponent::class)
        ->set('perPage', 10)
        ->call('nextPage');

    expect($component->instance()->getPage())->toBeGreaterThan(1);

    $component->call('sort', 'name');

    expect($component->instance()->getPage())->toBe(1);
});

test('sorting uses a qualified key column when the query joins another id column', function () {
    $firstUser = User::factory()->create(['name' => 'Alpha User']);
    $secondUser = User::factory()->create(['name' => 'Zulu User']);

    $firstUser->assignRole(RoleConfig::defaultRole());
    $secondUser->assignRole(RoleConfig::defaultRole());

    Livewire::test(JoinedTableComponent::class)
        ->assertOk()
        ->assertSeeInOrder(['Alpha User', 'Zulu User']);
});
