<?php

use App\Concerns\PasswordValidationRules;
use App\Infrastructure\UiFeedback\ModalService;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    use PasswordValidationRules;

    private const string CONFIRM_MODAL = 'modal-confirm';

    private const string INFO_MODAL = 'modal-info';

    private const string FORM_MODAL = 'modal-form';

    /**
     * @var list<string>
     */
    private const array VALID_CONFIRM_VARIANTS = [
        ModalService::VARIANT_STANDARD,
        ModalService::VARIANT_PASSWORD,
    ];

    /**
     * @var array<string, string>
     */
    private const array FORM_COMPONENTS = [
        'users.create' => 'users.create-user-form',
        'countries.create' => 'countries.create-country-form',
        'identification-document-types.create' => 'identification-document-types.create-identification-document-type-form',
        'bed-types.create' => 'bed-types.create-bed-type-form',
        'fee-types.create' => 'fee-types.create-fee-type-form',
        'charge-bases.create' => 'charge-bases.create-charge-basis-form',
        'bath-room-types.create' => 'bath-room-types.create-bath-room-type-form',
        'platforms.create' => 'platforms.create-platform-form',
        'roles.create' => 'roles.create-role-form',
        'calendar.pricing-category-form' => 'calendar.pricing-category-form',
        'calendar.pricing-rules.form' => 'calendar.pricing-rule-form',
        'calendar.season-block-form' => 'calendar.season-block-form',
    ];

    public string $confirmTitle = '';

    public string $confirmMessage = '';

    public string $confirmLabel = '';

    public string $confirmVariant = ModalService::VARIANT_STANDARD;

    public string $confirmPassword = '';

    public string $confirmUsername = '';

    public string $infoTitle = '';

    public string $infoMessage = '';

    public string $formModalName = '';

    public string $formTitle = '';

    public string $formDescription = '';

    public string $formWidth = ModalService::WIDTH_DEFAULT;

    /**
     * @var array<string, mixed>
     */
    public array $formContext = [];

    #[On('open-confirm-modal')]
    public function openConfirm(
        string $title,
        string $message,
        ?string $confirmLabel = null,
        string $variant = ModalService::VARIANT_STANDARD,
    ): void {
        $this->confirmTitle = $title;
        $this->confirmMessage = $message;
        $this->confirmLabel = $confirmLabel ?? $this->defaultConfirmLabel();
        $this->confirmVariant = $this->normalizeConfirmVariant($variant);
        $this->confirmUsername = $this->currentUserIdentifier();
        $this->resetConfirmPasswordState();

        $this->showModal(self::CONFIRM_MODAL);
    }

    #[On('open-info-modal')]
    public function openInfo(string $title, string $message): void
    {
        $this->infoTitle = $title;
        $this->infoMessage = $message;

        $this->showModal(self::INFO_MODAL);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    #[On('open-form-modal')]
    public function openForm(
        string $name,
        string $title,
        string $description = '',
        array $context = [],
        string $width = ModalService::WIDTH_DEFAULT,
    ): void {
        abort_if(! array_key_exists($name, self::FORM_COMPONENTS), 404);

        $this->formModalName = $name;
        $this->formTitle = $title;
        $this->formDescription = $description;
        $this->formContext = $context;
        $this->formWidth = $width;

        $this->showModal(self::FORM_MODAL);
    }

    public function confirm(): void
    {
        $this->validateConfirmPassword();

        $this->closeConfirmModal();

        $this->dispatch('modal-confirmed');
    }

    public function closeConfirm(): void
    {
        $this->closeConfirmModal();

        $this->dispatch('modal-confirm-cancelled');
    }

    private function closeConfirmModal(): void
    {
        $this->closeModal(self::CONFIRM_MODAL);

        $this->reset('confirmTitle', 'confirmMessage', 'confirmUsername');
        $this->confirmLabel = $this->defaultConfirmLabel();
        $this->confirmVariant = ModalService::VARIANT_STANDARD;
        $this->resetConfirmPasswordState();
    }

    public function closeInfo(): void
    {
        $this->closeModal(self::INFO_MODAL);

        $this->reset('infoTitle', 'infoMessage');
    }

    #[On('close-form-modal')]
    public function closeForm(): void
    {
        $this->closeModal(self::FORM_MODAL);

        $this->reset('formModalName', 'formTitle', 'formDescription', 'formContext');
        $this->formWidth = ModalService::WIDTH_DEFAULT;
    }

    private function defaultConfirmLabel(): string
    {
        return __('actions.confirm');
    }

    public function requiresPasswordConfirmation(): bool
    {
        return $this->confirmVariant === ModalService::VARIANT_PASSWORD;
    }

    private function normalizeConfirmVariant(string $variant): string
    {
        return in_array($variant, self::VALID_CONFIRM_VARIANTS, true)
            ? $variant
            : ModalService::VARIANT_STANDARD;
    }

    public function formComponent(): ?string
    {
        return self::FORM_COMPONENTS[$this->formModalName] ?? null;
    }

    private function resetConfirmPasswordState(): void
    {
        $this->confirmPassword = '';
        $this->resetValidation('confirmPassword');
    }

    private function validateConfirmPassword(): void
    {
        if (! $this->requiresPasswordConfirmation()) {
            return;
        }

        try {
            $this->validate([
                'confirmPassword' => $this->currentPasswordRules(),
            ], [], [
                'confirmPassword' => __('Current password'),
            ]);
        } catch (ValidationException $exception) {
            $this->confirmPassword = '';

            throw $exception;
        }
    }

    private function currentUserIdentifier(): string
    {
        $user = Auth::user();

        return $user instanceof User ? (string) $user->email : '';
    }

    private function showModal(string $name): void
    {
        $this->invokeModalMethod($name, 'show');
    }

    private function closeModal(string $name): void
    {
        $this->invokeModalMethod($name, 'close');
    }

    private function invokeModalMethod(string $name, string $method): void
    {
        if (! app('livewire')->current()) {
            return;
        }

        $modal = $this->modal($name);

        if (is_object($modal) && method_exists($modal, $method)) {
            $modal->$method();
        }
    }
};
