<?php

use App\Actions\Calendar\GenerateCalendarDays;
use App\Models\CalendarDay;
use App\Models\HolidayDefinition;
use App\Models\PricingCategory;
use App\Models\PricingRule;
use App\Models\SeasonBlock;
use Carbon\CarbonImmutable;
use Database\Seeders\HolidayDefinitionSeeder;
use Database\Seeders\PricingCategorySeeder;
use Database\Seeders\PricingRuleSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SeasonBlockSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([
        RolesAndPermissionsSeeder::class,
        HolidayDefinitionSeeder::class,
        SeasonBlockSeeder::class,
        PricingCategorySeeder::class,
        PricingRuleSeeder::class,
    ]);

    $this->actingAs(makeAdmin());
});

function seedCalendar2026(): void
{
    app(GenerateCalendarDays::class)->handle(
        CarbonImmutable::createStrict(2026, 1, 1),
        CarbonImmutable::createStrict(2026, 12, 31),
    );
}

// ─── Index Page ───

test('admins can visit the calendar index page', function () {
    $this->get(route('calendar.index'))
        ->assertOk()
        ->assertSeeText(__('calendar.index.title'));
});

test('non admins cannot visit the calendar index page', function () {
    $this->actingAs(makeGuest());

    $this->get(route('calendar.index'))->assertForbidden();
});

test('hosts can visit the calendar index page', function () {
    $this->actingAs(makeHost());

    $this->get(route('calendar.index'))
        ->assertOk()
        ->assertSeeText(__('calendar.index.title'));
});

test('calendar index shows no-data message when year is empty', function () {
    Livewire::test('pages::calendar.index')
        ->assertSeeText(__('calendar.index.no_data'));
});

test('calendar index shows month grids when data exists', function () {
    seedCalendar2026();

    Livewire::test('pages::calendar.index', ['year' => 2026])
        ->assertSeeText(__('calendar.index.months.1'))
        ->assertSeeText(__('calendar.index.months.12'))
        ->assertDontSeeText(__('calendar.index.no_data'));
});

test('calendar index shows stats when data exists', function () {
    seedCalendar2026();

    Livewire::test('pages::calendar.index', ['year' => 2026])
        ->assertSeeText(__('calendar.index.stats.total_holidays'))
        ->assertSeeText('18');
});

test('calendar index shows legend', function () {
    seedCalendar2026();

    Livewire::test('pages::calendar.index', ['year' => 2026])
        ->assertSeeText(__('calendar.index.legend.title'));
});

test('calendar index navigates years', function () {
    Livewire::test('pages::calendar.index', ['year' => 2026])
        ->assertSet('selectedYear', 2026)
        ->call('previousYear')
        ->assertSet('selectedYear', 2025)
        ->call('nextYear')
        ->call('nextYear')
        ->assertSet('selectedYear', 2027);
});

test('sidebar shows calendar navigation for admins', function () {
    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSeeText(__('calendar.navigation.label'));
});

test('sidebar shows calendar navigation for hosts', function () {
    $this->actingAs(makeHost());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSeeText(__('calendar.navigation.label'))
        ->assertSeeText(__('calendar.index.title'));
});

test('sidebar hides calendar navigation for non admins', function () {
    $this->actingAs(makeGuest());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertDontSeeText(__('calendar.index.title'));
});

// ─── Show Page ───

test('admins can visit the calendar show page', function () {
    seedCalendar2026();

    $this->get(route('calendar.show', '2026-04-02'))
        ->assertOk()
        ->assertSeeText(__('calendar.show.title'));
});

test('non admins cannot visit the calendar show page', function () {
    seedCalendar2026();

    $this->actingAs(makeGuest());

    $this->get(route('calendar.show', '2026-04-02'))->assertForbidden();
});

test('hosts can visit the calendar show page', function () {
    seedCalendar2026();

    $this->actingAs(makeHost());

    $this->get(route('calendar.show', '2026-04-02'))
        ->assertOk()
        ->assertSeeText(__('calendar.show.title'));
});

test('show page displays holiday information', function () {
    seedCalendar2026();

    $this->get(route('calendar.show', '2026-04-02'))
        ->assertOk()
        ->assertSeeText(__('calendar.show.sections.holiday'))
        ->assertSeeText(__('calendar.holiday_groups.easter_based'));
});

test('show page displays season information', function () {
    seedCalendar2026();

    // Apr 2 is in Holy Week
    $this->get(route('calendar.show', '2026-04-02'))
        ->assertOk()
        ->assertSeeText(__('calendar.show.sections.season'));
});

test('show page displays pricing information', function () {
    seedCalendar2026();

    $this->get(route('calendar.show', '2026-04-02'))
        ->assertOk()
        ->assertSeeText(__('calendar.show.sections.pricing'));
});

test('show page returns 404 for non-existent date', function () {
    $this->get(route('calendar.show', '2099-01-01'))->assertNotFound();
});

// ─── Settings Page ───

test('admins can visit the calendar settings page', function () {
    $this->get(route('calendar.settings'))
        ->assertOk()
        ->assertSeeText(__('calendar.settings.title'));
});

test('non admins cannot visit the calendar settings page', function () {
    $this->actingAs(makeGuest());

    $this->get(route('calendar.settings'))->assertForbidden();
});

test('settings page shows all sections', function () {
    $this->get(route('calendar.settings'))
        ->assertOk()
        ->assertSeeText(__('calendar.settings.sections.holidays'))
        ->assertSeeText(__('calendar.settings.sections.seasons'))
        ->assertSeeText(__('calendar.settings.sections.categories'))
        ->assertSeeText(__('calendar.settings.sections.rules'));
});

test('settings page shows only authorized sections for partial viewers', function () {
    $viewer = makeGuest();
    $viewer->givePermissionTo('holiday_definition.viewAny');

    $this->actingAs($viewer);

    $this->get(route('calendar.settings'))
        ->assertOk()
        ->assertSeeText(__('calendar.settings.sections.holidays'))
        ->assertDontSeeText(__('calendar.settings.sections.seasons'))
        ->assertDontSeeText(__('calendar.settings.sections.categories'))
        ->assertDontSeeText(__('calendar.settings.sections.rules'))
        ->assertDontSeeText(__('calendar.settings.regenerate.button'));
});

