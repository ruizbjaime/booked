<?php

namespace App\Actions\Calendar;

use App\Models\PricingCategory;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class CreatePricingCategory
{
    public function __construct(
        private readonly BuildPricingCategoryPayload $buildPricingCategoryPayload = new BuildPricingCategoryPayload,
        private readonly ResolveCalendarFreshnessTimestamp $freshnessTimestamp = new ResolveCalendarFreshnessTimestamp,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function handle(User $actor, array $input): PricingCategory
    {
        Gate::forUser($actor)->authorize('create', PricingCategory::class);

        $pricingCategory = PricingCategory::create($this->buildPricingCategoryPayload->handle($input));

        $this->freshnessTimestamp->stampModel($pricingCategory, ['created_at', 'updated_at']);

        return $pricingCategory->refresh();
    }
}
