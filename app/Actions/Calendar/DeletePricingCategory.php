<?php

namespace App\Actions\Calendar;

use App\Models\PricingCategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DeletePricingCategory
{
    public function __construct(
        private readonly PricingCategoryHasReferences $pricingCategoryHasReferences = new PricingCategoryHasReferences,
        private readonly ResolveCalendarFreshnessTimestamp $freshnessTimestamp = new ResolveCalendarFreshnessTimestamp,
    ) {}

    /**
     * Delete the pricing category if it has no references, otherwise deactivate it.
     *
     * @return bool True if deleted, false if deactivated.
     */
    public function handle(User $actor, PricingCategory $pricingCategory): bool
    {
        Gate::forUser($actor)->authorize('delete', $pricingCategory);

        return DB::transaction(function () use ($pricingCategory): bool {
            $locked = PricingCategory::query()->lockForUpdate()->findOrFail($pricingCategory->id);

            if ($this->pricingCategoryHasReferences->handle($locked)) {
                $locked->forceFill(['is_active' => false])->save();
                $this->freshnessTimestamp->stampModel($locked);

                return false;
            }

            $locked->delete();

            $remainingCategory = PricingCategory::query()->latest('updated_at')->first();

            if ($remainingCategory !== null) {
                $this->freshnessTimestamp->stampModel($remainingCategory);
            } else {
                $this->freshnessTimestamp->markConfigurationChanged();
            }

            return true;
        });
    }
}