test('settings page is accessible from other calendar view permissions', function () {
    $viewer = makeGuest();
    $viewer->givePermissionTo('pricing_category.viewAny');

    $this->actingAs($viewer);

    $this->get(route('calendar.settings'))
        ->assertOk()
        ->assertSeeText(__('calendar.settings.sections.categories'))
        ->assertDontSeeText(__('calendar.settings.sections.holidays'));

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSeeText(__('calendar.navigation.settings'));
});

test('settings page shows holiday definitions', function () {
    Livewire::test('pages::calendar.settings')
        ->assertSeeText("New Year's Day")
        ->assertSeeText('Christmas Day')
        ->assertSeeText('Saints Peter and Paul');
});

test('settings page shows season blocks', function () {
    Livewire::test('pages::calendar.settings')
        ->assertSeeText('Holy Week')
        ->assertSeeText('December Season');
});

test('settings page shows pricing categories', function () {
    Livewire::test('pages::calendar.settings')
        ->assertSeeText('Premium')
        ->assertSeeText('Economy');
});

test('settings page shows pricing category create action', function () {
    Livewire::test('pages::calendar.settings')
        ->assertSeeText(__('calendar.settings.pricing_category_form.create_action'));
});

test('settings page shows holiday definition create action', function () {
    Livewire::test('pages::calendar.settings')
        ->assertSeeText(__('calendar.settings.holiday_definition_form.create_action'));
});

test('settings page shows season block create action', function () {
    Livewire::test('pages::calendar.settings')
        ->assertSeeText(__('calendar.settings.season_block_form.create_action'));
});

test('settings page shows pricing rules', function () {
    Livewire::test('pages::calendar.settings')
        ->assertSeeText(__('calendar.rule_types.season_days'))
        ->assertSeeText(__('calendar.rule_types.holiday_bridge'));
});

test('settings id column is first and active switch is second in all tables except pricing rules', function () {
    $component = Livewire::test('pages::calendar.settings');

    $assertLeadingColumns = function (array $columns): void {
        expect($columns[0]->name())->toBe('id')
            ->and($columns[0]->label())->toBe('#')
            ->and($columns[1]->name())->toBe('is_active')
            ->and($columns[1]->type())->toBe('toggle');
    };

    $instance = $component->instance();

    $assertLeadingColumns($instance->holidayColumns());
    $assertLeadingColumns($instance->seasonBlockColumns());
    $assertLeadingColumns($instance->pricingCategoryColumns());

    $ruleColumns = $instance->pricingRuleColumns();
    expect($ruleColumns[0]->name())->toBe('id')
        ->and($ruleColumns[1]->name())->toBe('is_active')
        ->and($ruleColumns[2]->name())->toBe('conditions');
});

test('settings page renders disabled active switches for viewers without update permissions', function () {
    $viewer = makeGuest();
    $viewer->givePermissionTo([
        'holiday_definition.viewAny',
        'season_block.viewAny',
        'pricing_category.viewAny',
        'pricing_rule.viewAny',
    ]);

    $this->actingAs($viewer);

    $holiday = HolidayDefinition::query()->where('name', 'new_year')->firstOrFail();
    $seasonBlock = SeasonBlock::query()->where('name', 'holy_week')->firstOrFail();
    $category = PricingCategory::query()->where('name', 'cat_1_premium')->firstOrFail();
    $rule = PricingRule::query()->where('name', 'holy_week')->firstOrFail();

    $component = Livewire::test('pages::calendar.settings');

    foreach ([
        'holiday-active-'.$holiday->id,
        'season-block-active-'.$seasonBlock->id,
        'pricing-category-active-'.$category->id,
        'pricing-rule-active-'.$rule->id,
    ] as $switchId) {
        $component->assertSeeHtml('id="'.$switchId.'"');
    }

    $component->assertSeeHtml('data-disabled="true"');
});

test('settings page shows create rule action and condition summary', function () {
    Livewire::test('pages::calendar.settings')
        ->assertSeeText(__('calendar.settings.rule_form.create_action'))
        ->assertSeeText(__('calendar.settings.fields.conditions'));
});

test('pricing category table uses row actions instead of inline editable columns', function () {
    $columns = Livewire::test('pages::calendar.settings')->instance()->pricingCategoryColumns();

    expect(collect($columns)->map->type()->all())
        ->toContain('actions')
        ->not->toContain('editable-text')
        ->not->toContain('editable-number')
        ->not->toContain('editable-color');
});

test('settings can update a holiday definition name', function () {
    $holiday = HolidayDefinition::query()->where('name', 'new_year')->first();

    Livewire::test('pages::calendar.settings')
        ->call('updateHoliday', $holiday->id, 'en_name', 'New Year Updated')
        ->assertHasNoErrors();

    expect($holiday->fresh()->en_name)->toBe('New Year Updated');
});

test('settings can update a season block name', function () {
    $block = SeasonBlock::query()->where('name', 'holy_week')->first();

    Livewire::test('pages::calendar.settings')
        ->call('updateSeasonBlock', $block->id, 'en_name', 'Holy Week Updated')
        ->assertHasNoErrors();

    expect($block->fresh()->en_name)->toBe('Holy Week Updated');
});

test('settings can open the create season block modal', function () {
    Livewire::test('pages::calendar.settings')
        ->call('openCreateSeasonBlockModal')
        ->assertDispatched('open-form-modal', fn (string $event, array $params) => ($params['name'] ?? null) === 'calendar.season-block-form');
});

test('settings can open the edit holiday definition modal with edit context', function () {
    $holiday = HolidayDefinition::query()->where('name', 'new_year')->firstOrFail();

    Livewire::test('pages::calendar.settings')
        ->call('openEditHolidayDefinitionModal', $holiday->id)
        ->assertDispatched('open-form-modal', fn (string $event, array $params) => ($params['name'] ?? null) === 'calendar.holiday-definition-form'
            && ($params['context']['mode'] ?? null) === 'edit'
            && ($params['context']['holidayDefinitionId'] ?? null) === $holiday->id);
});

test('settings can open the edit season block modal with edit context', function () {
    $seasonBlock = SeasonBlock::query()->where('name', 'holy_week')->firstOrFail();

    Livewire::test('pages::calendar.settings')
        ->call('openEditSeasonBlockModal', $seasonBlock->id)
        ->assertDispatched('open-form-modal', fn (string $event, array $params) => ($params['name'] ?? null) === 'calendar.season-block-form'
            && ($params['context']['mode'] ?? null) === 'edit'
            && ($params['context']['seasonBlockId'] ?? null) === $seasonBlock->id);
});

