<?php

use App\Actions\Platforms\CreatePlatform;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Concerns\ThrottlesFormActions;
use App\Infrastructure\UiFeedback\ToastService;
use App\Models\Platform;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

new class extends Component
{
    use ResolvesAuthenticatedUser;
    use ThrottlesFormActions;

    private const string THROTTLE_KEY_PREFIX = 'platform-mgmt';

    public string $name = '';

    public string $en_name = '';

    public string $es_name = '';

    public string $colorMode = 'zinc';

    public string $color = 'zinc';

    public string $customColor = '';

    public int $sort_order = 999;

    public float $commission = 0;

    public float $commission_tax = 0;

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
        Gate::authorize('create', Platform::class);

        $this->context = $context;
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['name', 'en_name', 'es_name', 'color', 'customColor', 'sort_order', 'commission', 'commission_tax', 'is_active'], true)) {
            $this->resetValidation($property === 'customColor' ? 'color' : $property);
        }
    }

    public function save(CreatePlatform $createPlatform): void
    {
        $this->throttle('create', 5);

        $resolvedColor = $this->colorMode === 'custom' ? $this->customColor : $this->colorMode;

        $platform = $createPlatform->handle($this->actor(), [
            'name' => $this->name,
            'en_name' => $this->en_name,
            'es_name' => $this->es_name,
            'color' => $resolvedColor,
            'sort_order' => $this->sort_order,
            'commission' => $this->commission,
            'commission_tax' => $this->commission_tax,
            'is_active' => $this->is_active,
        ]);

        ToastService::success(__('platforms.create.created', [
            'platform' => __('platforms.platform_label', ['name' => $platform->localizedName(), 'id' => $platform->id]),
        ]));

        $this->resetForm();

        $this->dispatch('close-form-modal');
        $this->dispatch('platform-created', platformId: $platform->id);
    }

    private function resetForm(): void
    {
        $this->reset('name', 'en_name', 'es_name', 'customColor', 'colorMode', 'color', 'sort_order', 'commission', 'commission_tax', 'is_active');
    }
};
