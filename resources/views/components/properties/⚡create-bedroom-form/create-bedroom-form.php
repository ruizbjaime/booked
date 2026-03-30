<?php

use App\Actions\Bedrooms\CreateBedroom;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Concerns\ThrottlesFormActions;
use App\Infrastructure\UiFeedback\ToastService;
use App\Models\Property;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    use ResolvesAuthenticatedUser;
    use ThrottlesFormActions;

    private const string THROTTLE_KEY_PREFIX = 'property-mgmt';

    public string $en_name = '';

    public string $es_name = '';

    public ?string $en_description = null;

    public ?string $es_description = null;

    /**
     * @var array<string, mixed>
     */
    #[Locked]
    public array $context = [];

    public Property $property;

    /**
     * @param  array<string, mixed>  $context
     */
    public function mount(array $context = []): void
    {
        $this->context = $context;

        $propertyId = $context['property_id'] ?? null;
        abort_unless(is_int($propertyId), 404);

        $this->property = Property::query()->findOrFail($propertyId);

        Gate::forUser($this->actor())->authorize('update', $this->property);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['en_name', 'es_name', 'en_description', 'es_description'], true)) {
            $this->resetValidation($property);
        }
    }

    public function save(CreateBedroom $createBedroom): void
    {
        if ($this->throttle('create')) {
            return;
        }

        $bedroom = $createBedroom->handle($this->actor(), $this->property, [
            'en_name' => $this->en_name,
            'es_name' => $this->es_name,
            'en_description' => $this->en_description,
            'es_description' => $this->es_description,
        ]);

        ToastService::success(__('properties.show.saved.accommodation', [
            'bedroom' => $bedroom->en_name,
        ]));

        $this->dispatch('close-form-modal');
        $this->dispatch('bedroom-created');
    }
};
