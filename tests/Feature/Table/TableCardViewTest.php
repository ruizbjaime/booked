<?php

use App\Domain\Table\ActionItem;
use App\Domain\Table\Columns\ActionsColumn;
use App\Domain\Table\Columns\AvatarColumn;
use App\Domain\Table\Columns\TextColumn;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Livewire;
use Tests\Fixtures\Table\Components\CardViewTableComponent;
use Tests\Fixtures\Table\Components\MultiActionsCardViewTableComponent;
use Tests\Fixtures\Table\Components\SeparatorOnlyCardViewTableComponent;
use Tests\Fixtures\Table\Components\SortableCardViewTableComponent;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed(RolesAndPermissionsSeeder::class);
    actingAs(makeAdmin());
});

function stringKeyUserRecord(): Model
{
    $record = new class extends Model
    {
        protected $table = 'users';

        public $timestamps = false;

        public $incrementing = false;

        protected $keyType = 'string';

        protected $guarded = [];
    };

    $record->forceFill([
        'id' => 'user-uuid-1',
        'name' => 'UUID User',
        'email' => 'uuid@example.com',
    ]);
    $record->exists = true;

    return $record;
}

test('card view renders records with zone structure', function () {
    $user = User::factory()->create(['name' => 'Jane Doe']);

    Livewire::test(CardViewTableComponent::class)
        ->call('syncTableViewport', true)
        ->assertSee('Jane Doe')
        ->assertSee($user->email);
});

test('card view renders avatar column in the mobile card', function () {
    User::factory()->create(['name' => 'Jane Doe']);

    Livewire::test(CardViewTableComponent::class)
        ->call('syncTableViewport', true)
        ->assertSeeHtml('data-table-viewport-mobile')
        ->assertSee('Jane Doe');
});

test('card view renders id in footer zone', function () {
    $user = User::factory()->create(['name' => 'Jane Doe']);

    Livewire::test(CardViewTableComponent::class)
        ->call('syncTableViewport', true)
        ->assertSee((string) $user->id);
});

test('card view hides content and shows loading placeholder before viewport is resolved', function () {
    User::factory()->create(['name' => 'Jane Doe']);

    Livewire::test(CardViewTableComponent::class)
        ->assertSeeHtml('x-show="loading"')
        ->assertSeeHtml('data-table-viewport-desktop');
});

test('card view renders only the desktop variant when the viewport is desktop', function () {
    User::factory()->create(['name' => 'Jane Doe']);

    Livewire::test(CardViewTableComponent::class)
        ->call('syncTableViewport', false)
        ->assertSeeHtml('data-table-viewport-desktop')
        ->assertDontSeeHtml('data-table-viewport-mobile');
});

test('card view renders swipe actions for records with actions column', function () {
    $user = User::factory()->create(['name' => 'Swipeable User']);

    Livewire::test(CardViewTableComponent::class)
        ->call('syncTableViewport', true)
        ->assertSeeHtml('x-data="cardSwipe('.$user->getKey().', 2)"')
        ->assertSee('Edit')
        ->assertSee('Delete');
});

test('card view merges actions from multiple action columns in mobile tray', function () {
    $user = User::factory()->create(['name' => 'Jane Doe']);

    Livewire::test(MultiActionsCardViewTableComponent::class)
        ->call('syncTableViewport', true)
        ->assertSeeHtml('x-data="cardSwipe('.$user->getKey().', 2)"')
        ->assertSee('View')
        ->assertSee('Archive');
});

test('card view omits swipe bindings when no mobile actions are available', function () {
    User::factory()->create(['name' => 'Jane Doe']);

    Livewire::test(SeparatorOnlyCardViewTableComponent::class)
        ->call('syncTableViewport', true)
        ->assertDontSeeHtml('cardSwipe(')
        ->assertDontSee('Delete');
});

