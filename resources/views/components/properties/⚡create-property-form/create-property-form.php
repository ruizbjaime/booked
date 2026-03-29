<?php

use App\Actions\Properties\CreateProperty;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Concerns\ThrottlesFormActions;
use App\Infrastructure\UiFeedback\ToastService;
use App\Models\Country;
use App\Models\Property;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    use ResolvesAuthenticatedUser;
    use ThrottlesFormActions;

    private const string THROTTLE_KEY_PREFIX = 'property-mgmt';

    public string $name = '';

    public string $city = '';

    public string $address = '';

    public ?int $country_id = null;

    public string $countrySearch = '';

    public bool $is_active = true;

    /**
     * @var array<string, mixed>
     */
    #[Locked]
    public array $context = [];

    /**
     * @param  array<string, mixed>  $context
     */
    public function mount(array $context = []): void
    {
        Gate::authorize('create', Property::class);

        $this->context = $context;
        $this->country_id = $this->defaultCountryId();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['name', 'city', 'address', 'country_id', 'is_active'], true)) {
            $this->resetValidation($property);
        }
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

    public function save(CreateProperty $createProperty): void
    {
        if ($this->throttle('create')) {
            return;
        }

        $property = $createProperty->handle($this->actor(), [
            'name' => $this->name,
            'city' => $this->city,
            'address' => $this->address,
            'country_id' => $this->country_id,
            'is_active' => $this->is_active,
        ]);

        ToastService::success(__('properties.create.created', [
            'property' => $property->label(),
        ]));

        $this->resetForm();

        $this->dispatch('close-form-modal');
        $this->dispatch('property-created', propertyId: $property->id);
    }

    private function resetForm(): void
    {
        $this->reset('name', 'city', 'address', 'country_id', 'countrySearch');
        $this->country_id = $this->defaultCountryId();
        $this->is_active = true;
    }

    private function defaultCountryId(): ?int
    {
        $countryId = Country::query()
            ->active()
            ->where(fn ($query) => $query
                ->where('en_name', 'Colombia')
                ->orWhere('es_name', 'Colombia'))
            ->value('id');

        if (is_int($countryId)) {
            return $countryId;
        }

        return is_string($countryId) ? (int) $countryId : null;
    }
};