test('settings can update a pricing category active state inline and mark the calendar stale', function () {
    seedCalendar2026();

    $category = PricingCategory::query()->where('name', 'cat_1_premium')->first();

    Livewire::test('pages::calendar.settings')
        ->assertSet('isCalendarStale', false)
        ->call('updatePricingCategory', $category->id, 'is_active', false)
        ->assertHasNoErrors()
        ->assertSet('isCalendarStale', true);

    expect($category->fresh()->is_active)->toBeFalse();
});

test('settings can update a pricing rule active state inline and mark the calendar stale', function () {
    seedCalendar2026();

    $rule = PricingRule::query()->where('name', 'bridge_first_day')->firstOrFail();

    Livewire::test('pages::calendar.settings')
        ->assertSet('isCalendarStale', false)
        ->call('updatePricingRule', $rule->id, 'is_active', false)
        ->assertHasNoErrors()
        ->assertSet('isCalendarStale', true);

    expect($rule->fresh()->is_active)->toBeFalse();
});

test('settings can open the create pricing rule modal', function () {
    Livewire::test('pages::calendar.settings')
        ->call('openCreatePricingRuleModal')
        ->assertDispatched('open-form-modal', fn (string $event, array $params) => ($params['name'] ?? null) === 'calendar.pricing-rules.form');
});

test('settings can open the edit pricing rule modal with edit context', function () {
    $pricingRule = PricingRule::query()->where('name', 'bridge_first_day')->firstOrFail();

    Livewire::test('pages::calendar.settings')
        ->call('openEditPricingRuleModal', $pricingRule->id)
        ->assertDispatched('open-form-modal', fn (string $event, array $params) => ($params['name'] ?? null) === 'calendar.pricing-rules.form'
            && ($params['context']['mode'] ?? null) === 'edit'
            && ($params['context']['pricingRuleId'] ?? null) === $pricingRule->id);
});

test('settings can open the create pricing category modal', function () {
    Livewire::test('pages::calendar.settings')
        ->call('openCreatePricingCategoryModal')
        ->assertDispatched('open-form-modal', fn (string $event, array $params) => ($params['name'] ?? null) === 'calendar.pricing-category-form');
});

test('settings can open the edit pricing category modal with edit context', function () {
    $pricingCategory = PricingCategory::query()->where('name', 'cat_1_premium')->firstOrFail();

    Livewire::test('pages::calendar.settings')
        ->call('openEditPricingCategoryModal', $pricingCategory->id)
        ->assertDispatched('open-form-modal', fn (string $event, array $params) => ($params['name'] ?? null) === 'calendar.pricing-category-form'
            && ($params['context']['mode'] ?? null) === 'edit'
            && ($params['context']['pricingCategoryId'] ?? null) === $pricingCategory->id);
});

test('settings can open the duplicate pricing rule modal with duplicate context', function () {
    $pricingRule = PricingRule::query()->where('name', 'bridge_first_day')->firstOrFail();

    Livewire::test('pages::calendar.settings')
        ->call('openDuplicatePricingRuleModal', $pricingRule->id)
        ->assertDispatched('open-form-modal', fn (string $event, array $params) => ($params['name'] ?? null) === 'calendar.pricing-rules.form'
            && ($params['context']['mode'] ?? null) === 'duplicate'
            && ($params['context']['pricingRuleId'] ?? null) === $pricingRule->id);
});

test('settings resolves invalid per-page query parameters to defaults', function () {
    Livewire::withQueryParams([
        'holidays_per_page' => 999,
        'seasons_per_page' => 999,
        'categories_per_page' => 999,
        'rules_per_page' => 999,
    ])->test('pages::calendar.settings')
        ->assertSet('holidaysPerPage', 10)
        ->assertSet('seasonsPerPage', 10)
        ->assertSet('categoriesPerPage', 10)
        ->assertSet('rulesPerPage', 10);
});

test('settings exposes the supported per-page options', function () {
    expect(Livewire::test('pages::calendar.settings')->instance()->perPageOptions())
        ->toBe([10, 15, 25, 50, 100]);
});

test('settings reset each named paginator when the per-page selection changes', function () {
    $component = Livewire::withQueryParams([
        'holidays' => 2,
        'seasons' => 2,
        'categories' => 2,
        'rules' => 2,
    ])->test('pages::calendar.settings');

    $instance = $component->instance();

    expect($instance->holidays()->currentPage())->toBe(2)
        ->and($instance->seasonBlocks()->currentPage())->toBe(2)
        ->and($instance->pricingCategories()->currentPage())->toBe(2)
        ->and($instance->pricingRules()->currentPage())->toBe(2);

    $component->set('holidaysPerPage', 15);
    expect($component->instance()->holidays()->currentPage())->toBe(1);

    $component->set('seasonsPerPage', 15);
    expect($component->instance()->seasonBlocks()->currentPage())->toBe(1);

    $component->set('categoriesPerPage', 15);
    expect($component->instance()->pricingCategories()->currentPage())->toBe(1);

    $component->set('rulesPerPage', 15);
    expect($component->instance()->pricingRules()->currentPage())->toBe(1);
});

test('pricing rule form can create a season-based rule', function () {
    $category = PricingCategory::query()->where('name', 'cat_2_high')->first();
    $seasonBlock = SeasonBlock::query()->where('name', 'october_recess')->first();

    Livewire::test('calendar.pricing-rule-form', ['context' => ['mode' => 'create']])
        ->set('name', 'mid_year_high')
        ->set('en_description', 'Mid-year high season')
        ->set('es_description', 'Temporada alta de mitad de año')
        ->set('pricing_category_id', $category->id)
        ->set('rule_type', 'season_days')
        ->set('priority', 15)
        ->set('season_mode', 'season')
        ->set('season_block_id', $seasonBlock->id)
        ->set('day_of_week', ['friday', 'saturday'])
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('pricing-rule-saved');

    $rule = PricingRule::query()->where('name', 'mid_year_high')->first();

    expect($rule)->not->toBeNull()
        ->and($rule->conditions)->toMatchArray([
            'season_block_id' => $seasonBlock->id,
            'day_of_week' => ['friday', 'saturday'],
        ]);
});