test('card view serializes string record keys in mobile bindings', function () {
    $record = stringKeyUserRecord();

    $view = test()->blade(
        <<<'BLADE'
        <x-table.card-item
            :record="$record"
            :columns-by-zone="$columnsByZone"
            :action-columns="$actionColumns"
        />
        BLADE,
        [
            'record' => $record,
            'columnsByZone' => [
                'header' => [AvatarColumn::make('name')->label('User')],
                'body' => [TextColumn::make('email')->label('Email')],
                'footer' => [],
            ],
            'actionColumns' => [
                ActionsColumn::make('actions')->actions(fn (Model $model) => [
                    ActionItem::button('Delete', 'confirmDelete', 'trash', 'danger'),
                ]),
            ],
        ],
    );

    $view->assertSeeHtml('x-data="cardSwipe(\'user-uuid-1\', 1)"')
        ->assertSeeHtml('wire:click="confirmDelete(\'user-uuid-1\')"');
});

test('mobile card view keeps sortable bindings when simple tables are sortable', function () {
    $user = User::factory()->create([
        'name' => 'Sortable User',
        'email' => 'sortable@example.com',
    ]);

    Livewire::test(SortableCardViewTableComponent::class)
        ->call('syncTableViewport', true)
        ->assertSeeHtml('wire:sort="reorderRows"')
        ->assertSeeHtml('wire:sort:item="'.$user->getKey().'"')
        ->assertSeeHtml('wire:sort:handle')
        ->assertSeeHtml(__('actions.reorder'));
});

test('desktop action menu serializes string record keys', function () {
    $record = stringKeyUserRecord();

    $view = test()->blade(
        <<<'BLADE'
        <x-table.cells.actions :column="$column" :record="$record" />
        BLADE,
        [
            'record' => $record,
            'column' => ActionsColumn::make('actions')->actions(fn (Model $model) => [
                ActionItem::button('Delete', 'confirmDelete', 'trash', 'danger'),
            ]),
        ],
    );

    $view->assertSeeHtml('wire:click="confirmDelete(\'user-uuid-1\')"');
});

test('mobile card view and desktop table view render in same component', function () {
    User::factory()->create(['name' => 'Jane Doe']);

    Livewire::test(CardViewTableComponent::class)
        ->call('syncTableViewport', false)
        ->assertSeeHtml('data-table-viewport-desktop')
        ->call('syncTableViewport', true)
        ->assertSeeHtml('data-table-viewport-mobile');
});

test('syncTableViewport skips update when value matches current viewport', function () {
    User::factory()->create(['name' => 'Jane Doe']);

    $component = Livewire::test(CardViewTableComponent::class)
        ->call('syncTableViewport', true)
        ->assertSet('tableIsMobileViewport', true);

    // Call again with the same value — should hit the early return (line 18)
    $component->call('syncTableViewport', true)
        ->assertSet('tableIsMobileViewport', true);

    // Also verify with false
    $component->call('syncTableViewport', false)
        ->assertSet('tableIsMobileViewport', false)
        ->call('syncTableViewport', false)
        ->assertSet('tableIsMobileViewport', false);
});

test('table state is preserved when changing viewport variants', function () {
    foreach (range(1, 15) as $index) {
        User::factory()->create([
            'name' => sprintf('User %02d', $index),
            'email' => sprintf('user%02d@example.com', $index),
        ]);
    }

    Livewire::test(CardViewTableComponent::class)
        ->set('search', 'User')
        ->set('perPage', 10)
        ->call('sort', 'email')
        ->call('gotoPage', 2)
        ->assertSet('paginators.page', 2)
        ->assertSee('User 11')
        ->assertDontSee('User 01')
        ->call('syncTableViewport', false)
        ->assertSet('search', 'User')
        ->assertSet('perPage', 10)
        ->assertSet('sortBy', 'email')
        ->assertSet('paginators.page', 2)
        ->assertSee('User 11')
        ->assertDontSee('User 01')
        ->call('syncTableViewport', true)
        ->assertSet('search', 'User')
        ->assertSet('perPage', 10)
        ->assertSet('sortBy', 'email')
        ->assertSet('paginators.page', 2)
        ->assertSee('User 11')
        ->assertDontSee('User 01');
});
