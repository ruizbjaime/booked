<?php

use App\Actions\BathRoomTypes\DeleteBathRoomType;
use App\Concerns\InteractsWithTable;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Concerns\ThrottlesFormActions;
use App\Concerns\WithSortableRows;
use App\Domain\Table\ActionItem;
use App\Domain\Table\CardZone;
use App\Domain\Table\Column;
use App\Domain\Table\Columns\ActionsColumn;
use App\Domain\Table\Columns\BadgeColumn;
use App\Domain\Table\Columns\DateColumn;
use App\Domain\Table\Columns\IdColumn;
use App\Domain\Table\Columns\LinkColumn;
use App\Domain\Table\Columns\TextColumn;
use App\Domain\Table\TableAction;
use App\Infrastructure\UiFeedback\ModalService;
use App\Infrastructure\UiFeedback\ToastService;
use App\Models\BathRoomType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    use InteractsWithTable;
    use ResolvesAuthenticatedUser;
    use ThrottlesFormActions;
    use WithSortableRows;

    private const string THROTTLE_KEY_PREFIX = 'bath-room-type-mgmt';

    #[Locked]
    public ?int $bathRoomTypeIdPendingDeletion = null;

    public function mount(): void
    {
        Gate::authorize('viewAny', BathRoomType::class);
    }

    /**
     * @return list<Column>
     */
    protected function columns(): array
    {
        $actor = $this->actor();
        $canView = $actor->can('view', new BathRoomType);
        $canDelete = $actor->can('delete', new BathRoomType);

        return [
            IdColumn::make('id')
                ->label('#'),

            LinkColumn::make(BathRoomType::localizedNameColumn())
                ->label(__('bath_room_types.index.columns.name'))
                ->href(fn (BathRoomType $bathRoomType) => $canView ? route('bath-room-types.show', $bathRoomType) : null)
                ->wireNavigate()
                ->sortable()
                ->cardZone(CardZone::Header),

            BadgeColumn::make('name')
                ->label(__('bath_room_types.index.columns.slug')),

            TextColumn::make('description')
                ->label(__('bath_room_types.index.columns.description')),

            TextColumn::make('sort_order')
                ->label(__('bath_room_types.index.columns.sort_order'))
                ->sortable(),

            DateColumn::make('created_at')
                ->label(__('bath_room_types.index.columns.created'))
                ->sortable()
                ->defaultSortDirection('desc'),

            ...($canView || $canDelete ? [
                ActionsColumn::make('actions')
                    ->label(__('actions.actions'))
                    ->actions(fn (BathRoomType $bathRoomType) => [
                        ...($canView ? [
                            ActionItem::link(__('actions.view'), route('bath-room-types.show', $bathRoomType), 'eye', wireNavigate: true),
                        ] : []),
                        ...($canDelete ? [
                            ActionItem::separator(),
                            ActionItem::button(__('actions.delete'), 'confirmBathRoomTypeDeletion', 'trash', 'danger'),
                        ] : []),
                    ]),
            ] : []),
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

    protected function orderColumnName(): string
    {
        return 'sort_order';
    }

    /**
     * @return class-string<BathRoomType>
     */
    protected function orderModelClass(): string
    {
        return BathRoomType::class;
    }

    /**
     * @return list<string>
     */
    protected function searchableFields(): array
    {
        return ['name', 'en_name', 'es_name', 'description'];
    }

    /**
     * @return list<TableAction>
     */
    protected function actions(): array
    {
        if (! $this->actor()->can('create', BathRoomType::class)) {
            return [];
        }

        return [
            TableAction::make('create')
                ->label(__('bath_room_types.index.create_action'))
                ->icon('plus')
                ->wireClick('openCreateBathRoomTypeModal')
                ->variant('primary')
                ->responsive(),
        ];
    }

    /**
     * @return LengthAwarePaginator<int, BathRoomType>
     */
    #[Computed]
    public function bathRoomTypes(): LengthAwarePaginator
    {
        return $this->paginatedQuery($this->baseQuery());
    }

    public function openCreateBathRoomTypeModal(): void
    {
        Gate::forUser($this->actor())->authorize('create', BathRoomType::class);

        ModalService::form(
            $this,
            name: 'bath-room-types.create',
            title: __('bath_room_types.create.title'),
            description: __('bath_room_types.create.description'),
        );
    }

    public function confirmBathRoomTypeDeletion(int $bathRoomTypeId): void
    {
        if ($this->throttle('delete')) {
            return;
        }

        $actor = $this->actor();
        $bathRoomType = $this->findBathRoomType($bathRoomTypeId);

        Gate::forUser($actor)->authorize('delete', $bathRoomType);

        $this->bathRoomTypeIdPendingDeletion = $bathRoomType->id;

        ModalService::confirm(
            $this,
            title: __('bath_room_types.index.confirm_delete.title'),
            message: __('bath_room_types.index.confirm_delete.message', ['bath_room_type' => $this->bathRoomTypeLabel($bathRoomType)]),
            confirmLabel: __('bath_room_types.index.confirm_delete.confirm_label'),
            variant: ModalService::VARIANT_PASSWORD,
        );
    }

    #[On('modal-confirmed')]
    public function deleteBathRoomType(DeleteBathRoomType $deleteBathRoomType): void
    {
        if ($this->throttle('delete')) {
            return;
        }

        $bathRoomType = $this->pendingDeletionBathRoomType();
        $bathRoomTypeLabel = $this->bathRoomTypeLabel($bathRoomType);

        $deleteBathRoomType->handle($this->actor(), $bathRoomType);

        $this->bathRoomTypeIdPendingDeletion = null;
        $this->syncCurrentPage($this->baseQuery());

        ToastService::success(__('bath_room_types.index.deleted', ['bath_room_type' => $bathRoomTypeLabel]));
    }

    #[On('modal-confirm-cancelled')]
    public function resetPendingDeletion(): void
    {
        $this->bathRoomTypeIdPendingDeletion = null;
    }

    #[On('bath-room-type-created')]
    public function refreshBathRoomTypes(): void
    {
        $this->resetPage();
    }

    private function pendingDeletionBathRoomType(): BathRoomType
    {
        abort_if($this->bathRoomTypeIdPendingDeletion === null, 404);

        return $this->findBathRoomType($this->bathRoomTypeIdPendingDeletion);
    }

    private function bathRoomTypeLabel(BathRoomType $bathRoomType): string
    {
        return __('bath_room_types.bath_room_type_label', [
            'name' => $bathRoomType->localizedName(),
            'id' => $bathRoomType->id,
        ]);
    }

    private function findBathRoomType(int $bathRoomTypeId): BathRoomType
    {
        return BathRoomType::query()->findOrFail($bathRoomTypeId);
    }

    /**
     * @return Builder<BathRoomType>
     */
    private function baseQuery(): Builder
    {
        return BathRoomType::query();
    }
};
