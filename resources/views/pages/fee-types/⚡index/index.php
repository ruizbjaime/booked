<?php

use App\Actions\FeeTypes\DeleteFeeType;
use App\Concerns\InteractsWithTable;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Domain\Table\ActionItem;
use App\Domain\Table\CardZone;
use App\Domain\Table\Column;
use App\Domain\Table\Columns\ActionsColumn;
use App\Domain\Table\Columns\BadgeColumn;
use App\Domain\Table\Columns\DateColumn;
use App\Domain\Table\Columns\IdColumn;
use App\Domain\Table\Columns\TextColumn;
use App\Domain\Table\TableAction;
use App\Infrastructure\UiFeedback\ModalService;
use App\Infrastructure\UiFeedback\ToastService;
use App\Models\FeeType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    use InteractsWithTable;
    use ResolvesAuthenticatedUser;

    #[Locked]
    public ?int $feeTypeIdPendingDeletion = null;

    public function mount(): void
    {
        Gate::authorize('viewAny', FeeType::class);
    }

    /**
     * @return list<Column>
     */
    protected function columns(): array
    {
        return [
            IdColumn::make('id')
                ->label('#'),

            TextColumn::make(FeeType::localizedNameColumn())
                ->label(__('fee_types.index.columns.name'))
                ->cardZone(CardZone::Header)
                ->sortable(),

            BadgeColumn::make('name')
                ->label(__('fee_types.index.columns.slug')),

            TextColumn::make('order')
                ->label(__('fee_types.index.columns.order'))
                ->sortable(),

            DateColumn::make('created_at')
                ->label(__('fee_types.index.columns.created'))
                ->sortable()
                ->defaultSortDirection('desc'),

            ActionsColumn::make('actions')
                ->label(__('actions.actions'))
                ->actions(fn (FeeType $feeType) => [
                    ActionItem::link(__('actions.view'), route('fee-types.show', $feeType), 'eye', wireNavigate: true),
                    ActionItem::separator(),
                    ActionItem::button(__('actions.delete'), 'confirmFeeTypeDeletion', 'trash', 'danger'),
                ]),
        ];
    }

    protected function defaultSortBy(): string
    {
        return 'order';
    }

    protected function defaultSortDirection(): string
    {
        return 'asc';
    }

    /**
     * @return list<string>
     */
    protected function searchableFields(): array
    {
        return ['name', 'en_name', 'es_name'];
    }

    /**
     * @return list<TableAction>
     */
    protected function actions(): array
    {
        return [
            TableAction::make('create')
                ->label(__('fee_types.index.create_action'))
                ->icon('plus')
                ->wireClick('openCreateFeeTypeModal')
                ->variant('primary')
                ->responsive(),
        ];
    }

    /**
     * @return LengthAwarePaginator<int, FeeType>
     */
    #[Computed]
    public function feeTypes(): LengthAwarePaginator
    {
        return $this->paginatedQuery($this->baseQuery());
    }

    public function openCreateFeeTypeModal(): void
    {
        Gate::forUser($this->actor())->authorize('create', FeeType::class);

        ModalService::form(
            $this,
            name: 'fee-types.create',
            title: __('fee_types.create.title'),
            description: __('fee_types.create.description'),
        );
    }

    public function confirmFeeTypeDeletion(int $feeTypeId): void
    {
        $this->throttle('delete', 5);

        $actor = $this->actor();
        $feeType = $this->findFeeType($feeTypeId);

        Gate::forUser($actor)->authorize('delete', $feeType);

        $this->feeTypeIdPendingDeletion = $feeType->id;

        ModalService::confirm(
            $this,
            title: __('fee_types.index.confirm_delete.title'),
            message: __('fee_types.index.confirm_delete.message', ['fee_type' => $this->feeTypeLabel($feeType)]),
            confirmLabel: __('fee_types.index.confirm_delete.confirm_label'),
            variant: ModalService::VARIANT_PASSWORD,
        );
    }

    #[On('modal-confirmed')]
    public function deleteFeeType(DeleteFeeType $deleteFeeType): void
    {
        $this->throttle('delete', 5);

        $feeType = $this->pendingDeletionFeeType();
        $feeTypeLabel = $this->feeTypeLabel($feeType);

        $deleteFeeType->handle($this->actor(), $feeType);

        $this->feeTypeIdPendingDeletion = null;
        $this->syncCurrentPage($this->baseQuery());

        ToastService::success(__('fee_types.index.deleted', ['fee_type' => $feeTypeLabel]));
    }

    #[On('modal-confirm-cancelled')]
    public function resetPendingDeletion(): void
    {
        $this->feeTypeIdPendingDeletion = null;
    }

    #[On('fee-type-created')]
    public function refreshFeeTypes(): void
    {
        $this->resetPage();
    }

    private function pendingDeletionFeeType(): FeeType
    {
        abort_if($this->feeTypeIdPendingDeletion === null, 404);

        return $this->findFeeType($this->feeTypeIdPendingDeletion);
    }

    private function feeTypeLabel(FeeType $feeType): string
    {
        return __('fee_types.fee_type_label', [
            'name' => $feeType->localizedName(),
            'id' => $feeType->id,
        ]);
    }

    private function findFeeType(int $feeTypeId): FeeType
    {
        return FeeType::query()->findOrFail($feeTypeId);
    }

    /**
     * @return Builder<FeeType>
     */
    private function baseQuery(): Builder
    {
        return FeeType::query();
    }

    private function throttle(string $action, int $maxAttempts = 10): void
    {
        $key = "fee-type-mgmt:{$action}:{$this->actor()->id}";

        abort_if(RateLimiter::tooManyAttempts($key, $maxAttempts), 429);

        RateLimiter::hit($key, 60);
    }
};
