<?php

use App\Actions\Users\DeleteUser;
use App\Actions\Users\UpdateUserAccess;
use App\Actions\Users\UpdateUserAvatar;
use App\Actions\Users\UpdateUserPassword;
use App\Actions\Users\UpdateUserProfile;
use App\Concerns\FormatsLocalizedDates;
use App\Concerns\HasRolePresentation;
use App\Concerns\ProfileValidationRules;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Concerns\ThrottlesFormActions;
use App\Domain\Users\PhoneCountryResolver;
use App\Domain\Users\RoleNormalizer;
use App\Infrastructure\UiFeedback\ModalService;
use App\Infrastructure\UiFeedback\ToastService;
use App\Models\Country;
use App\Models\IdentificationDocumentType;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new class extends Component
{
    use FormatsLocalizedDates;
    use HasRolePresentation;
    use ProfileValidationRules;
    use ResolvesAuthenticatedUser;
    use ThrottlesFormActions;
    use WithFileUploads;

    private const string THROTTLE_KEY_PREFIX = 'user-mgmt';

    private const string SECTION_ACCOUNT = 'account';

    private const string SECTION_PERSONAL = 'personal';

    private const string SECTION_ACCESS = 'access';

    public User $targetUser;

    public ?string $editingSection = null;

    public ?int $userIdPendingDeletion = null;

    public string $name = '';

    public string $email = '';

    /**
     * @var list<string>
     */
    public array $roles = [];

    public bool $active = false;

    public string $password = '';

    public string $password_confirmation = '';

    /**
     * @var array<int, string>
     */
    public array $availableRoles = [];

    public ?bool $twoFactorValue = null;

    public ?bool $twoFactorOriginalValue = null;

    public ?bool $twoFactorPendingValue = null;

    public bool $showTwoFactorModal = false;

    public bool $showTwoFactorVerificationStep = false;

    public string $twoFactorCode = '';

    /** @var TemporaryUploadedFile|null */
    public $photo = null;

    public ?string $phone = '';

    public ?int $document_type_id = null;

    public ?string $document_number = '';

    public ?int $country_id = null;

    public ?string $state = '';

    public ?string $city = '';

    public ?string $address = '';

    public string $countrySearch = '';

    #[Locked]
    public string $twoFactorQrCodeSvg = '';

    #[Locked]
    public string $twoFactorManualSetupKey = '';

    public function mount(string $user): void
    {
        $target = User::query()->with(['roles', 'media'])->findOrFail($user);

        Gate::authorize('view', $target);

        $this->targetUser = $target;
        $this->availableRoles = RoleNormalizer::available();
        $this->fillForm($target);
    }

    #[Computed]
    public function user(): User
    {
        return $this->targetUser;
    }

    #[Computed]
    public function userAvatarUrl(): ?string
    {
        return $this->targetUser->avatarUrl();
    }

    #[Computed]
    public function maxUploadSizeMb(): int
    {
        return SystemSetting::instance()->max_upload_size_mb;
    }

    /**
     * @return Collection<int, Country>
     */
    #[Computed]
    public function countries(): Collection
    {
        return Country::query()
            ->active()
            ->when($this->countrySearch !== '', fn ($query) => $query->search($this->countrySearch))
            ->orderBy('sort_order')
            ->orderBy('en_name')
            ->get();
    }

    /**
     * @return Collection<int, IdentificationDocumentType>
     */
    #[Computed]
    public function documentTypes(): Collection
    {
        return IdentificationDocumentType::query()->active()->orderBy('sort_order')->get();
    }

    public function startEditingSection(string $section): void
    {
        abort_unless($this->isValidSection($section), 404);

        $this->authorizeUserUpdate();

        $this->editingSection = $section;
        $this->fillForm($this->user());
        $this->resetValidation();
    }

    public function cancelEditingSection(): void
    {
        $this->editingSection = null;
        $this->fillForm($this->user());
        $this->resetValidation();
        $this->resetTwoFactorUi();
    }

    public function confirmUserDeletion(): void
    {
        if ($this->throttle('delete', 5)) {
            return;
        }

        $actor = $this->actor();
        $user = $this->user();

        abort_if($actor->is($user), 403);

        Gate::forUser($actor)->authorize('delete', $user);

        $this->userIdPendingDeletion = $user->id;

        ModalService::confirm(
            $this,
            title: __('users.show.quick_actions.delete.title'),
            message: __('users.show.quick_actions.delete.message', [
                'user' => $this->userLabel($user),
            ]),
            confirmLabel: __('users.show.quick_actions.delete.confirm_label'),
            variant: ModalService::VARIANT_PASSWORD,
        );
    }

    public function updatedRoles(): void
    {
        $this->roles = $this->normalizeRoles($this->roles);
        $this->resetValidation('roles');
    }

    public function updatedPassword(): void
    {
        $this->resetValidation(['password', 'password_confirmation']);
    }

    public function updatedPasswordConfirmation(): void
    {
        $this->resetValidation(['password', 'password_confirmation']);
    }

    public function updatedName(): void
    {
        if ($this->editingSection !== self::SECTION_ACCOUNT) {
            return;
        }

        $this->authorizeUserUpdate();
        $this->resetValidation('name');
        $this->saveAccountField('name');
    }

    public function updatedEmail(): void
    {
        if ($this->editingSection !== self::SECTION_ACCOUNT) {
            return;
        }

        $this->authorizeUserUpdate();
        $this->resetValidation('email');
        $this->saveAccountField('email');
    }

    public function updatedPhone(): void
    {
        if ($this->editingSection !== self::SECTION_PERSONAL) {
            return;
        }

        $this->authorizeUserUpdate();
        $this->resetValidation('phone');

        if (filled($this->phone)) {
            $isoCode = app(PhoneCountryResolver::class)->detectCountryFromPhone($this->phone);

            if ($isoCode !== null) {
                $country = Country::query()->active()->where('iso_alpha2', $isoCode)->first();

                if ($country !== null) {
                    $this->country_id = $country->id;
                }
            }
        }

        $this->savePersonalField('phone');

        if ($this->country_id !== $this->targetUser->country_id) {
            $this->autosavePersonalField('country_id');
        }
    }

    public function updatedCountryId(): void
    {
        $this->autosavePersonalField('country_id');
    }

    public function updatedDocumentTypeId(): void
    {
        $this->resetValidation(['document_type_id', 'document_number']);
        $this->autosavePersonalField('document_type_id');
        $this->crossValidateDocumentFields();
    }

    public function updatedDocumentNumber(): void
    {
        $this->resetValidation(['document_type_id', 'document_number']);
        $this->autosavePersonalField('document_number');
        $this->crossValidateDocumentFields();
    }

    public function updatedState(): void
    {
        $this->autosavePersonalField('state');
    }

    public function updatedCity(): void
    {
        $this->autosavePersonalField('city');
    }

    public function updatedAddress(): void
    {
        $this->autosavePersonalField('address');
    }

    public function updatedActive(): void
    {
        if ($this->editingSection !== self::SECTION_ACCESS) {
            return;
        }

        $this->authorizeUserUpdate();
        $this->saveActiveStatus();
    }

    public function updatedPhoto(): void
    {
        $photo = $this->photo;

        if (! $photo instanceof TemporaryUploadedFile) {
            return;
        }

        $this->authorizeUserUpdate();

        app(UpdateUserAvatar::class)->handle(
            $this->actor(),
            $this->user(),
            $photo,
        );

        $this->photo = null;
        $this->refreshUserMedia();

        ToastService::success(__('users.show.saved.avatar'));
    }

    public function deleteAvatar(): void
    {
        $this->authorizeUserUpdate();

        $this->user()->clearMediaCollection('avatar');
        $this->refreshUserMedia();

        ToastService::success(__('users.show.saved.avatar_deleted'));
    }

    public function updatedTwoFactorValue(): void
    {
        $this->authorizeUserUpdate();
        $this->handleTwoFactorToggle();
    }

    public function saveRoles(): void
    {
        $this->authorizeUserUpdate();

        $user = $this->user();

        $user = app(UpdateUserAccess::class)->handle($this->actor(), $user, [
            'is_active' => $user->is_active,
            'roles' => $this->roles,
        ]);

        $this->fillForm($user);

        ToastService::success(__('users.show.saved.roles'));
    }

    public function updatePassword(): void
    {
        if ($this->throttle('update-password', 5)) {
            return;
        }

        $this->authorizeUserUpdate();

        try {
            app(UpdateUserPassword::class)->handle($this->actor(), $this->user(), [
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
            ]);
        } catch (ValidationException $exception) {
            $this->resetPasswordFields();

            throw $exception;
        }

        $this->resetPasswordFields();

        ToastService::success(__('users.show.saved.password'));
    }

    public function confirmTwoFactor(ConfirmTwoFactorAuthentication $confirmTwoFactorAuthentication): void
    {
        $this->authorizeUserUpdate();

        $this->validate([
            'twoFactorCode' => ['required', 'string', 'size:6'],
        ], [], [
            'twoFactorCode' => __('users.show.fields.two_factor'),
        ]);

        $confirmTwoFactorAuthentication($this->user(), $this->twoFactorCode);

        $this->refreshUserState();
        $this->resetTwoFactorUi();

        ToastService::success(__('users.show.saved.access'));
    }

    public function advanceTwoFactorSetup(): void
    {
        if ($this->requiresTwoFactorConfirmation()) {
            $this->showTwoFactorVerificationStep = true;
            $this->resetValidation('twoFactorCode');

            return;
        }

        $this->refreshUserState();
        $this->resetTwoFactorUi();

        ToastService::success(__('users.show.saved.access'));
    }

    public function resetTwoFactorVerification(): void
    {
        $this->twoFactorCode = '';
        $this->showTwoFactorVerificationStep = false;
        $this->resetValidation('twoFactorCode');
    }

    public function closeTwoFactorModal(): void
    {
        if ($this->hasPendingTwoFactorEnrollment()) {
            $this->cancelPendingTwoFactorEnrollment(app(DisableTwoFactorAuthentication::class));

            return;
        }

        $this->resetTwoFactorUi();
    }

    #[On('modal-confirmed')]
    public function handleModalConfirmed(
        DeleteUser $deleteUser,
        EnableTwoFactorAuthentication $enableTwoFactorAuthentication,
        DisableTwoFactorAuthentication $disableTwoFactorAuthentication,
    ): void {
        if ($this->throttle('confirmed-action', 5)) {
            return;
        }

        if ($this->userIdPendingDeletion !== null) {
            $this->executeUserDeletion($deleteUser);

            return;
        }

        if ($this->twoFactorPendingValue !== null) {
            $this->executeTwoFactorChange($enableTwoFactorAuthentication, $disableTwoFactorAuthentication);
        }
    }

    #[On('modal-confirm-cancelled')]
    public function resetPendingSensitiveActions(): void
    {
        $this->userIdPendingDeletion = null;
        $this->twoFactorPendingValue = null;
    }

    public function canEdit(): bool
    {
        return Gate::forUser($this->actor())->allows('update', $this->user());
    }

    public function canManageTwoFactor(): bool
    {
        return Features::canManageTwoFactorAuthentication();
    }

    public function canRevealTwoFactorSetup(): bool
    {
        return $this->actor()->is($this->user());
    }

    public function canToggleActive(): bool
    {
        return ! $this->actor()->is($this->user());
    }

    public function canDelete(): bool
    {
        $user = $this->user();

        return Gate::forUser($this->actor())->allows('delete', $user)
            && ! $this->actor()->is($user);
    }

    public function requiresTwoFactorConfirmation(): bool
    {
        return Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
    }

    /**
     * @return array{title: string, description: string, buttonText: string}
     */
    public function twoFactorModalConfig(): array
    {
        if ($this->showTwoFactorVerificationStep) {
            return [
                'title' => __('users.show.two_factor.verify_title'),
                'description' => __('users.show.two_factor.verify_description'),
                'buttonText' => __('actions.confirm'),
            ];
        }

        if (! $this->requiresTwoFactorConfirmation() && $this->showTwoFactorModal) {
            return [
                'title' => __('users.show.two_factor.enabled_title'),
                'description' => __('users.show.two_factor.enabled_description'),
                'buttonText' => __('actions.close'),
            ];
        }

        return [
            'title' => __('users.show.two_factor.enable_title'),
            'description' => __('users.show.two_factor.enable_description'),
            'buttonText' => __('actions.continue'),
        ];
    }

    private function crossValidateDocumentFields(): void
    {
        if ($this->editingSection !== self::SECTION_PERSONAL) {
            return;
        }

        if (filled($this->document_type_id) && blank($this->document_number)) {
            $this->addError('document_number', __('validation.required_with', [
                'attribute' => __('validation.attributes.document_number'),
                'values' => __('validation.attributes.document_type_id'),
            ]));
        } elseif (blank($this->document_type_id) && filled($this->document_number)) {
            $this->addError('document_type_id', __('validation.required_with', [
                'attribute' => __('validation.attributes.document_type_id'),
                'values' => __('validation.attributes.document_number'),
            ]));
        }
    }

    private function autosavePersonalField(string $property): void
    {
        if ($this->editingSection !== self::SECTION_PERSONAL) {
            return;
        }

        $this->authorizeUserUpdate();
        $this->resetValidation($property);
        $this->savePersonalField($property);
    }

    private function savePersonalField(string $property): void
    {
        $rules = $this->personalInformationRules();

        if (isset($rules[$property])) {
            $this->validate([$property => $rules[$property]]);
        }

        $this->targetUser->update([
            $property => $this->$property ?: null,
        ]);

        ToastService::success(__('users.show.saved.personal'));
    }

    private function saveAccountField(string $property): void
    {
        $current = $this->user();

        $user = $this->performUserUpdate(
            fn (): User => app(UpdateUserProfile::class)->handle($this->actor(), $current, [
                'name' => $property === 'name' ? $this->name : $current->name,
                'email' => $property === 'email' ? $this->email : $current->email,
            ]),
        );

        $this->fillForm($user);

        ToastService::success(__('users.show.saved.account'));
    }

    private function saveActiveStatus(): void
    {
        $user = $this->user();

        if (! $this->canToggleActive()) {
            $this->active = $user->is_active;
            $this->addError('active', __('users.show.validation.cannot_deactivate_self'));

            return;
        }

        $user = $this->performUserUpdate(
            fn (): User => app(UpdateUserAccess::class)->handle($this->actor(), $user, [
                'is_active' => $this->active,
                'roles' => $this->persistedRoleNames($user),
            ]),
        );

        $this->fillForm($user);

        ToastService::success(__('users.show.saved.active'));
    }

    private function handleTwoFactorToggle(): void
    {
        if (! $this->canManageTwoFactor()) {
            $this->twoFactorValue = $this->twoFactorOriginalValue;

            return;
        }

        $nextValue = $this->twoFactorValue;

        if ($nextValue === $this->twoFactorOriginalValue) {
            return;
        }

        $this->twoFactorValue = $this->twoFactorOriginalValue;
        $this->twoFactorPendingValue = $nextValue;

        ModalService::confirm(
            $this,
            title: __('users.show.two_factor.confirm_title', [
                'action' => $nextValue ? __('users.show.two_factor.enable_action') : __('users.show.two_factor.disable_action'),
            ]),
            message: __('users.show.two_factor.confirm_message', [
                'action' => $nextValue ? __('users.show.two_factor.enable_action') : __('users.show.two_factor.disable_action'),
                'user' => $this->userLabel($this->user()),
            ]),
            confirmLabel: $nextValue ? __('users.show.two_factor.enable_button') : __('users.show.two_factor.disable_button'),
            variant: ModalService::VARIANT_PASSWORD,
        );
    }

    private function enableTwoFactor(EnableTwoFactorAuthentication $enableTwoFactorAuthentication): void
    {
        $enableTwoFactorAuthentication($this->user());

        if (! $this->canRevealTwoFactorSetup()) {
            $this->refreshUserState();
            $this->resetTwoFactorUi();

            ToastService::success(__('users.show.saved.two_factor_pending_owner_setup'));

            return;
        }

        $this->loadTwoFactorSetupData();
        $this->showTwoFactorModal = true;
        $this->showTwoFactorVerificationStep = false;
        $this->twoFactorPendingValue = null;

        if (Fortify::confirmsTwoFactorAuthentication() && ! $this->isTargetTwoFactorConfirmed()) {
            return;
        }

        $this->refreshUserState();
    }

    private function disableTwoFactor(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $disableTwoFactorAuthentication($this->user());

        $this->refreshUserState();
        $this->resetTwoFactorUi();

        ToastService::success(__('users.show.saved.access'));
    }

    private function executeUserDeletion(DeleteUser $deleteUser): void
    {
        $user = $this->user();
        $userLabel = $this->userLabel($user);

        $deleteUser->handle($this->actor(), $user);

        $this->userIdPendingDeletion = null;

        ToastService::success(__('users.show.quick_actions.delete.deleted', [
            'user' => $userLabel,
        ]));

        $this->redirect(route('users.index'), navigate: true);
    }

    private function executeTwoFactorChange(
        EnableTwoFactorAuthentication $enableTwoFactorAuthentication,
        DisableTwoFactorAuthentication $disableTwoFactorAuthentication,
    ): void {
        if ($this->twoFactorPendingValue) {
            $this->enableTwoFactor($enableTwoFactorAuthentication);

            return;
        }

        $this->disableTwoFactor($disableTwoFactorAuthentication);
    }

    private function cancelPendingTwoFactorEnrollment(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $disableTwoFactorAuthentication($this->user());

        $this->refreshUserState();
        $this->resetTwoFactorUi();
    }

    private function loadTwoFactorSetupData(): void
    {
        $user = $this->user();

        $this->twoFactorQrCodeSvg = (string) $user->twoFactorQrCodeSvg();
        $decrypted = decrypt((string) $user->two_factor_secret);
        $this->twoFactorManualSetupKey = is_string($decrypted) ? $decrypted : '';
    }

    private function refreshUserState(): void
    {
        $this->targetUser = User::query()->with(['roles', 'media'])->where('id', $this->targetUser->getKey())->firstOrFail();

        $this->fillForm($this->targetUser);
    }

    private function refreshUserMedia(): void
    {
        $this->targetUser->load('media');

        unset($this->userAvatarUrl);
    }

    private function fillForm(User $user): void
    {
        $this->name = $user->name;
        $this->email = $user->email;
        $this->active = $user->is_active;
        $this->roles = $this->persistedRoleNames($user);
        $this->phone = $user->phone ?? '';
        $this->document_type_id = $user->document_type_id;
        $this->document_number = $user->document_number ?? '';
        $this->country_id = $user->country_id;
        $this->state = $user->state ?? '';
        $this->city = $user->city ?? '';
        $this->address = $user->address ?? '';
        $this->twoFactorValue = $user->two_factor_secret !== null;
        $this->twoFactorOriginalValue = $this->twoFactorValue;
        $this->twoFactorPendingValue = null;
        $this->resetPasswordFields();
    }

    /**
     * @param  callable(): User  $callback
     */
    private function performUserUpdate(callable $callback): User
    {
        try {
            return $callback();
        } catch (ValidationException $exception) {
            $this->fillForm($this->user());

            throw $exception;
        }
    }

    private function isValidSection(string $section): bool
    {
        return in_array($section, [self::SECTION_ACCOUNT, self::SECTION_PERSONAL, self::SECTION_ACCESS], true);
    }

    /**
     * @param  list<string>  $roles
     * @return list<string>
     */
    private function normalizeRoles(array $roles): array
    {
        return RoleNormalizer::normalize($roles, $this->availableRoles);
    }

    private function authorizeUserUpdate(): void
    {
        Gate::forUser($this->actor())->authorize('update', $this->user());
    }

    private function resetTwoFactorUi(): void
    {
        $this->showTwoFactorModal = false;
        $this->showTwoFactorVerificationStep = false;
        $this->twoFactorCode = '';
        $this->twoFactorQrCodeSvg = '';
        $this->twoFactorManualSetupKey = '';
        $this->twoFactorPendingValue = null;
        $this->resetValidation('twoFactorCode');
    }

    private function resetPasswordFields(): void
    {
        $this->password = '';
        $this->password_confirmation = '';
    }

    private function userLabel(User $user): string
    {
        return __('users.user_label', ['name' => $user->name, 'id' => $user->id]);
    }

    public function twoFactorStatusLabel(): string
    {
        $user = $this->user();

        return match (true) {
            $user->two_factor_confirmed_at !== null => __('users.show.status.two_factor_enabled'),
            $user->two_factor_secret !== null => __('users.show.status.two_factor_pending'),
            default => __('users.show.status.two_factor_disabled'),
        };
    }

    public function profileCompletionPercentage(): int
    {
        $user = $this->user();

        $criteria = [
            filled($user->name),
            filled($user->email),
            $user->email_verified_at !== null,
            $user->roles->isNotEmpty(),
            $user->is_active,
            $user->two_factor_confirmed_at !== null,
            $user->avatarUrl() !== null,
            filled($user->phone),
            $user->country_id !== null,
            filled($user->document_number),
        ];

        return (int) round((count(array_filter($criteria)) / count($criteria)) * 100);
    }

    public function completionTone(): string
    {
        $completion = $this->profileCompletionPercentage();

        return match (true) {
            $completion >= 84 => 'emerald',
            $completion >= 50 => 'amber',
            default => 'rose',
        };
    }

    public function completionToneClasses(): string
    {
        return match ($this->completionTone()) {
            'emerald' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-400/15 dark:text-emerald-200',
            'amber' => 'bg-amber-100 text-amber-700 dark:bg-amber-400/15 dark:text-amber-200',
            default => 'bg-rose-100 text-rose-700 dark:bg-rose-400/15 dark:text-rose-200',
        };
    }

    public function lastAccessText(): string
    {
        $lastLogin = $this->user()->last_login_at;

        if ($lastLogin === null) {
            return __('users.show.stats.not_available');
        }

        $localized = $lastLogin->copy();
        $localized->locale(app()->getLocale());

        return $localized->diffForHumans();
    }

    public function securityScoreText(): string
    {
        $user = $this->user();

        $score = count(array_filter([
            $user->email_verified_at !== null,
            $user->two_factor_confirmed_at !== null,
            $user->is_active,
        ]));

        return match ($score) {
            3 => __('users.show.stats.security_strong'),
            2 => __('users.show.stats.security_medium'),
            default => __('users.show.stats.security_low'),
        };
    }

    public function profileReadinessText(): string
    {
        return $this->profileCompletionPercentage() === 100
            ? __('users.show.stats.ready')
            : __('users.show.stats.in_progress');
    }

    private function isTargetTwoFactorConfirmed(): bool
    {
        return ! is_null($this->user()->two_factor_confirmed_at);
    }

    private function hasPendingTwoFactorEnrollment(): bool
    {
        $user = $this->user();

        return $user->two_factor_secret !== null && $user->two_factor_confirmed_at === null;
    }

    /**
     * @return list<string>
     */
    private function persistedRoleNames(User $user): array
    {
        return array_values(
            $user->roles
                ->map(fn (Role $role): string => $role->name)
                ->all(),
        );
    }
};
