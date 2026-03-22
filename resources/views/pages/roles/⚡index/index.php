<?php

use App\Actions\Roles\DeleteRole;
use App\Actions\Roles\ToggleRoleActiveStatus;
use App\Concerns\InteractsWithTable;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Concerns\ThrottlesFormActions;
use App\Concerns\WithSortableRows;
use App\Domain\Table\ActionItem;
use App\Domain\Table\CardZone;
use App\Domain\Table\Column;
use App\Domain\Table\Columns\ActionsColumn;
use App\Domain\Table\Columns\BadgeColumn;
use App\Domain\Table\Columns\DateColumn;
use App\Domain\Table\Columns\IdColumn;
use App\Domain\Table\Columns\TextColumn;
use App\Domain\Table\Columns\ToggleColumn;
use App\Domain\Table\TableAction;
use App\Domain\Users\RoleConfig;
use App\Infrastructure\UiFeedback\ModalService;
use App\Infrastructure\UiFeedback\ToastService;
use App\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    use InteractsWithTable;
    use ResolvesAuthenticatedUser;
    use ThrottlesFormActions;
    use WithSortableRows;

    private const string THROTTLE_KEY_PREFIX = 'role-mgmt';

    #[Locked]
    public ?int $roleIdPendingDeletion = null;

    public function mount(): void
    {
        Gate::authorize('viewAny', Role::class);
    }

    /**
     * @return list<Column>
     */
    protected function columns(): array
    {
        $actor = $this->actor();
        $canUpdate = $actor->can('update', new Role);
        $canView = $actor->can('view', new Role);
        $canDelete = $actor->can('delete', new Role);

        return [
            IdColumn::make('id')
                ->label('#'),

            ToggleColumn::make('is_active')
                ->label(__('roles.index.columns.active'))
                ->wireChange('toggleRoleActiveStatus')
                ->disabled(fn (Role $r) => ! $canUpdate || $r->users_count > 0 || (RoleConfig::isSystemRole($r->name) && $r->is_active))
                ->idPrefix('role-active'),

            BadgeColumn::make('name')
                ->label(__('roles.index.columns.name'))
                ->color(fn (Role $r) => $r->color),

            TextColumn::make(Role::localizedLabelColumn())
                ->label(__('roles.index.columns.label'))
                ->sortable()
                ->cardZone(CardZone::Header),

            TextColumn::make('sort_order')
                ->label(__('roles.index.columns.sort_order'))
                ->sortable(),

            BadgeColumn::make('default_badge_label')
                ->label(__('roles.index.columns.default'))
                ->color('sky'),

            TextColumn::make('users_count')
                ->label(__('roles.index.columns.users'))
                ->sortable(),

            DateColumn::make('created_at')
                ->label(__('roles.index.columns.created'))
                ->sortable()
                ->defaultSortDirection('desc'),

            ...($canView || $canDelete ? [
                ActionsColumn::make('actions')
                    ->label(__('actions.actions'))
                    ->actions(fn (Role $r) => [
                        ...($canView ? [
                            ActionItem::link(__('actions.view'), route('roles.show', $r), 'eye', wireNavigate: true),
                        ] : []),
                        ...($canDelete ? [
                            ActionItem::separator(),
                            ActionItem::button(__('actions.delete'), 'confirmRoleDeletion', 'trash', 'danger')
                                ->visible(fn () => $r->users_count === 0 && ! RoleConfig::isSystemRole($r->name)),
                        ] : []),
                    ]),
            ] : []),
        ];
    }

    protected function defaultSortBy(): string
    {
        return 'sort_order';
    }

    protected function defaultSortDirection(): string
    {
        return 'asc';
    }

    protected function orderColumnName(): string
    {
        return 'sort_order';
    }

    /**
     * @return class-string<Role>
     */
    protected function orderModelClass(): string
    {
        return Role::class;
    }

    /**
     * @return list<string>
     */
    protected function searchableFields(): array
    {
        return ['name', 'en_label', 'es_label'];
    }

    /**
     * @return list<TableAction>
     */
    protected function actions(): array
    {
        if (! $this->actor()->can('create', Role::class)) {
            return [];
        }

        return [
            TableAction::make('create')
                ->label(__('roles.index.create_action'))
                ->icon('plus')
                ->wireClick('openCreateRoleModal')
                ->variant('primary')
                ->responsive(),
        ];
    }

    /**
     * @return LengthAwarePaginator<int, Role>
     */
    #[Computed]
    public function roles(): LengthAwarePaginator
    {
        return $this->paginatedQuery($this->baseQuery());
    }

    public function toggleRoleActiveStatus(int $roleId, bool $isActive): void
    {
        if ($this->throttle('toggle-active')) {
            return;
        }

        $role = $this->findRole($roleId);

        app(ToggleRoleActiveStatus::class)->handle($this->actor(), $role, $isActive);

        $messageKey = match ($isActive) {
            true => 'roles.index.activated',
            false => 'roles.index.deactivated',
        };

        ToastService::success(__($messageKey, ['role' => $this->roleLabel($role)]));
    }

    public function openCreateRoleModal(): void
    {
        Gate::forUser($this->actor())->authorize('create', Role::class);

        ModalService::form(
            $this,
            name: 'roles.create',
            title: __('roles.create.title'),
            description: __('roles.create.description'),
        );
    }

    public function confirmRoleDeletion(int $roleId): void
    {
        if ($this->throttle('delete', 5)) {
            return;
        }

        $actor = $this->actor();
        $role = $this->findRole($roleId);

        Gate::forUser($actor)->authorize('delete', $role);

        $this->roleIdPendingDeletion = (int) $role->id;

        ModalService::confirm(
            $this,
            title: __('roles.index.confirm_delete.title'),
            message: __('roles.index.confirm_delete.message', ['role' => $this->roleLabel($role)]),
            confirmLabel: __('roles.index.confirm_delete.confirm_label'),
            variant: ModalService::VARIANT_PASSWORD,
        );
    }

    #[On('modal-confirmed')]
    public function deleteRole(DeleteRole $deleteRole): void
    {
        if ($this->throttle('delete', 5)) {
            return;
        }

        $role = $this->pendingDeletionRole();
        $roleLabel = $this->roleLabel($role);

        $deleteRole->handle($this->actor(), $role);

        $this->roleIdPendingDeletion = null;

        $this->syncCurrentPage($this->baseQuery());

        ToastService::success(__('roles.index.deleted', ['role' => $roleLabel]));
    }

    #[On('modal-confirm-cancelled')]
    public function resetPendingDeletion(): void
    {
        $this->roleIdPendingDeletion = null;
    }

    #[On('role-created')]
    public function refreshRoles(): void
    {
        $this->resetPage();
    }

    private function pendingDeletionRole(): Role
    {
        abort_if($this->roleIdPendingDeletion === null, 404);

        return $this->findRole($this->roleIdPendingDeletion);
    }

    private function roleLabel(Role $role): string
    {
        return __('roles.role_label', [
            'name' => $role->localizedLabel(),
            'id' => $role->id,
        ]);
    }

    private function findRole(int $roleId): Role
    {
        return Role::query()->findOrFail($roleId);
    }

    /**
     * @return Builder<Role>
     */
    private function baseQuery(): Builder
    {
        return Role::query()->where('guard_name', 'web')->withCount('users');
    }
};
