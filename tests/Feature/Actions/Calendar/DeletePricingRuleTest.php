<?php

use App\Actions\Calendar\DeletePricingRule;
use App\Domain\Calendar\Enums\PricingRuleType;
use App\Models\PricingCategory;
use App\Models\PricingRule;
use App\Models\SystemSetting;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('prevents deleting the active economy default fallback rule', function () {
    $admin = makeAdmin();
    $rule = PricingRule::factory()->create([
        'pricing_category_id' => PricingCategory::factory(),
        'rule_type' => PricingRuleType::EconomyDefault,
        'conditions' => ['fallback' => true],
        'is_active' => true,
    ]);

    expect(fn () => app(DeletePricingRule::class)->handle($admin, $rule))
        ->toThrow(ValidationException::class);

    expect($rule->fresh())->not->toBeNull();
});

it('deletes a non-fallback rule and stamps the remaining one', function () {
    $admin = makeAdmin();
    $remaining = PricingRule::factory()->create([
        'pricing_category_id' => PricingCategory::factory(),
        'rule_type' => PricingRuleType::EconomyDefault,
        'conditions' => ['fallback' => true],
        'is_active' => true,
    ]);
    $toDelete = PricingRule::factory()->create([
        'pricing_category_id' => PricingCategory::factory(),
        'rule_type' => PricingRuleType::SeasonDays,
        'conditions' => ['season' => 'high'],
        'is_active' => true,
    ]);

    app(DeletePricingRule::class)->handle($admin, $toDelete);

    expect($toDelete->fresh())->toBeNull()
        ->and($remaining->fresh())->not->toBeNull()
        ->and(SystemSetting::instance()->calendar_config_updated_at)->not->toBeNull();
});

it('deletes the last remaining rule and marks configuration changed', function () {
    $admin = makeAdmin();
    $rule = PricingRule::factory()->create([
        'pricing_category_id' => PricingCategory::factory(),
        'rule_type' => PricingRuleType::SeasonDays,
        'conditions' => ['season' => 'high'],
        'is_active' => true,
    ]);

    app(DeletePricingRule::class)->handle($admin, $rule);

    expect($rule->fresh())->toBeNull()
        ->and(PricingRule::query()->count())->toBe(0)
        ->and(SystemSetting::instance()->calendar_config_updated_at)->not->toBeNull();
});
