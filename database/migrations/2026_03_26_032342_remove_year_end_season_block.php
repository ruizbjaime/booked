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

        if (! is_int($yearEndId)) {
            $this->normalizeSeasonBlockOrder();

            return;
        }

        CalendarDay::query()
            ->where('season_block_id', $yearEndId)
            ->orWhere('season_block_name', 'year_end')
            ->update([
                'season_block_id' => null,
                'season_block_name' => null,
            ]);

        PricingRule::query()
            ->get()
            ->filter(fn (PricingRule $rule): bool => $this->referencesSeasonBlock($rule, $yearEndId))
            ->each(fn (PricingRule $rule) => $rule->delete());

        SeasonBlock::query()
            ->whereKey($yearEndId)
            ->delete();

        $this->normalizeSeasonBlockOrder();
    }

    private function normalizeSeasonBlockOrder(): void
    {
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

    private function referencesSeasonBlock(PricingRule $rule, int $seasonBlockId): bool
    {
        $conditions = $rule->conditions;

        return is_array($conditions)
            && ($conditions['season_block_id'] ?? null) === $seasonBlockId;
    }
};
