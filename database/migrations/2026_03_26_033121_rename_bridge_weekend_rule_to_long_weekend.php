<?php

use App\Models\PricingRule;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $bridgeWeekend = PricingRule::query()->where('name', 'bridge_weekend')->first();
        $longWeekend = PricingRule::query()->where('name', 'long_weekend')->first();

        if ($bridgeWeekend === null && $longWeekend === null) {
            return;
        }

        if ($longWeekend === null && $bridgeWeekend !== null) {
            $bridgeWeekend->update([
                'name' => 'long_weekend',
            ]);

            return;
        }

        if ($bridgeWeekend !== null && $longWeekend !== null) {
            $longWeekend->update([
                'en_description' => $bridgeWeekend->en_description,
                'es_description' => $bridgeWeekend->es_description,
                'pricing_category_id' => $bridgeWeekend->pricing_category_id,
                'rule_type' => $bridgeWeekend->rule_type,
                'conditions' => $bridgeWeekend->conditions,
                'priority' => $bridgeWeekend->priority,
            ]);

            $bridgeWeekend->delete();
        }
    }
};
