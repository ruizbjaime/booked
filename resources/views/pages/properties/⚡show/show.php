<?php

use App\Actions\Properties\DeleteProperty;
use App\Actions\Properties\TogglePropertyActiveStatus;
use App\Actions\Properties\UpdateProperty;
use App\Actions\Properties\UpdatePropertyAvatar;
use App\Concerns\FormatsLocalizedDates;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Concerns\ThrottlesFormActions;
use App\Infrastructure\UiFeedback\ModalService;
use App\Infrastructure\UiFeedback\ToastService;
use App\Models\Country;
use App\Models\Property;
use App\Models\SystemSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new class extends Component
{
    use FormatsLocalizedDates;
    use ResolvesAuthenticatedUser;
    use ThrottlesFormActions;
    use WithFileUploads;

    private const string THROTTLE_KEY_PREFIX = 'property-mgmt';

    private const string SECTION_DETAILS = 'details';

    private const string SECTION_CAPACITY = 'capacity';

    /** @var list<string> */
    private const array AUTOSAVE_FIELDS = ['name', 'city', 'address', 'country_id'];

    /** @var list<string> */
    private const array CAPACITY_AUTOSAVE_FIELDS = ['base_capacity', 'max_capacity'];

    public Property $targetProperty;

    public ?string $editingSection = null;

    #[Locked]
    public ?int $propertyIdPendingDeletion = null;

    public string $name = '';

    public ?string $description = null;

    public string $city = '';

    public string $address = '';

    public ?int $country_id = null;

    public string $countrySearch = '';

    public bool $is_active = false;

    public ?int $base_capacity = null;

    public ?int $max_capacity = null;

    /** @var TemporaryUploadedFile|null */
    public $photo = null;

    public function mount(string $property): void
    {
        $target = $this->loadProperty($property);

        Gate::authorize('view', $target);

        $this->targetProperty = $target;
        $this->fillForm($target);
    }

    #[Computed]
    public function property(): Property
    {
        return $this->targetProperty;
    }

    #[Computed]
    public function propertyAvatarUrl(): ?string
    {
        return $this->targetProperty->avatarUrl();
    }

    #[Computed]
    public function maxUploadSizeMb(): int
    {
        return SystemSetting::instance()->max_upload_size_mb;
    }

    /**
     * @return Collection<int, Country>
     */
    #[Computed]
    public function countries(): Collection
    {
        return Country::query()
            ->active()
            ->when($this->countrySearch !== '', fn ($query) => $query->search($this->countrySearch))
            ->orderBy('sort_order')
            ->orderBy(Country::localizedNameColumn())
            ->get();
    }

    public function startEditingSection(string $section): void
    {
        abort_unless(in_array($section, [self::SECTION_DETAILS, self::SECTION_CAPACITY], true), 404);

        $this->authorizePropertyUpdate();

        $this->editingSection = $section;
        $this->fillForm($this->property());
        $this->resetValidation();
    }

    public function cancelEditingSection(): void
    {
        $this->editingSection = null;
        $this->fillForm($this->property());
        $this->resetValidation();
    }

    public function updatedPhoto(): void
    {
        $photo = $this->photo;

        if (! $photo instanceof TemporaryUploadedFile) {
            return;
        }

        $this->authorizePropertyUpdate();

        app(UpdatePropertyAvatar::class)->handle(
            $this->actor(),
            $this->property(),
            $photo,
        );

        $this->photo = null;
        $this->refreshPropertyMedia();

        ToastService::success(__('properties.show.saved.avatar'));
    }

    public function deleteAvatar(): void
    {
        $this->authorizePropertyUpdate();

        $this->property()->clearMediaCollection('avatar');
        $this->refreshPropertyMedia();

        ToastService::success(__('properties.show.saved.avatar_deleted'));
    }

    public function saveDescription(): void
    {
        $this->autosaveField('description', self::SECTION_DETAILS, 'properties.show.saved.details');
    }

    public function updated(string $property): void
    {
        if (in_array($property, self::AUTOSAVE_FIELDS, true)) {
            $this->autosaveField($property, self::SECTION_DETAILS, 'properties.show.saved.details');
        }

        if (in_array($property, self::CAPACITY_AUTOSAVE_FIELDS, true)) {
            $this->autosaveField($property, self::SECTION_CAPACITY, 'properties.show.saved.capacity');
        }
    }

    public function updatedIsActive(): void
    {
        if (! $this->isEditingSection(self::SECTION_DETAILS)) {
            return;
        }

        if ($this->throttle('toggle-active')) {
            return;
        }

        $this->authorizePropertyUpdate();

        app(TogglePropertyActiveStatus::class)->handle($this->actor(), $this->property(), $this->is_active);

        $this->refreshPropertyState();

        ToastService::success(__('properties.show.saved.active'));
    }

    public function confirmPropertyDeletion(): void
    {
        if ($this->throttle('delete')) {
            return;
        }

        $property = $this->property();

        Gate::forUser($this->actor())->authorize('delete', $property);

        $this->propertyIdPendingDeletion = $property->id;

        ModalService::confirm(
            $this,
            title: __('properties.show.quick_actions.delete.title'),
            message: __('properties.show.quick_actions.delete.message', ['property' => $this->property()->label()]),
            confirmLabel: __('properties.show.quick_actions.delete.confirm_label'),
            variant: ModalService::VARIANT_PASSWORD,
        );
    }

    #[On('modal-confirmed')]
    public function handleModalConfirmed(DeleteProperty $deleteProperty): void
    {
        if ($this->throttle('confirmed-action')) {
            return;
        }

        if ($this->propertyIdPendingDeletion === null) {
            return;
        }

        $label = $this->property()->label();

        $deleteProperty->handle($this->actor(), $this->property());

        $this->propertyIdPendingDeletion = null;

        ToastService::success(__('properties.show.quick_actions.delete.deleted', ['property' => $label]));

        $this->redirect(route('properties.index'), navigate: true);
    }

    #[On('modal-confirm-cancelled')]
    public function resetPendingSensitiveActions(): void
    {
        $this->propertyIdPendingDeletion = null;
    }

    public function canDelete(): bool
    {
        return Gate::forUser($this->actor())->allows('delete', $this->property());
    }

    public function canEdit(): bool
    {
        return Gate::forUser($this->actor())->allows('update', $this->property());
    }

    private function autosaveField(string $property, string $section, string $toastKey): void
    {
        if (! $this->isEditingSection($section)) {
            return;
        }

        if ($this->throttle('autosave')) {
            return;
        }

        $this->authorizePropertyUpdate();
        $this->resetValidation($property);

        try {
            app(UpdateProperty::class)->handle($this->actor(), $this->property(), $property, $this->$property);
        } catch (ValidationException $exception) {
            $this->fillForm($this->property());

            throw $exception;
        }

        $this->refreshPropertyState();

        ToastService::success(__($toastKey));
    }

    private function refreshPropertyState(): void
    {
        $propertyId = $this->targetProperty->getKey();

        abort_unless(is_int($propertyId) || is_string($propertyId), 404);

        $this->targetProperty = $this->loadProperty($propertyId);

        $this->fillForm($this->targetProperty);
    }

    private function isEditingSection(string $section): bool
    {
        return $this->editingSection === $section;
    }

    private function fillForm(Property $property): void
    {
        $this->name = $property->name;
        $this->description = $property->description;
        $this->city = $property->city;
        $this->address = $property->address;
        $this->country_id = $property->country_id;
        $this->is_active = $property->is_active;
        $this->base_capacity = $property->base_capacity;
        $this->max_capacity = $property->max_capacity;
        $this->countrySearch = '';
    }

    private function refreshPropertyMedia(): void
    {
        $this->targetProperty->load('media');

        unset($this->propertyAvatarUrl);
    }

    private function authorizePropertyUpdate(): void
    {
        Gate::forUser($this->actor())->authorize('update', $this->property());
    }

    private function loadProperty(int|string $propertyId): Property
    {
        return Property::query()
            ->ownedBy($this->actor())
            ->with(['country', 'media'])
            ->findOrFail($propertyId);
    }
};
