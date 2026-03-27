<?php

namespace App\Actions\Calendar;

use App\Domain\Calendar\Enums\PricingRuleType;
use App\Models\PricingRule;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class DeletePricingRule
{
    public function __construct(
        private readonly ResolveCalendarFreshnessTimestamp $freshnessTimestamp = new ResolveCalendarFreshnessTimestamp,
    ) {}

    public function handle(User $actor, PricingRule $pricingRule): void
    {
        Gate::forUser($actor)->authorize('delete', $pricingRule);

        if ($pricingRule->is_active && $pricingRule->rule_type === PricingRuleType::EconomyDefault) {
            throw ValidationException::withMessages([
                'pricing_rule' => __('calendar.settings.validation.cannot_delete_active_fallback'),
            ]);
        }

        $pricingRule->delete();

        $remainingRule = PricingRule::query()->latest('updated_at')->first();

        if ($remainingRule !== null) {
            $this->freshnessTimestamp->stampModel($remainingRule);
        } else {
            $this->freshnessTimestamp->markConfigurationChanged();
        }
    }
}
