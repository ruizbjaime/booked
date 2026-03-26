<?php

use App\Models\CalendarDay;
use App\Models\PricingRule;
use App\Models\SeasonBlock;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $yearEndId = SeasonBlock::query()
            ->where('name', 'year_end')
            ->value('id');

        if (is_int($yearEndId)) {
            CalendarDay::query()
                ->where('season_block_id', $yearEndId)
                ->orWhere('season_block_name', 'year_end')
                ->update([
                    'season_block_id' => null,
                    'season_block_name' => null,
                ]);

            PricingRule::query()
                ->get()
                ->filter(function (PricingRule $rule) use ($yearEndId): bool {
                    $conditions = $rule->conditions;

                    return is_array($conditions)
                        && ($conditions['season_block_id'] ?? null) === $yearEndId;
                })
                ->each(fn (PricingRule $rule) => $rule->delete());

            SeasonBlock::query()
                ->whereKey($yearEndId)
                ->delete();
        }

        SeasonBlock::query()
            ->where('name', 'december_season')
            ->update([
                'priority' => 2,
                'sort_order' => 2,
            ]);

        SeasonBlock::query()
            ->where('name', 'october_recess')
            ->update([
                'priority' => 3,
                'sort_order' => 3,
            ]);
    }
};
