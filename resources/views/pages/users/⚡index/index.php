<?php

use App\Actions\Users\DeleteUser;
use App\Actions\Users\ToggleUserActiveStatus;
use App\Concerns\InteractsWithTable;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Concerns\ThrottlesFormActions;
use App\Domain\Table\ActionItem;
use App\Domain\Table\Column;
use App\Domain\Table\Columns\ActionsColumn;
use App\Domain\Table\Columns\AvatarColumn;
use App\Domain\Table\Columns\BadgeListColumn;
use App\Domain\Table\Columns\BooleanColumn;
use App\Domain\Table\Columns\DateColumn;
use App\Domain\Table\Columns\IdColumn;
use App\Domain\Table\Columns\MailtoColumn;
use App\Domain\Table\Columns\ToggleColumn;
use App\Domain\Table\Filter;
use App\Domain\Table\Filters\SelectFilter;
use App\Domain\Table\TableAction;
use App\Domain\Users\RoleConfig;
use App\Infrastructure\UiFeedback\ModalService;
use App\Infrastructure\UiFeedback\ToastService;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component
{
    use InteractsWithTable;
    use ResolvesAuthenticatedUser;
    use ThrottlesFormActions;

    private const string THROTTLE_KEY_PREFIX = 'user-mgmt';

    /** @var list<string> */
    #[Url(as: 'roles', except: [])]
    public array $roleFilter = [];

    public ?int $userIdPendingDeletion = null;

    public function mount(): void
    {
        Gate::authorize('viewAny', User::class);

        $this->roleFilter = $this->sanitizedRoleFilter();
    }

    /**
     * @return list<Column>
     */
    protected function columns(): array
    {
        return [
            IdColumn::make('id')
                ->label('#'),

            ToggleColumn::make('is_active')
                ->label(__('users.index.columns.active'))
                ->wireChange('toggleUserActiveStatus')
                ->disabled(fn (User $u) => $this->isCurrentActor($u))
                ->idPrefix('user-active'),

            AvatarColumn::make('name')
                ->label(__('users.index.columns.name'))
                ->sortable()
                ->avatarSrc(fn (User $u) => $u->avatarUrl())
                ->initials(fn (User $u) => $u->initials())
                ->colorSeed(fn (User $u) => $u->id)
                ->recordUrl(fn (User $u) => route('users.show', $u))
                ->wireNavigate(),

            MailtoColumn::make('email')
                ->label(__('users.index.columns.email'))
                ->sortable(),

            BadgeListColumn::make('roles')
                ->label(__('users.index.columns.roles'))
                ->itemLabel(fn (Role $role) => $role->localizedLabel())
                ->itemColor(fn (Role $role) => $role->color)
                ->emptyLabel(__('users.index.empty_role')),

            BooleanColumn::make('email_verified_at')
                ->label(__('users.index.columns.verified'))
                ->sortable()
                ->defaultSortDirection('desc')
                ->trueLabel(__('users.index.verification.verified'))
                ->falseLabel(__('users.index.verification.pending'))
                ->trueColor('green')
                ->falseColor('yellow')
                ->trueIcon('check-circle')
                ->falseIcon('exclamation-circle'),

            DateColumn::make('created_at')
                ->label(__('users.index.columns.created'))
                ->sortable()
                ->defaultSortDirection('desc'),

            DateColumn::make('updated_at')
                ->label(__('users.index.columns.modified'))
                ->sortable()
                ->defaultSortDirection('desc'),

            ActionsColumn::make('actions')
                ->label(__('actions.actions'))
                ->actions(fn (User $u) => [
                    ActionItem::link(__('actions.view'), route('users.show', $u), 'eye', wireNavigate: true),
                    ...($this->canDelete($u) ? [
                        ActionItem::separator(),
                        ActionItem::button(__('actions.delete'), 'confirmUserDeletion', 'trash', 'danger'),
                    ] : []),
                ]),
        ];
    }

    protected function defaultSortBy(): string
    {
        return 'created_at';
    }

    protected function defaultSortDirection(): string
    {
        return 'desc';
    }

    /**
     * @return list<string>
     */
    protected function searchableFields(): array
    {
        return ['name', 'email'];
    }

    public function updatedRoleFilter(): void
    {
        $this->roleFilter = $this->sanitizedRoleFilter();
        $this->resetPage();
    }

    /**
     * @return list<Filter>
     */
    protected function filters(): array
    {
        return [
            SelectFilter::make('roleFilter')
                ->multiple()
                ->placeholder(__('users.index.filters.role'))
                ->options(fn () => Role::query()
                    ->where('guard_name', 'web')
                    ->active()
                    ->orderBy('sort_order')
                    ->get()
                    ->mapWithKeys(fn (Role $role) => [$role->name => $role->localizedLabel()])
                    ->all()),
        ];
    }

    /**
     * @return list<TableAction>
     */
    protected function actions(): array
    {
        return [
            TableAction::make('create')
                ->label(__('users.index.create_action'))
                ->icon('plus')
                ->wireClick('openCreateUserModal')
                ->variant('primary')
                ->responsive(),
        ];
    }

    /**
     * @return LengthAwarePaginator<int, User>
     */
    #[Computed]
    public function users(): LengthAwarePaginator
    {
        return $this->paginatedQuery($this->filteredQuery());
    }

    public function toggleUserActiveStatus(int $userId, bool $isActive): void
    {
        if ($this->throttle('toggle-active')) {
            return;
        }

        $user = $this->findUser($userId);

        app(ToggleUserActiveStatus::class)->handle($this->actor(), $user, $isActive);

        $messageKey = match ($isActive) {
            true => 'users.index.activated',
            false => 'users.index.deactivated',
        };

        ToastService::success(__($messageKey, ['user' => $this->userLabel($user)]));
    }

    public function openCreateUserModal(): void
    {
        Gate::forUser($this->actor())->authorize('create', User::class);

        ModalService::form(
            $this,
            name: 'users.create',
            title: __('users.create.title'),
            description: __('users.create.description'),
        );
    }

    public function confirmUserDeletion(int $userId): void
    {
        if ($this->throttle('delete', 5)) {
            return;
        }

        $actor = $this->actor();
        $user = $this->findUser($userId);

        abort_if($actor->is($user), 403);

        Gate::forUser($actor)->authorize('delete', $user);

        $this->userIdPendingDeletion = $user->id;

        ModalService::confirm(
            $this,
            title: __('users.index.confirm_delete.title'),
            message: __('users.index.confirm_delete.message', [
                'user' => $this->userLabel($user),
            ]),
            confirmLabel: __('users.index.confirm_delete.confirm_label'),
            variant: ModalService::VARIANT_PASSWORD,
        );
    }

    #[On('modal-confirmed')]
    public function deleteUser(DeleteUser $deleteUser): void
    {
        if ($this->throttle('delete', 5)) {
            return;
        }

        $user = $this->pendingDeletionUser();
        $userLabel = $this->userLabel($user);

        $deleteUser->handle($this->actor(), $user);

        $this->userIdPendingDeletion = null;
        $this->syncCurrentPage($this->filteredQuery());

        ToastService::success(__('users.index.deleted', [
            'user' => $userLabel,
        ]));
    }

    #[On('modal-confirm-cancelled')]
    public function resetPendingDeletion(): void
    {
        $this->userIdPendingDeletion = null;
    }

    #[On('user-created')]
    public function refreshUsers(): void
    {
        $this->resetPage();
    }

    private function isCurrentActor(User $user): bool
    {
        return $this->actor()->is($user);
    }

    private function canDelete(User $user): bool
    {
        return ! $this->isCurrentActor($user) && $this->actor()->can('delete', $user);
    }

    private function pendingDeletionUser(): User
    {
        abort_if($this->userIdPendingDeletion === null, 404);

        return $this->findUser($this->userIdPendingDeletion);
    }

    private function userLabel(User $user): string
    {
        return __('users.user_label', [
            'name' => $user->name,
            'id' => $user->id,
        ]);
    }

    private function findUser(int $userId): User
    {
        return User::query()->findOrFail($userId);
    }

    /**
     * @return list<string>
     */
    private function sanitizedRoleFilter(): array
    {
        return array_values(array_intersect($this->roleFilter, RoleConfig::names()));
    }

    /**
     * @return Builder<User>
     */
    private function filteredQuery(): Builder
    {
        $query = $this->baseQuery();

        if ($this->roleFilter !== []) {
            $query->whereHas('roles', fn (Builder $q) => $q->whereIn('name', $this->roleFilter));
        }

        return $query;
    }

    /**
     * @return Builder<User>
     */
    private function baseQuery(): Builder
    {
        return User::query()->with(['roles', 'media']);
    }
};
