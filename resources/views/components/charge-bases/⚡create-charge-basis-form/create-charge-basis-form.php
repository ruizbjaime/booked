<?php

use App\Actions\ChargeBases\CreateChargeBasis;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Infrastructure\UiFeedback\ToastService;
use App\Models\ChargeBasis;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    use ResolvesAuthenticatedUser;

    public string $name = '';

    public string $en_name = '';

    public string $es_name = '';

    public string $description = '';

    public int $order = 999;

    public bool $is_active = true;

    public bool $requires_quantity = false;

    public string $quantity_subject = '';

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
        Gate::authorize('create', ChargeBasis::class);

        $this->context = $context;
    }

    public function updated(string $property): void
    {
        if ($property === 'requires_quantity' && ! $this->requires_quantity) {
            $this->quantity_subject = '';
            $this->resetValidation('quantity_subject');

            return;
        }

        if (in_array($property, ['name', 'en_name', 'es_name', 'description', 'order', 'is_active', 'requires_quantity', 'quantity_subject'], true)) {
            $this->resetValidation($property);
        }
    }

    public function save(CreateChargeBasis $createChargeBasis): void
    {
        $this->throttle('create', 5);

        $chargeBasis = $createChargeBasis->handle($this->actor(), [
            'name' => $this->name,
            'en_name' => $this->en_name,
            'es_name' => $this->es_name,
            'description' => $this->blankToNull($this->description),
            'order' => $this->order,
            'is_active' => $this->is_active,
            'metadata' => [
                'requires_quantity' => $this->requires_quantity,
                'quantity_subject' => $this->requires_quantity ? $this->blankToNull($this->quantity_subject) : null,
            ],
        ]);

        ToastService::success(__('charge_bases.create.created', [
            'charge_basis' => __('charge_bases.charge_basis_label', ['name' => $chargeBasis->localizedName(), 'id' => $chargeBasis->id]),
        ]));

        $this->resetForm();

        $this->dispatch('close-form-modal');
        $this->dispatch('charge-basis-created', chargeBasisId: $chargeBasis->id);
    }

    private function resetForm(): void
    {
        $this->reset('name', 'en_name', 'es_name', 'description', 'quantity_subject');
        $this->order = 999;
        $this->is_active = true;
        $this->requires_quantity = false;
    }

    private function blankToNull(string $value): ?string
    {
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function throttle(string $action, int $maxAttempts = 10): void
    {
        $key = "charge-basis-mgmt:{$action}:{$this->actor()->id}";

        abort_if(RateLimiter::tooManyAttempts($key, $maxAttempts), 429);

        RateLimiter::hit($key, 60);
    }
};
