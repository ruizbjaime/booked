<?php

namespace App\Actions\Calendar;

use App\Models\CalendarDay;
use App\Models\PricingCategory;
use App\Models\PricingRule;

class PricingCategoryHasReferences
{
    public function handle(PricingCategory $pricingCategory): bool
    {
        return PricingRule::query()->whereBelongsTo($pricingCategory)->exists()
            || CalendarDay::query()->whereBelongsTo($pricingCategory)->exists();
    }
}
