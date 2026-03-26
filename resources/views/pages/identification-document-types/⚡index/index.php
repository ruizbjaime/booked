<?php

use App\Actions\IdentificationDocumentTypes\DeleteIdentificationDocumentType;
use App\Actions\IdentificationDocumentTypes\ToggleIdentificationDocumentTypeActiveStatus;
use App\Concerns\InteractsWithTable;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Concerns\ThrottlesFormActions;
use App\Concerns\WithSortableRows;
use App\Domain\Table\ActionItem;
use App\Domain\Table\Column;
use App\Domain\Table\Columns\ActionsColumn;
use App\Domain\Table\Columns\AvatarColumn;
use App\Domain\Table\Columns\BadgeColumn;
use App\Domain\Table\Columns\DateColumn;
use App\Domain\Table\Columns\IdColumn;
use App\Domain\Table\Columns\TextColumn;
use App\Domain\Table\Columns\ToggleColumn;
use App\Domain\Table\TableAction;
use App\Infrastructure\UiFeedback\ModalService;
use App\Infrastructure\UiFeedback\ToastService;
use App\Models\IdentificationDocumentType;
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

    private const string THROTTLE_KEY_PREFIX = 'doc-type-mgmt';

    #[Locked]
    public ?int $docTypeIdPendingDeletion = null;

    public function mount(): void
    {
        Gate::authorize('viewAny', IdentificationDocumentType::class);
    }

    /**
     * @return list<Column>
     */
    protected function columns(): array
    {
        $actor = $this->actor();
        $canUpdate = $actor->can('update', new IdentificationDocumentType);
        $canView = $actor->can('view', new IdentificationDocumentType);
        $canDelete = $actor->can('delete', new IdentificationDocumentType);

        return [
            IdColumn::make('id')
                ->label('#'),

            ToggleColumn::make('is_active')
                ->label(__('identification_document_types.index.columns.active'))
                ->wireChange('toggleDocTypeActiveStatus')
                ->disabled(! $canUpdate)
                ->idPrefix('doc-type-active'),

            AvatarColumn::make(IdentificationDocumentType::localizedNameColumn())
                ->label(__('identification_document_types.index.columns.name'))
                ->sortable()
                ->initials(fn (IdentificationDocumentType $dt) => $dt->code)
                ->colorSeed(fn (IdentificationDocumentType $dt) => $dt->id)
                ->recordUrl(fn (IdentificationDocumentType $dt) => $canView ? route('identification-document-types.show', $dt) : null)
                ->wireNavigate(),

            BadgeColumn::make('code')
                ->label(__('identification_document_types.index.columns.code')),

            TextColumn::make('sort_order')
                ->label(__('identification_document_types.index.columns.sort_order'))
                ->sortable(),

            DateColumn::make('created_at')
                ->label(__('identification_document_types.index.columns.created'))
                ->sortable()
                ->defaultSortDirection('desc'),

            ...($canView || $canDelete ? [
                ActionsColumn::make('actions')
                    ->label(__('actions.actions'))
                    ->actions(fn (IdentificationDocumentType $dt) => [
                        ...($canView ? [
                            ActionItem::link(__('actions.view'), route('identification-document-types.show', $dt), 'eye', wireNavigate: true),
                        ] : []),
                        ...($canDelete ? [
                            ActionItem::separator(),
                            ActionItem::button(__('actions.delete'), 'confirmDocTypeDeletion', 'trash', 'danger'),
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
     * @return class-string<IdentificationDocumentType>
     */
    protected function orderModelClass(): string
    {
        return IdentificationDocumentType::class;
    }

    /**
     * @return list<string>
     */
    protected function searchableFields(): array
    {
        return ['code', 'en_name', 'es_name'];
    }

    /**
     * @return list<TableAction>
     */
    protected function actions(): array
    {
        if (! $this->actor()->can('create', IdentificationDocumentType::class)) {
            return [];
        }

        return [
            TableAction::make('create')
                ->label(__('identification_document_types.index.create_action'))
                ->icon('plus')
                ->wireClick('openCreateDocTypeModal')
                ->variant('primary')
                ->responsive(),
        ];
    }

    /**
     * @return LengthAwarePaginator<int, IdentificationDocumentType>
     */
    #[Computed]
    public function docTypes(): LengthAwarePaginator
    {
        return $this->paginatedQuery($this->baseQuery());
    }

    public function toggleDocTypeActiveStatus(int $docTypeId, string $field, bool $isActive): void
    {
        if ($this->throttle('toggle-active')) {
            return;
        }

        $docType = $this->findDocType($docTypeId);

        app(ToggleIdentificationDocumentTypeActiveStatus::class)->handle($this->actor(), $docType, $isActive);

        $messageKey = match ($isActive) {
            true => 'identification_document_types.index.activated',
            false => 'identification_document_types.index.deactivated',
        };

        ToastService::success(__($messageKey, ['doc_type' => $this->docTypeLabel($docType)]));
    }

    public function openCreateDocTypeModal(): void
    {
        Gate::forUser($this->actor())->authorize('create', IdentificationDocumentType::class);

        ModalService::form(
            $this,
            name: 'identification-document-types.create',
            title: __('identification_document_types.create.title'),
            description: __('identification_document_types.create.description'),
        );
    }

    public function confirmDocTypeDeletion(int $docTypeId): void
    {
        if ($this->throttle('delete', 5)) {
            return;
        }

        $actor = $this->actor();
        $docType = $this->findDocType($docTypeId);

        Gate::forUser($actor)->authorize('delete', $docType);

        $this->docTypeIdPendingDeletion = $docType->id;
        $docTypeLabel = $this->docTypeLabel($docType);
        $hasUsers = $docType->users()->exists();

        $prefix = $hasUsers ? 'identification_document_types.index.confirm_deactivate' : 'identification_document_types.index.confirm_delete';

        ModalService::confirm(
            $this,
            title: __("{$prefix}.title"),
            message: __("{$prefix}.message", ['doc_type' => $docTypeLabel]),
            confirmLabel: __("{$prefix}.confirm_label"),
            variant: ModalService::VARIANT_PASSWORD,
        );
    }

    #[On('modal-confirmed')]
    public function deleteDocType(DeleteIdentificationDocumentType $deleteDocType): void
    {
        if ($this->throttle('delete', 5)) {
            return;
        }

        $docType = $this->pendingDeletionDocType();
        $docTypeLabel = $this->docTypeLabel($docType);

        $wasDeleted = $deleteDocType->handle($this->actor(), $docType);

        $this->docTypeIdPendingDeletion = null;

        if ($wasDeleted) {
            $this->syncCurrentPage($this->baseQuery());

            ToastService::success(__('identification_document_types.index.deleted', ['doc_type' => $docTypeLabel]));
        } else {
            unset($this->docTypes);

            ToastService::success(__('identification_document_types.index.deactivated_instead', ['doc_type' => $docTypeLabel]));
        }
    }

    #[On('modal-confirm-cancelled')]
    public function resetPendingDeletion(): void
    {
        $this->docTypeIdPendingDeletion = null;
    }

    #[On('doc-type-created')]
    public function refreshDocTypes(): void
    {
        $this->resetPage();
    }

    private function pendingDeletionDocType(): IdentificationDocumentType
    {
        abort_if($this->docTypeIdPendingDeletion === null, 404);

        return $this->findDocType($this->docTypeIdPendingDeletion);
    }

    private function docTypeLabel(IdentificationDocumentType $docType): string
    {
        return __('identification_document_types.doc_type_label', [
            'name' => $docType->localizedName(),
            'id' => $docType->id,
        ]);
    }

    private function findDocType(int $docTypeId): IdentificationDocumentType
    {
        return IdentificationDocumentType::query()->findOrFail($docTypeId);
    }

    /**
     * @return Builder<IdentificationDocumentType>
     */
    private function baseQuery(): Builder
    {
        return IdentificationDocumentType::query();
    }
};
