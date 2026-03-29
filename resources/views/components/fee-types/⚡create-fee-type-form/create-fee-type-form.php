<?php

use App\Actions\FeeTypes\CreateFeeType;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Concerns\ThrottlesFormActions;
use App\Infrastructure\UiFeedback\ToastService;
use App\Models\FeeType;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    use ResolvesAuthenticatedUser;
    use ThrottlesFormActions;

    private const string THROTTLE_KEY_PREFIX = 'fee-type-mgmt';

    public string $name = '';

    public string $en_name = '';

    public string $es_name = '';

    public int $order = 999;

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
        Gate::authorize('create', FeeType::class);

        $this->context = $context;
    }

    public function updated(string $property): void
    {
        $this->resetValidation($property);
    }

    public function save(CreateFeeType $createFeeType): void
    {
        if ($this->throttle('create')) {
            return;
        }

        $feeType = $createFeeType->handle($this->actor(), [
            'name' => $this->name,
            'en_name' => $this->en_name,
            'es_name' => $this->es_name,
            'order' => $this->order,
        ]);

        ToastService::success(__('fee_types.create.created', [
            'fee_type' => __('fee_types.fee_type_label', ['name' => $feeType->localizedName(), 'id' => $feeType->id]),
        ]));

        $this->resetForm();

        $this->dispatch('close-form-modal');
        $this->dispatch('fee-type-created', feeTypeId: $feeType->id);
    }

    private function resetForm(): void
    {
        $this->reset('name', 'en_name', 'es_name');
        $this->order = 999;
    }
};
