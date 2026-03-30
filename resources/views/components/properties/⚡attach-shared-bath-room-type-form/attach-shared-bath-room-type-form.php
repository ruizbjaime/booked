<?php

use App\Actions\Properties\AttachBathRoomTypeToProperty;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Concerns\ThrottlesFormActions;
use App\Infrastructure\UiFeedback\ToastService;
use App\Models\BathRoomType;
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

    public ?int $bath_room_type_id = null;

    public int $quantity = 1;

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
        if (in_array($property, ['bath_room_type_id', 'quantity'], true)) {
            $this->resetValidation($property);
        }
    }

    /**
     * @return Collection<int, BathRoomType>
     */
    #[Computed]
    public function bathRoomTypes(): Collection
    {
        return BathRoomType::query()
            ->orderBy('sort_order')
            ->orderBy(BathRoomType::localizedNameColumn())
            ->get();
    }

    public function save(AttachBathRoomTypeToProperty $attachBathRoomTypeToProperty): void
    {
        if ($this->throttle('create')) {
            return;
        }

        $attachBathRoomTypeToProperty->handle($this->actor(), $this->property, [
            'bath_room_type_id' => $this->bath_room_type_id,
            'quantity' => $this->quantity,
        ]);

        $bathRoomType = BathRoomType::query()->findOrFail($this->bath_room_type_id);

        ToastService::success(__('properties.show.accommodation.shared_bath_room_types.created', [
            'bath_room_type' => $bathRoomType->localizedName(),
            'property' => $this->property->name,
        ]));

        $this->dispatch('close-form-modal');
        $this->dispatch('property-shared-bath-room-type-attached', propertyId: $this->property->id);
    }
};
