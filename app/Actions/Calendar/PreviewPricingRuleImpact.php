<?php

namespace App\Actions\Calendar;

use App\Domain\Calendar\Data\PricingRuleData;
use App\Domain\Calendar\Data\PricingRuleImpactPreviewData;
use App\Domain\Calendar\Data\PricingRulePreviewSample;
use App\Domain\Calendar\Enums\PricingRuleType;
use App\Models\PricingCategory;
use App\Models\PricingRule;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class PreviewPricingRuleImpact
{
    public function __construct(
        private readonly AnalyzeCalendarRange $analyzeCalendarRange = new AnalyzeCalendarRange,
        private readonly BuildPricingRulePayload $buildPricingRulePayload = new BuildPricingRulePayload,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function handle(
        User $actor,
        array $input,
        ?PricingRule $existingRule = null,
        ?CarbonImmutable $from = null,
        ?CarbonImmutable $to = null,
    ): PricingRuleImpactPreviewData {
        if ($existingRule !== null) {
            Gate::forUser($actor)->authorize('update', $existingRule);
        } else {
            Gate::forUser($actor)->authorize('create', PricingRule::class);
        }

        $payload = $this->buildPricingRulePayload->handle($input, $existingRule);
        $previewFrom = $from ?? CarbonImmutable::now()->startOfYear();
        $previewTo = $to ?? CarbonImmutable::now()->addYear()->endOfYear();

        $baselineAnalysis = $this->analyzeCalendarRange->handle($previewFrom, $previewTo);
        $projectedRules = $this->projectedRules($payload, $existingRule);
        $projectedAnalysis = $this->analyzeCalendarRange->handle($previewFrom, $previewTo, $projectedRules);

        /** @var array<int, string> $categoryLabels */
        $categoryLabels = PricingCategory::query()
            ->pluck(PricingCategory::localizedNameColumn(), 'id')
            ->all();

        $changesByCategory = [];
        $sampleDates = [];
        $affectedCount = 0;

        foreach ($baselineAnalysis as $index => $baselineDay) {
            $projectedDay = $projectedAnalysis[$index];

            if ($baselineDay->pricingCategoryId === $projectedDay->pricingCategoryId) {
                continue;
            }

            $affectedCount++;

            $fromCategory = $categoryLabels[$baselineDay->pricingCategoryId] ?? __('calendar.settings.preview.unassigned');
            $toCategory = $categoryLabels[$projectedDay->pricingCategoryId] ?? __('calendar.settings.preview.unassigned');
            $transition = "{$fromCategory} → {$toCategory}";

            $changesByCategory[$transition] = ($changesByCategory[$transition] ?? 0) + 1;

            if (count($sampleDates) < 8) {
                $sampleDates[] = new PricingRulePreviewSample(
                    date: $baselineDay->date,
                    fromCategory: $fromCategory,
                    toCategory: $toCategory,
                );
            }
        }

        return new PricingRuleImpactPreviewData(
            affectedCount: $affectedCount,
            changesByCategory: $changesByCategory,
            sampleDates: $sampleDates,
            warnings: $this->warningsFor($affectedCount, $payload, $projectedRules, $existingRule),
        );
    }

    /**
     * @param  array{
     *     name: string,
     *     en_description: string,
     *     es_description: string,
     *     pricing_category_id: int,
     *     rule_type: string,
     *     conditions: array<string, mixed>,
     *     priority: int,
     *     is_active: bool
     * }  $payload
     * @return list<PricingRuleData>
     */
    private function projectedRules(array $payload, ?PricingRule $existingRule): array
    {
        $activeRules = PricingRule::query()
            ->whereHas('pricingCategory', fn (Builder $query) => $query->where('is_active', true))
            ->with('pricingCategory:id,level')
            ->when($existingRule !== null, fn (Builder $query) => $query->whereKeyNot($existingRule?->id))
            ->get()
            ->filter(fn (PricingRule $rule): bool => $rule->is_active)
            ->map(fn (PricingRule $rule) => new PricingRuleData(
                id: $rule->id,
                name: $rule->name,
                pricingCategoryId: $rule->pricing_category_id,
                pricingCategoryLevel: $rule->pricingCategory->level ?? 0,
                ruleType: $rule->rule_type,
                conditions: $rule->conditions,
                priority: $rule->priority,
            ))
            ->values()
            ->all();

        if ($payload['is_active']) {
            $rawLevel = PricingCategory::query()
                ->whereKey($payload['pricing_category_id'])
                ->value('level');

            $activeRules[] = new PricingRuleData(
                id: $existingRule->id ?? 0,
                name: $payload['name'],
                pricingCategoryId: $payload['pricing_category_id'],
                pricingCategoryLevel: is_numeric($rawLevel) ? (int) $rawLevel : 0,
                ruleType: PricingRuleType::from($payload['rule_type']),
                conditions: $payload['conditions'],
                priority: $payload['priority'],
            );
        }

        usort($activeRules, fn (PricingRuleData $left, PricingRuleData $right): int => $left->priority <=> $right->priority);

        return $activeRules;
    }

    /**
     * @param  array{
     *     name: string,
     *     rule_type: string,
     *     priority: int
     * }  $payload
     * @param  list<PricingRuleData>  $projectedRules
     * @return list<string>
     */
    private function warningsFor(int $affectedCount, array $payload, array $projectedRules, ?PricingRule $existingRule): array
    {
        $warnings = [];

        if ($affectedCount === 0) {
            $warnings[] = __('calendar.settings.preview.no_changes_warning');
        }

        $hasHigherPriorityRules = collect($projectedRules)
            ->filter(fn (PricingRuleData $rule): bool => $rule->id !== ($existingRule->id ?? 0))
            ->contains(fn (PricingRuleData $rule): bool => $rule->priority < $payload['priority']);

        if ($affectedCount === 0 && $hasHigherPriorityRules) {
            $warnings[] = __('calendar.settings.preview.priority_overlap_warning');
        }

        return $warnings;
    }
}
