<?php

use App\Actions\ChargeBases\CreateChargeBasis;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Concerns\ThrottlesFormActions;
use App\Infrastructure\UiFeedback\ToastService;
use App\Models\ChargeBasis;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    use ResolvesAuthenticatedUser;
    use ThrottlesFormActions;

    private const string THROTTLE_KEY_PREFIX = 'charge-basis-mgmt';

    public string $name = '';

    public string $en_name = '';

    public string $es_name = '';

    public string $en_description = '';

    public string $es_description = '';

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
        }

        $this->resetValidation($property);
    }

    public function save(CreateChargeBasis $createChargeBasis): void
    {
        if ($this->throttle('create')) {
            return;
        }

        $chargeBasis = $createChargeBasis->handle($this->actor(), [
            'name' => $this->name,
            'en_name' => $this->en_name,
            'es_name' => $this->es_name,
            'en_description' => $this->blankToNull($this->en_description),
            'es_description' => $this->blankToNull($this->es_description),
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
        $this->reset('name', 'en_name', 'es_name', 'en_description', 'es_description', 'quantity_subject');
        $this->order = 999;
        $this->is_active = true;
        $this->requires_quantity = false;
    }

    private function blankToNull(string $value): ?string
    {
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
};
