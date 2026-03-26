<?php

use App\Models\PricingRule;
use App\Models\SeasonBlock;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $seasonBlocks = SeasonBlock::query()->pluck('id', 'name');

        PricingRule::query()
            ->where('rule_type', 'season_days')
            ->lazyById()
            ->each(function (PricingRule $pricingRule) use ($seasonBlocks): void {
                $conditions = $pricingRule->conditions;

                if (! is_array($conditions) || isset($conditions['season_block_id'])) {
                    return;
                }

                $seasonName = $conditions['season'] ?? null;

                if (! is_string($seasonName)) {
                    return;
                }

                $seasonBlockId = $seasonBlocks->get($seasonName);

                if (! is_numeric($seasonBlockId)) {
                    return;
                }

                $conditions['season_block_id'] = (int) $seasonBlockId;
                unset($conditions['season']);

                $pricingRule->forceFill(['conditions' => $conditions])->save();
            });
    }

    public function down(): void
    {
        $seasonBlocks = SeasonBlock::query()->pluck('name', 'id');

        PricingRule::query()
            ->where('rule_type', 'season_days')
            ->lazyById()
            ->each(function (PricingRule $pricingRule) use ($seasonBlocks): void {
                $conditions = $pricingRule->conditions;

                if (! is_array($conditions) || ! isset($conditions['season_block_id'])) {
                    return;
                }

                $seasonBlockId = $conditions['season_block_id'];

                if (! is_int($seasonBlockId) && ! is_numeric($seasonBlockId)) {
                    return;
                }

                $seasonName = $seasonBlocks->get((int) $seasonBlockId);

                if (! is_string($seasonName)) {
                    return;
                }

                $conditions['season'] = $seasonName;
                unset($conditions['season_block_id']);

                $pricingRule->forceFill(['conditions' => $conditions])->save();
            });
    }
};
