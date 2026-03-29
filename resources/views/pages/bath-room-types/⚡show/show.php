<?php

use App\Actions\BathRoomTypes\DeleteBathRoomType;
use App\Actions\BathRoomTypes\UpdateBathRoomType;
use App\Concerns\FormatsLocalizedDates;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Concerns\ThrottlesFormActions;
use App\Infrastructure\UiFeedback\ModalService;
use App\Infrastructure\UiFeedback\ToastService;
use App\Models\BathRoomType;
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

    private const string THROTTLE_KEY_PREFIX = 'bath-room-type-mgmt';

    private const string SECTION_DETAILS = 'details';

    /** @var list<string> */
    private const array AUTOSAVE_FIELDS = ['en_name', 'es_name', 'description', 'sort_order'];

    public BathRoomType $targetBathRoomType;

    public ?string $editingSection = null;

    #[Locked]
    public ?int $bathRoomTypeIdPendingDeletion = null;

    public string $en_name = '';

    public string $es_name = '';

    public string $description = '';

    public int $sort_order = 0;

    public function mount(string $bathRoomType): void
    {
        $target = BathRoomType::query()->findOrFail($bathRoomType);

        Gate::authorize('view', $target);

        $this->targetBathRoomType = $target;
        $this->fillForm($target);
    }

    #[Computed]
    public function bathRoomType(): BathRoomType
    {
        return $this->targetBathRoomType;
    }

    public function startEditingSection(string $section): void
    {
        abort_unless($section === self::SECTION_DETAILS, 404);

        $this->authorizeBathRoomTypeUpdate();

        $this->editingSection = $section;
        $this->fillForm($this->bathRoomType());
        $this->resetValidation();
    }

    public function cancelEditingSection(): void
    {
        $this->editingSection = null;
        $this->fillForm($this->bathRoomType());
        $this->resetValidation();
    }

    public function updated(string $property): void
    {
        if (in_array($property, self::AUTOSAVE_FIELDS, true)) {
            $this->autosaveField($property);
        }
    }

    public function confirmBathRoomTypeDeletion(): void
    {
        if ($this->throttle('delete')) {
            return;
        }

        $actor = $this->actor();
        $bathRoomType = $this->bathRoomType();

        Gate::forUser($actor)->authorize('delete', $bathRoomType);

        $this->bathRoomTypeIdPendingDeletion = $bathRoomType->id;

        ModalService::confirm(
            $this,
            title: __('bath_room_types.show.quick_actions.delete.title'),
            message: __('bath_room_types.show.quick_actions.delete.message', ['bath_room_type' => $this->bathRoomTypeLabel()]),
            confirmLabel: __('bath_room_types.show.quick_actions.delete.confirm_label'),
            variant: ModalService::VARIANT_PASSWORD,
        );
    }

    #[On('modal-confirmed')]
    public function handleModalConfirmed(DeleteBathRoomType $deleteBathRoomType): void
    {
        if ($this->throttle('confirmed-action')) {
            return;
        }

        if ($this->bathRoomTypeIdPendingDeletion === null) {
            return;
        }

        $bathRoomTypeLabel = $this->bathRoomTypeLabel();

        $deleteBathRoomType->handle($this->actor(), $this->bathRoomType());

        $this->bathRoomTypeIdPendingDeletion = null;

        ToastService::success(__('bath_room_types.show.quick_actions.delete.deleted', ['bath_room_type' => $bathRoomTypeLabel]));

        $this->redirect(route('bath-room-types.index'), navigate: true);
    }

    #[On('modal-confirm-cancelled')]
    public function resetPendingSensitiveActions(): void
    {
        $this->bathRoomTypeIdPendingDeletion = null;
    }

    public function canEdit(): bool
    {
        return Gate::forUser($this->actor())->allows('update', $this->bathRoomType());
    }

    public function canDelete(): bool
    {
        return Gate::forUser($this->actor())->allows('delete', $this->bathRoomType());
    }

    private function autosaveField(string $property): void
    {
        if ($this->editingSection !== self::SECTION_DETAILS) {
            return;
        }

        if ($this->throttle('autosave')) {
            return;
        }

        $this->authorizeBathRoomTypeUpdate();
        $this->resetValidation($property);

        try {
            app(UpdateBathRoomType::class)->handle($this->actor(), $this->bathRoomType(), $property, $this->$property);
        } catch (ValidationException $exception) {
            $this->fillForm($this->bathRoomType());

            throw $exception;
        }

        $this->refreshBathRoomTypeState();

        ToastService::success(__('bath_room_types.show.saved.details'));
    }

    private function refreshBathRoomTypeState(): void
    {
        $this->targetBathRoomType = BathRoomType::query()->where('id', $this->targetBathRoomType->getKey())->firstOrFail();

        $this->fillForm($this->targetBathRoomType);
    }

    private function fillForm(BathRoomType $bathRoomType): void
    {
        $this->en_name = $bathRoomType->en_name;
        $this->es_name = $bathRoomType->es_name;
        $this->description = $bathRoomType->description;
        $this->sort_order = (int) $bathRoomType->sort_order;
    }

    private function authorizeBathRoomTypeUpdate(): void
    {
        Gate::forUser($this->actor())->authorize('update', $this->bathRoomType());
    }

    private function bathRoomTypeLabel(): string
    {
        return __('bath_room_types.bath_room_type_label', [
            'name' => $this->bathRoomType()->localizedName(),
            'id' => $this->bathRoomType()->id,
        ]);
    }
};
