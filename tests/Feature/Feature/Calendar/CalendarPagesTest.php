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

test('settings can update a pricing category multiplier', function () {
    $category = PricingCategory::query()->where('name', 'cat_1_premium')->first();

    Livewire::test('pages::calendar.settings')
        ->call('updatePricingCategory', $category->id, 'multiplier', 3.00)
        ->assertHasNoErrors();

    expect((float) $category->fresh()->multiplier)->toBe(3.00);
});

test('settings can update a pricing rule priority', function () {
    $rule = PricingRule::query()->where('name', 'economy_fallback')->first();

    Livewire::test('pages::calendar.settings')
        ->call('updatePricingRule', $rule->id, 'priority', 200)
        ->assertHasNoErrors();

    expect($rule->fresh()->priority)->toBe(200);
});

test('settings can update a pricing rule category', function () {
    $rule = PricingRule::query()->where('name', 'holy_week')->first();
    $newCategory = PricingCategory::query()->where('name', 'cat_2_high')->first();

    Livewire::test('pages::calendar.settings')
        ->call('updatePricingRule', $rule->id, 'pricing_category_id', $newCategory->id)
        ->assertHasNoErrors();

    expect($rule->fresh()->pricing_category_id)->toBe($newCategory->id);
});

test('settings validates invalid pricing rule category', function () {
    $rule = PricingRule::query()->where('name', 'holy_week')->first();

    Livewire::test('pages::calendar.settings')
        ->call('updatePricingRule', $rule->id, 'pricing_category_id', 99999)
        ->assertHasErrors(['pricing_category_id']);
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

test('settings regenerate creates calendar days', function () {
    expect(CalendarDay::query()->count())->toBe(0);

    Livewire::test('pages::calendar.settings')
        ->call('regenerateCalendar')
        ->assertHasNoErrors();

    expect(CalendarDay::query()->count())->toBeGreaterThan(0);
});

test('settings shows regenerate button', function () {
    $this->get(route('calendar.settings'))
        ->assertOk()
        ->assertSeeText(__('calendar.settings.regenerate.button'));
});
