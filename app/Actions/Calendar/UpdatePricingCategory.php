<?php

namespace App\Actions\Calendar;

use App\Models\PricingCategory;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class UpdatePricingCategory
{
    public function __construct(
        private readonly BuildPricingCategoryPayload $buildPricingCategoryPayload = new BuildPricingCategoryPayload,
        private readonly ResolveCalendarFreshnessTimestamp $freshnessTimestamp = new ResolveCalendarFreshnessTimestamp,
    ) {}

    public function handle(User $actor, PricingCategory $category, string $field, mixed $value): void
    {
        Gate::forUser($actor)->authorize('update', $category);

        $normalized = $this->buildPricingCategoryPayload->normalizeField($field, $value);
        $this->buildPricingCategoryPayload->validateField($category, $field, $normalized);

        $category->update([$field => $normalized]);

        $this->freshnessTimestamp->stampModel($category);
    }
}