test('pricing category form can create a pricing category', function () {
    Livewire::test('calendar.pricing-category-form', ['context' => ['mode' => 'create']])
        ->set('name', 'cat_5_peak')
        ->set('en_name', 'Peak')
        ->set('es_name', 'Pico')
        ->set('level', 5)
        ->set('color', '#A855F7')
        ->set('multiplier', '2.80')
        ->set('sort_order', 5)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('pricing-category-saved');

    $category = PricingCategory::query()->where('name', 'cat_5_peak')->first();

    expect($category)->not->toBeNull()
        ->and($category->en_name)->toBe('Peak')
        ->and((float) $category->multiplier)->toBe(2.8);
});

test('season block form can create a custom fixed-range block', function () {
    Livewire::test('calendar.season-block-form', ['context' => ['mode' => 'create']])
        ->set('name', 'mid_year_break')
        ->set('en_name', 'Mid-year Break')
        ->set('es_name', 'Receso de Mitad de Año')
        ->set('fixed_start_month', 6)
        ->set('fixed_start_day', 1)
        ->set('fixed_end_month', 6)
        ->set('fixed_end_day', 30)
        ->set('priority', 8)
        ->set('sort_order', 8)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('season-block-saved');

    $block = SeasonBlock::query()->where('name', 'mid_year_break')->first();

    expect($block)->not->toBeNull()
        ->and($block->calculation_strategy->value)->toBe('fixed_range')
        ->and($block->fixed_start_month)->toBe(6)
        ->and($block->fixed_end_day)->toBe(30);
});

test('season block form can edit an existing custom block', function () {
    $block = SeasonBlock::factory()->fixedRange(6, 1, 6, 30)->create([
        'name' => 'mid_year_break',
        'en_name' => 'Mid-year Break',
        'es_name' => 'Receso de Mitad de Año',
        'priority' => 8,
        'sort_order' => 8,
    ]);

    Livewire::test('calendar.season-block-form', ['context' => ['mode' => 'edit', 'seasonBlockId' => $block->id]])
        ->set('en_name', 'Mid-year Holiday')
        ->set('fixed_end_day', 28)
        ->set('priority', 9)
        ->call('save')
        ->assertHasNoErrors();

    expect($block->fresh()->en_name)->toBe('Mid-year Holiday')
        ->and($block->fresh()->fixed_end_day)->toBe(28)
        ->and($block->fresh()->priority)->toBe(9);
});

test('pricing category form can edit an existing pricing category', function () {
    $category = PricingCategory::query()->where('name', 'cat_2_high')->firstOrFail();

    Livewire::test('calendar.pricing-category-form', ['context' => ['mode' => 'edit', 'pricingCategoryId' => $category->id]])
        ->set('en_name', 'High Demand')
        ->set('multiplier', '1.95')
        ->set('color', '#0EA5E9')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('pricing-category-saved');

    expect($category->fresh()->en_name)->toBe('High Demand')
        ->and((float) $category->fresh()->multiplier)->toBe(1.95)
        ->and($category->fresh()->color)->toBe('#0EA5E9');
});

test('pricing category form defaults to level 10 when all levels are taken', function () {
    // Seeder creates levels 1-4; fill remaining levels 5-10
    foreach (range(5, 10) as $level) {
        PricingCategory::factory()->create(['level' => $level]);
    }

    Livewire::test('calendar.pricing-category-form', ['context' => ['mode' => 'create']])
        ->assertSet('level', 10);
});

test('settings cannot delete a custom season block that is referenced by a pricing rule', function () {
    $block = SeasonBlock::factory()->fixedRange(6, 1, 6, 30)->create([
        'name' => 'mid_year_break',
        'en_name' => 'Mid-year Break',
        'es_name' => 'Receso de Mitad de Año',
    ]);

    $pricingCategoryId = PricingCategory::query()
        ->where('name', 'cat_2_high')
        ->value('id');

    PricingRule::factory()->create([
        'name' => 'mid_year_break_rule',
        'pricing_category_id' => $pricingCategoryId,
        'rule_type' => 'season_days',
        'conditions' => ['season_block_id' => $block->id],
    ]);

    Livewire::test('pages::calendar.settings')
        ->call('confirmSeasonBlockDeletion', $block->id)
        ->call('handleConfirmedModalAction')
        ->assertHasNoErrors();

    expect($block->fresh())->not->toBeNull();
});

test('settings can delete an unreferenced custom season block', function () {
    $block = SeasonBlock::factory()->fixedRange(6, 1, 6, 30)->create([
        'name' => 'mid_year_break',
        'en_name' => 'Mid-year Break',
        'es_name' => 'Receso de Mitad de Año',
    ]);

    Livewire::test('pages::calendar.settings')
        ->call('confirmSeasonBlockDeletion', $block->id)
        ->call('handleConfirmedModalAction')
        ->assertHasNoErrors();

    expect(SeasonBlock::query()->whereKey($block->id)->exists())->toBeFalse();
});

test('settings cannot delete a pricing category referenced by a pricing rule', function () {
    $category = PricingCategory::query()->where('name', 'cat_2_high')->firstOrFail();

    Livewire::test('pages::calendar.settings')
        ->call('confirmPricingCategoryDeletion', $category->id)
        ->assertDispatched('open-confirm-modal', fn (string $event, array $params) => ($params['title'] ?? null) === __('calendar.settings.confirm_deactivate_category.title'))
        ->call('handleConfirmedModalAction')
        ->assertHasNoErrors()
        ->assertDispatched('toast-show', fn (string $event, array $params) => ($params['slots']['text'] ?? null) === __('calendar.settings.pricing_category_form.deactivated', [
            'category' => __('calendar.settings.pricing_category_label', ['name' => $category->name, 'id' => $category->id]),
        ]));

    $fresh = PricingCategory::query()->findOrFail($category->id);

    expect($fresh->is_active)->toBeFalse();
});

test('settings deactivates a pricing category referenced by generated calendar days instead of deleting it', function () {
    $category = PricingCategory::factory()->create([
        'name' => 'cat_5_peak',
        'en_name' => 'Peak',
        'es_name' => 'Pico',
        'level' => 5,
        'sort_order' => 5,
    ]);

    CalendarDay::factory()->forDate(CarbonImmutable::createStrict(2026, 7, 15))->create([
        'pricing_category_id' => $category->id,
        'pricing_category_level' => $category->level,
    ]);

    Livewire::test('pages::calendar.settings')
        ->call('confirmPricingCategoryDeletion', $category->id)
        ->assertDispatched('open-confirm-modal', fn (string $event, array $params) => ($params['title'] ?? null) === __('calendar.settings.confirm_deactivate_category.title'))
        ->call('handleConfirmedModalAction')
        ->assertHasNoErrors();

    expect(PricingCategory::query()->findOrFail($category->id)->is_active)->toBeFalse()
        ->and(CalendarDay::query()->where('pricing_category_id', $category->id)->exists())->toBeTrue();
});

