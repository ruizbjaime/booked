<?php

use App\Actions\ChargeBases\DeleteChargeBasis;
use App\Actions\ChargeBases\UpdateChargeBasis;
use App\Concerns\FormatsLocalizedDates;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Infrastructure\UiFeedback\ModalService;
use App\Infrastructure\UiFeedback\ToastService;
use App\Models\ChargeBasis;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    use FormatsLocalizedDates;
    use ResolvesAuthenticatedUser;

    private const string SECTION_DETAILS = 'details';

    /** @var list<string> */
    private const array AUTOSAVE_FIELDS = ['name', 'en_name', 'es_name', 'description', 'order', 'is_active'];

    public ChargeBasis $targetChargeBasis;

    public ?string $editingSection = null;

    #[Locked]
    public ?int $chargeBasisIdPendingDeletion = null;

    public string $name = '';

    public string $en_name = '';

    public string $es_name = '';

    public string $description = '';

    public int $order = 0;

    public bool $is_active = false;

    public bool $requires_quantity = false;

    public string $quantity_subject = '';

    public function mount(string $chargeBasis): void
    {
        $target = ChargeBasis::query()->findOrFail($chargeBasis);

        Gate::authorize('view', $target);

        $this->targetChargeBasis = $target;
        $this->fillForm($target);
    }

    #[Computed]
    public function chargeBasis(): ChargeBasis
    {
        return $this->targetChargeBasis;
    }

    public function startEditingSection(string $section): void
    {
        abort_unless($section === self::SECTION_DETAILS, 404);

        $this->authorizeChargeBasisUpdate();

        $this->editingSection = $section;
        $this->fillForm($this->chargeBasis());
        $this->resetValidation();
    }

    public function cancelEditingSection(): void
    {
        $this->editingSection = null;
        $this->fillForm($this->chargeBasis());
        $this->resetValidation();
    }

    public function updated(string $property): void
    {
        if (in_array($property, self::AUTOSAVE_FIELDS, true)) {
            $this->autosaveField($property);

            return;
        }

        if ($property === 'requires_quantity') {
            if (! $this->requires_quantity) {
                $this->quantity_subject = '';
                $this->resetValidation('quantity_subject');
            }

            $this->autosaveField('metadata.requires_quantity');

            if (! $this->requires_quantity) {
                $this->autosaveField('metadata.quantity_subject');
            }

            return;
        }

        if ($property === 'quantity_subject') {
            $this->autosaveField('metadata.quantity_subject');
        }
    }

    public function confirmChargeBasisDeletion(): void
    {
        $this->throttle('delete', 5);

        $actor = $this->actor();
        $chargeBasis = $this->chargeBasis();

        Gate::forUser($actor)->authorize('delete', $chargeBasis);

        $this->chargeBasisIdPendingDeletion = $chargeBasis->id;

        ModalService::confirm(
            $this,
            title: __('charge_bases.show.quick_actions.delete.title'),
            message: __('charge_bases.show.quick_actions.delete.message', ['charge_basis' => $this->chargeBasisLabel()]),
            confirmLabel: __('charge_bases.show.quick_actions.delete.confirm_label'),
            variant: ModalService::VARIANT_PASSWORD,
        );
    }

    #[On('modal-confirmed')]
    public function handleModalConfirmed(DeleteChargeBasis $deleteChargeBasis): void
    {
        $this->throttle('confirmed-action', 5);

        if ($this->chargeBasisIdPendingDeletion === null) {
            return;
        }

        $chargeBasisLabel = $this->chargeBasisLabel();

        $deleteChargeBasis->handle($this->actor(), $this->chargeBasis());

        $this->chargeBasisIdPendingDeletion = null;

        ToastService::success(__('charge_bases.show.quick_actions.delete.deleted', ['charge_basis' => $chargeBasisLabel]));

        $this->redirect(route('charge-bases.index'), navigate: true);
    }

    #[On('modal-confirm-cancelled')]
    public function resetPendingSensitiveActions(): void
    {
        $this->chargeBasisIdPendingDeletion = null;
    }

    public function canEdit(): bool
    {
        return Gate::forUser($this->actor())->allows('update', $this->chargeBasis());
    }

    public function canDelete(): bool
    {
        return Gate::forUser($this->actor())->allows('delete', $this->chargeBasis());
    }

    private function autosaveField(string $field): void
    {
        if ($this->editingSection !== self::SECTION_DETAILS) {
            return;
        }

        $this->throttle('autosave');
        $this->authorizeChargeBasisUpdate();
        $this->resetValidation([$field, str_replace('metadata.', '', $field)]);

        try {
            app(UpdateChargeBasis::class)->handle($this->actor(), $this->chargeBasis(), $field, $this->fieldValue($field));
        } catch (ValidationException $exception) {
            $this->fillForm($this->chargeBasis());

            throw $exception;
        }

        $this->refreshChargeBasisState();

        ToastService::success(__('charge_bases.show.saved.details'));
    }

    private function fieldValue(string $field): mixed
    {
        return match ($field) {
            'metadata.requires_quantity' => $this->requires_quantity,
            'metadata.quantity_subject' => $this->requires_quantity ? $this->blankToNull($this->quantity_subject) : null,
            'description' => $this->blankToNull($this->description),
            default => $this->{$field},
        };
    }

    private function refreshChargeBasisState(): void
    {
        $this->targetChargeBasis = ChargeBasis::query()->where('id', $this->targetChargeBasis->getKey())->firstOrFail();

        $this->fillForm($this->targetChargeBasis);
    }

    private function fillForm(ChargeBasis $chargeBasis): void
    {
        /** @var mixed $metadataValue */
        $metadataValue = $chargeBasis->getAttributeValue('metadata');
        /** @var array<string, mixed> $metadata */
        $metadata = is_array($metadataValue) ? $metadataValue : [];

        $this->name = $chargeBasis->name;
        $this->en_name = $chargeBasis->en_name;
        $this->es_name = $chargeBasis->es_name;
        $this->description = $chargeBasis->description ?? '';
        $this->order = (int) $chargeBasis->order;
        $this->is_active = $chargeBasis->is_active;
        $this->requires_quantity = (bool) ($metadata['requires_quantity'] ?? false);
        $this->quantity_subject = is_string($metadata['quantity_subject'] ?? null) ? $metadata['quantity_subject'] : '';
    }

    private function authorizeChargeBasisUpdate(): void
    {
        Gate::forUser($this->actor())->authorize('update', $this->chargeBasis());
    }

    private function chargeBasisLabel(): string
    {
        return __('charge_bases.charge_basis_label', [
            'name' => $this->chargeBasis()->localizedName(),
            'id' => $this->chargeBasis()->id,
        ]);
    }

    private function blankToNull(string $value): ?string
    {
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function throttle(string $action, int $maxAttempts = 10): void
    {
        $key = "charge-basis-mgmt:{$action}:{$this->actor()->id}";

        abort_if(RateLimiter::tooManyAttempts($key, $maxAttempts), 429);

        RateLimiter::hit($key, 60);
    }
};
