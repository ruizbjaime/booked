<?php

use App\Actions\FeeTypes\DeleteFeeType;
use App\Actions\FeeTypes\UpdateFeeType;
use App\Actions\FeeTypes\UpdateFeeTypeChargeBases;
use App\Concerns\FormatsLocalizedDates;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Concerns\ThrottlesFormActions;
use App\Infrastructure\UiFeedback\ModalService;
use App\Infrastructure\UiFeedback\ToastService;
use App\Models\ChargeBasis;
use App\Models\FeeType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    use FormatsLocalizedDates;
    use ResolvesAuthenticatedUser;
    use ThrottlesFormActions;

    private const string THROTTLE_KEY_PREFIX = 'fee-type-mgmt';

    private const string SECTION_DETAILS = 'details';

    private const string SECTION_CHARGE_BASES = 'charge_bases';

    /** @var list<string> */
    private const array AUTOSAVE_FIELDS = ['name', 'en_name', 'es_name', 'order'];

    public FeeType $targetFeeType;

    public ?string $editingSection = null;

    #[Locked]
    public ?int $feeTypeIdPendingDeletion = null;

    public string $name = '';

    public string $en_name = '';

    public string $es_name = '';

    public int $order = 0;

    /** @var list<int> */
    public array $selectedChargeBases = [];

    public function mount(string $feeType): void
    {
        $target = FeeType::query()->with('chargeBases')->findOrFail($feeType);

        Gate::authorize('view', $target);

        $this->targetFeeType = $target;
        $this->fillForm($target);
    }

    #[Computed]
    public function feeType(): FeeType
    {
        return $this->targetFeeType;
    }

    /**
     * @return Collection<int, ChargeBasis>
     */
    #[Computed]
    public function availableChargeBases(): Collection
    {
        return ChargeBasis::query()->orderBy('order')->orderBy('id')->get();
    }

    public function startEditingSection(string $section): void
    {
        abort_unless(in_array($section, [self::SECTION_DETAILS, self::SECTION_CHARGE_BASES], true), 404);

        $this->authorizeFeeTypeUpdate();

        $this->editingSection = $section;
        $this->fillForm($this->feeType());
        $this->resetValidation();
    }

    public function cancelEditingSection(): void
    {
        $this->editingSection = null;
        $this->fillForm($this->feeType());
        $this->resetValidation();
    }

    public function updated(string $property): void
    {
        if (in_array($property, self::AUTOSAVE_FIELDS, true)) {
            $this->autosaveField($property);
        }
    }

    public function updatedSelectedChargeBases(): void
    {
        if ($this->editingSection !== self::SECTION_CHARGE_BASES) {
            return;
        }

        $this->selectedChargeBases = $this->normalizeSelectedChargeBases($this->selectedChargeBases);
        $this->resetValidation('selectedChargeBases');
    }

    public function handleChargeBasisSort(int|string $id, int|string $position, UpdateFeeTypeChargeBases $updateFeeTypeChargeBases): void
    {
        if ($this->throttle('sort-charge-bases')) {
            return;
        }

        $this->authorizeFeeTypeUpdate();

        $id = (int) $id;
        $position = (int) $position;

        $ordered = array_values(array_filter(
            $this->selectedChargeBases,
            static fn (int $cbId): bool => $cbId !== $id,
        ));

        array_splice($ordered, $position, 0, [$id]);

        $this->selectedChargeBases = $ordered;

        $updateFeeTypeChargeBases->handle($this->actor(), $this->feeType(), $ordered);

        $this->refreshFeeTypeState();

        ToastService::success(__('fee_types.show.saved.charge_bases'));
    }

    public function saveChargeBases(UpdateFeeTypeChargeBases $updateFeeTypeChargeBases): void
    {
        if ($this->editingSection !== self::SECTION_CHARGE_BASES) {
            return;
        }

        if ($this->throttle('save-charge-bases')) {
            return;
        }

        $this->authorizeFeeTypeUpdate();

        $this->selectedChargeBases = $this->normalizeSelectedChargeBases($this->selectedChargeBases);

        $updateFeeTypeChargeBases->handle($this->actor(), $this->feeType(), $this->selectedChargeBases);

        $this->refreshFeeTypeState();

        ToastService::success(__('fee_types.show.saved.charge_bases'));
    }

    public function confirmFeeTypeDeletion(): void
    {
        if ($this->throttle('delete')) {
            return;
        }

        $actor = $this->actor();
        $feeType = $this->feeType();

        Gate::forUser($actor)->authorize('delete', $feeType);

        $this->feeTypeIdPendingDeletion = $feeType->id;

        ModalService::confirm(
            $this,
            title: __('fee_types.show.quick_actions.delete.title'),
            message: __('fee_types.show.quick_actions.delete.message', ['fee_type' => $this->feeTypeLabel()]),
            confirmLabel: __('fee_types.show.quick_actions.delete.confirm_label'),
            variant: ModalService::VARIANT_PASSWORD,
        );
    }

    #[On('modal-confirmed')]
    public function handleModalConfirmed(DeleteFeeType $deleteFeeType): void
    {
        if ($this->throttle('confirmed-action')) {
            return;
        }

        if ($this->feeTypeIdPendingDeletion === null) {
            return;
        }

        $feeTypeLabel = $this->feeTypeLabel();

        $deleteFeeType->handle($this->actor(), $this->feeType());

        $this->feeTypeIdPendingDeletion = null;

        ToastService::success(__('fee_types.show.quick_actions.delete.deleted', ['fee_type' => $feeTypeLabel]));

        $this->redirect(route('fee-types.index'), navigate: true);
    }

    #[On('modal-confirm-cancelled')]
    public function resetPendingSensitiveActions(): void
    {
        $this->feeTypeIdPendingDeletion = null;
    }

    public function canEdit(): bool
    {
        return Gate::forUser($this->actor())->allows('update', $this->feeType());
    }

    public function canDelete(): bool
    {
        return Gate::forUser($this->actor())->allows('delete', $this->feeType());
    }

    private function autosaveField(string $property): void
    {
        if ($this->editingSection !== self::SECTION_DETAILS) {
            return;
        }

        if ($this->throttle('autosave')) {
            return;
        }

        $this->authorizeFeeTypeUpdate();
        $this->resetValidation($property);

        try {
            app(UpdateFeeType::class)->handle($this->actor(), $this->feeType(), $property, $this->$property);
        } catch (ValidationException $exception) {
            $this->fillForm($this->feeType());

            throw $exception;
        }

        $this->refreshFeeTypeState();

        ToastService::success(__('fee_types.show.saved.details'));
    }

    private function refreshFeeTypeState(): void
    {
        $this->targetFeeType = FeeType::query()
            ->with('chargeBases')
            ->where('id', $this->targetFeeType->getKey())
            ->firstOrFail();

        $this->fillForm($this->targetFeeType);
    }

    private function fillForm(FeeType $feeType): void
    {
        $this->name = $feeType->name;
        $this->en_name = $feeType->en_name;
        $this->es_name = $feeType->es_name;
        $this->order = (int) $feeType->order;
        /** @var list<int|string> $selectedChargeBases */
        $selectedChargeBases = $feeType->chargeBases->sortBy('pivot.sort_order')->pluck('id')->all();
        $this->selectedChargeBases = $this->normalizeSelectedChargeBases(
            $selectedChargeBases,
        );
    }

    private function authorizeFeeTypeUpdate(): void
    {
        Gate::forUser($this->actor())->authorize('update', $this->feeType());
    }

    private function feeTypeLabel(): string
    {
        return __('fee_types.fee_type_label', [
            'name' => $this->feeType()->localizedName(),
            'id' => $this->feeType()->id,
        ]);
    }

    /**
     * @param  list<int|string>  $selectedChargeBases
     * @return list<int>
     */
    private function normalizeSelectedChargeBases(array $selectedChargeBases): array
    {
        $normalized = array_map(
            static fn (int|string $chargeBasisId): int => (int) $chargeBasisId,
            $selectedChargeBases,
        );

        $normalized = array_values(array_filter($normalized, static fn (int $chargeBasisId): bool => $chargeBasisId > 0));

        return array_values(array_unique($normalized));
    }
};
