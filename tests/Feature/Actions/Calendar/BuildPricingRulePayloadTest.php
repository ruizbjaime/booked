<?php

use App\Actions\Calendar\BuildPricingRulePayload;
use App\Models\PricingCategory;
use App\Models\PricingRule;
use App\Models\SeasonBlock;
use Database\Seeders\PricingCategorySeeder;
use Database\Seeders\PricingRuleSeeder;
use Database\Seeders\SeasonBlockSeeder;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed([
        SeasonBlockSeeder::class,
        PricingCategorySeeder::class,
        PricingRuleSeeder::class,
    ]);
});

it('rejects duplicate active priorities', function () {
    expect(fn () => app(BuildPricingRulePayload::class)->handle([
        'name' => 'competing_rule',
        'en_description' => 'Competing rule',
        'es_description' => 'Regla competidora',
        'pricing_category_id' => PricingCategory::query()->where('name', 'cat_2_high')->value('id'),
        'rule_type' => 'season_days',
        'priority' => 1,
        'is_active' => true,
        'season_mode' => 'dates',
        'recurring_dates' => ['11-01'],
    ]))->toThrow(ValidationException::class);
});

it('rejects an active fallback that is not last', function () {
    expect(fn () => app(BuildPricingRulePayload::class)->handle([
        'name' => 'new_fallback',
        'en_description' => 'Fallback',
        'es_description' => 'Fallback',
        'pricing_category_id' => PricingCategory::query()->where('name', 'cat_4_economy')->value('id'),
        'rule_type' => 'economy_default',
        'priority' => 10,
        'is_active' => true,
    ]))->toThrow(ValidationException::class);
});

it('normalizes season block rules using stable season block ids', function () {
    $payload = app(BuildPricingRulePayload::class)->handle([
        'name' => 'mid_year_high',
        'en_description' => 'Mid-year high season',
        'es_description' => 'Temporada alta de mitad de año',
        'pricing_category_id' => PricingCategory::query()->where('name', 'cat_2_high')->value('id'),
        'rule_type' => 'season_days',
        'priority' => 15,
        'is_active' => true,
        'season_mode' => 'season',
        'season_block_id' => SeasonBlock::query()->where('name', 'october_recess')->value('id'),
        'day_of_week' => ['friday', 'saturday'],
    ]);

    expect($payload['conditions'])->toBe([
        'day_of_week' => ['friday', 'saturday'],
        'season_block_id' => SeasonBlock::query()->where('name', 'october_recess')->value('id'),
    ]);
});

it('rejects season days rules without season or dates', function () {
    expect(fn () => app(BuildPricingRulePayload::class)->handle([
        'name' => 'no_season_no_dates',
        'en_description' => 'No season',
        'es_description' => 'Sin temporada',
        'pricing_category_id' => PricingCategory::query()->where('name', 'cat_2_high')->value('id'),
        'rule_type' => 'season_days',
        'priority' => 50,
        'is_active' => true,
        'season_mode' => 'season',
    ]))->toThrow(ValidationException::class);
});

it('rejects conflicting last day filters', function () {
    expect(fn () => app(BuildPricingRulePayload::class)->handle([
        'name' => 'conflicting_filters',
        'en_description' => 'Conflicting',
        'es_description' => 'Conflicto',
        'pricing_category_id' => PricingCategory::query()->where('name', 'cat_2_high')->value('id'),
        'rule_type' => 'season_days',
        'priority' => 50,
        'is_active' => true,
        'season_mode' => 'season',
        'season_block_id' => SeasonBlock::query()->first()->id,
        'only_last_n_days' => 5,
        'exclude_last_n_days' => 3,
    ]))->toThrow(ValidationException::class);
});

it('rejects non-string legacy season names', function () {
    expect(fn () => app(BuildPricingRulePayload::class)->handle([
        'name' => 'bad_legacy_season',
        'en_description' => 'Bad legacy',
        'es_description' => 'Legado malo',
        'pricing_category_id' => PricingCategory::query()->where('name', 'cat_2_high')->value('id'),
        'rule_type' => 'season_days',
        'priority' => 50,
        'is_active' => true,
        'season_mode' => 'season',
        'conditions' => ['season' => 12345],
    ]))->toThrow(ValidationException::class);
});

it('rejects legacy season names that do not match any season block', function () {
    expect(fn () => app(BuildPricingRulePayload::class)->handle([
        'name' => 'unknown_season',
        'en_description' => 'Unknown season',
        'es_description' => 'Temporada desconocida',
        'pricing_category_id' => PricingCategory::query()->where('name', 'cat_2_high')->value('id'),
        'rule_type' => 'season_days',
        'priority' => 50,
        'is_active' => true,
        'season_mode' => 'season',
        'conditions' => ['season' => 'nonexistent_season'],
    ]))->toThrow(ValidationException::class);
});

it('allows duplicate priorities for inactive rules', function () {
    $payload = app(BuildPricingRulePayload::class)->handle([
        'name' => 'inactive_rule',
        'en_description' => 'Inactive',
        'es_description' => 'Inactiva',
        'pricing_category_id' => PricingCategory::query()->where('name', 'cat_2_high')->value('id'),
        'rule_type' => 'season_days',
        'priority' => 1,
        'is_active' => false,
        'season_mode' => 'dates',
        'recurring_dates' => ['06-15'],
    ]);

    expect($payload['is_active'])->toBeFalse()
        ->and($payload['priority'])->toBe(1);
});

it('skips fallback position check when no active fallback exists', function () {
    PricingRule::query()->delete();

    try {
        app(BuildPricingRulePayload::class)->handle([
            'name' => 'first_rule',
            'en_description' => 'First rule',
            'es_description' => 'Primera regla',
            'pricing_category_id' => PricingCategory::query()->where('name', 'cat_2_high')->value('id'),
            'rule_type' => 'season_days',
            'priority' => 1,
            'is_active' => true,
            'season_mode' => 'dates',
            'recurring_dates' => ['12-25'],
        ]);
        $this->fail('Expected ValidationException was not thrown');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('rule_type')
            ->and($e->errors())->not->toHaveKey('priority');
    }
});
