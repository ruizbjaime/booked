<?php

namespace App\Livewire\Settings;

use App\Actions\Users\UpdateUserAvatar;
use App\Concerns\ProfileValidationRules;
use App\Domain\Users\PhoneCountryResolver;
use App\Infrastructure\UiFeedback\ToastService;
use App\Models\Country;
use App\Models\IdentificationDocumentType;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class Profile extends Component
{
    use ProfileValidationRules;
    use WithFileUploads;

    public function title(): string
    {
        return __('Profile settings');
    }

    public string $name = '';

    public string $email = '';

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

    public function mount(): void
    {
        $user = $this->user();

        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone ?? '';
        $this->document_type_id = $user->document_type_id;
        $this->document_number = $user->document_number ?? '';
        $this->country_id = $user->country_id;
        $this->state = $user->state ?? '';
        $this->city = $user->city ?? '';
        $this->address = $user->address ?? '';
    }

    public function updateProfileInformation(): void
    {
        $user = $this->user();

        $this->validate($this->profileRules($user->id));

        $user->fill([
            'name' => $this->name,
            'email' => $this->email,
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    public function updatePersonalInformation(): void
    {
        $this->validate($this->personalInformationRules());

        $this->user()->update([
            'phone' => $this->phone ?: null,
            'document_type_id' => $this->document_type_id,
            'document_number' => $this->document_number ?: null,
            'country_id' => $this->country_id,
            'state' => $this->state ?: null,
            'city' => $this->city ?: null,
            'address' => $this->address ?: null,
        ]);

        $this->dispatch('personal-info-updated');
    }

    public function updatedPhoto(): void
    {
        $photo = $this->photo;

        if (! $photo instanceof TemporaryUploadedFile) {
            return;
        }

        $user = $this->user();

        app(UpdateUserAvatar::class)->handle($user, $user, $photo);

        $this->photo = null;
        $this->refreshUser();

        ToastService::success(__('users.show.saved.avatar'));
    }

    public function deleteAvatar(): void
    {
        $this->user()->clearMediaCollection('avatar');
        $this->refreshUser();

        ToastService::success(__('users.show.saved.avatar_deleted'));
    }

    public function updatedPhone(): void
    {
        $this->resetValidation('phone');

        if (blank($this->phone)) {
            return;
        }

        $isoCode = app(PhoneCountryResolver::class)->detectCountryFromPhone($this->phone);

        if ($isoCode === null) {
            return;
        }

        $country = Country::query()->active()->where('iso_alpha2', $isoCode)->first();

        if ($country !== null) {
            $this->country_id = $country->id;
        }
    }

    public function updatedCountryId(): void
    {
        $this->resetValidation('country_id');
    }

    public function resendVerificationNotification(): void
    {
        $user = $this->user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
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

    #[Computed]
    public function userAvatarUrl(): ?string
    {
        return $this->user()->avatarUrl();
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        $user = $this->user();

        return ! $user->hasVerifiedEmail();
    }

    #[Computed]
    public function maxUploadSizeMb(): int
    {
        return SystemSetting::instance()->max_upload_size_mb;
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return $this->user()->hasVerifiedEmail();
    }

    private function user(): User
    {
        $user = Auth::user();

        abort_if(! $user instanceof User, 403);

        return $user;
    }

    private function refreshUser(): void
    {
        $this->user()->load('media');

        unset($this->userAvatarUrl);
    }
}
