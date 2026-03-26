<?php

namespace App\Actions\Calendar;

use App\Models\PricingCategory;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class EditPricingCategory
{
    public function __construct(
        private readonly BuildPricingCategoryPayload $buildPricingCategoryPayload = new BuildPricingCategoryPayload,
        private readonly ResolveCalendarFreshnessTimestamp $freshnessTimestamp = new ResolveCalendarFreshnessTimestamp,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function handle(User $actor, PricingCategory $pricingCategory, array $input): PricingCategory
    {
        Gate::forUser($actor)->authorize('update', $pricingCategory);

        $pricingCategory->update($this->buildPricingCategoryPayload->handle($input, $pricingCategory));

        $this->freshnessTimestamp->stampModel($pricingCategory);

        return $pricingCategory->refresh();
    }
}
