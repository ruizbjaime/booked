<?php

use App\Actions\Platforms\DeletePlatform;
use App\Actions\Platforms\TogglePlatformActiveStatus;
use App\Concerns\InteractsWithTable;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Domain\Table\ActionItem;
use App\Domain\Table\Column;
use App\Domain\Table\Columns\ActionsColumn;
use App\Domain\Table\Columns\AvatarColumn;
use App\Domain\Table\Columns\BadgeColumn;
use App\Domain\Table\Columns\DateColumn;
use App\Domain\Table\Columns\IdColumn;
use App\Domain\Table\Columns\PercentageColumn;
use App\Domain\Table\Columns\TextColumn;
use App\Domain\Table\Columns\ToggleColumn;
use App\Domain\Table\TableAction;
use App\Infrastructure\UiFeedback\ModalService;
use App\Infrastructure\UiFeedback\ToastService;
use App\Models\Platform;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    use InteractsWithTable;
    use ResolvesAuthenticatedUser;

    #[Locked]
    public ?int $platformIdPendingDeletion = null;

    public function mount(): void
    {
        Gate::authorize('viewAny', Platform::class);
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
                ->label(__('platforms.index.columns.active'))
                ->wireChange('togglePlatformActiveStatus')
                ->idPrefix('platform-active'),

            AvatarColumn::make(Platform::localizedNameColumn())
                ->label(__('platforms.index.columns.localized_name'))
                ->sortable()
                ->initials(fn (Platform $p) => mb_substr($p->localizedName(), 0, 1))
                ->color(fn (Platform $p) => $p->color)
                ->recordUrl(fn (Platform $p) => route('platforms.show', $p))
                ->wireNavigate(),

            BadgeColumn::make('name')
                ->label(__('platforms.index.columns.name'))
                ->color(fn (Platform $p) => $p->color),

            PercentageColumn::make('commission')
                ->label(__('platforms.index.columns.commission'))
                ->multiplier(100)
                ->decimals(2),

            PercentageColumn::make('commission_tax')
                ->label(__('platforms.index.columns.commission_tax'))
                ->multiplier(100)
                ->decimals(2),

            TextColumn::make('sort_order')
                ->label(__('platforms.index.columns.sort_order'))
                ->sortable(),

            DateColumn::make('created_at')
                ->label(__('platforms.index.columns.created'))
                ->sortable()
                ->defaultSortDirection('desc'),

            ActionsColumn::make('actions')
                ->label(__('actions.actions'))
                ->actions(fn (Platform $p) => [
                    ActionItem::link(__('actions.view'), route('platforms.show', $p), 'eye', wireNavigate: true),
                    ActionItem::separator(),
                    ActionItem::button(__('actions.delete'), 'confirmPlatformDeletion', 'trash', 'danger'),
                ]),
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

    /**
     * @return list<string>
     */
    protected function searchableFields(): array
    {
        return ['name', 'en_name', 'es_name'];
    }

    /**
     * @return list<TableAction>
     */
    protected function actions(): array
    {
        return [
            TableAction::make('create')
                ->label(__('platforms.index.create_action'))
                ->icon('plus')
                ->wireClick('openCreatePlatformModal')
                ->variant('primary')
                ->responsive(),
        ];
    }

    /**
     * @return LengthAwarePaginator<int, Platform>
     */
    #[Computed]
    public function platforms(): LengthAwarePaginator
    {
        return $this->paginatedQuery($this->baseQuery());
    }

    public function togglePlatformActiveStatus(int $platformId, bool $isActive): void
    {
        $this->throttle('toggle-active');

        $platform = $this->findPlatform($platformId);

        app(TogglePlatformActiveStatus::class)->handle($this->actor(), $platform, $isActive);

        $messageKey = match ($isActive) {
            true => 'platforms.index.activated',
            false => 'platforms.index.deactivated',
        };

        ToastService::success(__($messageKey, ['platform' => $this->platformLabel($platform)]));
    }

    public function openCreatePlatformModal(): void
    {
        Gate::forUser($this->actor())->authorize('create', Platform::class);

        ModalService::form(
            $this,
            name: 'platforms.create',
            title: __('platforms.create.title'),
            description: __('platforms.create.description'),
        );
    }

    public function confirmPlatformDeletion(int $platformId): void
    {
        $this->throttle('delete', 5);

        $actor = $this->actor();
        $platform = $this->findPlatform($platformId);

        Gate::forUser($actor)->authorize('delete', $platform);

        $this->platformIdPendingDeletion = $platform->id;

        ModalService::confirm(
            $this,
            title: __('platforms.index.confirm_delete.title'),
            message: __('platforms.index.confirm_delete.message', ['platform' => $this->platformLabel($platform)]),
            confirmLabel: __('platforms.index.confirm_delete.confirm_label'),
            variant: ModalService::VARIANT_PASSWORD,
        );
    }

    #[On('modal-confirmed')]
    public function deletePlatform(DeletePlatform $deletePlatform): void
    {
        $this->throttle('delete', 5);

        $platform = $this->pendingDeletionPlatform();
        $platformLabel = $this->platformLabel($platform);

        $deletePlatform->handle($this->actor(), $platform);

        $this->platformIdPendingDeletion = null;

        $this->syncCurrentPage($this->baseQuery());

        ToastService::success(__('platforms.index.deleted', ['platform' => $platformLabel]));
    }

    #[On('modal-confirm-cancelled')]
    public function resetPendingDeletion(): void
    {
        $this->platformIdPendingDeletion = null;
    }

    #[On('platform-created')]
    public function refreshPlatforms(): void
    {
        $this->resetPage();
    }

    private function pendingDeletionPlatform(): Platform
    {
        abort_if($this->platformIdPendingDeletion === null, 404);

        return $this->findPlatform($this->platformIdPendingDeletion);
    }

    private function platformLabel(Platform $platform): string
    {
        return __('platforms.platform_label', [
            'name' => $platform->localizedName(),
            'id' => $platform->id,
        ]);
    }

    private function findPlatform(int $platformId): Platform
    {
        return Platform::query()->findOrFail($platformId);
    }

    /**
     * @return Builder<Platform>
     */
    private function baseQuery(): Builder
    {
        return Platform::query();
    }

    private function throttle(string $action, int $maxAttempts = 10): void
    {
        $key = "platform-mgmt:{$action}:{$this->actor()->id}";

        abort_if(RateLimiter::tooManyAttempts($key, $maxAttempts), 429);

        RateLimiter::hit($key, 60);
    }
};
