<?php

use App\Models\PricingRule;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $holyWeek = PricingRule::query()->where('name', 'holy_week')->first();

        if (! $holyWeek) {
            return;
        }

        // holy_week matches only the last 3 days of the season (Thu-Fri-Sat)
        $holyWeek->update([
            'en_description' => 'Holy Week Thursday through Saturday at high rate',
            'es_description' => 'Semana Santa jueves a sábado a tarifa alta',
            'conditions' => json_encode(['season' => 'holy_week', 'only_last_n_days' => 3]),
        ]);

        // holy_week_non_premium matches all days except the last 3
        PricingRule::query()->firstOrCreate(
            ['name' => 'holy_week_non_premium'],
            [
                'en_description' => 'Holy Week non-premium days at high rate',
                'es_description' => 'Semana Santa días no premium a tarifa alta',
                'pricing_category_id' => $holyWeek->pricing_category_id,
                'rule_type' => 'season_days',
                'conditions' => json_encode(['season' => 'holy_week', 'exclude_last_n_days' => 3]),
                'priority' => 13,
                'is_active' => true,
            ],
        );
    }
};
