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
        ->assertSeeText('new_year')
        ->assertSeeText('christmas')
        ->assertSeeText('holy_thursday');
});

test('settings page shows season blocks', function () {
    Livewire::test('pages::calendar.settings')
        ->assertSeeText('holy_week')
        ->assertSeeText('year_end');
});

test('settings page shows pricing categories', function () {
    Livewire::test('pages::calendar.settings')
        ->assertSeeText('cat_1_premium')
        ->assertSeeText('cat_4_economy');
});

test('settings page shows pricing rules', function () {
    Livewire::test('pages::calendar.settings')
        ->assertSeeText('holy_week')
        ->assertSeeText('economy_fallback');
});

test('settings page shows create rule action and condition summary', function () {
    Livewire::test('pages::calendar.settings')
        ->assertSeeText(__('calendar.settings.rule_form.create_action'))
        ->assertSeeText(__('calendar.settings.fields.conditions'));
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

test('settings can update a pricing category multiplier', function () {
    $category = PricingCategory::query()->where('name', 'cat_1_premium')->first();

    Livewire::test('pages::calendar.settings')
        ->call('updatePricingCategory', $category->id, 'multiplier', 3.00)
        ->assertHasNoErrors();

    expect((float) $category->fresh()->multiplier)->toBe(3.00);
});

test('settings can open the create pricing rule modal', function () {
    Livewire::test('pages::calendar.settings')
        ->call('openCreatePricingRuleModal')
        ->assertDispatched('open-form-modal', fn (string $event, array $params) => ($params['name'] ?? null) === 'calendar.pricing-rules.form');
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

test('pricing rule form can edit an existing rule', function () {
    $rule = PricingRule::query()->where('name', 'bridge_weekend')->first();

    Livewire::test('calendar.pricing-rule-form', ['context' => ['mode' => 'edit', 'pricingRuleId' => $rule->id]])
        ->set('day_of_week', ['friday', 'saturday', 'sunday'])
        ->set('priority', 11)
        ->call('save')
        ->assertHasNoErrors();

    $updated = $rule->fresh();

    expect($updated->priority)->toBe(11)
        ->and($updated->conditions)->toMatchArray([
            'is_bridge_weekend' => true,
            'day_of_week' => ['friday', 'saturday', 'sunday'],
        ]);
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

test('pricing rule form preview shows affected nights', function () {
    $category = PricingCategory::query()->where('name', 'cat_1_premium')->first();

    Livewire::test('calendar.pricing-rule-form', ['context' => ['mode' => 'create']])
        ->set('name', 'new_year_bump')
        ->set('en_description', 'Premium New Year bump')
        ->set('es_description', 'Incremento premium de año nuevo')
        ->set('pricing_category_id', $category->id)
        ->set('rule_type', 'season_days')
        ->set('priority', 4)
        ->set('season_mode', 'dates')
        ->set('recurring_dates', ['01-01'])
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

test('settings can delete a non-fallback pricing rule', function () {
    $rule = PricingRule::query()->where('name', 'bridge_first_day')->first();

    Livewire::test('pages::calendar.settings')
        ->call('confirmPricingRuleDeletion', $rule->id)
        ->assertDispatched('open-confirm-modal')
        ->dispatch('modal-confirmed');

    expect(PricingRule::query()->whereKey($rule->id)->exists())->toBeFalse();
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

    Livewire::test('calendar.pricing-rule-form', ['context' => ['mode' => 'edit', 'pricingRuleId' => PricingRule::query()->where('name', 'bridge_weekend')->value('id')]])
        ->set('priority', 11)
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
