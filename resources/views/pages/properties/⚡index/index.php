<?php

use App\Actions\Properties\DeleteProperty;
use App\Concerns\InteractsWithTable;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Concerns\ThrottlesFormActions;
use App\Domain\Table\ActionItem;
use App\Domain\Table\Column;
use App\Domain\Table\Columns\ActionsColumn;
use App\Domain\Table\Columns\AvatarColumn;
use App\Domain\Table\Columns\BooleanColumn;
use App\Domain\Table\Columns\DateColumn;
use App\Domain\Table\Columns\IdColumn;
use App\Domain\Table\Columns\TextColumn;
use App\Domain\Table\TableAction;
use App\Infrastructure\UiFeedback\ModalService;
use App\Infrastructure\UiFeedback\ToastService;
use App\Models\Property;
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

    private const string THROTTLE_KEY_PREFIX = 'property-mgmt';

    #[Locked]
    public ?int $propertyIdPendingDeletion = null;

    public function mount(): void
    {
        Gate::authorize('viewAny', Property::class);
    }

    /**
     * @return list<Column>
     */
    protected function columns(): array
    {
        $actor = $this->actor();
        $canView = $actor->can('view', new Property);
        $canDelete = $actor->can('delete', new Property);

        return [
            IdColumn::make('id')
                ->label('#'),

            AvatarColumn::make('name')
                ->label(__('properties.index.columns.name'))
                ->sortable()
                ->avatarSrc(fn (Property $property) => $property->avatarUrl())
                ->initials(fn (Property $property) => $property->initials())
                ->colorSeed(fn (Property $property) => $property->id)
                ->recordUrl(fn (Property $property) => $canView ? route('properties.show', $property) : null)
                ->wireNavigate(),

            TextColumn::make('slug')
                ->label(__('properties.index.columns.slug')),

            TextColumn::make('city')
                ->label(__('properties.index.columns.city'))
                ->sortable(),

            TextColumn::make('address')
                ->label(__('properties.index.columns.address')),

            TextColumn::make('country.en_name')
                ->label(__('properties.index.columns.country'))
                ->formatUsing(fn (mixed $value, Property $property) => $property->country?->localizedName())
                ->sortable(),

            BooleanColumn::make('is_active')
                ->label(__('properties.index.columns.active'))
                ->trueLabel(__('properties.index.status.active'))
                ->falseLabel(__('properties.index.status.inactive'))
                ->trueColor('emerald')
                ->falseColor('zinc'),

            DateColumn::make('created_at')
                ->label(__('properties.index.columns.created'))
                ->sortable()
                ->defaultSortDirection('desc'),

            ...($canView ? [
                ActionsColumn::make('actions')
                    ->label(__('actions.actions'))
                    ->actions(fn (Property $property) => [
                        ActionItem::link(__('actions.view'), route('properties.show', $property), 'eye', wireNavigate: true),
                        ActionItem::button(__('actions.delete'), 'confirmPropertyDeletion', 'trash', 'danger')
                            ->visible(fn () => $canDelete),
                    ]),
            ] : []),
        ];
    }

    protected function defaultSortBy(): string
    {
        return 'name';
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
        return ['slug', 'name', 'city', 'address', 'country.en_name', 'country.es_name'];
    }

    /**
     * @return list<TableAction>
     */
    protected function actions(): array
    {
        if (! $this->actor()->can('create', Property::class)) {
            return [];
        }

        return [
            TableAction::make('create')
                ->label(__('properties.index.create_action'))
                ->icon('plus')
                ->wireClick('openCreatePropertyModal')
                ->variant('primary')
                ->responsive(),
        ];
    }

    /**
     * @return LengthAwarePaginator<int, Property>
     */
    #[Computed]
    public function properties(): LengthAwarePaginator
    {
        return $this->paginatedQuery($this->baseQuery());
    }

    public function openCreatePropertyModal(): void
    {
        Gate::forUser($this->actor())->authorize('create', Property::class);

        ModalService::form(
            $this,
            name: 'properties.create',
            title: __('properties.create.title'),
            description: __('properties.create.description'),
        );
    }

    public function confirmPropertyDeletion(int $propertyId): void
    {
        if ($this->throttle('delete')) {
            return;
        }

        $property = $this->findProperty($propertyId);

        Gate::forUser($this->actor())->authorize('delete', $property);

        $this->propertyIdPendingDeletion = $property->id;

        ModalService::confirm(
            $this,
            title: __('properties.index.confirm_delete.title'),
            message: __('properties.index.confirm_delete.message', ['property' => $property->label()]),
            confirmLabel: __('properties.index.confirm_delete.confirm_label'),
            variant: ModalService::VARIANT_PASSWORD,
        );
    }

    #[On('modal-confirmed')]
    public function deleteProperty(DeleteProperty $deleteProperty): void
    {
        if ($this->throttle('delete')) {
            return;
        }

        $property = $this->pendingDeletionProperty();
        $label = $property->label();

        $deleteProperty->handle($this->actor(), $property);

        $this->propertyIdPendingDeletion = null;
        $this->syncCurrentPage($this->baseQuery());

        ToastService::success(__('properties.index.deleted', ['property' => $label]));
    }

    #[On('modal-confirm-cancelled')]
    public function resetPendingDeletion(): void
    {
        $this->propertyIdPendingDeletion = null;
    }

    #[On('property-created')]
    public function refreshProperties(): void
    {
        $this->resetPage();
    }

    private function pendingDeletionProperty(): Property
    {
        abort_if($this->propertyIdPendingDeletion === null, 404);

        return $this->findProperty($this->propertyIdPendingDeletion);
    }

    private function findProperty(int $propertyId): Property
    {
        return Property::query()
            ->ownedBy($this->actor())
            ->findOrFail($propertyId);
    }

    /**
     * @return Builder<Property>
     */
    private function baseQuery(): Builder
    {
        return Property::query()
            ->ownedBy($this->actor())
            ->with(['country', 'media']);
    }
};
