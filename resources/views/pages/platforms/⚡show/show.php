<?php

use App\Actions\Platforms\CreatePlatform;
use App\Actions\Platforms\DeletePlatform;
use App\Actions\Platforms\TogglePlatformActiveStatus;
use App\Actions\Platforms\UpdatePlatform;
use App\Concerns\FormatsLocalizedDates;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Concerns\ThrottlesFormActions;
use App\Infrastructure\UiFeedback\ModalService;
use App\Infrastructure\UiFeedback\ToastService;
use App\Models\Platform;
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

    private const string THROTTLE_KEY_PREFIX = 'platform-mgmt';

    private const string SECTION_DETAILS = 'details';

    /** @var list<string> */
    private const array AUTOSAVE_FIELDS = ['name', 'en_name', 'es_name', 'color', 'sort_order', 'commission', 'commission_tax'];

    public Platform $targetPlatform;

    public ?string $editingSection = null;

    #[Locked]
    public ?int $platformIdPendingDeletion = null;

    public string $name = '';

    public string $en_name = '';

    public string $es_name = '';

    public string $color = 'zinc';

    public string $colorMode = 'preset';

    public string $customColor = '';

    public int $sort_order = 0;

    public float $commission = 0;

    public float $commission_tax = 0;

    public bool $is_active = false;

    public function mount(string $platform): void
    {
        $target = Platform::query()->findOrFail($platform);

        Gate::authorize('view', $target);

        $this->targetPlatform = $target;
        $this->fillForm($target);
    }

    #[Computed]
    public function platform(): Platform
    {
        return $this->targetPlatform;
    }

    public function startEditingSection(string $section): void
    {
        abort_unless($section === self::SECTION_DETAILS, 404);

        $this->authorizePlatformUpdate();

        $this->editingSection = $section;
        $this->fillForm($this->platform());
        $this->resetValidation();
    }

    public function cancelEditingSection(): void
    {
        $this->editingSection = null;
        $this->fillForm($this->platform());
        $this->resetValidation();
    }

    public function updated(string $property): void
    {
        if ($property === 'colorMode') {
            $this->handleColorModeChange();

            return;
        }

        if ($property === 'customColor') {
            $this->handleCustomColorChange();

            return;
        }

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

        $this->authorizePlatformUpdate();

        app(TogglePlatformActiveStatus::class)->handle($this->actor(), $this->platform(), $this->is_active);

        $this->refreshPlatformState();

        ToastService::success(__('platforms.show.saved.active'));
    }

    public function confirmPlatformDeletion(): void
    {
        if ($this->throttle('delete')) {
            return;
        }

        $actor = $this->actor();
        $platform = $this->platform();

        Gate::forUser($actor)->authorize('delete', $platform);

        $this->platformIdPendingDeletion = $platform->id;

        ModalService::confirm(
            $this,
            title: __('platforms.show.quick_actions.delete.title'),
            message: __('platforms.show.quick_actions.delete.message', ['platform' => $this->platformLabel()]),
            confirmLabel: __('platforms.show.quick_actions.delete.confirm_label'),
            variant: ModalService::VARIANT_PASSWORD,
        );
    }

    #[On('modal-confirmed')]
    public function handleModalConfirmed(DeletePlatform $deletePlatform): void
    {
        if ($this->throttle('confirmed-action')) {
            return;
        }

        if ($this->platformIdPendingDeletion === null) {
            return;
        }

        $platformLabel = $this->platformLabel();

        $deletePlatform->handle($this->actor(), $this->platform());

        $this->platformIdPendingDeletion = null;

        ToastService::success(__('platforms.show.quick_actions.delete.deleted', ['platform' => $platformLabel]));

        $this->redirect(route('platforms.index'), navigate: true);
    }

    #[On('modal-confirm-cancelled')]
    public function resetPendingSensitiveActions(): void
    {
        $this->platformIdPendingDeletion = null;
    }

    public function canEdit(): bool
    {
        return Gate::forUser($this->actor())->allows('update', $this->platform());
    }

    public function canDelete(): bool
    {
        return Gate::forUser($this->actor())->allows('delete', $this->platform());
    }

    private function handleColorModeChange(): void
    {
        if ($this->editingSection !== self::SECTION_DETAILS) {
            return;
        }

        if ($this->colorMode === 'custom') {
            return;
        }

        $this->color = $this->colorMode;
        $this->autosaveField('color');
    }

    private function handleCustomColorChange(): void
    {
        if ($this->editingSection !== self::SECTION_DETAILS) {
            return;
        }

        if ($this->colorMode !== 'custom' || $this->customColor === '') {
            return;
        }

        $this->color = $this->customColor;
        $this->autosaveField('color');
    }

    private function autosaveField(string $property): void
    {
        if ($this->editingSection !== self::SECTION_DETAILS) {
            return;
        }

        if ($this->throttle('autosave')) {
            return;
        }

        $this->authorizePlatformUpdate();
        $this->resetValidation($property);

        try {
            app(UpdatePlatform::class)->handle($this->actor(), $this->platform(), $property, $this->$property);
        } catch (ValidationException $exception) {
            $this->fillForm($this->platform());

            throw $exception;
        }

        $this->refreshPlatformState();

        ToastService::success(__('platforms.show.saved.details'));
    }

    private function refreshPlatformState(): void
    {
        $this->targetPlatform = Platform::query()->where('id', $this->targetPlatform->getKey())->firstOrFail();

        $this->fillForm($this->targetPlatform);
    }

    private function fillForm(Platform $platform): void
    {
        $this->name = $platform->name;
        $this->en_name = $platform->en_name;
        $this->es_name = $platform->es_name;
        $this->sort_order = (int) $platform->sort_order;
        $this->commission = round((float) $platform->commission * 100, 2);
        $this->commission_tax = round((float) $platform->commission_tax * 100, 2);
        $this->is_active = $platform->is_active;

        $isPreset = in_array($platform->color, CreatePlatform::AVAILABLE_COLORS, true);
        $this->colorMode = $isPreset ? $platform->color : 'custom';
        $this->color = $platform->color;
        $this->customColor = $isPreset ? '' : $platform->color;
    }

    private function authorizePlatformUpdate(): void
    {
        Gate::forUser($this->actor())->authorize('update', $this->platform());
    }

    private function platformLabel(): string
    {
        return __('platforms.platform_label', [
            'name' => $this->platform()->localizedName(),
            'id' => $this->platform()->id,
        ]);
    }
};
