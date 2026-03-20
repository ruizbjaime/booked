<?php

use App\Actions\BedTypes\DeleteBedType;
use App\Concerns\InteractsWithTable;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Domain\Table\ActionItem;
use App\Domain\Table\Column;
use App\Domain\Table\Columns\ActionsColumn;
use App\Domain\Table\Columns\BadgeColumn;
use App\Domain\Table\Columns\DateColumn;
use App\Domain\Table\Columns\IdColumn;
use App\Domain\Table\Columns\TextColumn;
use App\Domain\Table\TableAction;
use App\Infrastructure\UiFeedback\ModalService;
use App\Infrastructure\UiFeedback\ToastService;
use App\Models\BedType;
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
    public ?int $bedTypeIdPendingDeletion = null;

    public function mount(): void
    {
        Gate::authorize('viewAny', BedType::class);
    }

    /**
     * @return list<Column>
     */
    protected function columns(): array
    {
        return [
            IdColumn::make('id')
                ->label('#'),

            TextColumn::make(BedType::localizedNameColumn())
                ->label(__('bed_types.index.columns.name'))
                ->sortable(),

            BadgeColumn::make('name')
                ->label(__('bed_types.index.columns.slug')),

            TextColumn::make('bed_capacity')
                ->label(__('bed_types.index.columns.bed_capacity'))
                ->sortable(),

            TextColumn::make('sort_order')
                ->label(__('bed_types.index.columns.sort_order'))
                ->sortable(),

            DateColumn::make('created_at')
                ->label(__('bed_types.index.columns.created'))
                ->sortable()
                ->defaultSortDirection('desc'),

            ActionsColumn::make('actions')
                ->label(__('actions.actions'))
                ->actions(fn (BedType $bedType) => [
                    ActionItem::link(__('actions.view'), route('bed-types.show', $bedType), 'eye', wireNavigate: true),
                    ActionItem::separator(),
                    ActionItem::button(__('actions.delete'), 'confirmBedTypeDeletion', 'trash', 'danger'),
                ]),
        ];
    }

    protected function defaultSortBy(): string
    {
        return 'sort_order';
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
        return ['name', 'name_en', 'name_es'];
    }

    /**
     * @return list<TableAction>
     */
    protected function actions(): array
    {
        return [
            TableAction::make('create')
                ->label(__('bed_types.index.create_action'))
                ->icon('plus')
                ->wireClick('openCreateBedTypeModal')
                ->variant('primary')
                ->responsive(),
        ];
    }

    /**
     * @return LengthAwarePaginator<int, BedType>
     */
    #[Computed]
    public function bedTypes(): LengthAwarePaginator
    {
        return $this->paginatedQuery($this->baseQuery());
    }

    public function openCreateBedTypeModal(): void
    {
        Gate::forUser($this->actor())->authorize('create', BedType::class);

        ModalService::form(
            $this,
            name: 'bed-types.create',
            title: __('bed_types.create.title'),
            description: __('bed_types.create.description'),
        );
    }

    public function confirmBedTypeDeletion(int $bedTypeId): void
    {
        $this->throttle('delete', 5);

        $actor = $this->actor();
        $bedType = $this->findBedType($bedTypeId);

        Gate::forUser($actor)->authorize('delete', $bedType);

        $this->bedTypeIdPendingDeletion = $bedType->id;

        ModalService::confirm(
            $this,
            title: __('bed_types.index.confirm_delete.title'),
            message: __('bed_types.index.confirm_delete.message', ['bed_type' => $this->bedTypeLabel($bedType)]),
            confirmLabel: __('bed_types.index.confirm_delete.confirm_label'),
            variant: ModalService::VARIANT_PASSWORD,
        );
    }

    #[On('modal-confirmed')]
    public function deleteBedType(DeleteBedType $deleteBedType): void
    {
        $this->throttle('delete', 5);

        $bedType = $this->pendingDeletionBedType();
        $bedTypeLabel = $this->bedTypeLabel($bedType);

        $deleteBedType->handle($this->actor(), $bedType);

        $this->bedTypeIdPendingDeletion = null;
        $this->syncCurrentPage($this->baseQuery());

        ToastService::success(__('bed_types.index.deleted', ['bed_type' => $bedTypeLabel]));
    }

    #[On('modal-confirm-cancelled')]
    public function resetPendingDeletion(): void
    {
        $this->bedTypeIdPendingDeletion = null;
    }

    #[On('bed-type-created')]
    public function refreshBedTypes(): void
    {
        $this->resetPage();
    }

    private function pendingDeletionBedType(): BedType
    {
        abort_if($this->bedTypeIdPendingDeletion === null, 404);

        return $this->findBedType($this->bedTypeIdPendingDeletion);
    }

    private function bedTypeLabel(BedType $bedType): string
    {
        return __('bed_types.bed_type_label', [
            'name' => $bedType->localizedName(),
            'id' => $bedType->id,
        ]);
    }

    private function findBedType(int $bedTypeId): BedType
    {
        return BedType::query()->findOrFail($bedTypeId);
    }

    /**
     * @return Builder<BedType>
     */
    private function baseQuery(): Builder
    {
        return BedType::query();
    }

    private function throttle(string $action, int $maxAttempts = 10): void
    {
        $key = "bed-type-mgmt:{$action}:{$this->actor()->id}";

        abort_if(RateLimiter::tooManyAttempts($key, $maxAttempts), 429);

        RateLimiter::hit($key, 60);
    }
};
