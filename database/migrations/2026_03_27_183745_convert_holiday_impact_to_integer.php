<?php

use App\Models\HolidayDefinition;
use App\Models\PricingRule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('calendar_days')
            ->whereNotNull('holiday_impact')
            ->update(['holiday_impact' => DB::raw('ROUND(holiday_impact)')]);

        Schema::table('calendar_days', function (Blueprint $table) {
            $table->unsignedTinyInteger('holiday_impact')->nullable()->change();
        });

        HolidayDefinition::query()->each(function (HolidayDefinition $hd): void {
            $weights = $hd->base_impact_weights;
            $rounded = array_map(
                fn (mixed $v): int|string => is_numeric($v) ? (int) round((float) $v) : $v,
                $weights,
            );

            $overrides = $hd->special_overrides;

            if (is_array($overrides)) {
                foreach ($overrides as &$override) {
                    if (isset($override['impact']) && is_numeric($override['impact'])) {
                        $override['impact'] = (int) round((float) $override['impact']);
                    }
                }
                unset($override);
            }

            $hd->update([
                'base_impact_weights' => $rounded,
                'special_overrides' => $overrides,
            ]);
        });

        PricingRule::query()->each(function (PricingRule $rule): void {
            $conditions = $rule->conditions;
            $changed = false;

            if (isset($conditions['min_impact']) && is_numeric($conditions['min_impact'])) {
                $conditions['min_impact'] = (int) round((float) $conditions['min_impact']);
                $changed = true;
            }

            // floor() preserves the "below threshold" semantic: 7.9 meant "< 8", so it becomes 7 (≤ 7).
            if (isset($conditions['max_impact']) && is_numeric($conditions['max_impact'])) {
                $conditions['max_impact'] = (int) floor((float) $conditions['max_impact']);
                $changed = true;
            }

            if ($changed) {
                $rule->update(['conditions' => $conditions]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('calendar_days', function (Blueprint $table) {
            $table->decimal('holiday_impact', 4, 1)->nullable()->change();
        });
    }
};
