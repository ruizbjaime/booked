<?php

use App\Actions\BedTypes\DeleteBedType;
use App\Actions\BedTypes\UpdateBedType;
use App\Concerns\FormatsLocalizedDates;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Concerns\ThrottlesFormActions;
use App\Infrastructure\UiFeedback\ModalService;
use App\Infrastructure\UiFeedback\ToastService;
use App\Models\BedType;
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

    private const string THROTTLE_KEY_PREFIX = 'bed-type-mgmt';

    private const string SECTION_DETAILS = 'details';

    /** @var list<string> */
    private const array AUTOSAVE_FIELDS = ['name', 'en_name', 'es_name', 'bed_capacity', 'sort_order'];

    public BedType $targetBedType;

    public ?string $editingSection = null;

    #[Locked]
    public ?int $bedTypeIdPendingDeletion = null;

    public string $name = '';

    public string $en_name = '';

    public string $es_name = '';

    public int $bed_capacity = 1;

    public int $sort_order = 0;

    public function mount(string $bedType): void
    {
        $target = BedType::query()->findOrFail($bedType);

        Gate::authorize('view', $target);

        $this->targetBedType = $target;
        $this->fillForm($target);
    }

    #[Computed]
    public function bedType(): BedType
    {
        return $this->targetBedType;
    }

    public function startEditingSection(string $section): void
    {
        abort_unless($section === self::SECTION_DETAILS, 404);

        $this->authorizeBedTypeUpdate();

        $this->editingSection = $section;
        $this->fillForm($this->bedType());
        $this->resetValidation();
    }

    public function cancelEditingSection(): void
    {
        $this->editingSection = null;
        $this->fillForm($this->bedType());
        $this->resetValidation();
    }

    public function updated(string $property): void
    {
        if (in_array($property, self::AUTOSAVE_FIELDS, true)) {
            $this->autosaveField($property);
        }
    }

    public function confirmBedTypeDeletion(): void
    {
        if ($this->throttle('delete')) {
            return;
        }

        $actor = $this->actor();
        $bedType = $this->bedType();

        Gate::forUser($actor)->authorize('delete', $bedType);

        $this->bedTypeIdPendingDeletion = $bedType->id;

        ModalService::confirm(
            $this,
            title: __('bed_types.show.quick_actions.delete.title'),
            message: __('bed_types.show.quick_actions.delete.message', ['bed_type' => $this->bedTypeLabel()]),
            confirmLabel: __('bed_types.show.quick_actions.delete.confirm_label'),
            variant: ModalService::VARIANT_PASSWORD,
        );
    }

    #[On('modal-confirmed')]
    public function handleModalConfirmed(DeleteBedType $deleteBedType): void
    {
        if ($this->throttle('confirmed-action')) {
            return;
        }

        if ($this->bedTypeIdPendingDeletion === null) {
            return;
        }

        $bedTypeLabel = $this->bedTypeLabel();

        $deleteBedType->handle($this->actor(), $this->bedType());

        $this->bedTypeIdPendingDeletion = null;

        ToastService::success(__('bed_types.show.quick_actions.delete.deleted', ['bed_type' => $bedTypeLabel]));

        $this->redirect(route('bed-types.index'), navigate: true);
    }

    #[On('modal-confirm-cancelled')]
    public function resetPendingSensitiveActions(): void
    {
        $this->bedTypeIdPendingDeletion = null;
    }

    public function canEdit(): bool
    {
        return Gate::forUser($this->actor())->allows('update', $this->bedType());
    }

    public function canDelete(): bool
    {
        return Gate::forUser($this->actor())->allows('delete', $this->bedType());
    }

    private function autosaveField(string $property): void
    {
        if ($this->editingSection !== self::SECTION_DETAILS) {
            return;
        }

        if ($this->throttle('autosave')) {
            return;
        }

        $this->authorizeBedTypeUpdate();
        $this->resetValidation($property);

        try {
            app(UpdateBedType::class)->handle($this->actor(), $this->bedType(), $property, $this->$property);
        } catch (ValidationException $exception) {
            $this->fillForm($this->bedType());

            throw $exception;
        }

        $this->refreshBedTypeState();

        ToastService::success(__('bed_types.show.saved.details'));
    }

    private function refreshBedTypeState(): void
    {
        $this->targetBedType = BedType::query()->where('id', $this->targetBedType->getKey())->firstOrFail();

        $this->fillForm($this->targetBedType);
    }

    private function fillForm(BedType $bedType): void
    {
        $this->name = $bedType->name;
        $this->en_name = $bedType->en_name;
        $this->es_name = $bedType->es_name;
        $this->bed_capacity = (int) $bedType->bed_capacity;
        $this->sort_order = (int) $bedType->sort_order;
    }

    private function authorizeBedTypeUpdate(): void
    {
        Gate::forUser($this->actor())->authorize('update', $this->bedType());
    }

    private function bedTypeLabel(): string
    {
        return __('bed_types.bed_type_label', [
            'name' => $this->bedType()->localizedName(),
            'id' => $this->bedType()->id,
        ]);
    }
};
