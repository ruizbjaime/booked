<?php

use App\Actions\Bedrooms\AttachBedTypeToBedroom;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Concerns\ThrottlesFormActions;
use App\Infrastructure\UiFeedback\ToastService;
use App\Models\Bedroom;
use App\Models\BedType;
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

    public ?int $bed_type_id = null;

    public int $quantity = 1;

    /**
     * @var array<string, mixed>
     */
    #[Locked]
    public array $context = [];

    public Bedroom $bedroom;

    /**
     * @param  array<string, mixed>  $context
     */
    public function mount(array $context = []): void
    {
        $this->context = $context;

        $bedroomId = $context['bedroom_id'] ?? null;
        abort_unless(is_int($bedroomId), 404);

        $this->bedroom = Bedroom::query()->with('property')->findOrFail($bedroomId);

        Gate::forUser($this->actor())->authorize('update', $this->bedroom->property);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['bed_type_id', 'quantity'], true)) {
            $this->resetValidation($property);
        }
    }

    /**
     * @return Collection<int, BedType>
     */
    #[Computed]
    public function bedTypes(): Collection
    {
        return BedType::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy(BedType::localizedNameColumn())
            ->get();
    }

    public function save(AttachBedTypeToBedroom $attachBedTypeToBedroom): void
    {
        if ($this->throttle('create')) {
            return;
        }

        $bedType = BedType::query()->findOrFail($this->bed_type_id);

        $attachBedTypeToBedroom->handle($this->actor(), $this->bedroom, [
            'bed_type_id' => $bedType->id,
            'quantity' => $this->quantity,
        ]);

        ToastService::success(__('properties.show.accommodation.bed_types.created', [
            'bed_type' => $bedType->localizedName(),
            'bedroom' => $this->bedroom->en_name,
        ]));

        $this->dispatch('close-form-modal');
        $this->dispatch('bedroom-bed-type-attached', bedroomId: $this->bedroom->id);
    }
};
