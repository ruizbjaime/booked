<?php

use App\Models\PricingCategory;
use App\Models\PricingRule;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $premiumCategoryId = PricingCategory::query()->where('name', 'cat_1_premium')->value('id');
        $highCategoryId = PricingCategory::query()->where('name', 'cat_2_high')->value('id');

        if ($premiumCategoryId === null || $highCategoryId === null) {
            return;
        }

        $holyWeek = PricingRule::query()->where('name', 'holy_week')->first();

        if ($holyWeek !== null) {
            $holyWeek->update([
                'en_description' => 'Holy Week Thursday through Saturday at premium rate',
                'es_description' => 'Semana Santa jueves a sábado a tarifa premium',
                'pricing_category_id' => $premiumCategoryId,
                'conditions' => ['season' => 'holy_week', 'only_last_n_days' => 3],
                'priority' => 1,
            ]);
        }

        $holyWeekNonPremium = PricingRule::query()->firstOrNew(['name' => 'holy_week_non_premium']);
        $holyWeekNonPremium->fill([
            'en_description' => 'Holy Week non-premium days at high rate',
            'es_description' => 'Semana Santa días no premium a tarifa alta',
            'pricing_category_id' => $highCategoryId,
            'rule_type' => 'season_days',
            'conditions' => ['season' => 'holy_week', 'exclude_last_n_days' => 3],
            'priority' => 13,
            'is_active' => true,
        ]);
        $holyWeekNonPremium->save();
    }

    public function down(): void
    {
        $highCategoryId = PricingCategory::query()->where('name', 'cat_2_high')->value('id');

        if ($highCategoryId === null) {
            return;
        }

        $holyWeek = PricingRule::query()->where('name', 'holy_week')->first();

        if ($holyWeek !== null) {
            $holyWeek->update([
                'en_description' => 'Holy Week Thursday through Saturday at high rate',
                'es_description' => 'Semana Santa jueves a sábado a tarifa alta',
                'pricing_category_id' => $highCategoryId,
                'conditions' => ['season' => 'holy_week', 'only_last_n_days' => 3],
                'priority' => 1,
            ]);
        }

        $holyWeekNonPremium = PricingRule::query()->where('name', 'holy_week_non_premium')->first();

        if ($holyWeekNonPremium !== null) {
            $holyWeekNonPremium->update([
                'en_description' => 'Holy Week non-premium days at high rate',
                'es_description' => 'Semana Santa días no premium a tarifa alta',
                'pricing_category_id' => $highCategoryId,
                'rule_type' => 'season_days',
                'conditions' => ['season' => 'holy_week', 'exclude_last_n_days' => 3],
                'priority' => 13,
                'is_active' => true,
            ]);
        }
    }
};
