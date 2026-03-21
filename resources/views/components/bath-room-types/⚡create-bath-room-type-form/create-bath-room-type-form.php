<?php

use App\Actions\BathRoomTypes\CreateBathRoomType;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Concerns\ThrottlesFormActions;
use App\Infrastructure\UiFeedback\ToastService;
use App\Models\BathRoomType;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    use ResolvesAuthenticatedUser;
    use ThrottlesFormActions;

    private const string THROTTLE_KEY_PREFIX = 'bath-room-type-mgmt';

    public string $name = '';

    public string $name_en = '';

    public string $name_es = '';

    public string $description = '';

    public int $sort_order = 999;

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
        Gate::authorize('create', BathRoomType::class);

        $this->context = $context;
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['name', 'name_en', 'name_es', 'description', 'sort_order'], true)) {
            $this->resetValidation($property);
        }
    }

    public function save(CreateBathRoomType $createBathRoomType): void
    {
        $this->throttle('create', 5);

        $bathRoomType = $createBathRoomType->handle($this->actor(), [
            'name' => $this->name,
            'name_en' => $this->name_en,
            'name_es' => $this->name_es,
            'description' => $this->description,
            'sort_order' => $this->sort_order,
        ]);

        ToastService::success(__('bath_room_types.create.created', [
            'bath_room_type' => __('bath_room_types.bath_room_type_label', ['name' => $bathRoomType->localizedName(), 'id' => $bathRoomType->id]),
        ]));

        $this->resetForm();

        $this->dispatch('close-form-modal');
        $this->dispatch('bath-room-type-created', bathRoomTypeId: $bathRoomType->id);
    }

    private function resetForm(): void
    {
        $this->reset('name', 'name_en', 'name_es', 'description');
        $this->sort_order = 999;
    }
};
