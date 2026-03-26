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

            $ruleId = $rule->id;
            $fallbackIds = [];
            $orderedIds = [];

            foreach ($rules as $pricingRule) {
                if ($pricingRule->rule_type === PricingRuleType::EconomyDefault) {
                    $fallbackIds[] = $pricingRule->id;

                    continue;
                }

                if ($pricingRule->id !== $ruleId) {
                    $orderedIds[] = $pricingRule->id;
                }
            }

            if ($rule->rule_type !== PricingRuleType::EconomyDefault) {
                $newPosition = max(0, min($newPosition, count($orderedIds)));
                array_splice($orderedIds, $newPosition, 0, [$ruleId]);
            }

            $orderedIds = [...$orderedIds, ...$fallbackIds];

            foreach ($orderedIds as $position => $id) {
                PricingRule::query()
                    ->whereKey($id)
                    ->update(['priority' => $position + 1]);
            }
        });
    }
}
