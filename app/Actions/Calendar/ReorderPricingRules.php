<?php

namespace App\Actions\Calendar;

use App\Domain\Calendar\Enums\PricingRuleType;
use App\Models\PricingRule;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ReorderPricingRules
{
    public function handle(User $actor, PricingRule $rule, int $newPosition): void
    {
        Gate::forUser($actor)->authorize('update', $rule);

        DB::transaction(function () use ($rule, $newPosition): void {
            $rules = PricingRule::query()
                ->orderBy('priority')
                ->orderBy('id')
                ->get(['id', 'rule_type']);

            $orderedIds = $rules
                ->reject(fn (PricingRule $pricingRule) => $pricingRule->id === $rule->id || $pricingRule->rule_type === PricingRuleType::EconomyDefault)
                ->map(static fn (PricingRule $pricingRule): int => $pricingRule->id)
                ->values()
                ->all();

            $fallbackIds = $rules
                ->filter(fn (PricingRule $pricingRule): bool => $pricingRule->rule_type === PricingRuleType::EconomyDefault)
                ->map(static fn (PricingRule $pricingRule): int => $pricingRule->id)
                ->values()
                ->all();

            if ($rule->rule_type !== PricingRuleType::EconomyDefault) {
                $newPosition = max(0, min($newPosition, count($orderedIds)));
                array_splice($orderedIds, $newPosition, 0, [$rule->id]);
            }

            $this->updatePriorities(array_merge($orderedIds, $fallbackIds));
        });
    }

    /**
     * @param  list<int>  $orderedIds
     */
    private function updatePriorities(array $orderedIds): void
    {
        foreach ($orderedIds as $position => $id) {
            PricingRule::query()
                ->whereKey($id)
                ->update(['priority' => $position + 1]);
        }
    }
}