test('settings can delete an unreferenced pricing category', function () {
    $category = PricingCategory::factory()->create([
        'name' => 'cat_5_peak',
        'en_name' => 'Peak',
        'es_name' => 'Pico',
        'level' => 5,
        'sort_order' => 5,
    ]);

    Livewire::test('pages::calendar.settings')
        ->call('confirmPricingCategoryDeletion', $category->id)
        ->call('handleConfirmedModalAction')
        ->assertHasNoErrors();

    expect(PricingCategory::query()->whereKey($category->id)->exists())->toBeFalse();
});

test('settings can update a pricing category multiplier via inline action', function () {
    $category = PricingCategory::query()->where('name', 'cat_1_premium')->firstOrFail();

    Livewire::test('pages::calendar.settings')
        ->call('updatePricingCategory', $category->id, 'multiplier', '2.50')
        ->assertHasNoErrors();

    expect((float) $category->fresh()->multiplier)->toBe(2.5);
});

test('settings rejects malformed pricing category boolean updates', function () {
    $category = PricingCategory::query()->where('name', 'cat_1_premium')->firstOrFail();

    Livewire::test('pages::calendar.settings')
        ->call('updatePricingCategory', $category->id, 'is_active', 'definitely-not-a-bool')
        ->assertHasErrors(['is_active']);

    expect($category->fresh()->is_active)->toBeTrue();
});

test('pricing rule form can edit an existing rule', function () {
    $rule = PricingRule::query()->where('name', 'long_weekend_high_impact')->first();

    Livewire::test('calendar.pricing-rule-form', ['context' => ['mode' => 'edit', 'pricingRuleId' => $rule->id]])
        ->set('day_of_week', ['friday', 'saturday', 'sunday'])
        ->set('priority', 15)
        ->call('save')
        ->assertHasNoErrors();

    $updated = $rule->fresh();

    expect($updated->priority)->toBe(15)
        ->and($updated->conditions)->toMatchArray([
            'is_bridge_weekend' => true,
            'day_of_week' => ['friday', 'saturday', 'sunday'],
            'min_impact' => 8.0,
        ]);
});

test('settings page enables drag and drop sorting for pricing rules', function () {
    Livewire::test('pages::calendar.settings')
        ->assertSeeHtml('wire:sort="reorderPricingRules"');
});

test('settings page keeps pricing rule sorting available in the mobile card layout', function () {
    Livewire::test('pages::calendar.settings')
        ->call('syncTableViewport', true)
        ->assertSeeHtml('data-table-viewport-mobile')
        ->assertSeeHtml('wire:sort="reorderPricingRules"')
        ->assertSeeHtml('wire:sort:handle')
        ->assertSeeHtml(__('actions.reorder'));
});

test('settings can reorder pricing rules while keeping fallback last', function () {
    $longWeekend = PricingRule::query()->where('name', 'long_weekend_high_impact')->firstOrFail();
    $fallback = PricingRule::query()->where('name', 'economy_fallback')->firstOrFail();

    Livewire::test('pages::calendar.settings')
        ->call('reorderPricingRules', $longWeekend->id, 0)
        ->assertDispatched('toast-show', function (string $event, array $params) {
            return ($params['dataset']['variant'] ?? null) === 'success';
        });

    $orderedNames = PricingRule::query()
        ->orderBy('priority')
        ->pluck('name')
        ->all();

    expect($orderedNames[0])->toBe('long_weekend_high_impact')
        ->and($orderedNames[array_key_last($orderedNames)])->toBe('economy_fallback')
        ->and($longWeekend->fresh()->priority)->toBe(1)
        ->and($fallback->fresh()->priority)->toBe(999);
});

test('pricing rule form can duplicate an existing rule', function () {
    $rule = PricingRule::query()->where('name', 'october_recess')->first();

    Livewire::test('calendar.pricing-rule-form', ['context' => ['mode' => 'duplicate', 'pricingRuleId' => $rule->id]])
        ->set('name', 'october_recess_copy')
        ->set('priority', 16)
        ->call('save')
        ->assertHasNoErrors();

    $copy = PricingRule::query()->where('name', 'october_recess_copy')->first();

    expect($copy)->not->toBeNull()
        ->and($copy->id)->not->toBe($rule->id)
        ->and($copy->conditions)->toBe($rule->conditions);
});

test('pricing rule form falls back to create mode for invalid context mode', function () {
    $expectedCategoryId = PricingCategory::query()->active()->orderBy('sort_order')->value('id');

    Livewire::test('calendar.pricing-rule-form', ['context' => ['mode' => 'invalid-mode']])
        ->assertSet('mode', 'create')
        ->assertSet('pricing_category_id', $expectedCategoryId)
        ->assertSet('priority', 998);
});

test('pricing rule form boot create mode uses defaults when there is no active fallback rule', function () {
    PricingRule::query()->where('rule_type', 'economy_default')->update(['is_active' => false]);

    $expectedCategoryId = PricingCategory::query()->active()->orderBy('sort_order')->value('id');

    Livewire::test('calendar.pricing-rule-form', ['context' => ['mode' => 'create']])
        ->assertSet('priority', 99)
        ->assertSet('pricing_category_id', $expectedCategoryId);
});

test('pricing rule form hydrates date mode fields from season day rules', function () {
    $rule = PricingRule::factory()->create([
        'name' => 'december_specials',
        'pricing_category_id' => PricingCategory::query()->where('name', 'cat_1_premium')->value('id'),
        'rule_type' => 'season_days',
        'conditions' => [
            'dates' => ['12-31', '12-24'],
            'days_before' => 2,
            'days_after' => 1,
        ],
        'priority' => 25,
    ]);

    Livewire::test('calendar.pricing-rule-form', ['context' => ['mode' => 'edit', 'pricingRuleId' => $rule->id]])
        ->assertSet('season_mode', 'dates')
        ->assertSet('season_block_id', null)
        ->assertSet('recurring_dates', ['12-24', '12-31'])
        ->assertSet('days_before', 2)
        ->assertSet('days_after', 1);
});

