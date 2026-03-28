<?php

use App\Domain\Calendar\Enums\SeasonStrategy;
use App\Models\CalendarDay;
use App\Models\SeasonBlock;
use Carbon\CarbonImmutable;

it('returns localized season block name, column, and accessor for both locales', function () {
    $block = SeasonBlock::factory()->fixedRange(6, 1, 6, 30)->create([
        'en_name' => 'Mid Season',
        'es_name' => 'Temporada Media',
    ]);

    app()->setLocale('en');

    expect($block->localizedName())->toBe('Mid Season')
        ->and(SeasonBlock::localizedNameColumn())->toBe('en_name')
        ->and($block->localized_name_attribute)->toBe('Mid Season');

    app()->setLocale('es');

    expect($block->localizedName())->toBe('Temporada Media')
        ->and(SeasonBlock::localizedNameColumn())->toBe('es_name')
        ->and($block->localized_name_attribute)->toBe('Temporada Media');
});

it('casts attributes, filters active records, and formats fixed range labels', function () {
    SeasonBlock::factory()->fixedRange(1, 1, 1, 31)->create(['is_active' => true]);
    SeasonBlock::factory()->fixedRange(2, 1, 2, 28)->create(['is_active' => false]);

    $fixedBlock = SeasonBlock::factory()->fixedRange(6, 1, 8, 31)->create([
        'calculation_strategy' => SeasonStrategy::FixedRange,
        'priority' => 3,
        'sort_order' => 7,
        'is_active' => true,
    ])->fresh();

    $nonFixedBlock = SeasonBlock::factory()->create([
        'calculation_strategy' => SeasonStrategy::DecemberSeason,
        'fixed_start_month' => null,
        'fixed_start_day' => null,
        'fixed_end_month' => null,
        'fixed_end_day' => null,
        'is_active' => false,
    ]);

    expect(SeasonBlock::query()->active()->count())->toBe(2)
        ->and($fixedBlock->calculation_strategy)->toBe(SeasonStrategy::FixedRange)
        ->and($fixedBlock->priority)->toBe(3)
        ->and($fixedBlock->sort_order)->toBe(7)
        ->and($fixedBlock->is_active)->toBeTrue()
        ->and($fixedBlock->isFixedRange())->toBeTrue()
        ->and($fixedBlock->fixedRangeLabel())->toBe('06-01 → 08-31')
        ->and($nonFixedBlock->isFixedRange())->toBeFalse()
        ->and($nonFixedBlock->fixedRangeLabel())->toBe('—');
});

it('exposes related calendar days through the relationship', function () {
    $block = SeasonBlock::factory()->fixedRange(6, 1, 6, 30)->create();
    $day = CalendarDay::factory()->forDate(CarbonImmutable::parse('2026-06-15'))->create([
        'season_block_id' => $block->id,
        'season_block_name' => $block->name,
    ]);

    expect($block->calendarDays)->toHaveCount(1)
        ->and($block->calendarDays->first()->is($day))->toBeTrue();
});

it('returns an em dash for incomplete fixed ranges and formats its label', function () {
    $block = SeasonBlock::factory()->create([
        'name' => 'summer_break',
        'calculation_strategy' => SeasonStrategy::FixedRange,
        'fixed_start_month' => 6,
        'fixed_start_day' => 1,
        'fixed_end_month' => null,
        'fixed_end_day' => null,
    ]);

    expect($block->fixedRangeLabel())->toBe('—')
        ->and($block->label())->toBe(__('calendar.settings.season_block_label', [
            'name' => 'summer_break',
            'id' => $block->id,
        ]));
});
