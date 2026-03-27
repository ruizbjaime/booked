<?php

namespace App\Livewire\Calendar;

use App\Actions\Calendar\CreatePricingRule;
use App\Actions\Calendar\DuplicatePricingRule;
use App\Actions\Calendar\EditPricingRule;
use App\Actions\Calendar\PreviewPricingRuleImpact;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Concerns\ThrottlesFormActions;
use App\Domain\Calendar\Data\PricingRuleImpactPreviewData;
use App\Domain\Calendar\Data\PricingRulePreviewSample;
use App\Domain\Calendar\Enums\PricingRuleType;
use App\Infrastructure\UiFeedback\ToastService;
use App\Models\PricingCategory;
use App\Models\PricingRule;
use App\Models\SeasonBlock;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class PricingRuleForm extends Component
{
    use ResolvesAuthenticatedUser;
    use ThrottlesFormActions;

    private const string THROTTLE_KEY_PREFIX = 'pricing-rule-form';

    public string $mode = 'create';

    public ?int $pricingRuleId = null;

    public string $name = '';

    public string $en_description = '';

    public string $es_description = '';

    public int $pricing_category_id = 0;

    public string $rule_type = 'season_days';

    public int $priority = 0;

    public bool $is_active = true;

    public string $season_mode = 'season';

    public ?int $season_block_id = null;

    /**
     * @var list<string>
     */
    public array $day_of_week = [];

    public ?int $only_last_n_days = null;

    public ?int $exclude_last_n_days = null;

    /**
     * @var list<string>
     */
    public array $recurring_dates = [];

    public string $recurring_month = '';

    public string $recurring_day = '';

    public bool $is_bridge_weekend = true;

    public bool $is_first_bridge_day = false;

    public ?float $min_impact = null;

    public ?float $max_impact = null;

    public bool $outside_season = true;

    public bool $not_bridge = true;

    /**
     * @var array{
     *     affectedCount?: int,
     *     changesByCategory?: array<string, int>,
     *     sampleDates?: list<array{date: string, fromCategory: string, toCategory: string}>,
     *     warnings?: list<string>
     * }
     */
    public array $preview = [];

    /**
     * @var array<string, mixed>
     */
    #[Locked]
    public array $context = [];

    /**
     * @param  array<string, mixed>  $context
     */
    public function mount(array $context = []): void
    {
        $this->context = $context;
        $rawMode = $context['mode'] ?? 'create';
        $this->mode = is_string($rawMode) && in_array($rawMode, ['create', 'edit', 'duplicate'], true)
            ? $rawMode
            : 'create';
        $this->pricingRuleId = is_numeric($context['pricingRuleId'] ?? null) ? (int) $context['pricingRuleId'] : null;

        match ($this->mode) {
            'edit' => $this->bootEditMode(),
            'duplicate' => $this->bootDuplicateMode(),
            default => $this->bootCreateMode(),
        };
    }

    public function updatedRuleType(string $value): void
    {
        $this->resetValidation();
        $this->resetConditionFieldsForRuleType($value);
        $this->preview = [];
    }

    public function updatedDayOfWeek(): void
    {
        $this->day_of_week = $this->normalizedDays($this->day_of_week);
        $this->resetValidation('day_of_week');
        $this->preview = [];
    }

    public function updatedRecurringDates(): void
    {
        $this->recurring_dates = $this->normalizeRecurringDates($this->recurring_dates);
        $this->resetValidation('recurring_dates');
        $this->preview = [];
    }

    public function updated(string $property): void
    {
        if ($property === 'rule_type') {
            return;
        }

        if (str_starts_with($property, 'preview')) {
            return;
        }

        $this->resetValidation($property);
        $this->preview = [];
    }

    public function addRecurringDate(): void
    {
        $this->validate([
            'recurring_month' => ['required', 'integer', 'between:1,12'],
            'recurring_day' => ['required', 'integer', 'between:1,31'],
        ]);

        $formatted = sprintf('%02d-%02d', (int) $this->recurring_month, (int) $this->recurring_day);

        if (! in_array($formatted, $this->recurring_dates, true)) {
            $this->recurring_dates[] = $formatted;
            $this->recurring_dates = $this->normalizeRecurringDates($this->recurring_dates);
        }

        $this->recurring_month = '';
        $this->recurring_day = '';
        $this->resetValidation(['recurring_month', 'recurring_day', 'recurring_dates']);
        $this->preview = [];
    }

    public function removeRecurringDate(string $date): void
    {
        $this->recurring_dates = array_values(array_filter(
            $this->recurring_dates,
            fn (string $candidate): bool => $candidate !== $date,
        ));

        $this->resetValidation('recurring_dates');
        $this->preview = [];
    }

    public function runPreview(PreviewPricingRuleImpact $previewPricingRuleImpact): void
    {
        if ($this->throttle('preview', 12)) {
            return;
        }

        $this->preview = $this->previewAsArray(
            $previewPricingRuleImpact->handle(
                $this->actor(),
                $this->payload(),
                $this->editingRule(),
                $this->previewFrom(),
                $this->previewTo(),
            ),
        );
    }

    public function save(
        CreatePricingRule $createPricingRule,
        EditPricingRule $editPricingRule,
        DuplicatePricingRule $duplicatePricingRule,
    ): void {
        if ($this->throttle('create', 5)) {
            return;
        }

        $rule = match ($this->mode) {
            'edit' => $editPricingRule->handle($this->actor(), $this->rule(), $this->payload()),
            'duplicate' => $duplicatePricingRule->handle($this->actor(), $this->rule(), $this->payload()),
            default => $createPricingRule->handle($this->actor(), $this->payload()),
        };

        $messageKey = match ($this->mode) {
            'edit' => 'calendar.settings.rule_form.updated',
            'duplicate' => 'calendar.settings.rule_form.duplicated',
            default => 'calendar.settings.rule_form.created',
        };

        ToastService::success(__($messageKey, [
            'rule' => $this->ruleLabel($rule),
        ]));

        $this->dispatch('close-form-modal');
        $this->dispatch('pricing-rule-saved', pricingRuleId: $rule->id);
    }

    /**
     * @return list<array{id: int, label: string}>
     */
    #[Computed]
    public function availablePricingCategories(): array
    {
        return array_values(PricingCategory::query()
            ->active()
            ->orderBy('sort_order')
            ->get()
            ->map(fn (PricingCategory $category): array => [
                'id' => $category->id,
                'label' => "{$category->localizedName()} ({$category->multiplier}x)",
            ])
            ->all());
    }

    /**
     * @return list<array{id: int, label: string}>
     */
    #[Computed]
    public function availableSeasonBlocks(): array
    {
        return array_values(SeasonBlock::query()
            ->where(function ($query): void {
                $query->where('is_active', true);

                if ($this->season_block_id !== null) {
                    $query->orWhere('id', $this->season_block_id);
                }
            })
            ->orderBy('sort_order')
            ->get()
            ->map(fn (SeasonBlock $seasonBlock): array => [
                'id' => $seasonBlock->id,
                'label' => $seasonBlock->localizedName(),
            ])
            ->all());
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    #[Computed]
    public function dayOptions(): array
    {
        return array_map(
            fn (string $day): array => [
                'value' => $day,
                'label' => __('calendar.days_of_week.'.$day),
            ],
            [
                'monday',
                'tuesday',
                'wednesday',
                'thursday',
                'friday',
                'saturday',
                'sunday',
            ],
        );
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    #[Computed]
    public function ruleTypeOptions(): array
    {
        return array_map(
            fn (PricingRuleType $type): array => [
                'value' => $type->value,
                'label' => __('calendar.rule_types.'.$type->value),
            ],
            PricingRuleType::cases(),
        );
    }

    #[Computed]
    public function previewRangeFrom(): string
    {
        return $this->previewFrom()->toDateString();
    }

    #[Computed]
    public function previewRangeTo(): string
    {
        return $this->previewTo()->toDateString();
    }

    public function render(): View
    {
        return view('livewire.calendar.pricing-rule-form');
    }

    private function bootCreateMode(): void
    {
        Gate::authorize('create', PricingRule::class);

        $fallbackRule = PricingRule::query()
            ->where('rule_type', PricingRuleType::EconomyDefault)
            ->where('is_active', true)
            ->first();

        $this->priority = ($fallbackRule !== null ? $fallbackRule->priority : 100) - 1;
        $rawCategoryId = PricingCategory::query()->active()->orderBy('sort_order')->value('id');
        $this->pricing_category_id = is_numeric($rawCategoryId) ? (int) $rawCategoryId : 0;
    }

    private function bootEditMode(): void
    {
        $rule = $this->rule();

        Gate::authorize('update', $rule);

        $this->fillFromRule($rule);
    }

    private function bootDuplicateMode(): void
    {
        $rule = $this->rule();

        Gate::authorize('view', $rule);
        Gate::authorize('create', PricingRule::class);

        $this->fillFromRule($rule);
        $this->name = $this->duplicateName($rule->name);
    }

    private function fillFromRule(PricingRule $rule): void
    {
        $this->name = $rule->name;
        $this->en_description = $rule->en_description;
        $this->es_description = $rule->es_description;
        $this->pricing_category_id = $rule->pricing_category_id;
        $this->rule_type = $rule->rule_type->value;
        $this->priority = $rule->priority;
        $this->is_active = $rule->is_active;

        $conditions = $rule->conditions;
        $this->day_of_week = $this->extractDaysOfWeek($conditions);

        if ($rule->rule_type === PricingRuleType::SeasonDays) {
            $this->season_mode = isset($conditions['dates']) ? 'dates' : 'season';
            $this->season_block_id = $this->resolveSeasonBlockIdFromConditions($conditions);
            $rawOnlyLast = $conditions['only_last_n_days'] ?? null;
            $this->only_last_n_days = is_int($rawOnlyLast) ? $rawOnlyLast : null;
            $rawExcludeLast = $conditions['exclude_last_n_days'] ?? null;
            $this->exclude_last_n_days = is_int($rawExcludeLast) ? $rawExcludeLast : null;
            $this->recurring_dates = $this->normalizeRecurringDates(is_array($conditions['dates'] ?? null) ? $conditions['dates'] : []);

            return;
        }

        if ($rule->rule_type === PricingRuleType::Holiday) {
            $this->min_impact = is_numeric($conditions['min_impact'] ?? null) ? (float) $conditions['min_impact'] : null;
            $this->max_impact = is_numeric($conditions['max_impact'] ?? null) ? (float) $conditions['max_impact'] : null;

            return;
        }

        if ($rule->rule_type === PricingRuleType::HolidayBridge) {
            $this->is_bridge_weekend = (bool) ($conditions['is_bridge_weekend'] ?? true);
            $this->is_first_bridge_day = (bool) ($conditions['is_first_bridge_day'] ?? false);
            $this->min_impact = is_numeric($conditions['min_impact'] ?? null) ? (float) $conditions['min_impact'] : null;
            $this->max_impact = is_numeric($conditions['max_impact'] ?? null) ? (float) $conditions['max_impact'] : null;

            return;
        }

        if ($rule->rule_type === PricingRuleType::NormalWeekend) {
            $this->outside_season = (bool) ($conditions['outside_season'] ?? false);
            $this->not_bridge = (bool) ($conditions['not_bridge'] ?? false);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'name' => $this->name,
            'en_description' => $this->en_description,
            'es_description' => $this->es_description,
            'pricing_category_id' => $this->pricing_category_id,
            'rule_type' => $this->rule_type,
            'priority' => $this->priority,
            'is_active' => $this->is_active,
            'season_mode' => $this->season_mode,
            'season_block_id' => $this->season_block_id,
            'day_of_week' => $this->day_of_week,
            'only_last_n_days' => $this->only_last_n_days,
            'exclude_last_n_days' => $this->exclude_last_n_days,
            'recurring_dates' => $this->recurring_dates,
            'is_bridge_weekend' => $this->is_bridge_weekend,
            'is_first_bridge_day' => $this->is_first_bridge_day,
            'min_impact' => $this->min_impact,
            'max_impact' => $this->max_impact,
            'outside_season' => $this->outside_season,
            'not_bridge' => $this->not_bridge,
            'fallback' => true,
        ];
    }

    private function rule(): PricingRule
    {
        abort_if($this->pricingRuleId === null, 404);

        return PricingRule::query()->findOrFail($this->pricingRuleId);
    }

    private function editingRule(): ?PricingRule
    {
        return $this->mode === 'edit' ? $this->rule() : null;
    }

    private function previewFrom(): CarbonImmutable
    {
        if (is_string($this->context['preview_from'] ?? null)) {
            return CarbonImmutable::parse($this->context['preview_from']);
        }

        return CarbonImmutable::now()->startOfYear();
    }

    private function previewTo(): CarbonImmutable
    {
        if (is_string($this->context['preview_to'] ?? null)) {
            return CarbonImmutable::parse($this->context['preview_to']);
        }

        return CarbonImmutable::now()->addYear()->endOfYear();
    }

    private function duplicateName(string $baseName): string
    {
        $candidate = "{$baseName}_copy";
        $suffix = 2;

        while (PricingRule::query()->where('name', $candidate)->exists()) {
            $candidate = "{$baseName}_copy_{$suffix}";
            $suffix++;
        }

        return $candidate;
    }

    /**
     * @param  array<string, mixed>  $conditions
     * @return list<string>
     */
    private function extractDaysOfWeek(array $conditions): array
    {
        $raw = $conditions['day_of_week'] ?? [];

        return $this->normalizedDays(is_array($raw) ? $raw : []);
    }

    /**
     * @param  array<int|string, mixed>  $days
     * @return list<string>
     */
    private function normalizedDays(array $days): array
    {
        return array_values(collect($days)
            ->filter(fn (mixed $day): bool => is_string($day) && $day !== '')
            ->map(fn (string $day): string => mb_strtolower(trim($day)))
            ->intersect(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])
            ->all());
    }

    /**
     * @param  array<int|string, mixed>  $dates
     * @return list<string>
     */
    private function normalizeRecurringDates(array $dates): array
    {
        return array_values(collect($dates)
            ->filter(fn (mixed $date): bool => is_string($date) && preg_match('/^\d{2}-\d{2}$/', $date) === 1)
            ->unique()
            ->sort()
            ->all());
    }

    /**
     * @return array{
     *     affectedCount: int,
     *     changesByCategory: array<string, int>,
     *     sampleDates: list<array{date: string, fromCategory: string, toCategory: string}>,
     *     warnings: list<string>
     * }
     */
    private function previewAsArray(PricingRuleImpactPreviewData $preview): array
    {
        return [
            'affectedCount' => $preview->affectedCount,
            'changesByCategory' => $preview->changesByCategory,
            'sampleDates' => array_map(
                fn (PricingRulePreviewSample $sample): array => [
                    'date' => $sample->date->toDateString(),
                    'fromCategory' => $sample->fromCategory,
                    'toCategory' => $sample->toCategory,
                ],
                $preview->sampleDates,
            ),
            'warnings' => $preview->warnings,
        ];
    }

    private function resetConditionFieldsForRuleType(string $ruleType): void
    {
        if ($ruleType !== PricingRuleType::SeasonDays->value) {
            $this->season_mode = 'season';
            $this->season_block_id = null;
            $this->only_last_n_days = null;
            $this->exclude_last_n_days = null;
            $this->recurring_dates = [];
        }

        if (! in_array($ruleType, [PricingRuleType::Holiday->value, PricingRuleType::HolidayBridge->value], true)) {
            $this->min_impact = null;
            $this->max_impact = null;
        }

        if ($ruleType === PricingRuleType::EconomyDefault->value) {
            $this->day_of_week = [];
        }

        $this->resetBooleanConditions();
    }

    private function resetBooleanConditions(): void
    {
        $this->is_bridge_weekend = true;
        $this->is_first_bridge_day = false;
        $this->outside_season = true;
        $this->not_bridge = true;
    }

    private function ruleLabel(PricingRule $rule): string
    {
        return __('calendar.settings.rule_label', [
            'name' => $rule->name,
            'id' => $rule->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $conditions
     */
    private function resolveSeasonBlockIdFromConditions(array $conditions): ?int
    {
        $seasonBlockId = $conditions['season_block_id'] ?? null;

        if (is_int($seasonBlockId)) {
            return $seasonBlockId;
        }

        if (is_numeric($seasonBlockId)) {
            return (int) $seasonBlockId;
        }

        $legacySeason = $conditions['season'] ?? null;

        if (! is_string($legacySeason) || $legacySeason === '') {
            return null;
        }

        $resolvedId = SeasonBlock::query()->where('name', $legacySeason)->value('id');

        return is_numeric($resolvedId) ? (int) $resolvedId : null;
    }
}