test('pricing rule form resolves legacy season block conditions when editing', function () {
    $seasonBlockId = SeasonBlock::query()->where('name', 'october_recess')->value('id');
    $rule = PricingRule::factory()->create([
        'name' => 'legacy_october_recess',
        'pricing_category_id' => PricingCategory::query()->where('name', 'cat_3_weekend_std')->value('id'),
        'rule_type' => 'season_days',
        'conditions' => [
            'season' => 'october_recess',
            'day_of_week' => ['saturday'],
        ],
        'priority' => 26,
    ]);

    Livewire::test('calendar.pricing-rule-form', ['context' => ['mode' => 'edit', 'pricingRuleId' => $rule->id]])
        ->assertSet('season_mode', 'season')
        ->assertSet('season_block_id', $seasonBlockId)
        ->assertSet('day_of_week', ['saturday']);
});

test('pricing rule form resolves numeric string season block ids when editing', function () {
    $seasonBlockId = SeasonBlock::query()->where('name', 'holy_week')->value('id');
    $rule = PricingRule::factory()->create([
        'name' => 'holy_week_string_id',
        'pricing_category_id' => PricingCategory::query()->where('name', 'cat_1_premium')->value('id'),
        'rule_type' => 'season_days',
        'conditions' => [
            'season_block_id' => (string) $seasonBlockId,
        ],
        'priority' => 27,
    ]);

    Livewire::test('calendar.pricing-rule-form', ['context' => ['mode' => 'edit', 'pricingRuleId' => $rule->id]])
        ->assertSet('season_block_id', $seasonBlockId);
});

test('pricing rule form hydrates holiday and normal weekend fields from conditions', function () {
    $holidayRule = PricingRule::factory()->create([
        'name' => 'holiday_window',
        'pricing_category_id' => PricingCategory::query()->where('name', 'cat_2_high')->value('id'),
        'rule_type' => 'holiday',
        'conditions' => [
            'min_impact' => 3,
            'max_impact' => 7,
            'day_of_week' => ['friday'],
        ],
        'priority' => 28,
    ]);

    Livewire::test('calendar.pricing-rule-form', ['context' => ['mode' => 'edit', 'pricingRuleId' => $holidayRule->id]])
        ->assertSet('min_impact', 3)
        ->assertSet('max_impact', 7)
        ->assertSet('day_of_week', ['friday']);

    $weekendRule = PricingRule::factory()->create([
        'name' => 'outside_bridge_weekend',
        'pricing_category_id' => PricingCategory::query()->where('name', 'cat_3_weekend_std')->value('id'),
        'rule_type' => 'normal_weekend',
        'conditions' => [
            'day_of_week' => ['friday'],
            'outside_season' => false,
            'not_bridge' => false,
        ],
        'priority' => 29,
    ]);

    Livewire::test('calendar.pricing-rule-form', ['context' => ['mode' => 'edit', 'pricingRuleId' => $weekendRule->id]])
        ->assertSet('outside_season', false)
        ->assertSet('not_bridge', false)
        ->assertSet('day_of_week', ['friday']);
});

test('pricing rule form clears stale condition fields when switching to economy default', function () {
    Livewire::test('calendar.pricing-rule-form', ['context' => ['mode' => 'create']])
        ->set('season_mode', 'dates')
        ->set('season_block_id', SeasonBlock::query()->where('name', 'holy_week')->value('id'))
        ->set('only_last_n_days', 2)
        ->set('exclude_last_n_days', 1)
        ->set('recurring_dates', ['12-24'])
        ->set('days_before', 3)
        ->set('days_after', 4)
        ->set('min_impact', 5)
        ->set('max_impact', 8)
        ->set('day_of_week', ['friday'])
        ->set('is_bridge_weekend', false)
        ->set('is_first_bridge_day', true)
        ->set('outside_season', false)
        ->set('not_bridge', false)
        ->set('rule_type', 'economy_default')
        ->assertSet('season_mode', 'season')
        ->assertSet('season_block_id', null)
        ->assertSet('only_last_n_days', null)
        ->assertSet('exclude_last_n_days', null)
        ->assertSet('recurring_dates', [])
        ->assertSet('days_before', null)
        ->assertSet('days_after', null)
        ->assertSet('min_impact', null)
        ->assertSet('max_impact', null)
        ->assertSet('day_of_week', [])
        ->assertSet('is_bridge_weekend', true)
        ->assertSet('is_first_bridge_day', false)
        ->assertSet('outside_season', true)
        ->assertSet('not_bridge', true);
});

test('pricing rule form normalizes updated day of week values and clears preview', function () {
    Livewire::test('calendar.pricing-rule-form', ['context' => ['mode' => 'create']])
        ->set('preview', ['affectedCount' => 2])
        ->set('day_of_week', ['Friday', ' ', 'friday', 'MONDAY', 'invalid'])
        ->assertSet('day_of_week', ['friday', 'friday', 'monday'])
        ->assertSet('preview', []);
});

test('pricing rule form normalizes recurring dates and clears preview', function () {
    Livewire::test('calendar.pricing-rule-form', ['context' => ['mode' => 'create']])
        ->set('preview', ['affectedCount' => 2])
        ->set('recurring_dates', ['12-31', 'bad-date', '01-01', '12-31'])
        ->assertSet('recurring_dates', ['01-01', '12-31'])
        ->assertSet('preview', []);
});

test('pricing rule form adds and removes recurring dates without duplicates', function () {
    Livewire::test('calendar.pricing-rule-form', ['context' => ['mode' => 'create']])
        ->set('preview', ['affectedCount' => 1])
        ->set('recurring_month', '2')
        ->set('recurring_day', '14')
        ->call('addRecurringDate')
        ->assertSet('recurring_dates', ['02-14'])
        ->assertSet('recurring_month', '')
        ->assertSet('recurring_day', '')
        ->assertSet('preview', [])
        ->set('recurring_month', '2')
        ->set('recurring_day', '14')
        ->call('addRecurringDate')
        ->assertSet('recurring_dates', ['02-14'])
        ->call('removeRecurringDate', '02-14')
        ->assertSet('recurring_dates', []);
});

