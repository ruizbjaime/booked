<?php

use App\Actions\Countries\DeleteCountry;
use App\Actions\Countries\ToggleCountryActiveStatus;
use App\Concerns\InteractsWithTable;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Concerns\ThrottlesFormActions;
use App\Concerns\WithSortableRows;
use App\Domain\Table\ActionItem;
use App\Domain\Table\Column;
use App\Domain\Table\Columns\ActionsColumn;
use App\Domain\Table\Columns\AvatarColumn;
use App\Domain\Table\Columns\DateColumn;
use App\Domain\Table\Columns\IdColumn;
use App\Domain\Table\Columns\TextColumn;
use App\Domain\Table\Columns\ToggleColumn;
use App\Domain\Table\TableAction;
use App\Infrastructure\UiFeedback\ModalService;
use App\Infrastructure\UiFeedback\ToastService;
use App\Models\Country;
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

    private const string THROTTLE_KEY_PREFIX = 'country-mgmt';

    #[Locked]
    public ?int $countryIdPendingDeletion = null;

    public function mount(): void
    {
        Gate::authorize('viewAny', Country::class);
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
                ->label(__('countries.index.columns.active'))
                ->wireChange('toggleCountryActiveStatus')
                ->idPrefix('country-active'),

            AvatarColumn::make(Country::localizedNameColumn())
                ->label(__('countries.index.columns.name'))
                ->sortable()
                ->initials(fn (Country $c) => $c->iso_alpha2)
                ->colorSeed(fn (Country $c) => $c->id)
                ->recordUrl(fn (Country $c) => route('countries.show', $c))
                ->wireNavigate(),

            TextColumn::make('phone_code')
                ->label(__('countries.index.columns.phone_code')),

            TextColumn::make('sort_order')
                ->label(__('countries.index.columns.sort_order'))
                ->sortable(),

            DateColumn::make('created_at')
                ->label(__('countries.index.columns.created'))
                ->sortable()
                ->defaultSortDirection('desc'),

            ActionsColumn::make('actions')
                ->label(__('actions.actions'))
                ->actions(fn (Country $c) => [
                    ActionItem::link(__('actions.view'), route('countries.show', $c), 'eye', wireNavigate: true),
                    ActionItem::separator(),
                    ActionItem::button(__('actions.delete'), 'confirmCountryDeletion', 'trash', 'danger'),
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

    protected function orderColumnName(): string
    {
        return 'sort_order';
    }

    /**
     * @return class-string<Country>
     */
    protected function orderModelClass(): string
    {
        return Country::class;
    }

    /**
     * @return list<string>
     */
    protected function searchableFields(): array
    {
        return ['en_name', 'es_name', 'phone_code'];
    }

    /**
     * @return list<TableAction>
     */
    protected function actions(): array
    {
        return [
            TableAction::make('create')
                ->label(__('countries.index.create_action'))
                ->icon('plus')
                ->wireClick('openCreateCountryModal')
                ->variant('primary')
                ->responsive(),
        ];
    }

    /**
     * @return LengthAwarePaginator<int, Country>
     */
    #[Computed]
    public function countries(): LengthAwarePaginator
    {
        return $this->paginatedQuery($this->baseQuery());
    }

    public function toggleCountryActiveStatus(int $countryId, bool $isActive): void
    {
        if ($this->throttle('toggle-active')) {
            return;
        }

        $country = $this->findCountry($countryId);

        app(ToggleCountryActiveStatus::class)->handle($this->actor(), $country, $isActive);

        $messageKey = match ($isActive) {
            true => 'countries.index.activated',
            false => 'countries.index.deactivated',
        };

        ToastService::success(__($messageKey, ['country' => $this->countryLabel($country)]));
    }

    public function openCreateCountryModal(): void
    {
        Gate::forUser($this->actor())->authorize('create', Country::class);

        ModalService::form(
            $this,
            name: 'countries.create',
            title: __('countries.create.title'),
            description: __('countries.create.description'),
        );
    }

    public function confirmCountryDeletion(int $countryId): void
    {
        if ($this->throttle('delete', 5)) {
            return;
        }

        $actor = $this->actor();
        $country = $this->findCountry($countryId);

        Gate::forUser($actor)->authorize('delete', $country);

        $this->countryIdPendingDeletion = $country->id;
        $countryLabel = $this->countryLabel($country);
        $hasUsers = $country->users()->exists();

        $prefix = $hasUsers ? 'countries.index.confirm_deactivate' : 'countries.index.confirm_delete';

        ModalService::confirm(
            $this,
            title: __("{$prefix}.title"),
            message: __("{$prefix}.message", ['country' => $countryLabel]),
            confirmLabel: __("{$prefix}.confirm_label"),
            variant: ModalService::VARIANT_PASSWORD,
        );
    }

    #[On('modal-confirmed')]
    public function deleteCountry(DeleteCountry $deleteCountry): void
    {
        if ($this->throttle('delete', 5)) {
            return;
        }

        $country = $this->pendingDeletionCountry();
        $countryLabel = $this->countryLabel($country);

        $wasDeleted = $deleteCountry->handle($this->actor(), $country);

        $this->countryIdPendingDeletion = null;

        if ($wasDeleted) {
            $this->syncCurrentPage($this->baseQuery());

            ToastService::success(__('countries.index.deleted', ['country' => $countryLabel]));
        } else {
            unset($this->countries);

            ToastService::success(__('countries.index.deactivated_instead', ['country' => $countryLabel]));
        }
    }

    #[On('modal-confirm-cancelled')]
    public function resetPendingDeletion(): void
    {
        $this->countryIdPendingDeletion = null;
    }

    #[On('country-created')]
    public function refreshCountries(): void
    {
        $this->resetPage();
    }

    private function pendingDeletionCountry(): Country
    {
        abort_if($this->countryIdPendingDeletion === null, 404);

        return $this->findCountry($this->countryIdPendingDeletion);
    }

    private function countryLabel(Country $country): string
    {
        return __('countries.country_label', [
            'name' => $country->localizedName(),
            'id' => $country->id,
        ]);
    }

    private function findCountry(int $countryId): Country
    {
        return Country::query()->findOrFail($countryId);
    }

    /**
     * @return Builder<Country>
     */
    private function baseQuery(): Builder
    {
        return Country::query();
    }
};
