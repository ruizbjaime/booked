<?php

use App\Actions\Calendar\DeleteHolidayDefinition;
use App\Actions\Calendar\DeletePricingCategory;
use App\Actions\Calendar\DeletePricingRule;
use App\Actions\Calendar\DeleteSeasonBlock;
use App\Actions\Calendar\PricingCategoryHasReferences;
use App\Actions\Calendar\RecalculateCalendarAfterConfigChange;
use App\Actions\Calendar\ReorderPricingRules;
use App\Actions\Calendar\ResolveCalendarFreshnessTimestamp;
use App\Actions\Calendar\UpdateHolidayDefinition;
use App\Actions\Calendar\UpdatePricingCategory;
use App\Actions\Calendar\UpdatePricingRule;
use App\Actions\Calendar\UpdateSeasonBlock;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Concerns\ThrottlesFormActions;
use App\Domain\Calendar\Enums\PricingRuleType;
use App\Domain\Calendar\PricingRuleConditionSchemaRegistry;
use App\Domain\Table\ActionItem;
use App\Domain\Table\Column;
use App\Domain\Table\Columns\ActionsColumn;
use App\Domain\Table\Columns\BadgeColumn;
use App\Domain\Table\Columns\IdColumn;
use App\Domain\Table\Columns\TextColumn;
use App\Domain\Table\Columns\ToggleColumn;
use App\Infrastructure\UiFeedback\ModalService;
use App\Infrastructure\UiFeedback\ToastService;
use App\Models\CalendarDay;
use App\Models\HolidayDefinition;
use App\Models\PricingCategory;
use App\Models\PricingRule;
use App\Models\SeasonBlock;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use ResolvesAuthenticatedUser;
    use ThrottlesFormActions;
    use WithPagination;

    private const string THROTTLE_KEY_PREFIX = 'calendar-settings';

    public ?int $holidayDefinitionIdPendingDeletion = null;

    public ?int $pricingRuleIdPendingDeletion = null;

    public ?int $pricingCategoryIdPendingDeletion = null;

    public ?int $seasonBlockIdPendingDeletion = null;

    public bool $regenerationPendingConfirmation = false;

    public function mount(): void
    {
        abort_unless($this->canAccessSettings(), 403);
    }

    private const int PER_PAGE = 10;

    /**
     * @return LengthAwarePaginator<int, HolidayDefinition>
     */
    #[Computed]
    public function holidays(): LengthAwarePaginator
    {
        if (! $this->canViewHolidays()) {
            return new LengthAwarePaginator([], 0, self::PER_PAGE);
        }

        return HolidayDefinition::query()
            ->orderBy('sort_order')
            ->paginate(self::PER_PAGE, pageName: 'holidays');
    }

    /**
     * @return LengthAwarePaginator<int, SeasonBlock>
     */
    #[Computed]
    public function seasonBlocks(): LengthAwarePaginator
    {
        if (! $this->canViewSeasonBlocks()) {
            return new LengthAwarePaginator([], 0, self::PER_PAGE);
        }

        return SeasonBlock::query()
            ->orderBy('sort_order')
            ->paginate(self::PER_PAGE, pageName: 'seasons');
    }

    /**
     * @return LengthAwarePaginator<int, PricingCategory>
     */
    #[Computed]
    public function pricingCategories(): LengthAwarePaginator
    {
        if (! $this->canViewPricingCategories()) {
            return new LengthAwarePaginator([], 0, self::PER_PAGE);
        }

        return PricingCategory::query()
            ->orderBy('sort_order')
            ->paginate(self::PER_PAGE, pageName: 'categories');
    }

    /**
     * @return LengthAwarePaginator<int, PricingRule>
     */
    #[Computed]
    public function pricingRules(): LengthAwarePaginator
    {
        if (! $this->canViewPricingRules()) {
            return new LengthAwarePaginator([], 0, self::PER_PAGE);
        }

        return PricingRule::query()
            ->with('pricingCategory')
            ->orderBy('priority')
            ->paginate(self::PER_PAGE, pageName: 'rules');
    }

    #[Computed]
    public function canSortPricingRules(): bool
    {
        return $this->canUpdatePricingRules();
    }

    /**
     * @return list<Column>
     */
    #[Computed]
    public function holidayColumns(): array
    {
        if (! $this->canViewHolidays()) {
            return [];
        }

        $canUpdate = $this->canUpdateHolidays();

        $columns = [
            $this->idColumn(),
            $this->activeSwitchColumn(
                wireChange: 'updateHoliday',
                disabled: ! $canUpdate,
                idPrefix: 'holiday-active',
            ),
            BadgeColumn::make('name')->label(__('calendar.settings.fields.name')),
            TextColumn::make('en_name')->label(__('calendar.settings.fields.en_name')),
            TextColumn::make('es_name')->label(__('calendar.settings.fields.es_name')),
            TextColumn::make('group')->label(__('calendar.settings.fields.group'))->formatUsing(
                fn (mixed $_, HolidayDefinition $record) => __('calendar.holiday_groups.'.$record->group->value)
            ),
            TextColumn::make('sort_order')->label(__('calendar.settings.fields.sort_order')),
        ];

        $canDelete = $this->canDeleteHolidays();

        if (! $canUpdate && ! $canDelete) {
            return $columns;
        }

        $columns[] = ActionsColumn::make('actions')
            ->label(__('actions.actions'))
            ->actions(fn (HolidayDefinition $holiday) => [
                ...($canUpdate ? [
                    ActionItem::button(__('actions.edit'), 'openEditHolidayDefinitionModal', 'pencil-square'),
                ] : []),
                ...($canDelete ? [
                    ActionItem::separator(),
                    ActionItem::button(__('actions.delete'), 'confirmHolidayDefinitionDeletion', 'trash', 'danger'),
                ] : []),
            ]);

        return $columns;
    }

    /**
     * @return list<Column>
     */
    #[Computed]
    public function seasonBlockColumns(): array
    {
        if (! $this->canViewSeasonBlocks()) {
            return [];
        }

        $canUpdate = $this->canUpdateSeasonBlocks();

        $columns = [
            $this->idColumn(),
            $this->activeSwitchColumn(
                wireChange: 'updateSeasonBlock',
                disabled: ! $canUpdate,
                idPrefix: 'season-block-active',
            ),
            BadgeColumn::make('name')->label(__('calendar.settings.fields.name')),
            TextColumn::make('en_name')->label(__('calendar.settings.fields.en_name')),
            TextColumn::make('es_name')->label(__('calendar.settings.fields.es_name')),
            TextColumn::make('calculation_strategy')->label(__('calendar.settings.fields.calculation_strategy'))->formatUsing(fn (mixed $_, SeasonBlock $record) => __('calendar.season_strategies.'.$record->calculation_strategy->value)),
            TextColumn::make('fixed_start_month')->label(__('calendar.settings.fields.range'))->formatUsing(fn (mixed $_, SeasonBlock $record) => $record->fixedRangeLabel()),
            TextColumn::make('priority')->label(__('calendar.settings.fields.priority')),
            TextColumn::make('sort_order')->label(__('calendar.settings.fields.sort_order')),
        ];

        if (! $this->canManageSeasonBlocks()) {
            return $columns;
        }

        $canDelete = $this->canDeleteSeasonBlocks();

        $columns[] = ActionsColumn::make('actions')
            ->label(__('actions.actions'))
            ->actions(fn (SeasonBlock $seasonBlock) => [
                ...($canUpdate ? [
                    ActionItem::button(__('actions.edit'), 'openEditSeasonBlockModal', 'pencil-square'),
                ] : []),
                ...($canDelete && $seasonBlock->isFixedRange() ? [
                    ActionItem::separator(),
                    ActionItem::button(__('actions.delete'), 'confirmSeasonBlockDeletion', 'trash', 'danger'),
                ] : []),
            ]);

        return $columns;
    }

    /**
     * @return list<Column>
     */
    #[Computed]
    public function pricingCategoryColumns(): array
    {
        if (! $this->canViewPricingCategories()) {
            return [];
        }

        $canUpdate = $this->canUpdatePricingCategories();

        $columns = [
            $this->idColumn(),
            $this->activeSwitchColumn(
                wireChange: 'updatePricingCategory',
                disabled: ! $canUpdate,
                idPrefix: 'pricing-category-active',
            ),
            BadgeColumn::make('name')->label(__('calendar.settings.fields.name')),
            TextColumn::make('en_name')->label(__('calendar.settings.fields.en_name')),
            TextColumn::make('es_name')->label(__('calendar.settings.fields.es_name')),
            TextColumn::make('level')->label(__('calendar.settings.fields.level')),
            TextColumn::make('color')->label(__('calendar.settings.fields.color')),
            TextColumn::make('multiplier')->label(__('calendar.settings.fields.multiplier')),
        ];

        if (! $this->canManagePricingCategories()) {
            return $columns;
        }

        $canDelete = $this->canDeletePricingCategories();

        $columns[] = ActionsColumn::make('actions')
            ->label(__('actions.actions'))
            ->actions(fn (PricingCategory $pricingCategory) => [
                ...($canUpdate ? [
                    ActionItem::button(__('actions.edit'), 'openEditPricingCategoryModal', 'pencil-square'),
                ] : []),
                ...($canDelete ? [
                    ActionItem::separator(),
                    ActionItem::button(__('actions.delete'), 'confirmPricingCategoryDeletion', 'trash', 'danger'),
                ] : []),
            ]);

        return $columns;
    }

    /**
     * @return list<Column>
     */
    #[Computed]
    public function pricingRuleColumns(): array
    {
        if (! $this->canViewPricingRules()) {
            return [];
        }

        $canUpdate = $this->canUpdatePricingRules();
        $seasonBlocksForLookup = $this->allSeasonBlocksForLookup();

        $columns = [
            $this->idColumn(),
            $this->activeSwitchColumn(
                wireChange: 'updatePricingRule',
                disabled: ! $canUpdate,
                idPrefix: 'pricing-rule-active',
            ),
            BadgeColumn::make('name')->label(__('calendar.settings.fields.name')),
            TextColumn::make('pricing_category_id')
                ->label(__('calendar.settings.fields.pricing_category'))
                ->formatUsing(fn (mixed $_, PricingRule $record) => $record->pricingCategory?->localizedName() ?? '—'),
            TextColumn::make('rule_type')
                ->label(__('calendar.settings.fields.rule_type'))
                ->formatUsing(fn (mixed $_, PricingRule $record) => __('calendar.rule_types.'.$record->rule_type->value)),
            TextColumn::make('conditions')
                ->label(__('calendar.settings.fields.conditions'))
                ->formatUsing(fn (mixed $_, PricingRule $record) => $this->pricingRuleConditionSummary($record, $seasonBlocksForLookup)),
            TextColumn::make('priority')->label(__('calendar.settings.fields.priority')),
        ];

        if (! $this->canManagePricingRules()) {
            return $columns;
        }

        $canDelete = $this->canDeletePricingRules();
        $canCreate = $this->canCreatePricingRules();

        $columns[] = ActionsColumn::make('actions')
            ->label(__('actions.actions'))
            ->actions(fn (PricingRule $pricingRule) => [
                ...($canUpdate ? [
                    ActionItem::button(__('actions.edit'), 'openEditPricingRuleModal', 'pencil-square'),
                ] : []),
                ...($canCreate ? [
                    ActionItem::button(__('actions.duplicate'), 'openDuplicatePricingRuleModal', 'document-duplicate'),
                ] : []),
                ...($canDelete ? [
                    ActionItem::separator(),
                    ActionItem::button(__('actions.delete'), 'confirmPricingRuleDeletion', 'trash', 'danger'),
                ] : []),
            ]);

        return $columns;
    }

    public function updateHoliday(int $id, string $field, mixed $value, UpdateHolidayDefinition $action): void
    {
        if ($this->throttle('autosave')) {
            return;
        }

        $action->handle($this->actor(), HolidayDefinition::findOrFail($id), $field, $value);

        unset($this->holidays);
        unset($this->isCalendarStale);
        ToastService::success(__('calendar.settings.saved'));
    }

    public function openCreateHolidayDefinitionModal(): void
    {
        Gate::authorize('create', HolidayDefinition::class);

        ModalService::form(
            $this,
            name: 'calendar.holiday-definition-form',
            title: __('calendar.settings.holiday_definition_form.create_title'),
            description: __('calendar.settings.holiday_definition_form.create_description'),
            context: $this->holidayDefinitionFormContext('create'),
            width: 'md:w-[42rem]',
        );
    }

    public function openEditHolidayDefinitionModal(int $holidayDefinitionId): void
    {
        $holiday = $this->findHolidayDefinition($holidayDefinitionId);

        Gate::authorize('update', $holiday);

        ModalService::form(
            $this,
            name: 'calendar.holiday-definition-form',
            title: __('calendar.settings.holiday_definition_form.edit_title'),
            description: __('calendar.settings.holiday_definition_form.edit_description', [
                'holiday' => $this->holidayDefinitionLabel($holiday),
            ]),
            context: $this->holidayDefinitionFormContext('edit', $holiday->id),
            width: 'md:w-[42rem]',
        );
    }

    public function confirmHolidayDefinitionDeletion(int $holidayDefinitionId): void
    {
        if ($this->throttle('delete', 5)) {
            return;
        }

        $holiday = $this->findHolidayDefinition($holidayDefinitionId);

        Gate::authorize('delete', $holiday);

        $this->resetAllPendingActions();
        $this->holidayDefinitionIdPendingDeletion = $holiday->id;

        ModalService::confirm(
            $this,
            title: __('calendar.settings.confirm_delete_holiday.title'),
            message: __('calendar.settings.confirm_delete_holiday.message', [
                'holiday' => $this->holidayDefinitionLabel($holiday),
            ]),
            confirmLabel: __('calendar.settings.confirm_delete_holiday.confirm_label'),
            variant: ModalService::VARIANT_PASSWORD,
        );
    }

    public function updateSeasonBlock(int $id, string $field, mixed $value, UpdateSeasonBlock $action): void
    {
        if ($this->throttle('autosave')) {
            return;
        }

        $action->handle($this->actor(), SeasonBlock::findOrFail($id), $field, $value);

        $this->refreshSeasonBlocks();
        ToastService::success(__('calendar.settings.saved'));
    }

    public function updatePricingCategory(int $id, string $field, mixed $value, UpdatePricingCategory $action): void
    {
        if ($this->throttle('autosave')) {
            return;
        }

        $action->handle($this->actor(), PricingCategory::findOrFail($id), $field, $value);

        $this->refreshPricingCategories();
        ToastService::success(__('calendar.settings.saved'));
    }

    public function updatePricingRule(int $id, string $field, mixed $value, UpdatePricingRule $action): void
    {
        if ($this->throttle('autosave')) {
            return;
        }

        $action->handle($this->actor(), PricingRule::findOrFail($id), $field, $value);

        $this->refreshPricingRules();
        ToastService::success(__('calendar.settings.saved'));
    }

    public function reorderPricingRules(int|string $id, int|string $position, ReorderPricingRules $action): void
    {
        if ($this->throttle('reorder')) {
            return;
        }

        $pricingRule = $this->findPricingRule((int) $id);

        $action->handle($this->actor(), $pricingRule, (int) $position);

        $this->refreshPricingRules();
        ToastService::success(__('actions.reorder_success'));
    }

    public function openCreatePricingRuleModal(): void
    {
        Gate::authorize('create', PricingRule::class);

        ModalService::form(
            $this,
            name: 'calendar.pricing-rules.form',
            title: __('calendar.settings.rule_form.create_title'),
            description: __('calendar.settings.rule_form.create_description'),
            context: $this->pricingRuleFormContext('create'),
            width: 'md:w-[72rem]',
        );
    }

    public function openCreatePricingCategoryModal(): void
    {
        Gate::authorize('create', PricingCategory::class);

        ModalService::form(
            $this,
            name: 'calendar.pricing-category-form',
            title: __('calendar.settings.pricing_category_form.create_title'),
            description: __('calendar.settings.pricing_category_form.create_description'),
            context: $this->pricingCategoryFormContext('create'),
            width: 'md:w-[42rem]',
        );
    }

    public function openCreateSeasonBlockModal(): void
    {
        Gate::authorize('create', SeasonBlock::class);

        ModalService::form(
            $this,
            name: 'calendar.season-block-form',
            title: __('calendar.settings.season_block_form.create_title'),
            description: __('calendar.settings.season_block_form.create_description'),
            context: $this->seasonBlockFormContext('create'),
            width: 'md:w-[42rem]',
        );
    }

    public function openEditSeasonBlockModal(int $seasonBlockId): void
    {
        $seasonBlock = $this->findSeasonBlock($seasonBlockId);

        Gate::authorize('update', $seasonBlock);

        ModalService::form(
            $this,
            name: 'calendar.season-block-form',
            title: __('calendar.settings.season_block_form.edit_title'),
            description: __('calendar.settings.season_block_form.edit_description', ['season_block' => $seasonBlock->label()]),
            context: $this->seasonBlockFormContext('edit', $seasonBlock->id),
            width: 'md:w-[42rem]',
        );
    }

    public function openEditPricingRuleModal(int $pricingRuleId): void
    {
        $pricingRule = $this->findPricingRule($pricingRuleId);

        Gate::authorize('update', $pricingRule);

        ModalService::form(
            $this,
            name: 'calendar.pricing-rules.form',
            title: __('calendar.settings.rule_form.edit_title'),
            description: __('calendar.settings.rule_form.edit_description', ['rule' => $this->pricingRuleLabel($pricingRule)]),
            context: $this->pricingRuleFormContext('edit', $pricingRule->id),
            width: 'md:w-[72rem]',
        );
    }

    public function openEditPricingCategoryModal(int $pricingCategoryId): void
    {
        $pricingCategory = $this->findPricingCategory($pricingCategoryId);

        Gate::authorize('update', $pricingCategory);

        ModalService::form(
            $this,
            name: 'calendar.pricing-category-form',
            title: __('calendar.settings.pricing_category_form.edit_title'),
            description: __('calendar.settings.pricing_category_form.edit_description', ['category' => $this->pricingCategoryLabel($pricingCategory)]),
            context: $this->pricingCategoryFormContext('edit', $pricingCategory->id),
            width: 'md:w-[42rem]',
        );
    }

    public function openDuplicatePricingRuleModal(int $pricingRuleId): void
    {
        $pricingRule = $this->findPricingRule($pricingRuleId);

        Gate::authorize('view', $pricingRule);
        Gate::authorize('create', PricingRule::class);

        ModalService::form(
            $this,
            name: 'calendar.pricing-rules.form',
            title: __('calendar.settings.rule_form.duplicate_title'),
            description: __('calendar.settings.rule_form.duplicate_description', ['rule' => $this->pricingRuleLabel($pricingRule)]),
            context: $this->pricingRuleFormContext('duplicate', $pricingRule->id),
            width: 'md:w-[72rem]',
        );
    }

    public function confirmPricingRuleDeletion(int $pricingRuleId): void
    {
        if ($this->throttle('delete', 5)) {
            return;
        }

        $pricingRule = $this->findPricingRule($pricingRuleId);

        Gate::authorize('delete', $pricingRule);

        $this->resetAllPendingActions();
        $this->pricingRuleIdPendingDeletion = $pricingRule->id;

        ModalService::confirm(
            $this,
            title: __('calendar.settings.confirm_delete_rule.title'),
            message: __('calendar.settings.confirm_delete_rule.message', ['rule' => $this->pricingRuleLabel($pricingRule)]),
            confirmLabel: __('calendar.settings.confirm_delete_rule.confirm_label'),
            variant: ModalService::VARIANT_PASSWORD,
        );
    }

    public function confirmPricingCategoryDeletion(int $pricingCategoryId, PricingCategoryHasReferences $pricingCategoryHasReferences): void
    {
        if ($this->throttle('delete', 5)) {
            return;
        }

        $pricingCategory = $this->findPricingCategory($pricingCategoryId);

        Gate::authorize('delete', $pricingCategory);

        $this->resetAllPendingActions();
        $this->pricingCategoryIdPendingDeletion = $pricingCategory->id;

        $prefix = $pricingCategoryHasReferences->handle($pricingCategory)
            ? 'calendar.settings.confirm_deactivate_category'
            : 'calendar.settings.confirm_delete_category';

        ModalService::confirm(
            $this,
            title: __("{$prefix}.title"),
            message: __("{$prefix}.message", ['category' => $this->pricingCategoryLabel($pricingCategory)]),
            confirmLabel: __("{$prefix}.confirm_label"),
            variant: ModalService::VARIANT_PASSWORD,
        );
    }

    public function confirmSeasonBlockDeletion(int $seasonBlockId): void
    {
        if ($this->throttle('delete', 5)) {
            return;
        }

        $seasonBlock = $this->findSeasonBlock($seasonBlockId);

        Gate::authorize('delete', $seasonBlock);

        $this->resetAllPendingActions();
        $this->seasonBlockIdPendingDeletion = $seasonBlock->id;

        ModalService::confirm(
            $this,
            title: __('calendar.settings.confirm_delete_season_block.title'),
            message: __('calendar.settings.confirm_delete_season_block.message', ['season_block' => $seasonBlock->label()]),
            confirmLabel: __('calendar.settings.confirm_delete_season_block.confirm_label'),
            variant: ModalService::VARIANT_PASSWORD,
        );
    }

    public function confirmRegenerate(): void
    {
        Gate::authorize('regenerate', CalendarDay::class);

        if ($this->throttle('regenerate', 5)) {
            return;
        }

        $this->resetAllPendingActions();
        $this->regenerationPendingConfirmation = true;

        ModalService::confirm(
            $this,
            title: __('calendar.settings.regenerate.title'),
            message: __('calendar.settings.regenerate.message'),
            confirmLabel: __('calendar.settings.regenerate.confirm_label'),
        );
    }

    #[On('modal-confirmed')]
    public function handleConfirmedModalAction(
        RecalculateCalendarAfterConfigChange $recalculate,
        DeleteHolidayDefinition $deleteHolidayDefinition,
        DeleteSeasonBlock $deleteSeasonBlock,
        DeletePricingCategory $deletePricingCategory,
        DeletePricingRule $deletePricingRule,
    ): void {
        if ($this->holidayDefinitionIdPendingDeletion !== null) {
            $this->deleteHolidayDefinition($deleteHolidayDefinition);

            return;
        }

        if ($this->seasonBlockIdPendingDeletion !== null) {
            $this->deleteSeasonBlock($deleteSeasonBlock);

            return;
        }

        if ($this->pricingCategoryIdPendingDeletion !== null) {
            $this->deletePricingCategory($deletePricingCategory);

            return;
        }

        if ($this->pricingRuleIdPendingDeletion !== null) {
            $this->deletePricingRule($deletePricingRule);

            return;
        }

        if ($this->regenerationPendingConfirmation) {
            $this->regenerateCalendar($recalculate);
        }
    }

    #[On('modal-confirm-cancelled')]
    public function resetPendingModalAction(): void
    {
        $this->resetAllPendingActions();
    }

    #[On('holiday-definition-saved')]
    public function refreshHolidays(): void
    {
        unset($this->holidays);
        unset($this->isCalendarStale);
    }

    #[On('pricing-category-saved')]
    public function refreshPricingCategories(): void
    {
        unset($this->pricingCategories);
        unset($this->isCalendarStale);
    }

    #[On('pricing-rule-saved')]
    public function refreshPricingRules(): void
    {
        unset($this->pricingRules);
        unset($this->isCalendarStale);
    }

    #[On('season-block-saved')]
    public function refreshSeasonBlocks(): void
    {
        unset($this->seasonBlocks);
        unset($this->isCalendarStale);
    }

    #[Computed]
    public function canViewHolidays(): bool
    {
        return Gate::allows('viewAny', HolidayDefinition::class);
    }

    #[Computed]
    public function canCreateHolidays(): bool
    {
        return Gate::allows('create', HolidayDefinition::class);
    }

    #[Computed]
    public function canViewSeasonBlocks(): bool
    {
        return Gate::allows('viewAny', SeasonBlock::class);
    }

    #[Computed]
    public function canViewPricingCategories(): bool
    {
        return Gate::allows('viewAny', PricingCategory::class);
    }

    #[Computed]
    public function canViewPricingRules(): bool
    {
        return Gate::allows('viewAny', PricingRule::class);
    }

    #[Computed]
    public function canCreatePricingRules(): bool
    {
        return Gate::allows('create', PricingRule::class);
    }

    #[Computed]
    public function canCreatePricingCategories(): bool
    {
        return Gate::allows('create', PricingCategory::class);
    }

    #[Computed]
    public function canCreateSeasonBlocks(): bool
    {
        return Gate::allows('create', SeasonBlock::class);
    }

    #[Computed]
    public function canRegenerateCalendar(): bool
    {
        return Gate::allows('regenerate', CalendarDay::class);
    }

    #[Computed]
    public function isCalendarStale(): bool
    {
        $freshness = app(ResolveCalendarFreshnessTimestamp::class);
        $latestConfigUpdate = $freshness->latestConfigurationUpdate();

        if ($latestConfigUpdate === null) {
            return false;
        }

        $latestCalendarUpdate = $freshness->latestCalendarUpdate();

        if ($latestCalendarUpdate === null) {
            return true;
        }

        return $latestConfigUpdate->greaterThan($latestCalendarUpdate);
    }

    public function regenerateCalendar(RecalculateCalendarAfterConfigChange $recalculate): void
    {
        Gate::authorize('regenerate', CalendarDay::class);

        if ($this->throttle('regenerate', 5)) {
            return;
        }

        $count = $recalculate->handle();
        $this->regenerationPendingConfirmation = false;
        unset($this->isCalendarStale);

        ToastService::success(__('calendar.settings.regenerate.success', ['count' => $count]));
    }

    private function activeSwitchColumn(string $wireChange, bool $disabled, string $idPrefix): ToggleColumn
    {
        return ToggleColumn::make('is_active')
            ->label(__('calendar.settings.fields.is_active'))
            ->wireChange($wireChange)
            ->disabled($disabled)
            ->idPrefix($idPrefix);
    }

    private function idColumn(): IdColumn
    {
        return IdColumn::make('id')
            ->label('#');
    }

    private function deletePricingRule(DeletePricingRule $deletePricingRule): void
    {
        if ($this->throttle('delete', 5)) {
            return;
        }

        $pricingRule = $this->pendingDeletionPricingRule();
        $ruleLabel = $this->pricingRuleLabel($pricingRule);

        try {
            $deletePricingRule->handle($this->actor(), $pricingRule);
        } catch (ValidationException $exception) {
            $this->pricingRuleIdPendingDeletion = null;
            ToastService::warning($exception->validator->errors()->first());

            return;
        }

        $this->pricingRuleIdPendingDeletion = null;
        $this->refreshPricingRules();
        ToastService::success(__('calendar.settings.rule_form.deleted', ['rule' => $ruleLabel]));
    }

    private function deleteHolidayDefinition(DeleteHolidayDefinition $deleteHolidayDefinition): void
    {
        if ($this->throttle('delete', 5)) {
            return;
        }

        $holiday = $this->pendingDeletionHolidayDefinition();
        $label = $this->holidayDefinitionLabel($holiday);

        try {
            $deleteHolidayDefinition->handle($this->actor(), $holiday);
        } catch (ValidationException $exception) {
            $this->holidayDefinitionIdPendingDeletion = null;
            ToastService::warning($exception->validator->errors()->first());

            return;
        }

        $this->holidayDefinitionIdPendingDeletion = null;
        $this->refreshHolidays();

        ToastService::success(__('calendar.settings.holiday_definition_form.deleted', ['holiday' => $label]));
    }

    private function resetAllPendingActions(): void
    {
        $this->holidayDefinitionIdPendingDeletion = null;
        $this->pricingCategoryIdPendingDeletion = null;
        $this->pricingRuleIdPendingDeletion = null;
        $this->seasonBlockIdPendingDeletion = null;
        $this->regenerationPendingConfirmation = false;
    }

    private function canAccessSettings(): bool
    {
        return $this->canViewHolidays()
            || $this->canViewSeasonBlocks()
            || $this->canViewPricingCategories()
            || $this->canViewPricingRules();
    }

    private function canUpdateHolidays(): bool
    {
        return Gate::allows('update', new HolidayDefinition);
    }

    private function canDeleteHolidays(): bool
    {
        return Gate::allows('delete', new HolidayDefinition);
    }

    private function canUpdateSeasonBlocks(): bool
    {
        return Gate::allows('update', new SeasonBlock);
    }

    private function canDeleteSeasonBlocks(): bool
    {
        return Gate::allows('delete', new SeasonBlock);
    }

    private function canUpdatePricingCategories(): bool
    {
        return Gate::allows('update', new PricingCategory);
    }

    private function canDeletePricingCategories(): bool
    {
        return Gate::allows('delete', new PricingCategory);
    }

    private function canUpdatePricingRules(): bool
    {
        return Gate::allows('update', new PricingRule);
    }

    private function canDeletePricingRules(): bool
    {
        return Gate::allows('delete', new PricingRule);
    }

    private function canManagePricingRules(): bool
    {
        return $this->canUpdatePricingRules()
            || $this->canCreatePricingRules()
            || $this->canDeletePricingRules();
    }

    private function canManagePricingCategories(): bool
    {
        return $this->canUpdatePricingCategories()
            || $this->canCreatePricingCategories()
            || $this->canDeletePricingCategories();
    }

    private function canManageSeasonBlocks(): bool
    {
        return $this->canUpdateSeasonBlocks()
            || $this->canCreateSeasonBlocks()
            || $this->canDeleteSeasonBlocks();
    }

    private function findHolidayDefinition(int $holidayDefinitionId): HolidayDefinition
    {
        return HolidayDefinition::query()->findOrFail($holidayDefinitionId);
    }

    private function pendingDeletionHolidayDefinition(): HolidayDefinition
    {
        abort_if($this->holidayDefinitionIdPendingDeletion === null, 404);

        return $this->findHolidayDefinition($this->holidayDefinitionIdPendingDeletion);
    }

    private function findSeasonBlock(int $seasonBlockId): SeasonBlock
    {
        return SeasonBlock::query()->findOrFail($seasonBlockId);
    }

    private function pendingDeletionSeasonBlock(): SeasonBlock
    {
        abort_if($this->seasonBlockIdPendingDeletion === null, 404);

        return $this->findSeasonBlock($this->seasonBlockIdPendingDeletion);
    }

    private function findPricingCategory(int $pricingCategoryId): PricingCategory
    {
        return PricingCategory::query()->findOrFail($pricingCategoryId);
    }

    private function pendingDeletionPricingCategory(): PricingCategory
    {
        abort_if($this->pricingCategoryIdPendingDeletion === null, 404);

        return $this->findPricingCategory($this->pricingCategoryIdPendingDeletion);
    }

    private function findPricingRule(int $pricingRuleId): PricingRule
    {
        return PricingRule::query()->with('pricingCategory')->findOrFail($pricingRuleId);
    }

    private function pendingDeletionPricingRule(): PricingRule
    {
        abort_if($this->pricingRuleIdPendingDeletion === null, 404);

        return $this->findPricingRule($this->pricingRuleIdPendingDeletion);
    }

    private function pricingRuleLabel(PricingRule $pricingRule): string
    {
        return __('calendar.settings.rule_label', [
            'name' => $pricingRule->name,
            'id' => $pricingRule->id,
        ]);
    }

    private function pricingCategoryLabel(PricingCategory $pricingCategory): string
    {
        return __('calendar.settings.pricing_category_label', [
            'name' => $pricingCategory->name,
            'id' => $pricingCategory->id,
        ]);
    }

    /**
     * @param  EloquentCollection<int, SeasonBlock>  $seasonBlocksForLookup
     */
    private function pricingRuleConditionSummary(PricingRule $pricingRule, EloquentCollection $seasonBlocksForLookup): string
    {
        $schema = app(PricingRuleConditionSchemaRegistry::class)
            ->for($pricingRule->rule_type);

        if ($pricingRule->rule_type !== PricingRuleType::SeasonDays) {
            return $schema->summary($pricingRule->conditions);
        }

        return $schema->summary($this->seasonConditionsForSummary($pricingRule->conditions, $seasonBlocksForLookup));
    }

    /**
     * @return array<string, mixed>
     */
    private function pricingRuleFormContext(string $mode, ?int $pricingRuleId = null): array
    {
        return array_filter([
            'mode' => $mode,
            'pricingRuleId' => $pricingRuleId,
            'preview_from' => CarbonImmutable::now()->startOfYear()->toDateString(),
            'preview_to' => CarbonImmutable::now()->addYear()->endOfYear()->toDateString(),
        ], fn (mixed $value): bool => $value !== null);
    }

    private function deleteSeasonBlock(DeleteSeasonBlock $deleteSeasonBlock): void
    {
        if ($this->throttle('delete', 5)) {
            return;
        }

        $seasonBlock = $this->pendingDeletionSeasonBlock();
        $seasonBlockLabel = $seasonBlock->label();

        try {
            $deleteSeasonBlock->handle($this->actor(), $seasonBlock);
        } catch (ValidationException $exception) {
            $this->seasonBlockIdPendingDeletion = null;
            ToastService::warning($exception->validator->errors()->first());

            return;
        }

        $this->seasonBlockIdPendingDeletion = null;
        unset($this->seasonBlocks);
        unset($this->isCalendarStale);

        ToastService::success(__('calendar.settings.season_block_form.deleted', ['season_block' => $seasonBlockLabel]));
    }

    private function deletePricingCategory(DeletePricingCategory $deletePricingCategory): void
    {
        if ($this->throttle('delete', 5)) {
            return;
        }

        $pricingCategory = $this->pendingDeletionPricingCategory();
        $pricingCategoryLabel = $this->pricingCategoryLabel($pricingCategory);

        try {
            $wasDeleted = $deletePricingCategory->handle($this->actor(), $pricingCategory);
        } catch (ValidationException $exception) {
            $this->pricingCategoryIdPendingDeletion = null;
            ToastService::warning($exception->validator->errors()->first());

            return;
        }

        $this->pricingCategoryIdPendingDeletion = null;
        $this->refreshPricingCategories();

        $messageKey = $wasDeleted
            ? 'calendar.settings.pricing_category_form.deleted'
            : 'calendar.settings.pricing_category_form.deactivated_instead';

        ToastService::success(__($messageKey, ['category' => $pricingCategoryLabel]));
    }

    /**
     * @param  array<string, mixed>  $conditions
     * @param  EloquentCollection<int, SeasonBlock>  $seasonBlocks
     */
    private function resolveSeasonBlockLabelFromConditions(array $conditions, EloquentCollection $seasonBlocks): ?string
    {
        $seasonBlockId = $conditions['season_block_id'] ?? null;

        if (is_int($seasonBlockId) || is_numeric($seasonBlockId)) {
            $seasonBlock = $seasonBlocks->find((int) $seasonBlockId);

            if ($seasonBlock instanceof SeasonBlock) {
                return $seasonBlock->localizedName();
            }
        }

        $legacySeason = $conditions['season'] ?? null;

        return is_string($legacySeason) && $legacySeason !== '' ? $legacySeason : null;
    }

    /**
     * @param  array<string, mixed>  $conditions
     * @param  EloquentCollection<int, SeasonBlock>  $seasonBlocks
     * @return array<string, mixed>
     */
    private function seasonConditionsForSummary(array $conditions, EloquentCollection $seasonBlocks): array
    {
        $seasonLabel = $this->resolveSeasonBlockLabelFromConditions($conditions, $seasonBlocks);

        if ($seasonLabel === null) {
            return $conditions;
        }

        unset($conditions['season_block_id']);
        $conditions['season'] = $seasonLabel;

        return $conditions;
    }

    /**
     * @return array<string, mixed>
     */
    private function seasonBlockFormContext(string $mode, ?int $seasonBlockId = null): array
    {
        return array_filter([
            'mode' => $mode,
            'seasonBlockId' => $seasonBlockId,
        ], fn (mixed $value): bool => $value !== null);
    }

    /**
     * @return array<string, mixed>
     */
    private function pricingCategoryFormContext(string $mode, ?int $pricingCategoryId = null): array
    {
        return array_filter([
            'mode' => $mode,
            'pricingCategoryId' => $pricingCategoryId,
        ], fn (mixed $value): bool => $value !== null);
    }

    /**
     * @return array<string, mixed>
     */
    private function holidayDefinitionFormContext(string $mode, ?int $holidayDefinitionId = null): array
    {
        return array_filter([
            'mode' => $mode,
            'holidayDefinitionId' => $holidayDefinitionId,
        ], fn (mixed $value): bool => $value !== null);
    }

    /**
     * @return EloquentCollection<int, SeasonBlock>
     */
    private function allSeasonBlocksForLookup(): EloquentCollection
    {
        return SeasonBlock::query()->get(['id', 'name', 'en_name', 'es_name']);
    }

    private function holidayDefinitionLabel(HolidayDefinition $holiday): string
    {
        return __('calendar.settings.holiday_definition_label', [
            'name' => $holiday->name,
            'id' => $holiday->id,
        ]);
    }
};