test('pricing rule form ignores preview state updates when resetting validation', function () {
    Livewire::test('calendar.pricing-rule-form', ['context' => ['mode' => 'create']])
        ->set('preview', [
            'affectedCount' => 3,
            'sampleDates' => [[
                'date' => '2026-02-14',
                'fromCategory' => 'cat_1_premium',
                'toCategory' => 'cat_2_high',
            ]],
        ])
        ->set('preview.sampleDates', [[
            'date' => '2026-02-15',
            'fromCategory' => 'cat_2_high',
            'toCategory' => 'cat_3_mid',
        ]])
        ->assertSet('preview.affectedCount', 3)
        ->set('name', 'new_preview_rule')
        ->assertSet('preview', []);
});

test('pricing rule form preview uses custom range in edit mode and returns warnings', function () {
    seedCalendar2026();

    $rule = PricingRule::query()->where('name', 'bridge_first_day')->firstOrFail();

    Livewire::test('calendar.pricing-rule-form', ['context' => [
        'mode' => 'edit',
        'pricingRuleId' => $rule->id,
        'preview_from' => '2026-01-01',
        'preview_to' => '2026-12-31',
    ]])
        ->call('runPreview')
        ->assertSet('preview.affectedCount', 0)
        ->assertSet('preview.warnings', fn (array $warnings) => $warnings !== []);
});

test('pricing rule form available season blocks include the selected inactive block', function () {
    $inactiveBlock = SeasonBlock::factory()->fixedRange(6, 1, 6, 30)->create([
        'name' => 'inactive_mid_year',
        'is_active' => false,
        'sort_order' => 99,
    ]);

    $rule = PricingRule::factory()->create([
        'name' => 'inactive_block_rule',
        'pricing_category_id' => PricingCategory::query()->where('name', 'cat_2_high')->value('id'),
        'rule_type' => 'season_days',
        'conditions' => [
            'season_block_id' => $inactiveBlock->id,
        ],
        'priority' => 30,
    ]);

    $component = Livewire::test('calendar.pricing-rule-form', ['context' => ['mode' => 'edit', 'pricingRuleId' => $rule->id]]);

    expect(collect($component->instance()->availableSeasonBlocks())
        ->contains(fn (array $block): bool => $block['id'] === $inactiveBlock->id))
        ->toBeTrue();
});

test('pricing rule form duplicate mode increments generated copy suffixes', function () {
    $rule = PricingRule::factory()->create([
        'name' => 'seasonal_offer',
        'pricing_category_id' => PricingCategory::query()->where('name', 'cat_2_high')->value('id'),
        'rule_type' => 'season_days',
        'conditions' => ['season_block_id' => SeasonBlock::query()->where('name', 'holy_week')->value('id')],
        'priority' => 31,
    ]);

    PricingRule::factory()->create([
        'name' => 'seasonal_offer_copy',
        'pricing_category_id' => $rule->pricing_category_id,
        'rule_type' => $rule->rule_type,
        'conditions' => $rule->conditions,
        'priority' => 32,
    ]);

    PricingRule::factory()->create([
        'name' => 'seasonal_offer_copy_2',
        'pricing_category_id' => $rule->pricing_category_id,
        'rule_type' => $rule->rule_type,
        'conditions' => $rule->conditions,
        'priority' => 33,
    ]);

    Livewire::test('calendar.pricing-rule-form', ['context' => ['mode' => 'duplicate', 'pricingRuleId' => $rule->id]])
        ->assertSet('name', 'seasonal_offer_copy_3');
});

test('pricing rule form preview shows affected nights', function () {
    $category = PricingCategory::query()->where('name', 'cat_1_premium')->first();

    Livewire::test('calendar.pricing-rule-form', ['context' => ['mode' => 'create']])
        ->set('name', 'valentines_bump')
        ->set('en_description', 'Premium Valentine bump')
        ->set('es_description', 'Incremento premium San Valentín')
        ->set('pricing_category_id', $category->id)
        ->set('rule_type', 'season_days')
        ->set('priority', 0)
        ->set('season_mode', 'dates')
        ->set('recurring_dates', ['02-14'])
        ->call('runPreview')
        ->assertSet('preview.affectedCount', 2);
});

test('settings shows pending regeneration after a pricing rule change', function () {
    Livewire::test('calendar.pricing-rule-form', ['context' => ['mode' => 'create']])
        ->set('name', 'late_march_demand')
        ->set('en_description', 'Late March demand')
        ->set('es_description', 'Demanda de finales de marzo')
        ->set('pricing_category_id', PricingCategory::query()->where('name', 'cat_2_high')->value('id'))
        ->set('rule_type', 'season_days')
        ->set('priority', 14)
        ->set('season_mode', 'dates')
        ->set('recurring_dates', ['03-28'])
        ->call('save');

    Livewire::test('pages::calendar.settings')
        ->assertSet('isCalendarStale', true);
});

test('settings shows the stale callout after a configuration change', function () {
    seedCalendar2026();

    $category = PricingCategory::query()->where('name', 'cat_1_premium')->firstOrFail();

    Livewire::test('pages::calendar.settings')
        ->call('updatePricingCategory', $category->id, 'is_active', false)
        ->assertSeeText(__('calendar.settings.stale.title'))
        ->assertSeeText(__('calendar.settings.stale.description'));
});

test('settings can delete a non-fallback pricing rule', function () {
    $rule = PricingRule::query()->where('name', 'bridge_first_day')->first();

    Livewire::test('pages::calendar.settings')
        ->call('confirmPricingRuleDeletion', $rule->id)
        ->assertDispatched('open-confirm-modal')
        ->dispatch('modal-confirmed');

    expect(PricingRule::query()->whereKey($rule->id)->exists())->toBeFalse();
});

test('settings marks the calendar stale after deleting the last holiday definition', function () {
    $holiday = HolidayDefinition::query()->orderBy('id')->firstOrFail();

    HolidayDefinition::query()
        ->whereKeyNot($holiday->id)
        ->delete();

    seedCalendar2026();

    Livewire::test('pages::calendar.settings')
        ->assertSet('isCalendarStale', false)
        ->call('confirmHolidayDefinitionDeletion', $holiday->id)
        ->dispatch('modal-confirmed')
        ->assertSet('isCalendarStale', true);

    expect(HolidayDefinition::query()->exists())->toBeFalse();
});

