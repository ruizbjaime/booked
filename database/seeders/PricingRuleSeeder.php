<?php

namespace Database\Seeders;

use App\Models\PricingCategory;
use App\Models\PricingRule;
use App\Models\SeasonBlock;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class PricingRuleSeeder extends Seeder
{
    public function run(): void
    {
        $categories = PricingCategory::query()
            ->pluck('id', 'name');

        $seasonBlocks = SeasonBlock::query()
            ->pluck('id', 'name');

        PricingRule::upsert(
            $this->rules($categories, $seasonBlocks),
            ['name'],
            ['en_description', 'es_description', 'pricing_category_id', 'rule_type', 'conditions', 'priority'],
        );
    }

    /**
     * @param  Collection<string, int>  $categories
     * @param  Collection<string, int>  $seasonBlocks
     * @return list<array{name: string, en_description: string, es_description: string, pricing_category_id: int, rule_type: string, conditions: string, priority: int}>
     */
    private function rules($categories, $seasonBlocks): array
    {
        $cat1 = $categories['cat_1_premium'];
        $cat2 = $categories['cat_2_high'];
        $cat3 = $categories['cat_3_weekend_std'];
        $cat4 = $categories['cat_4_economy'];
        $holyWeek = $seasonBlocks['holy_week'];
        $octoberRecess = $seasonBlocks['october_recess'];

        return [
            [
                'name' => 'holy_week',
                'en_description' => 'Holy Week Thursday through Saturday at premium rate',
                'es_description' => 'Semana Santa jueves a sábado a tarifa premium',
                'pricing_category_id' => $cat1,
                'rule_type' => 'season_days',
                'conditions' => json_encode(['season_block_id' => $holyWeek, 'only_last_n_days' => 3]),
                'priority' => 1,
            ],
            [
                'name' => 'dec_7_8_villa_de_leyva',
                'en_description' => 'December 7-8 Festival of Lights in Villa de Leyva',
                'es_description' => 'Dic 7-8 Festival de Luces en Villa de Leyva',
                'pricing_category_id' => $cat1,
                'rule_type' => 'season_days',
                'conditions' => json_encode(['dates' => ['12-07', '12-08']]),
                'priority' => 2,
            ],
            [
                'name' => 'new_years_eve',
                'en_description' => 'New Year\'s Eve at premium rate',
                'es_description' => 'Noche de Año Nuevo a tarifa premium',
                'pricing_category_id' => $cat1,
                'rule_type' => 'season_days',
                'conditions' => json_encode(['dates' => ['12-31']]),
                'priority' => 3,
            ],
            [
                'name' => 'bridge_first_day',
                'en_description' => 'First day of a bridge weekend (arrival day)',
                'es_description' => 'Primer día de un puente (día de llegada)',
                'pricing_category_id' => $cat3,
                'rule_type' => 'holiday_bridge',
                'conditions' => json_encode(['is_bridge_weekend' => true, 'is_first_bridge_day' => true]),
                'priority' => 9,
            ],
            [
                'name' => 'bridge_weekend',
                'en_description' => 'Bridge days around holiday long weekends',
                'es_description' => 'Días puente alrededor de fines de semana festivos',
                'pricing_category_id' => $cat2,
                'rule_type' => 'holiday_bridge',
                'conditions' => json_encode(['is_bridge_weekend' => true, 'day_of_week' => ['thursday', 'friday', 'saturday', 'sunday']]),
                'priority' => 10,
            ],
            [
                'name' => 'holy_week_non_premium',
                'en_description' => 'Holy Week non-premium days at high rate',
                'es_description' => 'Semana Santa días no premium a tarifa alta',
                'pricing_category_id' => $cat2,
                'rule_type' => 'season_days',
                'conditions' => json_encode(['season_block_id' => $holyWeek, 'exclude_last_n_days' => 3]),
                'priority' => 13,
            ],
            [
                'name' => 'october_recess',
                'en_description' => 'October school recess week',
                'es_description' => 'Semana de receso de octubre',
                'pricing_category_id' => $cat3,
                'rule_type' => 'season_days',
                'conditions' => json_encode(['season_block_id' => $octoberRecess]),
                'priority' => 12,
            ],
            [
                'name' => 'normal_weekend',
                'en_description' => 'Regular Friday and Saturday outside seasons',
                'es_description' => 'Viernes y sábado regular fuera de temporada',
                'pricing_category_id' => $cat3,
                'rule_type' => 'normal_weekend',
                'conditions' => json_encode(['day_of_week' => ['friday', 'saturday'], 'outside_season' => true, 'not_bridge' => true]),
                'priority' => 20,
            ],
            [
                'name' => 'economy_fallback',
                'en_description' => 'Default economy rate for all other days',
                'es_description' => 'Tarifa económica por defecto para los demás días',
                'pricing_category_id' => $cat4,
                'rule_type' => 'economy_default',
                'conditions' => json_encode(['fallback' => true]),
                'priority' => 100,
            ],
        ];
    }
}
