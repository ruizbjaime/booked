<?php

use App\Models\PricingCategory;
use App\Models\PricingRule;
use App\Models\SeasonBlock;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Remove foreign tourist season block and its pricing rule
        PricingRule::query()->where('name', 'foreign_tourist_season')->delete();
        SeasonBlock::query()->where('name', 'foreign_tourist')->delete();

        // Merge holy_week_premium + holy_week_non_premium into a single holy_week rule at CAT 2
        $cat2 = PricingCategory::query()->where('name', 'cat_2_high')->value('id');

        PricingRule::query()->where('name', 'holy_week_premium')->update([
            'name' => 'holy_week',
            'en_description' => 'Holy Week at high rate',
            'es_description' => 'Semana Santa a tarifa alta',
            'pricing_category_id' => $cat2,
            'conditions' => json_encode(['season' => 'holy_week']),
        ]);

        PricingRule::query()->where('name', 'holy_week_non_premium')->delete();

        // October recess from CAT 2 to CAT 3
        $cat3 = PricingCategory::query()->where('name', 'cat_3_weekend_std')->value('id');

        PricingRule::query()->where('name', 'october_recess')->update([
            'pricing_category_id' => $cat3,
        ]);
    }
};
