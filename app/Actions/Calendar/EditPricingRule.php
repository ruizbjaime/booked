<?php

namespace App\Actions\Calendar;

use App\Models\PricingRule;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class EditPricingRule
{
    public function __construct(
        private readonly BuildPricingRulePayload $buildPricingRulePayload = new BuildPricingRulePayload,
        private readonly ResolveCalendarFreshnessTimestamp $freshnessTimestamp = new ResolveCalendarFreshnessTimestamp,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function handle(User $actor, PricingRule $pricingRule, array $input): PricingRule
    {
        Gate::forUser($actor)->authorize('update', $pricingRule);

        $pricingRule->update($this->buildPricingRulePayload->handle($input, $pricingRule));

        $this->freshnessTimestamp->stampModel($pricingRule);

        return $pricingRule->refresh();
    }
}
