<?php

namespace App\Actions\Calendar;

use App\Models\CalendarDay;
use App\Models\HolidayDefinition;
use App\Models\PricingCategory;
use App\Models\PricingRule;
use App\Models\SeasonBlock;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

class ResolveCalendarFreshnessTimestamp
{
    public function forConfigurationChange(): CarbonImmutable
    {
        return $this->resolveAfter(
            CalendarDay::query()->max('updated_at'),
        );
    }

    public function forCalendarGeneration(): CarbonImmutable
    {
        $latestConfigUpdate = collect([
            HolidayDefinition::query()->max('updated_at'),
            SeasonBlock::query()->max('updated_at'),
            PricingCategory::query()->max('updated_at'),
            PricingRule::query()->max('updated_at'),
        ])->filter()->max();

        return $this->resolveAfter($latestConfigUpdate);
    }

    /**
     * @param  array<string>  $columns
     */
    public function stampModel(Model $model, array $columns = ['updated_at']): void
    {
        $timestamp = $this->forConfigurationChange();

        $model->timestamps = false;
        $model->forceFill(array_fill_keys($columns, $timestamp))->saveQuietly();
        $model->timestamps = true;
    }

    private function resolveAfter(mixed $latestUpdate): CarbonImmutable
    {
        $now = CarbonImmutable::now();

        if (! is_string($latestUpdate)) {
            return $now;
        }

        $afterLatest = CarbonImmutable::parse($latestUpdate)->addSecond();

        return $afterLatest->greaterThan($now) ? $afterLatest : $now;
    }
}
