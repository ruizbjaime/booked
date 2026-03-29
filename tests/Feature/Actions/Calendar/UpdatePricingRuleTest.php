<?php

use App\Actions\Calendar\UpdatePricingRule;
use App\Models\PricingCategory;
use App\Models\PricingRule;
use App\Models\SystemSetting;
use Database\Seeders\PricingCategorySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->seed([
        RolesAndPermissionsSeeder::class,
        PricingCategorySeeder::class,
    ]);
});

it('updates pricing rule fields using normalized values', function () {
    $admin = makeAdmin();
    $initialCategoryId = PricingCategory::query()->where('name', 'cat_1_premium')->value('id');
    $pricingRule = PricingRule::factory()->create([
        'name' => 'bridge_rule',
        'pricing_category_id' => $initialCategoryId,
        'conditions' => ['fallback' => true],
    ]);
    $categoryId = PricingCategory::query()->where('name', 'cat_2_high')->value('id');

    app(UpdatePricingRule::class)->handle($admin, $pricingRule, 'name', ' BRIDGE_FIRST_DAY ');
    app(UpdatePricingRule::class)->handle($admin, $pricingRule, 'en_description', ' First bridge day ');
    app(UpdatePricingRule::class)->handle($admin, $pricingRule, 'conditions', '{"is_bridge_weekend":true}');
    app(UpdatePricingRule::class)->handle($admin, $pricingRule, 'pricing_category_id', $categoryId);
    app(UpdatePricingRule::class)->handle($admin, $pricingRule, 'is_active', false);

    $fresh = $pricingRule->fresh();

    expect($fresh->name)->toBe('bridge_first_day')
        ->and($fresh->en_description)->toBe('First bridge day')
        ->and($fresh->conditions)->toBe(['is_bridge_weekend' => true])
        ->and($fresh->pricing_category_id)->toBe($categoryId)
        ->and($fresh->is_active)->toBeFalse()
        ->and(SystemSetting::instance()->calendar_config_updated_at)->not->toBeNull();
});

it('rejects invalid pricing rule json conditions', function () {
    $admin = makeAdmin();
    $pricingRule = PricingRule::factory()->create([
        'pricing_category_id' => PricingCategory::query()->where('name', 'cat_1_premium')->value('id'),
    ]);

    expect(fn () => app(UpdatePricingRule::class)->handle($admin, $pricingRule, 'conditions', '{bad-json'))
        ->toThrow(ValidationException::class);
});

it('requires authorization to update a pricing rule', function () {
    $guest = makeGuest();
    $pricingRule = PricingRule::factory()->create([
        'pricing_category_id' => PricingCategory::query()->where('name', 'cat_1_premium')->value('id'),
    ]);

    expect(fn () => app(UpdatePricingRule::class)->handle($guest, $pricingRule, 'en_description', 'Blocked'))
        ->toThrow(AuthorizationException::class);
});

it('aborts with 422 for an unknown pricing rule field', function () {
    $admin = makeAdmin();
    $pricingRule = PricingRule::factory()->create([
        'pricing_category_id' => PricingCategory::query()->where('name', 'cat_1_premium')->value('id'),
    ]);

    expect(fn () => app(UpdatePricingRule::class)->handle($admin, $pricingRule, 'unknown_field', 'value'))
        ->toThrow(HttpException::class);
});

it('rejects assigning an inactive pricing category via inline update', function () {
    $admin = makeAdmin();
    $activeCategory = PricingCategory::query()->where('name', 'cat_1_premium')->first();
    $inactiveCategory = PricingCategory::factory()->create(['is_active' => false, 'level' => 99]);

    $pricingRule = PricingRule::factory()->create([
        'pricing_category_id' => $activeCategory->id,
    ]);

    expect(fn () => app(UpdatePricingRule::class)->handle($admin, $pricingRule, 'pricing_category_id', $inactiveCategory->id))
        ->toThrow(ValidationException::class);

    expect($pricingRule->fresh()->pricing_category_id)->toBe($activeCategory->id);
});

it('updates additional pricing rule text and priority fields', function () {
    $admin = makeAdmin();
    $pricingRule = PricingRule::factory()->create([
        'pricing_category_id' => PricingCategory::query()->where('name', 'cat_1_premium')->value('id'),
        'es_description' => 'Original',
        'priority' => 10,
    ]);

    app(UpdatePricingRule::class)->handle($admin, $pricingRule, 'es_description', ' Descripcion ajustada ');
    app(UpdatePricingRule::class)->handle($admin, $pricingRule, 'priority', 15);

    expect($pricingRule->fresh()->es_description)->toBe('Descripcion ajustada')
        ->and($pricingRule->fresh()->priority)->toBe(15);
});
