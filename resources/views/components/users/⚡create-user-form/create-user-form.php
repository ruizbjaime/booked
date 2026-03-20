<?php

use App\Actions\Users\CreateUser;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Domain\Users\RoleConfig;
use App\Domain\Users\RoleNormalizer;
use App\Infrastructure\UiFeedback\ToastService;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

new class extends Component
{
    use ResolvesAuthenticatedUser;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    /**
     * @var list<string>
     */
    public array $roles = [];

    public bool $active = true;

    /**
     * @var array<int, string>
     */
    public array $availableRoles = [];

    /**
     * @var array<string, mixed>
     */
    public array $context = [];

    /**
     * @param  array<string, mixed>  $context
     */
    public function mount(array $context = []): void
    {
        Gate::authorize('create', User::class);

        $this->context = $context;
        $this->availableRoles = $this->availableRoles();
        $this->roles = $this->defaultRoles();
    }

    public function updatedRoles(): void
    {
        $this->roles = $this->normalizeRoles($this->roles);
        $this->resetValidation('roles');
    }

    public function updated(string $property): void
    {
        if ($this->resetsOwnValidation($property)) {
            $this->resetValidation($property);

            return;
        }

        if ($this->resetsPasswordValidation($property)) {
            $this->resetValidation(['password', 'password_confirmation']);
        }
    }

    public function save(CreateUser $createUser): void
    {
        $user = $createUser->handle($this->actor(), $this->payload());

        ToastService::success(__('users.create.created', [
            'user' => __('users.user_label', ['name' => $user->name, 'id' => $user->id]),
        ]));

        $this->resetForm();

        $this->dispatch('close-form-modal');
        $this->dispatch('user-created', userId: $user->id);
    }

    /**
     * @return list<string>
     */
    private function defaultRoles(): array
    {
        $defaultRole = RoleConfig::defaultRole();

        if ($this->roleExists($defaultRole)) {
            return [$defaultRole];
        }

        foreach ($this->availableRoles as $role) {
            if (! RoleConfig::isAdminRole($role)) {
                return [$role];
            }
        }

        return [];
    }

    /**
     * @return array<int, string>
     */
    private function availableRoles(): array
    {
        return RoleNormalizer::available();
    }

    /**
     * @return array{name: string, email: string, is_active: bool, password: string, password_confirmation: string, roles: list<string>}
     */
    private function payload(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'is_active' => $this->active,
            'password' => $this->password,
            'password_confirmation' => $this->password_confirmation,
            'roles' => $this->roles,
        ];
    }

    private function resetForm(): void
    {
        $this->reset('name', 'email', 'password', 'password_confirmation');
        $this->roles = $this->defaultRoles();
        $this->active = true;
    }

    private function resetsOwnValidation(string $property): bool
    {
        return in_array($property, ['name', 'email', 'active'], true);
    }

    private function resetsPasswordValidation(string $property): bool
    {
        return in_array($property, ['password', 'password_confirmation'], true);
    }

    private function roleExists(string $role): bool
    {
        return in_array($role, $this->availableRoles, true);
    }

    /**
     * @param  list<string>  $roles
     * @return list<string>
     */
    private function normalizeRoles(array $roles): array
    {
        return RoleNormalizer::normalize($roles, $this->availableRoles);
    }
};
