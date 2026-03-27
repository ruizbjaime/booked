<?php

namespace App\Actions\Calendar;

use App\Domain\Calendar\Enums\PricingRuleType;
use App\Models\PricingRule;
use App\Models\SeasonBlock;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class DeleteSeasonBlock
{
    public function __construct(
        private readonly ResolveCalendarFreshnessTimestamp $freshnessTimestamp = new ResolveCalendarFreshnessTimestamp,
    ) {}

    public function handle(User $actor, SeasonBlock $seasonBlock): void
    {
        Gate::forUser($actor)->authorize('delete', $seasonBlock);

        if (! $seasonBlock->isFixedRange()) {
            throw ValidationException::withMessages([
                'season_block' => __('calendar.settings.validation.cannot_delete_managed_season_block'),
            ]);
        }

        if ($this->hasReferencingPricingRule($seasonBlock)) {
            throw ValidationException::withMessages([
                'season_block' => __('calendar.settings.validation.cannot_delete_referenced_season_block'),
            ]);
        }

        $seasonBlock->delete();

        $this->stampLatestSeasonBlock();
    }

    private function hasReferencingPricingRule(SeasonBlock $seasonBlock): bool
    {
        return PricingRule::query()
            ->where('rule_type', PricingRuleType::SeasonDays)
            ->where(function ($query) use ($seasonBlock): void {
                $query->whereJsonContains('conditions->season_block_id', $seasonBlock->id)
                    ->orWhere('conditions->season', $seasonBlock->name);
            })
            ->exists();
    }

    private function stampLatestSeasonBlock(): void
    {
        $remainingBlock = SeasonBlock::query()->latest('updated_at')->first();

        if ($remainingBlock === null) {
            $this->freshnessTimestamp->markConfigurationChanged();

            return;
        }

        $this->freshnessTimestamp->stampModel($remainingBlock);
    }
}
