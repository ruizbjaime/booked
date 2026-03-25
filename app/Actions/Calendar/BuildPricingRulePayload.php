<?php

namespace App\Actions\Calendar;

use App\Domain\Calendar\Enums\PricingRuleType;
use App\Domain\Calendar\PricingRuleConditionSchemaRegistry;
use App\Models\PricingRule;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BuildPricingRulePayload
{
    public function __construct(
        private readonly PricingRuleConditionSchemaRegistry $schemaRegistry = new PricingRuleConditionSchemaRegistry,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @return array{
     *     name: string,
     *     en_description: string,
     *     es_description: string,
     *     pricing_category_id: int,
     *     rule_type: string,
     *     conditions: array<string, mixed>,
     *     priority: int,
     *     is_active: bool
     * }
     */
    public function handle(array $input, ?PricingRule $existingRule = null): array
    {
        $normalized = $this->normalize($input);
        $schema = $this->schemaRegistry->for($normalized['rule_type']);
        $normalized['conditions'] = $schema->normalize($normalized);

        Validator::make(
            $normalized,
            array_merge($this->baseRules($existingRule), $schema->rules($normalized)),
        )->after(function (ValidatorContract $validator) use ($normalized, $existingRule): void {
            $this->validateSeasonDaysConfiguration($validator, $normalized);
            $this->validateFallbackConstraints($validator, $normalized, $existingRule);
            $this->validateActivePriorityUniqueness($validator, $normalized, $existingRule);
        })->validate();

        return [
            'name' => $normalized['name'],
            'en_description' => $normalized['en_description'],
            'es_description' => $normalized['es_description'],
            'pricing_category_id' => $normalized['pricing_category_id'],
            'rule_type' => $normalized['rule_type'],
            'conditions' => $normalized['conditions'],
            'priority' => $normalized['priority'],
            'is_active' => $normalized['is_active'],
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{
     *     name: string,
     *     en_description: string,
     *     es_description: string,
     *     pricing_category_id: int,
     *     rule_type: string,
     *     priority: int,
     *     is_active: bool,
     *     season_mode: string,
     *     season: ?string,
     *     day_of_week: list<mixed>,
     *     only_last_n_days: mixed,
     *     exclude_last_n_days: mixed,
     *     recurring_dates: list<mixed>,
     *     is_bridge_weekend: bool,
     *     is_first_bridge_day: bool,
     *     outside_season: bool,
     *     not_bridge: bool,
     *     fallback: bool,
     *     conditions: array<string, mixed>
     * }
     */
    private function normalize(array $input): array
    {
        $rawCategoryId = $input['pricing_category_id'] ?? null;
        $rawPriority = $input['priority'] ?? null;

        return [
            'name' => is_string($input['name'] ?? null) ? Str::lower(trim($input['name'])) : '',
            'en_description' => is_string($input['en_description'] ?? null) ? trim($input['en_description']) : '',
            'es_description' => is_string($input['es_description'] ?? null) ? trim($input['es_description']) : '',
            'pricing_category_id' => is_numeric($rawCategoryId) ? (int) $rawCategoryId : 0,
            'rule_type' => is_string($input['rule_type'] ?? null) ? trim($input['rule_type']) : '',
            'priority' => is_numeric($rawPriority) ? (int) $rawPriority : 0,
            'is_active' => filter_var($input['is_active'] ?? false, FILTER_VALIDATE_BOOL),
            'season_mode' => is_string($input['season_mode'] ?? null) ? trim($input['season_mode']) : 'season',
            'season' => is_string($input['season'] ?? null) ? trim($input['season']) : null,
            'day_of_week' => is_array($input['day_of_week'] ?? null) ? array_values($input['day_of_week']) : [],
            'only_last_n_days' => $input['only_last_n_days'] ?? null,
            'exclude_last_n_days' => $input['exclude_last_n_days'] ?? null,
            'recurring_dates' => is_array($input['recurring_dates'] ?? null) ? array_values($input['recurring_dates']) : [],
            'is_bridge_weekend' => filter_var($input['is_bridge_weekend'] ?? true, FILTER_VALIDATE_BOOL),
            'is_first_bridge_day' => filter_var($input['is_first_bridge_day'] ?? false, FILTER_VALIDATE_BOOL),
            'outside_season' => filter_var($input['outside_season'] ?? false, FILTER_VALIDATE_BOOL),
            'not_bridge' => filter_var($input['not_bridge'] ?? false, FILTER_VALIDATE_BOOL),
            'fallback' => filter_var($input['fallback'] ?? true, FILTER_VALIDATE_BOOL),
            'conditions' => [],
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function baseRules(?PricingRule $existingRule): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9_]*$/', Rule::unique('pricing_rules', 'name')->ignore($existingRule?->id)],
            'en_description' => ['required', 'string', 'max:500'],
            'es_description' => ['required', 'string', 'max:500'],
            'pricing_category_id' => ['required', 'integer', Rule::exists('pricing_categories', 'id')->where(fn (QueryBuilder $query) => $query->where('is_active', true))],
            'rule_type' => ['required', 'string', Rule::enum(PricingRuleType::class)],
            'priority' => ['required', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function validateSeasonDaysConfiguration(ValidatorContract $validator, array $input): void
    {
        if (($input['rule_type'] ?? null) !== PricingRuleType::SeasonDays->value) {
            return;
        }

        /** @var array<string, mixed> $conditions */
        $conditions = is_array($input['conditions'] ?? null) ? $input['conditions'] : [];

        $hasSeason = isset($conditions['season']);
        $hasDates = isset($conditions['dates']) && is_array($conditions['dates']) && $conditions['dates'] !== [];

        if ($hasSeason === $hasDates) {
            $validator->errors()->add('season_mode', __('calendar.settings.validation.season_or_dates'));
        }

        if ($hasSeason && isset($conditions['only_last_n_days'], $conditions['exclude_last_n_days'])) {
            $validator->errors()->add('only_last_n_days', __('calendar.settings.validation.last_day_filters_conflict'));
        }
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function validateActivePriorityUniqueness(ValidatorContract $validator, array $input, ?PricingRule $existingRule): void
    {
        if (! ($input['is_active'] ?? false)) {
            return;
        }

        $duplicatePriorityExists = PricingRule::query()
            ->where('is_active', true)
            ->when($existingRule !== null, fn ($query) => $query->whereKeyNot($existingRule?->id))
            ->where('priority', $input['priority'])
            ->exists();

        if ($duplicatePriorityExists) {
            $validator->errors()->add('priority', __('calendar.settings.validation.unique_active_priority'));
        }
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function validateFallbackConstraints(ValidatorContract $validator, array $input, ?PricingRule $existingRule): void
    {
        $projectedType = $input['rule_type'];
        $projectedIsActive = (bool) $input['is_active'];

        $otherRules = PricingRule::query()
            ->when($existingRule !== null, fn ($query) => $query->whereKeyNot($existingRule?->id))
            ->get(['id', 'rule_type', 'priority', 'is_active']);

        $activeFallbacks = $otherRules
            ->filter(fn (PricingRule $rule): bool => $rule->is_active && $rule->rule_type === PricingRuleType::EconomyDefault)
            ->count();

        if ($projectedIsActive && $projectedType === PricingRuleType::EconomyDefault->value) {
            $activeFallbacks++;
        }

        if ($activeFallbacks !== 1) {
            $validator->errors()->add('rule_type', __('calendar.settings.validation.single_active_fallback'));
        }

        $fallbackPriority = $otherRules
            ->filter(fn (PricingRule $rule): bool => $rule->is_active && $rule->rule_type === PricingRuleType::EconomyDefault)
            ->max('priority');

        if ($projectedIsActive && $projectedType === PricingRuleType::EconomyDefault->value) {
            $fallbackPriority = $input['priority'];
        }

        if ($fallbackPriority === null) {
            return;
        }

        $highestNonFallbackPriority = $otherRules
            ->filter(fn (PricingRule $rule): bool => $rule->is_active && $rule->rule_type !== PricingRuleType::EconomyDefault)
            ->max('priority');

        if ($projectedIsActive && $projectedType !== PricingRuleType::EconomyDefault->value) {
            $highestNonFallbackPriority = max($highestNonFallbackPriority ?? 0, $input['priority']);
        }

        if ($fallbackPriority <= ($highestNonFallbackPriority ?? -1)) {
            $validator->errors()->add('priority', __('calendar.settings.validation.fallback_must_be_last'));
        }
    }
}
