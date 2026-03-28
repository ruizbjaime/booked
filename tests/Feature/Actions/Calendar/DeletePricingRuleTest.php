<?php

use App\Actions\Calendar\DeletePricingRule;
use App\Domain\Calendar\Enums\PricingRuleType;
use App\Models\PricingCategory;
use App\Models\PricingRule;
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
