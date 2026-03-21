<?php

use App\Actions\Roles\CreateRole;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Concerns\ThrottlesFormActions;
use App\Infrastructure\UiFeedback\ToastService;
use App\Models\Role;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

new class extends Component
{
    use ResolvesAuthenticatedUser;
    use ThrottlesFormActions;

    private const string THROTTLE_KEY_PREFIX = 'role-mgmt';

    public string $name = '';

    public string $en_label = '';

    public string $es_label = '';

    public string $color = 'zinc';

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
        Gate::authorize('create', Role::class);

        $this->context = $context;
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['name', 'en_label', 'es_label', 'color', 'sort_order', 'is_active'], true)) {
            $this->resetValidation($property);
        }
    }

    public function save(CreateRole $createRole): void
    {
        if ($this->throttle('create', 5)) {
            return;
        }

        $role = $createRole->handle($this->actor(), [
            'name' => $this->name,
            'en_label' => $this->en_label,
            'es_label' => $this->es_label,
            'color' => $this->color,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ]);

        ToastService::success(__('roles.create.created', [
            'role' => __('roles.role_label', ['name' => $role->localizedLabel(), 'id' => $role->id]),
        ]));

        $this->resetForm();

        $this->dispatch('close-form-modal');
        $this->dispatch('role-created', roleId: $role->id);
    }

    private function resetForm(): void
    {
        $this->reset('name', 'en_label', 'es_label');
        $this->color = 'zinc';
        $this->sort_order = 999;
        $this->is_active = true;
    }
};
