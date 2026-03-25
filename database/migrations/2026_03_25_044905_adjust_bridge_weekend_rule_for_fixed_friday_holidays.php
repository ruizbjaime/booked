<?php

use App\Models\PricingRule;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $bridgeWeekend = PricingRule::query()->where('name', 'bridge_weekend')->first();

        if ($bridgeWeekend === null) {
            return;
        }

        $bridgeWeekend->update([
            'en_description' => 'Bridge days around holiday long weekends',
            'es_description' => 'Días puente alrededor de fines de semana festivos',
            'conditions' => ['is_bridge_weekend' => true, 'day_of_week' => ['thursday', 'friday', 'saturday', 'sunday']],
        ]);
    }

    public function down(): void
    {
        $bridgeWeekend = PricingRule::query()->where('name', 'bridge_weekend')->first();

        if ($bridgeWeekend === null) {
            return;
        }

        $bridgeWeekend->update([
            'en_description' => 'Bridge weekend Friday through Sunday around holiday Monday',
            'es_description' => 'Puente viernes a domingo alrededor de festivo lunes',
            'conditions' => ['is_bridge_weekend' => true, 'day_of_week' => ['friday', 'saturday', 'sunday']],
        ]);
    }
};
