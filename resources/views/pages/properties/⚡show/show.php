<?php

use App\Actions\Properties\DeleteProperty;
use App\Actions\Properties\TogglePropertyActiveStatus;
use App\Actions\Properties\UpdateProperty;
use App\Concerns\FormatsLocalizedDates;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Concerns\ThrottlesFormActions;
use App\Infrastructure\UiFeedback\ModalService;
use App\Infrastructure\UiFeedback\ToastService;
use App\Models\Country;
use App\Models\Property;
use Illuminate\Support\Collection;
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

    private const string THROTTLE_KEY_PREFIX = 'property-mgmt';

    private const string SECTION_DETAILS = 'details';

    /** @var list<string> */
    private const array AUTOSAVE_FIELDS = ['name', 'city', 'address', 'country_id'];

    public Property $targetProperty;

    public ?string $editingSection = null;

    #[Locked]
    public ?int $propertyIdPendingDeletion = null;

    public string $name = '';

    public string $city = '';

    public string $address = '';

    public ?int $country_id = null;

    public string $countrySearch = '';

    public bool $is_active = false;

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
        abort_unless($section === self::SECTION_DETAILS, 404);

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

    public function updated(string $property): void
    {
        if (in_array($property, self::AUTOSAVE_FIELDS, true)) {
            $this->autosaveField($property);
        }
    }

    public function updatedIsActive(): void
    {
        if (! $this->isEditingDetailsSection()) {
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
        if ($this->throttle('delete', 5)) {
            return;
        }

        $property = $this->property();

        Gate::forUser($this->actor())->authorize('delete', $property);

        $this->propertyIdPendingDeletion = $property->id;

        ModalService::confirm(
            $this,
            title: __('properties.show.quick_actions.delete.title'),
            message: __('properties.show.quick_actions.delete.message', ['property' => $this->propertyLabel()]),
            confirmLabel: __('properties.show.quick_actions.delete.confirm_label'),
            variant: ModalService::VARIANT_PASSWORD,
        );
    }

    #[On('modal-confirmed')]
    public function handleModalConfirmed(DeleteProperty $deleteProperty): void
    {
        if ($this->throttle('confirmed-action', 5)) {
            return;
        }

        if ($this->propertyIdPendingDeletion === null) {
            return;
        }

        $propertyLabel = $this->propertyLabel();

        $deleteProperty->handle($this->actor(), $this->property());

        $this->propertyIdPendingDeletion = null;

        ToastService::success(__('properties.show.quick_actions.delete.deleted', ['property' => $propertyLabel]));

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

    private function autosaveField(string $property): void
    {
        if (! $this->isEditingDetailsSection()) {
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

        ToastService::success(__('properties.show.saved.details'));
    }

    private function refreshPropertyState(): void
    {
        $propertyId = $this->targetProperty->getKey();

        abort_unless(is_int($propertyId) || is_string($propertyId), 404);

        $this->targetProperty = $this->loadProperty($propertyId);

        $this->fillForm($this->targetProperty);
    }

    private function isEditingDetailsSection(): bool
    {
        return $this->editingSection === self::SECTION_DETAILS;
    }

    private function fillForm(Property $property): void
    {
        $this->name = $property->name;
        $this->city = $property->city;
        $this->address = $property->address;
        $this->country_id = $property->country_id;
        $this->is_active = $property->is_active;
        $this->countrySearch = '';
    }

    private function authorizePropertyUpdate(): void
    {
        Gate::forUser($this->actor())->authorize('update', $this->property());
    }

    private function loadProperty(int|string $propertyId): Property
    {
        return Property::query()->with('country')->findOrFail($propertyId);
    }

    private function propertyLabel(): string
    {
        return __('properties.property_label', [
            'name' => $this->property()->name,
            'id' => $this->property()->id,
        ]);
    }
};
