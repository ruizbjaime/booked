<?php

use App\Domain\Calendar\Data\HolidayDefinitionData;
use App\Domain\Calendar\Data\PricingRuleData;
use App\Domain\Calendar\Data\PricingRuleImpactPreviewData;
use App\Domain\Calendar\Data\PricingRulePreviewSample;
use App\Domain\Calendar\Data\SeasonBlockData;
use App\Domain\Calendar\Enums\HolidayGroup;
use App\Domain\Calendar\Enums\PricingRuleType;
use App\Domain\Calendar\Enums\SeasonStrategy;
use Carbon\CarbonImmutable;

// --- SeasonBlockData ---

it('constructs SeasonBlockData with all properties', function () {
    $data = new SeasonBlockData(
        id: 1,
        name: 'December Season',
        calculationStrategy: SeasonStrategy::DecemberSeason,
        fixedStartMonth: 12,
        fixedStartDay: 15,
        fixedEndMonth: 1,
        fixedEndDay: 15,
        priority: 1,
    );

    expect($data->id)->toBe(1)
        ->and($data->name)->toBe('December Season')
        ->and($data->calculationStrategy)->toBe(SeasonStrategy::DecemberSeason)
        ->and($data->fixedStartMonth)->toBe(12)
        ->and($data->priority)->toBe(1);
});

it('constructs SeasonBlockData with defaults for optional properties', function () {
    $data = new SeasonBlockData(
        id: 2,
        name: 'Holy Week',
        calculationStrategy: SeasonStrategy::HolyWeek,
    );

    expect($data->fixedStartMonth)->toBeNull()
        ->and($data->fixedStartDay)->toBeNull()
        ->and($data->fixedEndMonth)->toBeNull()
        ->and($data->fixedEndDay)->toBeNull()
        ->and($data->priority)->toBe(0);
});

// --- PricingRuleData ---

it('constructs PricingRuleData with all properties', function () {
    $data = new PricingRuleData(
        id: 10,
        name: 'Holiday Premium',
        pricingCategoryId: 3,
        pricingCategoryLevel: 5,
        ruleType: PricingRuleType::Holiday,
        conditions: ['min_impact' => 3],
        priority: 1,
    );

    expect($data->id)->toBe(10)
        ->and($data->name)->toBe('Holiday Premium')
        ->and($data->ruleType)->toBe(PricingRuleType::Holiday)
        ->and($data->conditions)->toBe(['min_impact' => 3])
        ->and($data->priority)->toBe(1);
});

// --- HolidayDefinitionData ---

it('constructs HolidayDefinitionData for a fixed holiday', function () {
    $data = new HolidayDefinitionData(
        id: 1,
        name: 'New Year',
        group: HolidayGroup::Fixed,
        month: 1,
        day: 1,
        easterOffset: null,
        movesToMonday: false,
        baseImpactWeights: ['default' => 5],
    );

    expect($data->id)->toBe(1)
        ->and($data->group)->toBe(HolidayGroup::Fixed)
        ->and($data->month)->toBe(1)
        ->and($data->day)->toBe(1)
        ->and($data->easterOffset)->toBeNull()
        ->and($data->movesToMonday)->toBeFalse()
        ->and($data->specialOverrides)->toBeNull();
});

it('constructs HolidayDefinitionData for an easter-based holiday', function () {
    $data = new HolidayDefinitionData(
        id: 2,
        name: 'Good Friday',
        group: HolidayGroup::EasterBased,
        month: null,
        day: null,
        easterOffset: -2,
        movesToMonday: false,
        baseImpactWeights: ['default' => 4],
    );

    expect($data->month)->toBeNull()
        ->and($data->day)->toBeNull()
        ->and($data->easterOffset)->toBe(-2);
});

// --- PricingRulePreviewSample ---

it('constructs PricingRulePreviewSample with date and categories', function () {
    $date = CarbonImmutable::createStrict(2026, 4, 1);

    $sample = new PricingRulePreviewSample(
        date: $date,
        fromCategory: 'Economy',
        toCategory: 'Premium',
    );

    expect($sample->date->toDateString())->toBe('2026-04-01')
        ->and($sample->fromCategory)->toBe('Economy')
        ->and($sample->toCategory)->toBe('Premium');
});

// --- PricingRuleImpactPreviewData ---

it('constructs PricingRuleImpactPreviewData with all properties', function () {
    $sample = new PricingRulePreviewSample(
        date: CarbonImmutable::createStrict(2026, 4, 1),
        fromCategory: 'Economy',
        toCategory: 'Premium',
    );

    $preview = new PricingRuleImpactPreviewData(
        affectedCount: 15,
        changesByCategory: ['Economy' => -10, 'Premium' => 10],
        sampleDates: [$sample],
        warnings: ['Some dates overlap with existing rules'],
    );

    expect($preview->affectedCount)->toBe(15)
        ->and($preview->changesByCategory)->toHaveCount(2)
        ->and($preview->sampleDates)->toHaveCount(1)
        ->and($preview->sampleDates[0])->toBeInstanceOf(PricingRulePreviewSample::class)
        ->and($preview->warnings)->toHaveCount(1);
});

it('constructs PricingRuleImpactPreviewData with empty collections', function () {
    $preview = new PricingRuleImpactPreviewData(
        affectedCount: 0,
        changesByCategory: [],
        sampleDates: [],
        warnings: [],
    );

    expect($preview->affectedCount)->toBe(0)
        ->and($preview->changesByCategory)->toBeEmpty()
        ->and($preview->sampleDates)->toBeEmpty()
        ->and($preview->warnings)->toBeEmpty();
});
