<?php

use App\Actions\Countries\DeleteCountry;
use App\Actions\Countries\ToggleCountryActiveStatus;
use App\Actions\Countries\UpdateCountry;
use App\Concerns\FormatsLocalizedDates;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Concerns\ThrottlesFormActions;
use App\Infrastructure\UiFeedback\ModalService;
use App\Infrastructure\UiFeedback\ToastService;
use App\Models\Country;
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

    private const string THROTTLE_KEY_PREFIX = 'country-mgmt';

    private const string SECTION_DETAILS = 'details';

    /** @var list<string> */
    private const array AUTOSAVE_FIELDS = ['en_name', 'es_name', 'iso_alpha2', 'iso_alpha3', 'phone_code', 'sort_order'];

    public Country $targetCountry;

    public ?string $editingSection = null;

    #[Locked]
    public ?int $countryIdPendingDeletion = null;

    public string $en_name = '';

    public string $es_name = '';

    public string $iso_alpha2 = '';

    public string $iso_alpha3 = '';

    public string $phone_code = '';

    public int $sort_order = 0;

    public bool $is_active = false;

    public function mount(string $country): void
    {
        $target = Country::query()->findOrFail($country);

        Gate::authorize('view', $target);

        $this->targetCountry = $target;
        $this->fillForm($target);
    }

    #[Computed]
    public function country(): Country
    {
        return $this->targetCountry;
    }

    #[Computed]
    public function associatedUsersCount(): int
    {
        return $this->targetCountry->users()->count();
    }

    public function startEditingSection(string $section): void
    {
        abort_unless($section === self::SECTION_DETAILS, 404);

        $this->authorizeCountryUpdate();

        $this->editingSection = $section;
        $this->fillForm($this->country());
        $this->resetValidation();
    }

    public function cancelEditingSection(): void
    {
        $this->editingSection = null;
        $this->fillForm($this->country());
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

        $this->authorizeCountryUpdate();

        app(ToggleCountryActiveStatus::class)->handle($this->actor(), $this->country(), $this->is_active);

        $this->refreshCountryState();

        ToastService::success(__('countries.show.saved.active'));
    }

    public function confirmCountryDeletion(): void
    {
        if ($this->throttle('delete')) {
            return;
        }

        $actor = $this->actor();
        $country = $this->country();

        Gate::forUser($actor)->authorize('delete', $country);

        $this->countryIdPendingDeletion = $country->id;
        $countryLabel = $this->countryLabel();
        $hasAssociations = $country->users()->exists() || $country->properties()->exists();

        $prefix = $hasAssociations ? 'countries.show.quick_actions.deactivate' : 'countries.show.quick_actions.delete';

        ModalService::confirm(
            $this,
            title: __("{$prefix}.title"),
            message: __("{$prefix}.message", ['country' => $countryLabel]),
            confirmLabel: __("{$prefix}.confirm_label"),
            variant: ModalService::VARIANT_PASSWORD,
        );
    }

    #[On('modal-confirmed')]
    public function handleModalConfirmed(DeleteCountry $deleteCountry): void
    {
        if ($this->throttle('confirmed-action')) {
            return;
        }

        if ($this->countryIdPendingDeletion === null) {
            return;
        }

        $countryLabel = $this->countryLabel();

        $wasDeleted = $deleteCountry->handle($this->actor(), $this->country());

        $this->countryIdPendingDeletion = null;

        if ($wasDeleted) {
            ToastService::success(__('countries.show.quick_actions.delete.deleted', ['country' => $countryLabel]));

            $this->redirect(route('countries.index'), navigate: true);
        } else {
            $this->refreshCountryState();

            ToastService::success(__('countries.show.quick_actions.deactivate.deactivated', ['country' => $countryLabel]));
        }
    }

    #[On('modal-confirm-cancelled')]
    public function resetPendingSensitiveActions(): void
    {
        $this->countryIdPendingDeletion = null;
    }

    public function canEdit(): bool
    {
        return Gate::forUser($this->actor())->allows('update', $this->country());
    }

    public function canDelete(): bool
    {
        return Gate::forUser($this->actor())->allows('delete', $this->country());
    }

    private function autosaveField(string $property): void
    {
        if ($this->editingSection !== self::SECTION_DETAILS) {
            return;
        }

        if ($this->throttle('autosave')) {
            return;
        }

        $this->authorizeCountryUpdate();
        $this->resetValidation($property);

        try {
            app(UpdateCountry::class)->handle($this->actor(), $this->country(), $property, $this->$property);
        } catch (ValidationException $exception) {
            $this->fillForm($this->country());

            throw $exception;
        }

        $this->refreshCountryState();

        ToastService::success(__('countries.show.saved.details'));
    }

    private function refreshCountryState(): void
    {
        $this->targetCountry = Country::query()->where('id', $this->targetCountry->getKey())->firstOrFail();

        $this->fillForm($this->targetCountry);
    }

    private function fillForm(Country $country): void
    {
        $this->en_name = $country->en_name;
        $this->es_name = $country->es_name;
        $this->iso_alpha2 = $country->iso_alpha2;
        $this->iso_alpha3 = $country->iso_alpha3;
        $this->phone_code = $country->phone_code;
        $this->sort_order = (int) $country->sort_order;
        $this->is_active = $country->is_active;
    }

    private function authorizeCountryUpdate(): void
    {
        Gate::forUser($this->actor())->authorize('update', $this->country());
    }

    private function countryLabel(): string
    {
        return __('countries.country_label', [
            'name' => $this->country()->localizedName(),
            'id' => $this->country()->id,
        ]);
    }
};
