<?php

use App\Actions\BedTypes\CreateBedType;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Infrastructure\UiFeedback\ToastService;
use App\Models\BedType;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

new class extends Component
{
    use ResolvesAuthenticatedUser;

    public string $name = '';

    public string $name_en = '';

    public string $name_es = '';

    public int $bed_capacity = 1;

    public int $sort_order = 999;

    /**
     * @var array<string, mixed>
     */
    public array $context = [];

    /**
     * @param  array<string, mixed>  $context
     */
    public function mount(array $context = []): void
    {
        Gate::authorize('create', BedType::class);

        $this->context = $context;
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['name', 'name_en', 'name_es', 'bed_capacity', 'sort_order'], true)) {
            $this->resetValidation($property);
        }
    }

    public function save(CreateBedType $createBedType): void
    {
        $this->throttle('create', 5);

        $bedType = $createBedType->handle($this->actor(), [
            'name' => $this->name,
            'name_en' => $this->name_en,
            'name_es' => $this->name_es,
            'bed_capacity' => $this->bed_capacity,
            'sort_order' => $this->sort_order,
        ]);

        ToastService::success(__('bed_types.create.created', [
            'bed_type' => __('bed_types.bed_type_label', ['name' => $bedType->localizedName(), 'id' => $bedType->id]),
        ]));

        $this->resetForm();

        $this->dispatch('close-form-modal');
        $this->dispatch('bed-type-created', bedTypeId: $bedType->id);
    }

    private function resetForm(): void
    {
        $this->reset('name', 'name_en', 'name_es');
        $this->bed_capacity = 1;
        $this->sort_order = 999;
    }

    private function throttle(string $action, int $maxAttempts = 10): void
    {
        $key = "bed-type-mgmt:{$action}:{$this->actor()->id}";

        abort_if(RateLimiter::tooManyAttempts($key, $maxAttempts), 429);

        RateLimiter::hit($key, 60);
    }
};
