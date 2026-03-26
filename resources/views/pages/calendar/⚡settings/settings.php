<?php

use App\Actions\Calendar\DeletePricingRule;
use App\Actions\Calendar\DeleteSeasonBlock;
use App\Actions\Calendar\RecalculateCalendarAfterConfigChange;
use App\Actions\Calendar\ReorderPricingRules;
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
use App\Domain\Table\Columns\EditableColorColumn;
use App\Domain\Table\Columns\EditableNumberColumn;
use App\Domain\Table\Columns\EditableSwitchColumn;
use App\Domain\Table\Columns\EditableTextColumn;
use App\Domain\Table\Columns\IdColumn;
use App\Domain\Table\Columns\TextColumn;
use App\Infrastructure\UiFeedback\ModalService;
use App\Infrastructure\UiFeedback\ToastService;
use App\Models\CalendarDay;
use App\Models\HolidayDefinition;
use App\Models\PricingCategory;
use App\Models\PricingRule;
use App\Models\SeasonBlock;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    use ResolvesAuthenticatedUser;
    use ThrottlesFormActions;

    private const string THROTTLE_KEY_PREFIX = 'calendar-settings';

    public ?int $pricingRuleIdPendingDeletion = null;

    public ?int $seasonBlockIdPendingDeletion = null;

    public bool $regenerationPendingConfirmation = false;

    public function mount(): void
    {
        abort_unless($this->canAccessSettings(), 403);
    }

    /**
     * @return EloquentCollection<int, HolidayDefinition>
     */
    #[Computed]
    public function holidays(): EloquentCollection
    {
        if (! $this->canViewHolidays()) {
            return HolidayDefinition::query()->getModel()->newCollection();
        }

        return HolidayDefinition::query()->orderBy('sort_order')->get();
    }

    /**
     * @return EloquentCollection<int, SeasonBlock>
     */
    #[Computed]
    public function seasonBlocks(): EloquentCollection
    {
        if (! $this->canViewSeasonBlocks()) {
            return SeasonBlock::query()->getModel()->newCollection();
        }

        return SeasonBlock::query()->orderBy('sort_order')->get();
    }

    /**
     * @return EloquentCollection<int, PricingCategory>
     */
    #[Computed]
    public function pricingCategories(): EloquentCollection
    {
        if (! $this->canViewPricingCategories()) {
            return PricingCategory::query()->getModel()->newCollection();
        }

        return PricingCategory::query()->orderBy('sort_order')->get();
    }

    /**
     * @return EloquentCollection<int, PricingRule>
     */
    #[Computed]
    public function pricingRules(): EloquentCollection
    {
        if (! $this->canViewPricingRules()) {
            return PricingRule::query()->getModel()->newCollection();
        }

        return PricingRule::query()
            ->with('pricingCategory')
            ->orderBy('priority')
            ->get();
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

        $activeColumn = $this->activeSwitchColumn(
            wireChange: 'updateHoliday',
            disabled: ! $this->canUpdateHolidays(),
            idPrefix: 'holiday-active',
        );

        if (! $this->canUpdateHolidays()) {
            return [
                $this->idColumn(),
                $activeColumn,
                BadgeColumn::make('name')->label(__('calendar.settings.fields.name')),
                TextColumn::make('en_name')->label(__('calendar.settings.fields.en_name')),
                TextColumn::make('es_name')->label(__('calendar.settings.fields.es_name')),
                TextColumn::make('group')->label(__('calendar.settings.fields.group'))->formatUsing(fn (mixed $_, HolidayDefinition $record) => __('calendar.holiday_groups.'.$record->group->value)),
                TextColumn::make('sort_order')->label(__('calendar.settings.fields.sort_order')),
            ];
        }

        return [
            $this->idColumn(),
            $activeColumn,
            BadgeColumn::make('name')->label(__('calendar.settings.fields.name')),
            EditableTextColumn::make('en_name')->label(__('calendar.settings.fields.en_name'))->wireChange('updateHoliday'),
            EditableTextColumn::make('es_name')->label(__('calendar.settings.fields.es_name'))->wireChange('updateHoliday'),
            TextColumn::make('group')->label(__('calendar.settings.fields.group'))->formatUsing(fn (mixed $_, HolidayDefinition $record) => __('calendar.holiday_groups.'.$record->group->value)),
            EditableNumberColumn::make('sort_order')->label(__('calendar.settings.fields.sort_order'))->wireChange('updateHoliday')->min(0)->max(9999)->inputClass('w-20'),
        ];
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

        $activeColumn = $this->activeSwitchColumn(
            wireChange: 'updatePricingCategory',
            disabled: ! $this->canUpdatePricingCategories(),
            idPrefix: 'pricing-category-active',
        );

        if (! $this->canUpdatePricingCategories()) {
            return [
                $this->idColumn(),
                $activeColumn,
                BadgeColumn::make('name')->label(__('calendar.settings.fields.name')),
                TextColumn::make('en_name')->label(__('calendar.settings.fields.en_name')),
                TextColumn::make('es_name')->label(__('calendar.settings.fields.es_name')),
                TextColumn::make('level')->label(__('calendar.settings.fields.level')),
                TextColumn::make('color')->label(__('calendar.settings.fields.color')),
                TextColumn::make('multiplier')->label(__('calendar.settings.fields.multiplier')),
            ];
        }

        return [
            $this->idColumn(),
            $activeColumn,
            BadgeColumn::make('name')->label(__('calendar.settings.fields.name')),
            EditableTextColumn::make('en_name')->label(__('calendar.settings.fields.en_name'))->wireChange('updatePricingCategory'),
            EditableTextColumn::make('es_name')->label(__('calendar.settings.fields.es_name'))->wireChange('updatePricingCategory'),
            EditableNumberColumn::make('level')->label(__('calendar.settings.fields.level'))->wireChange('updatePricingCategory')->min(1)->max(10)->inputClass('w-16'),
            EditableColorColumn::make('color')->label(__('calendar.settings.fields.color'))->wireChange('updatePricingCategory'),
            EditableNumberColumn::make('multiplier')->label(__('calendar.settings.fields.multiplier'))->wireChange('updatePricingCategory')->min(0)->max(99)->step('0.01')->inputClass('w-20'),
        ];
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
                ->formatUsing(fn (mixed $_, PricingRule $record) => $this->pricingRuleConditionSummary($record)),
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

        unset($this->pricingCategories);
        unset($this->isCalendarStale);
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

        $this->pricingRuleIdPendingDeletion = $pricingRule->id;
        $this->seasonBlockIdPendingDeletion = null;
        $this->regenerationPendingConfirmation = false;

        ModalService::confirm(
            $this,
            title: __('calendar.settings.confirm_delete_rule.title'),
            message: __('calendar.settings.confirm_delete_rule.message', ['rule' => $this->pricingRuleLabel($pricingRule)]),
            confirmLabel: __('calendar.settings.confirm_delete_rule.confirm_label'),
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

        $this->seasonBlockIdPendingDeletion = $seasonBlock->id;
        $this->pricingRuleIdPendingDeletion = null;
        $this->regenerationPendingConfirmation = false;

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

        $this->pricingRuleIdPendingDeletion = null;
        $this->seasonBlockIdPendingDeletion = null;
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
        DeleteSeasonBlock $deleteSeasonBlock,
        DeletePricingRule $deletePricingRule,
    ): void {
        if ($this->seasonBlockIdPendingDeletion !== null) {
            $this->deleteSeasonBlock($deleteSeasonBlock);

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
        $this->pricingRuleIdPendingDeletion = null;
        $this->seasonBlockIdPendingDeletion = null;
        $this->regenerationPendingConfirmation = false;
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
        $latestConfigUpdate = collect([
            HolidayDefinition::query()->max('updated_at'),
            SeasonBlock::query()->max('updated_at'),
            PricingCategory::query()->max('updated_at'),
            PricingRule::query()->max('updated_at'),
        ])->filter()->max();

        if (! is_string($latestConfigUpdate)) {
            return false;
        }

        $latestCalendarUpdate = CalendarDay::query()->max('updated_at');

        if (! is_string($latestCalendarUpdate)) {
            return true;
        }

        return strtotime($latestConfigUpdate) > strtotime($latestCalendarUpdate);
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

    private function activeSwitchColumn(string $wireChange, bool $disabled, string $idPrefix): EditableSwitchColumn
    {
        return EditableSwitchColumn::make('is_active')
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

    private function canManageSeasonBlocks(): bool
    {
        return $this->canUpdateSeasonBlocks()
            || $this->canCreateSeasonBlocks()
            || $this->canDeleteSeasonBlocks();
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

    private function pricingRuleConditionSummary(PricingRule $pricingRule): string
    {
        $schema = app(PricingRuleConditionSchemaRegistry::class)
            ->for($pricingRule->rule_type);

        if ($pricingRule->rule_type !== PricingRuleType::SeasonDays) {
            return $schema->summary($pricingRule->conditions);
        }

        return $schema->summary($this->seasonConditionsForSummary($pricingRule->conditions, $this->seasonBlocks()));
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
};
