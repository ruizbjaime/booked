<?php

use App\Actions\BathRoomTypes\DeleteBathRoomType;
use App\Actions\BathRoomTypes\UpdateBathRoomType;
use App\Concerns\FormatsLocalizedDates;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Infrastructure\UiFeedback\ModalService;
use App\Infrastructure\UiFeedback\ToastService;
use App\Models\BathRoomType;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    use FormatsLocalizedDates;
    use ResolvesAuthenticatedUser;

    private const string SECTION_DETAILS = 'details';

    /** @var list<string> */
    private const array AUTOSAVE_FIELDS = ['name', 'name_en', 'name_es', 'description', 'sort_order'];

    public BathRoomType $targetBathRoomType;

    public ?string $editingSection = null;

    #[Locked]
    public ?int $bathRoomTypeIdPendingDeletion = null;

    public string $name = '';

    public string $name_en = '';

    public string $name_es = '';

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
        $this->throttle('delete', 5);

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
        $this->throttle('confirmed-action', 5);

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

        $this->throttle('autosave');
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
        $this->name = $bathRoomType->name;
        $this->name_en = $bathRoomType->name_en;
        $this->name_es = $bathRoomType->name_es;
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

    private function throttle(string $action, int $maxAttempts = 10): void
    {
        $key = "bath-room-type-mgmt:{$action}:{$this->actor()->id}";

        abort_if(RateLimiter::tooManyAttempts($key, $maxAttempts), 429);

        RateLimiter::hit($key, 60);
    }
};
