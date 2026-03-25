<?php

namespace App\Actions\Calendar;

use App\Models\PricingRule;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class DuplicatePricingRule
{
    public function __construct(
        private readonly BuildPricingRulePayload $buildPricingRulePayload = new BuildPricingRulePayload,
        private readonly ResolveCalendarFreshnessTimestamp $freshnessTimestamp = new ResolveCalendarFreshnessTimestamp,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function handle(User $actor, PricingRule $sourceRule, array $input): PricingRule
    {
        Gate::forUser($actor)->authorize('view', $sourceRule);
        Gate::forUser($actor)->authorize('create', PricingRule::class);

        $pricingRule = PricingRule::create($this->buildPricingRulePayload->handle($input));

        $this->freshnessTimestamp->stampModel($pricingRule, ['created_at', 'updated_at']);

        return $pricingRule->refresh();
    }
}
