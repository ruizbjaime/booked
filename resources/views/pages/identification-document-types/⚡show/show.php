<?php

use App\Actions\IdentificationDocumentTypes\DeleteIdentificationDocumentType;
use App\Actions\IdentificationDocumentTypes\ToggleIdentificationDocumentTypeActiveStatus;
use App\Actions\IdentificationDocumentTypes\UpdateIdentificationDocumentType;
use App\Concerns\FormatsLocalizedDates;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Concerns\ThrottlesFormActions;
use App\Infrastructure\UiFeedback\ModalService;
use App\Infrastructure\UiFeedback\ToastService;
use App\Models\IdentificationDocumentType;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    use FormatsLocalizedDates;
    use ResolvesAuthenticatedUser;
    use ThrottlesFormActions;

    private const string THROTTLE_KEY_PREFIX = 'doc-type-mgmt';

    private const string SECTION_DETAILS = 'details';

    /** @var list<string> */
    private const array AUTOSAVE_FIELDS = ['code', 'en_name', 'es_name', 'sort_order'];

    public IdentificationDocumentType $targetDocType;

    public ?string $editingSection = null;

    #[Locked]
    public ?int $docTypeIdPendingDeletion = null;

    public string $code = '';

    public string $en_name = '';

    public string $es_name = '';

    public int $sort_order = 0;

    public bool $is_active = false;

    public function mount(string $identificationDocumentType): void
    {
        $target = IdentificationDocumentType::query()->findOrFail($identificationDocumentType);

        Gate::authorize('view', $target);

        $this->targetDocType = $target;
        $this->fillForm($target);
    }

    #[Computed]
    public function docType(): IdentificationDocumentType
    {
        return $this->targetDocType;
    }

    #[Computed]
    public function associatedUsersCount(): int
    {
        return $this->targetDocType->users()->count();
    }

    public function startEditingSection(string $section): void
    {
        abort_unless($section === self::SECTION_DETAILS, 404);

        $this->authorizeDocTypeUpdate();

        $this->editingSection = $section;
        $this->fillForm($this->docType());
        $this->resetValidation();
    }

    public function cancelEditingSection(): void
    {
        $this->editingSection = null;
        $this->fillForm($this->docType());
        $this->resetValidation();
    }

    public function updated(string $property): void
    {
        if (in_array($property, self::AUTOSAVE_FIELDS, true)) {
            $this->autosaveField($property);
        }
    }

    public function updatedIsActive(): void
    {
        if ($this->editingSection !== self::SECTION_DETAILS) {
            return;
        }

        if ($this->throttle('toggle-active')) {
            return;
        }

        $this->authorizeDocTypeUpdate();

        app(ToggleIdentificationDocumentTypeActiveStatus::class)->handle($this->actor(), $this->docType(), $this->is_active);

        $this->refreshDocTypeState();

        ToastService::success(__('identification_document_types.show.saved.active'));
    }

    public function confirmDocTypeDeletion(): void
    {
        if ($this->throttle('delete')) {
            return;
        }

        $actor = $this->actor();
        $docType = $this->docType();

        Gate::forUser($actor)->authorize('delete', $docType);

        $this->docTypeIdPendingDeletion = $docType->id;
        $docTypeLabel = $this->docTypeLabel();
        $hasUsers = $docType->users()->exists();

        $prefix = $hasUsers ? 'identification_document_types.show.quick_actions.deactivate' : 'identification_document_types.show.quick_actions.delete';

        ModalService::confirm(
            $this,
            title: __("{$prefix}.title"),
            message: __("{$prefix}.message", ['doc_type' => $docTypeLabel]),
            confirmLabel: __("{$prefix}.confirm_label"),
            variant: ModalService::VARIANT_PASSWORD,
        );
    }

    #[On('modal-confirmed')]
    public function handleModalConfirmed(DeleteIdentificationDocumentType $deleteDocType): void
    {
        if ($this->throttle('confirmed-action')) {
            return;
        }

        if ($this->docTypeIdPendingDeletion === null) {
            return;
        }

        $docTypeLabel = $this->docTypeLabel();

        $wasDeleted = $deleteDocType->handle($this->actor(), $this->docType());

        $this->docTypeIdPendingDeletion = null;

        if ($wasDeleted) {
            ToastService::success(__('identification_document_types.show.quick_actions.delete.deleted', ['doc_type' => $docTypeLabel]));

            $this->redirect(route('identification-document-types.index'), navigate: true);
        } else {
            $this->refreshDocTypeState();

            ToastService::success(__('identification_document_types.show.quick_actions.deactivate.deactivated', ['doc_type' => $docTypeLabel]));
        }
    }

    #[On('modal-confirm-cancelled')]
    public function resetPendingSensitiveActions(): void
    {
        $this->docTypeIdPendingDeletion = null;
    }

    public function canEdit(): bool
    {
        return Gate::forUser($this->actor())->allows('update', $this->docType());
    }

    public function canDelete(): bool
    {
        return Gate::forUser($this->actor())->allows('delete', $this->docType());
    }

    private function autosaveField(string $property): void
    {
        if ($this->editingSection !== self::SECTION_DETAILS) {
            return;
        }

        if ($this->throttle('autosave')) {
            return;
        }

        $this->authorizeDocTypeUpdate();
        $this->resetValidation($property);

        try {
            app(UpdateIdentificationDocumentType::class)->handle($this->actor(), $this->docType(), $property, $this->$property);
        } catch (ValidationException $exception) {
            $this->fillForm($this->docType());

            throw $exception;
        }

        $this->refreshDocTypeState();

        ToastService::success(__('identification_document_types.show.saved.details'));
    }

    private function refreshDocTypeState(): void
    {
        $this->targetDocType = IdentificationDocumentType::query()->where('id', $this->targetDocType->getKey())->firstOrFail();

        $this->fillForm($this->targetDocType);
    }

    private function fillForm(IdentificationDocumentType $docType): void
    {
        $this->code = $docType->code;
        $this->en_name = $docType->en_name;
        $this->es_name = $docType->es_name;
        $this->sort_order = (int) $docType->sort_order;
        $this->is_active = $docType->is_active;
    }

    private function authorizeDocTypeUpdate(): void
    {
        Gate::forUser($this->actor())->authorize('update', $this->docType());
    }

    private function docTypeLabel(): string
    {
        return __('identification_document_types.doc_type_label', [
            'name' => $this->docType()->localizedName(),
            'id' => $this->docType()->id,
        ]);
    }
};
