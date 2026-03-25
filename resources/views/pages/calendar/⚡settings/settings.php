<?php

use App\Actions\Calendar\RecalculateCalendarAfterConfigChange;
use App\Actions\Calendar\UpdateHolidayDefinition;
use App\Actions\Calendar\UpdatePricingCategory;
use App\Actions\Calendar\UpdatePricingRule;
use App\Actions\Calendar\UpdateSeasonBlock;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Concerns\ThrottlesFormActions;
use App\Domain\Table\Column;
use App\Domain\Table\Columns\BadgeColumn;
use App\Domain\Table\Columns\EditableColorColumn;
use App\Domain\Table\Columns\EditableNumberColumn;
use App\Domain\Table\Columns\EditableSelectColumn;
use App\Domain\Table\Columns\EditableSwitchColumn;
use App\Domain\Table\Columns\EditableTextColumn;
use App\Domain\Table\Columns\TextColumn;
use App\Infrastructure\UiFeedback\ModalService;
use App\Infrastructure\UiFeedback\ToastService;
use App\Models\HolidayDefinition;
use App\Models\PricingCategory;
use App\Models\PricingRule;
use App\Models\SeasonBlock;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    use ResolvesAuthenticatedUser;
    use ThrottlesFormActions;

    private const string THROTTLE_KEY_PREFIX = 'calendar-settings';

    public function mount(): void
    {
        Gate::authorize('viewAny', HolidayDefinition::class);
    }

    /**
     * @return Collection<int, HolidayDefinition>
     */
    #[Computed]
    public function holidays(): Collection
    {
        return HolidayDefinition::query()->orderBy('sort_order')->get();
    }

    /**
     * @return Collection<int, SeasonBlock>
     */
    #[Computed]
    public function seasonBlocks(): Collection
    {
        return SeasonBlock::query()->orderBy('sort_order')->get();
    }

    /**
     * @return Collection<int, PricingCategory>
     */
    #[Computed]
    public function pricingCategories(): Collection
    {
        return PricingCategory::query()->orderBy('sort_order')->get();
    }

    /**
     * @return Collection<int, PricingRule>
     */
    #[Computed]
    public function pricingRules(): Collection
    {
        return PricingRule::query()->with('pricingCategory')->orderBy('priority')->get();
    }

    /**
     * @return array<int, string>
     */
    private function pricingCategoryOptions(): array
    {
        return PricingCategory::query()
            ->orderBy('sort_order')
            ->get()
            ->mapWithKeys(fn (PricingCategory $category): array => [$category->id => $category->localizedName()])
            ->all();
    }

    /**
     * @return list<Column>
     */
    #[Computed]
    public function holidayColumns(): array
    {
        return [
            BadgeColumn::make('name')->label(__('calendar.settings.fields.name')),
            EditableTextColumn::make('en_name')->label(__('calendar.settings.fields.en_name'))->wireChange('updateHoliday'),
            EditableTextColumn::make('es_name')->label(__('calendar.settings.fields.es_name'))->wireChange('updateHoliday'),
            TextColumn::make('group')->label(__('calendar.settings.fields.group'))->formatUsing(fn (mixed $_, HolidayDefinition $record) => __('calendar.holiday_groups.'.$record->group->value)),
            EditableNumberColumn::make('sort_order')->label(__('calendar.settings.fields.sort_order'))->wireChange('updateHoliday')->min(0)->max(9999)->inputClass('w-20'),
            EditableSwitchColumn::make('is_active')->label(__('calendar.settings.fields.is_active'))->wireChange('updateHoliday'),
        ];
    }

    /**
     * @return list<Column>
     */
    #[Computed]
    public function seasonBlockColumns(): array
    {
        return [
            BadgeColumn::make('name')->label(__('calendar.settings.fields.name')),
            EditableTextColumn::make('en_name')->label(__('calendar.settings.fields.en_name'))->wireChange('updateSeasonBlock'),
            EditableTextColumn::make('es_name')->label(__('calendar.settings.fields.es_name'))->wireChange('updateSeasonBlock'),
            TextColumn::make('calculation_strategy')->label(__('calendar.settings.fields.calculation_strategy'))->formatUsing(fn (mixed $_, SeasonBlock $record) => __('calendar.season_strategies.'.$record->calculation_strategy->value)),
            EditableNumberColumn::make('priority')->label(__('calendar.settings.fields.priority'))->wireChange('updateSeasonBlock')->min(0)->max(9999)->inputClass('w-20'),
            EditableSwitchColumn::make('is_active')->label(__('calendar.settings.fields.is_active'))->wireChange('updateSeasonBlock'),
        ];
    }

    /**
     * @return list<Column>
     */
    #[Computed]
    public function pricingCategoryColumns(): array
    {
        return [
            BadgeColumn::make('name')->label(__('calendar.settings.fields.name')),
            EditableTextColumn::make('en_name')->label(__('calendar.settings.fields.en_name'))->wireChange('updatePricingCategory'),
            EditableTextColumn::make('es_name')->label(__('calendar.settings.fields.es_name'))->wireChange('updatePricingCategory'),
            EditableNumberColumn::make('level')->label(__('calendar.settings.fields.level'))->wireChange('updatePricingCategory')->min(1)->max(10)->inputClass('w-16'),
            EditableColorColumn::make('color')->label(__('calendar.settings.fields.color'))->wireChange('updatePricingCategory'),
            EditableNumberColumn::make('multiplier')->label(__('calendar.settings.fields.multiplier'))->wireChange('updatePricingCategory')->min(0)->max(99)->step('0.01')->inputClass('w-20'),
            EditableSwitchColumn::make('is_active')->label(__('calendar.settings.fields.is_active'))->wireChange('updatePricingCategory'),
        ];
    }

    /**
     * @return list<Column>
     */
    #[Computed]
    public function pricingRuleColumns(): array
    {
        return [
            BadgeColumn::make('name')->label(__('calendar.settings.fields.name')),
            EditableTextColumn::make('en_description')->label(__('calendar.settings.fields.en_description'))->wireChange('updatePricingRule'),
            EditableTextColumn::make('es_description')->label(__('calendar.settings.fields.es_description'))->wireChange('updatePricingRule'),
            EditableSelectColumn::make('pricing_category_id')->label(__('calendar.settings.fields.pricing_category'))->wireChange('updatePricingRule')->options($this->pricingCategoryOptions()),
            TextColumn::make('rule_type')->label(__('calendar.settings.fields.rule_type'))->formatUsing(fn (mixed $_, PricingRule $record) => __('calendar.rule_types.'.$record->rule_type->value)),
            EditableNumberColumn::make('priority')->label(__('calendar.settings.fields.priority'))->wireChange('updatePricingRule')->min(0)->max(9999)->inputClass('w-20'),
            EditableSwitchColumn::make('is_active')->label(__('calendar.settings.fields.is_active'))->wireChange('updatePricingRule'),
        ];
    }

    public function updateHoliday(int $id, string $field, mixed $value, UpdateHolidayDefinition $action): void
    {
        if ($this->throttle('autosave')) {
            return;
        }

        $action->handle($this->actor(), HolidayDefinition::findOrFail($id), $field, $value);

        unset($this->holidays);
        ToastService::success(__('calendar.settings.saved'));
    }

    public function updateSeasonBlock(int $id, string $field, mixed $value, UpdateSeasonBlock $action): void
    {
        if ($this->throttle('autosave')) {
            return;
        }

        $action->handle($this->actor(), SeasonBlock::findOrFail($id), $field, $value);

        unset($this->seasonBlocks);
        ToastService::success(__('calendar.settings.saved'));
    }

    public function updatePricingCategory(int $id, string $field, mixed $value, UpdatePricingCategory $action): void
    {
        if ($this->throttle('autosave')) {
            return;
        }

        $action->handle($this->actor(), PricingCategory::findOrFail($id), $field, $value);

        unset($this->pricingCategories);
        ToastService::success(__('calendar.settings.saved'));
    }

    public function updatePricingRule(int $id, string $field, mixed $value, UpdatePricingRule $action): void
    {
        if ($this->throttle('autosave')) {
            return;
        }

        $action->handle($this->actor(), PricingRule::findOrFail($id), $field, $value);

        unset($this->pricingRules);
        ToastService::success(__('calendar.settings.saved'));
    }

    public function confirmRegenerate(): void
    {
        if ($this->throttle('regenerate', 5)) {
            return;
        }

        ModalService::confirm(
            $this,
            title: __('calendar.settings.regenerate.title'),
            message: __('calendar.settings.regenerate.message'),
            confirmLabel: __('calendar.settings.regenerate.confirm_label'),
        );
    }

    #[On('modal-confirmed')]
    public function regenerateCalendar(RecalculateCalendarAfterConfigChange $recalculate): void
    {
        if ($this->throttle('regenerate', 5)) {
            return;
        }

        $count = $recalculate->handle();

        ToastService::success(__('calendar.settings.regenerate.success', ['count' => $count]));
    }
};
