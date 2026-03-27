<?php

namespace App\Actions\Calendar;

use App\Models\CalendarDay;
use App\Models\HolidayDefinition;
use App\Models\PricingCategory;
use App\Models\PricingRule;
use App\Models\SeasonBlock;
use App\Models\SystemSetting;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

class ResolveCalendarFreshnessTimestamp
{
    public function forConfigurationChange(): CarbonImmutable
    {
        return $this->resolveAfter($this->latestCalendarUpdate());
    }

    public function forCalendarGeneration(): CarbonImmutable
    {
        return $this->resolveAfter($this->latestConfigurationUpdate());
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

        $this->persistConfigurationMarker($timestamp);
    }

    public function markConfigurationChanged(): CarbonImmutable
    {
        $timestamp = $this->forConfigurationChange();

        $this->persistConfigurationMarker($timestamp);

        return $timestamp;
    }

    public function latestConfigurationUpdate(): ?CarbonImmutable
    {
        return $this->parseTimestamp(collect([
            HolidayDefinition::query()->max('updated_at'),
            SeasonBlock::query()->max('updated_at'),
            PricingCategory::query()->max('updated_at'),
            PricingRule::query()->max('updated_at'),
            SystemSetting::query()->max('calendar_config_updated_at'),
        ])->filter()->max());
    }

    public function latestCalendarUpdate(): ?CarbonImmutable
    {
        return $this->parseTimestamp(CalendarDay::query()->max('updated_at'));
    }

    private function resolveAfter(CarbonImmutable|string|null $latestUpdate): CarbonImmutable
    {
        $now = CarbonImmutable::now();

        $latest = $latestUpdate instanceof CarbonImmutable
            ? $latestUpdate
            : $this->parseTimestamp($latestUpdate);

        if ($latest === null) {
            return $now;
        }

        $afterLatest = $latest->addSecond();

        return $afterLatest->greaterThan($now) ? $afterLatest : $now;
    }

    private function persistConfigurationMarker(CarbonImmutable $timestamp): void
    {
        $settings = SystemSetting::instance();

        SystemSetting::query()
            ->whereKey($settings->id)
            ->update(['calendar_config_updated_at' => $timestamp]);

        SystemSetting::clearCache();
    }

    private function parseTimestamp(mixed $timestamp): ?CarbonImmutable
    {
        return is_string($timestamp) ? CarbonImmutable::parse($timestamp) : null;
    }
}