test('settings cannot delete the active fallback pricing rule', function () {
    $fallbackRule = PricingRule::query()->where('name', 'economy_fallback')->first();

    Livewire::test('pages::calendar.settings')
        ->call('confirmPricingRuleDeletion', $fallbackRule->id)
        ->dispatch('modal-confirmed');

    expect(PricingRule::query()->whereKey($fallbackRule->id)->exists())->toBeTrue();
});

test('settings validates invalid holiday name', function () {
    $holiday = HolidayDefinition::query()->where('name', 'new_year')->first();

    Livewire::test('pages::calendar.settings')
        ->call('updateHoliday', $holiday->id, 'en_name', '')
        ->assertHasErrors(['en_name']);
});

test('settings validates invalid pricing category color', function () {
    $category = PricingCategory::query()->where('name', 'cat_1_premium')->first();

    Livewire::test('pages::calendar.settings')
        ->call('updatePricingCategory', $category->id, 'color', 'not-a-color')
        ->assertHasErrors(['color']);
});

test('settings regenerate button dispatches confirmation modal', function () {
    Livewire::test('pages::calendar.settings')
        ->call('confirmRegenerate')
        ->assertDispatched('open-confirm-modal');
});

test('settings clears pending modal actions when the confirmation is cancelled', function () {
    Livewire::test('pages::calendar.settings')
        ->set('holidayDefinitionIdPendingDeletion', 1)
        ->set('seasonBlockIdPendingDeletion', 2)
        ->set('pricingCategoryIdPendingDeletion', 3)
        ->set('pricingRuleIdPendingDeletion', 4)
        ->set('regenerationPendingConfirmation', true)
        ->dispatch('modal-confirm-cancelled')
        ->assertSet('holidayDefinitionIdPendingDeletion', null)
        ->assertSet('seasonBlockIdPendingDeletion', null)
        ->assertSet('pricingCategoryIdPendingDeletion', null)
        ->assertSet('pricingRuleIdPendingDeletion', null)
        ->assertSet('regenerationPendingConfirmation', false);
});

test('pricing rule actions only show duplicate when the user can create but not update or delete', function () {
    $viewer = makeGuest();
    $viewer->givePermissionTo([
        'pricing_rule.viewAny',
        'pricing_rule.create',
    ]);

    $this->actingAs($viewer);

    $pricingRule = PricingRule::query()->where('name', 'bridge_first_day')->firstOrFail();
    $actionsColumn = collect(Livewire::test('pages::calendar.settings')->instance()->pricingRuleColumns())
        ->first(fn ($column) => $column->type() === 'actions');

    expect($actionsColumn)->not->toBeNull();

    $actions = $actionsColumn->resolveActions($pricingRule);

    expect(collect($actions)->filter->isButton()->map->wireClick()->values()->all())
        ->toBe(['openDuplicatePricingRuleModal']);
});

test('season block actions do not show delete for non fixed-range blocks', function () {
    $seasonBlock = SeasonBlock::query()->where('name', 'holy_week')->firstOrFail();
    $actionsColumn = collect(Livewire::test('pages::calendar.settings')->instance()->seasonBlockColumns())
        ->first(fn ($column) => $column->type() === 'actions');

    expect($actionsColumn)->not->toBeNull();

    $actions = $actionsColumn->resolveActions($seasonBlock);

    expect(collect($actions)->filter->isButton()->map->wireClick()->values()->all())
        ->toContain('openEditSeasonBlockModal')
        ->not->toContain('confirmSeasonBlockDeletion');
});

test('settings page hides row action columns for viewers without management permissions', function () {
    $viewer = makeGuest();
    $viewer->givePermissionTo([
        'holiday_definition.viewAny',
        'season_block.viewAny',
        'pricing_category.viewAny',
        'pricing_rule.viewAny',
    ]);

    $this->actingAs($viewer);

    $component = Livewire::test('pages::calendar.settings')->instance();

    expect(collect($component->holidayColumns())->map->type()->all())->not->toContain('actions')
        ->and(collect($component->seasonBlockColumns())->map->type()->all())->not->toContain('actions')
        ->and(collect($component->pricingCategoryColumns())->map->type()->all())->not->toContain('actions')
        ->and(collect($component->pricingRuleColumns())->map->type()->all())->not->toContain('actions');
});

test('settings regeneration requires explicit permission', function () {
    $viewer = makeGuest();
    $viewer->givePermissionTo('holiday_definition.viewAny');

    $this->actingAs($viewer);

    Livewire::test('pages::calendar.settings')
        ->call('regenerateCalendar')
        ->assertForbidden();
});

test('settings regenerate creates calendar days', function () {
    expect(CalendarDay::query()->count())->toBe(0);

    Livewire::test('pages::calendar.settings')
        ->call('regenerateCalendar')
        ->assertHasNoErrors();

    expect(CalendarDay::query()->count())->toBeGreaterThan(0);
});

test('settings regenerate refreshes previously generated future years', function () {
    CarbonImmutable::setTestNow(CarbonImmutable::createStrict(2026, 3, 25));

    app(GenerateCalendarDays::class)->handle(
        CarbonImmutable::createStrict(2028, 1, 1),
        CarbonImmutable::createStrict(2028, 12, 31),
    );

    CalendarDay::query()->where('date', '2028-07-20')->delete();

    expect(CalendarDay::query()->where('date', '2028-07-20')->exists())->toBeFalse();

    Livewire::test('pages::calendar.settings')
        ->call('regenerateCalendar')
        ->assertHasNoErrors();

    expect(CalendarDay::query()->where('date', '2028-07-20')->exists())->toBeTrue();

    CarbonImmutable::setTestNow();
});

test('settings regenerate clears the pending regeneration banner', function () {
    seedCalendar2026();

    Livewire::test('calendar.pricing-rule-form', ['context' => ['mode' => 'edit', 'pricingRuleId' => PricingRule::query()->where('name', 'long_weekend_high_impact')->value('id')]])
        ->set('priority', 15)
        ->call('save');

    Livewire::test('pages::calendar.settings')
        ->assertSet('isCalendarStale', true)
        ->call('confirmRegenerate')
        ->dispatch('modal-confirmed')
        ->assertSet('isCalendarStale', false);
});

test('settings shows regenerate button', function () {
    $this->get(route('calendar.settings'))
        ->assertOk()
        ->assertSeeText(__('calendar.settings.regenerate.button'));
});
