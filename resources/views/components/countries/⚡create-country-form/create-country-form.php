<?php

use App\Actions\Countries\CreateCountry;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Infrastructure\UiFeedback\ToastService;
use App\Models\Country;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

new class extends Component
{
    use ResolvesAuthenticatedUser;

    public string $en_name = '';

    public string $es_name = '';

    public string $iso_alpha2 = '';

    public string $iso_alpha3 = '';

    public string $phone_code = '';

    public int $sort_order = 999;

    public bool $is_active = true;

    /**
     * @var array<string, mixed>
     */
    public array $context = [];

    /**
     * @param  array<string, mixed>  $context
     */
    public function mount(array $context = []): void
    {
        Gate::authorize('create', Country::class);

        $this->context = $context;
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['en_name', 'es_name', 'iso_alpha2', 'iso_alpha3', 'phone_code', 'sort_order', 'is_active'], true)) {
            $this->resetValidation($property);
        }
    }

    public function save(CreateCountry $createCountry): void
    {
        $this->throttle('create', 5);

        $country = $createCountry->handle($this->actor(), [
            'en_name' => $this->en_name,
            'es_name' => $this->es_name,
            'iso_alpha2' => $this->iso_alpha2,
            'iso_alpha3' => $this->iso_alpha3,
            'phone_code' => $this->phone_code,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ]);

        ToastService::success(__('countries.create.created', [
            'country' => __('countries.country_label', ['name' => $country->localizedName(), 'id' => $country->id]),
        ]));

        $this->resetForm();

        $this->dispatch('close-form-modal');
        $this->dispatch('country-created', countryId: $country->id);
    }

    private function resetForm(): void
    {
        $this->reset('en_name', 'es_name', 'iso_alpha2', 'iso_alpha3', 'phone_code');
        $this->sort_order = 999;
        $this->is_active = true;
    }

    private function throttle(string $action, int $maxAttempts = 10): void
    {
        $key = "country-mgmt:{$action}:{$this->actor()->id}";

        abort_if(RateLimiter::tooManyAttempts($key, $maxAttempts), 429);

        RateLimiter::hit($key, 60);
    }
};
