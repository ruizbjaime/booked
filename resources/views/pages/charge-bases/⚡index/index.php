<?php

use App\Actions\ChargeBases\DeleteChargeBasis;
use App\Actions\ChargeBases\UpdateChargeBasis;
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
use App\Domain\Table\Columns\ToggleColumn;
use App\Domain\Table\TableAction;
use App\Infrastructure\UiFeedback\ModalService;
use App\Infrastructure\UiFeedback\ToastService;
use App\Models\ChargeBasis;
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

    private const string THROTTLE_KEY_PREFIX = 'charge-basis-mgmt';

    #[Locked]
    public ?int $chargeBasisIdPendingDeletion = null;

    public function mount(): void
    {
        Gate::authorize('viewAny', ChargeBasis::class);
    }

    /**
     * @return list<Column>
     */
    protected function columns(): array
    {
        $actor = $this->actor();
        $canUpdate = $actor->can('update', new ChargeBasis);
        $canView = $actor->can('view', new ChargeBasis);
        $canDelete = $actor->can('delete', new ChargeBasis);

        return [
            IdColumn::make('id')
                ->label('#'),

            ToggleColumn::make('is_active')
                ->label(__('charge_bases.index.columns.active'))
                ->wireChange('toggleChargeBasisActiveStatus')
                ->disabled(! $canUpdate)
                ->idPrefix('charge-basis-active'),

            LinkColumn::make(ChargeBasis::localizedNameColumn())
                ->label(__('charge_bases.index.columns.name'))
                ->href(fn (ChargeBasis $chargeBasis) => $canView ? route('charge-bases.show', $chargeBasis) : null)
                ->wireNavigate()
                ->cardZone(CardZone::Header)
                ->sortable(),

            BadgeColumn::make('slug')
                ->label(__('charge_bases.index.columns.slug')),

            TextColumn::make('order')
                ->label(__('charge_bases.index.columns.order'))
                ->sortable(),

            DateColumn::make('created_at')
                ->label(__('charge_bases.index.columns.created'))
                ->sortable()
                ->defaultSortDirection('desc'),

            ...($canView || $canDelete ? [
                ActionsColumn::make('actions')
                    ->label(__('actions.actions'))
                    ->actions(fn (ChargeBasis $chargeBasis) => [
                        ...($canView ? [
                            ActionItem::link(__('actions.view'), route('charge-bases.show', $chargeBasis), 'eye', wireNavigate: true),
                        ] : []),
                        ...($canDelete ? [
                            ActionItem::separator(),
                            ActionItem::button(__('actions.delete'), 'confirmChargeBasisDeletion', 'trash', 'danger'),
                        ] : []),
                    ]),
            ] : []),
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

    protected function orderColumnName(): string
    {
        return 'order';
    }

    /**
     * @return class-string<ChargeBasis>
     */
    protected function orderModelClass(): string
    {
        return ChargeBasis::class;
    }

    /**
     * @return list<string>
     */
    protected function searchableFields(): array
    {
        return ['slug', 'en_name', 'es_name'];
    }

    /**
     * @return list<TableAction>
     */
    protected function actions(): array
    {
        if (! $this->actor()->can('create', ChargeBasis::class)) {
            return [];
        }

        return [
            TableAction::make('create')
                ->label(__('charge_bases.index.create_action'))
                ->icon('plus')
                ->wireClick('openCreateChargeBasisModal')
                ->variant('primary')
                ->responsive(),
        ];
    }

    /**
     * @return LengthAwarePaginator<int, ChargeBasis>
     */
    #[Computed]
    public function chargeBases(): LengthAwarePaginator
    {
        return $this->paginatedQuery($this->baseQuery());
    }

    public function openCreateChargeBasisModal(): void
    {
        Gate::forUser($this->actor())->authorize('create', ChargeBasis::class);

        ModalService::form(
            $this,
            name: 'charge-bases.create',
            title: __('charge_bases.create.title'),
            description: __('charge_bases.create.description'),
        );
    }

    public function toggleChargeBasisActiveStatus(int $chargeBasisId, string $field, bool $isActive): void
    {
        if ($this->throttle('toggle-active')) {
            return;
        }

        $chargeBasis = $this->findChargeBasis($chargeBasisId);

        app(UpdateChargeBasis::class)->handle($this->actor(), $chargeBasis, 'is_active', $isActive);

        $messageKey = match ($isActive) {
            true => 'charge_bases.index.activated',
            false => 'charge_bases.index.deactivated',
        };

        ToastService::success(__($messageKey, ['charge_basis' => $this->chargeBasisLabel($chargeBasis)]));

        $this->resetPage();
    }

    public function confirmChargeBasisDeletion(int $chargeBasisId): void
    {
        if ($this->throttle('delete')) {
            return;
        }

        $actor = $this->actor();
        $chargeBasis = $this->findChargeBasis($chargeBasisId);

        Gate::forUser($actor)->authorize('delete', $chargeBasis);

        $this->chargeBasisIdPendingDeletion = $chargeBasis->id;

        ModalService::confirm(
            $this,
            title: __('charge_bases.index.confirm_delete.title'),
            message: __('charge_bases.index.confirm_delete.message', ['charge_basis' => $this->chargeBasisLabel($chargeBasis)]),
            confirmLabel: __('charge_bases.index.confirm_delete.confirm_label'),
            variant: ModalService::VARIANT_PASSWORD,
        );
    }

    #[On('modal-confirmed')]
    public function deleteChargeBasis(DeleteChargeBasis $deleteChargeBasis): void
    {
        if ($this->throttle('delete')) {
            return;
        }

        $chargeBasis = $this->pendingDeletionChargeBasis();
        $chargeBasisLabel = $this->chargeBasisLabel($chargeBasis);

        $deleteChargeBasis->handle($this->actor(), $chargeBasis);

        $this->chargeBasisIdPendingDeletion = null;
        $this->syncCurrentPage($this->baseQuery());

        ToastService::success(__('charge_bases.index.deleted', ['charge_basis' => $chargeBasisLabel]));
    }

    #[On('modal-confirm-cancelled')]
    public function resetPendingDeletion(): void
    {
        $this->chargeBasisIdPendingDeletion = null;
    }

    #[On('charge-basis-created')]
    public function refreshChargeBases(): void
    {
        $this->resetPage();
    }

    private function pendingDeletionChargeBasis(): ChargeBasis
    {
        abort_if($this->chargeBasisIdPendingDeletion === null, 404);

        return $this->findChargeBasis($this->chargeBasisIdPendingDeletion);
    }

    private function chargeBasisLabel(ChargeBasis $chargeBasis): string
    {
        return __('charge_bases.charge_basis_label', [
            'name' => $chargeBasis->localizedName(),
            'id' => $chargeBasis->id,
        ]);
    }

    private function findChargeBasis(int $chargeBasisId): ChargeBasis
    {
        return ChargeBasis::query()->findOrFail($chargeBasisId);
    }

    /**
     * @return Builder<ChargeBasis>
     */
    private function baseQuery(): Builder
    {
        return ChargeBasis::query();
    }
};
