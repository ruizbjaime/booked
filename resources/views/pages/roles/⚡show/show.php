<?php

use App\Actions\Roles\DeleteRole;
use App\Actions\Roles\SetDefaultRole;
use App\Actions\Roles\ToggleRoleActiveStatus;
use App\Actions\Roles\UpdateRole;
use App\Actions\Roles\UpdateRolePermissions;
use App\Concerns\FormatsLocalizedDates;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Domain\Auth\PermissionRegistry;
use App\Domain\Users\RoleConfig;
use App\Infrastructure\UiFeedback\ModalService;
use App\Infrastructure\UiFeedback\ToastService;
use App\Models\Role;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\HttpException;

new class extends Component
{
    use FormatsLocalizedDates;
    use ResolvesAuthenticatedUser;

    private const string SECTION_DETAILS = 'details';

    private const string SECTION_PERMISSIONS = 'permissions';

    /** @var list<string> */
    private const array AUTOSAVE_FIELDS = ['en_label', 'es_label', 'color', 'sort_order'];

    public Role $targetRole;

    public ?string $editingSection = null;

    #[Locked]
    public ?int $roleIdPendingDeletion = null;

    public string $en_label = '';

    public string $es_label = '';

    public string $color = 'zinc';

    public int $sort_order = 0;

    public bool $is_active = false;

    public bool $is_default = false;

    /** @var list<string> */
    public array $selectedPermissions = [];

    public function mount(string $role): void
    {
        $target = Role::query()->with('permissions')->withCount('users')->findOrFail($role);

        Gate::authorize('view', $target);

        $this->targetRole = $target;
        $this->fillForm($target);
    }

    #[Computed]
    public function role(): Role
    {
        return $this->targetRole;
    }

    #[Computed]
    public function associatedUsersCount(): int
    {
        return (int) $this->targetRole->users_count;
    }

    #[Computed]
    public function isSystemRole(): bool
    {
        return RoleConfig::isSystemRole($this->targetRole->name);
    }

    #[Computed]
    public function isDefaultSwitchDisabled(): bool
    {
        return $this->is_default || RoleConfig::isAdminRole($this->targetRole->name);
    }

    /**
     * @return array<string, list<string>>
     */
    #[Computed]
    public function permissionsByModel(): array
    {
        return PermissionRegistry::permissionsGroupedByModel();
    }

    public function isProtectedPermission(string $permissionName): bool
    {
        return RoleConfig::isAdminRole($this->targetRole->name)
            && PermissionRegistry::isAdminProtectedPermission($permissionName);
    }

    public function startEditingSection(string $section): void
    {
        abort_unless(in_array($section, [self::SECTION_DETAILS, self::SECTION_PERMISSIONS], true), 404);

        $this->authorizeRoleUpdate();

        $this->editingSection = $section;
        $this->fillForm($this->role());
        $this->resetValidation();
    }

    public function cancelEditingSection(): void
    {
        $this->editingSection = null;
        $this->fillForm($this->role());
        $this->resetValidation();
    }

    public function updated(string $property): void
    {
        if (in_array($property, self::AUTOSAVE_FIELDS, true)) {
            $this->autosaveField($property);
        }
    }

    public function updatedIsActive(): void
    {
        if ($this->editingSection !== self::SECTION_DETAILS) {
            return;
        }

        $this->throttle('toggle-active');
        $this->authorizeRoleUpdate();

        try {
            app(ToggleRoleActiveStatus::class)->handle($this->actor(), $this->role(), $this->is_active);
        } catch (HttpException $exception) {
            $this->refreshRoleState(reloadPermissions: false);

            throw $exception;
        }

        $this->refreshRoleState(reloadPermissions: false);

        ToastService::success(__('roles.show.saved.active'));
    }

    public function updatedIsDefault(): void
    {
        if ($this->editingSection !== self::SECTION_DETAILS) {
            return;
        }

        if (! $this->is_default) {
            $this->is_default = true;

            return;
        }

        $this->throttle('set-default');
        $this->authorizeRoleUpdate();

        app(SetDefaultRole::class)->handle($this->actor(), $this->role());

        $this->refreshRoleState(reloadPermissions: false);

        ToastService::success(__('roles.show.saved.default'));
    }

    public function savePermissions(): void
    {
        $this->throttle('save-permissions');
        $this->authorizeRoleUpdate();

        app(UpdateRolePermissions::class)->handle(
            $this->actor(),
            $this->role(),
            $this->selectedPermissions,
        );

        ToastService::success(__('roles.show.saved.permissions'));

        $this->redirect(route('roles.show', $this->targetRole), navigate: true);
    }

    public function confirmRoleDeletion(): void
    {
        $this->throttle('delete', 5);

        $actor = $this->actor();
        $role = $this->role();

        Gate::forUser($actor)->authorize('delete', $role);

        $this->roleIdPendingDeletion = (int) $role->id;

        ModalService::confirm(
            $this,
            title: __('roles.show.quick_actions.delete.title'),
            message: __('roles.show.quick_actions.delete.message', ['role' => $this->roleLabel()]),
            confirmLabel: __('roles.show.quick_actions.delete.confirm_label'),
            variant: ModalService::VARIANT_PASSWORD,
        );
    }

    #[On('modal-confirmed')]
    public function handleModalConfirmed(DeleteRole $deleteRole): void
    {
        $this->throttle('confirmed-action', 5);

        if ($this->roleIdPendingDeletion === null) {
            return;
        }

        $roleLabel = $this->roleLabel();

        $deleteRole->handle($this->actor(), $this->role());

        $this->roleIdPendingDeletion = null;

        ToastService::success(__('roles.show.quick_actions.delete.deleted', ['role' => $roleLabel]));

        $this->redirect(route('roles.index'), navigate: true);
    }

    #[On('modal-confirm-cancelled')]
    public function resetPendingSensitiveActions(): void
    {
        $this->roleIdPendingDeletion = null;
    }

    public function canEdit(): bool
    {
        return Gate::forUser($this->actor())->allows('update', $this->role());
    }

    public function canDelete(): bool
    {
        return Gate::forUser($this->actor())->allows('delete', $this->role())
            && ! $this->isSystemRole()
            && $this->associatedUsersCount() === 0;
    }

    private function autosaveField(string $property): void
    {
        if ($this->editingSection !== self::SECTION_DETAILS) {
            return;
        }

        $this->throttle('autosave');
        $this->authorizeRoleUpdate();
        $this->resetValidation($property);

        try {
            app(UpdateRole::class)->handle($this->actor(), $this->role(), $property, $this->$property);
        } catch (ValidationException $exception) {
            $this->fillForm($this->role());

            throw $exception;
        }

        $this->refreshRoleState(reloadPermissions: false);

        ToastService::success(__('roles.show.saved.details'));
    }

    private function refreshRoleState(bool $reloadPermissions = true): void
    {
        $query = Role::query()->withCount('users')->where('id', $this->targetRole->getKey());

        if ($reloadPermissions) {
            $query->with('permissions');
        }

        $this->targetRole = $query->firstOrFail();

        if (! $reloadPermissions) {
            $this->targetRole->setRelation('permissions', $this->targetRole->permissions ?? collect());
        }

        unset($this->associatedUsersCount, $this->isSystemRole, $this->isDefaultSwitchDisabled);

        $this->fillForm($this->targetRole);
    }

    private function fillForm(Role $role): void
    {
        $this->en_label = (string) ($role->en_label ?? '');
        $this->es_label = (string) ($role->es_label ?? '');
        $this->color = (string) $role->color;
        $this->sort_order = (int) $role->sort_order;
        $this->is_active = (bool) $role->is_active;
        $this->is_default = (bool) $role->is_default;
        /** @var list<string> $permissionNames */
        $permissionNames = $role->permissions->pluck('name')->all();
        $this->selectedPermissions = $permissionNames;
    }

    private function authorizeRoleUpdate(): void
    {
        Gate::forUser($this->actor())->authorize('update', $this->role());
    }

    private function roleLabel(): string
    {
        return __('roles.role_label', [
            'name' => $this->role()->localizedLabel(),
            'id' => $this->role()->id,
        ]);
    }

    private function throttle(string $action, int $maxAttempts = 10): void
    {
        $key = "role-mgmt:{$action}:{$this->actor()->id}";

        abort_if(RateLimiter::tooManyAttempts($key, $maxAttempts), 429);

        RateLimiter::hit($key, 60);
    }
};
