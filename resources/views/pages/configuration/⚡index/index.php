<?php

use App\Actions\Configuration\UpdateSystemSettings;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Infrastructure\UiFeedback\ToastService;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    use ResolvesAuthenticatedUser;

    private const IMAGE_FIELDS = [
        'avatar_size',
        'avatar_quality',
        'avatar_format',
        'max_upload_size_mb',
    ];

    private const TABLE_FIELDS = [
        'default_per_page',
    ];

    private const SECURITY_FIELDS = [
        'password_min_length',
        'password_require_mixed_case',
        'password_require_numbers',
        'password_require_symbols',
        'password_require_uncompromised',
        'login_rate_limit',
        'form_rate_limit_enabled',
        'form_edit_rate_limit',
        'form_action_rate_limit',
        'password_reset_expiry_minutes',
    ];

    private const SESSION_FIELDS = [
        'session_lifetime_minutes',
    ];

    private const SETTING_FIELDS = [
        ...self::IMAGE_FIELDS,
        ...self::TABLE_FIELDS,
        ...self::SECURITY_FIELDS,
        ...self::SESSION_FIELDS,
    ];

    /** @var array<string, mixed> */
    #[Locked]
    public array $originalValues = [];

    public int $avatar_size = 100;

    public int $avatar_quality = 80;

    public string $avatar_format = 'webp';

    public int $max_upload_size_mb = 2;

    public int $default_per_page = 10;

    public int $password_min_length = 12;

    public bool $password_require_mixed_case = true;

    public bool $password_require_numbers = true;

    public bool $password_require_symbols = true;

    public bool $password_require_uncompromised = true;

    public int $login_rate_limit = 5;

    public bool $form_rate_limit_enabled = true;

    public int $form_edit_rate_limit = 10;

    public int $form_action_rate_limit = 5;

    public int $password_reset_expiry_minutes = 60;

    public int $session_lifetime_minutes = 120;

    public function mount(): void
    {
        Gate::authorize('viewAny', SystemSetting::class);

        $settings = SystemSetting::instance();
        $values = $settings->only(self::SETTING_FIELDS);
        $values['avatar_format'] = $settings->avatar_format->value;

        $this->fill($values);
        $this->originalValues = $values;
    }

    public function saveImages(UpdateSystemSettings $updateSystemSettings): void
    {
        $this->saveSection($updateSystemSettings, self::IMAGE_FIELDS, 'configuration.index.saved.images');
    }

    public function saveTables(UpdateSystemSettings $updateSystemSettings): void
    {
        $this->saveSection($updateSystemSettings, self::TABLE_FIELDS, 'configuration.index.saved.tables');
    }

    public function saveSecurity(UpdateSystemSettings $updateSystemSettings): void
    {
        $this->saveSection($updateSystemSettings, self::SECURITY_FIELDS, 'configuration.index.saved.security');
    }

    public function saveSession(UpdateSystemSettings $updateSystemSettings): void
    {
        $this->saveSection($updateSystemSettings, self::SESSION_FIELDS, 'configuration.index.saved.session');
    }

    /**
     * @return array{upload_max_filesize: string, post_max_size: string}
     */
    #[Computed]
    public function serverLimits(): array
    {
        return [
            'upload_max_filesize' => ini_get('upload_max_filesize') ?: '?',
            'post_max_size' => ini_get('post_max_size') ?: '?',
        ];
    }

    #[Computed]
    public function imagesChanged(): bool
    {
        return $this->sectionChanged(self::IMAGE_FIELDS);
    }

    #[Computed]
    public function tablesChanged(): bool
    {
        return $this->sectionChanged(self::TABLE_FIELDS);
    }

    #[Computed]
    public function securityChanged(): bool
    {
        return $this->sectionChanged(self::SECURITY_FIELDS);
    }

    #[Computed]
    public function sessionChanged(): bool
    {
        return $this->sectionChanged(self::SESSION_FIELDS);
    }

    /**
     * @param  list<string>  $fields
     */
    private function sectionChanged(array $fields): bool
    {
        foreach ($fields as $field) {
            if ($this->{$field} !== ($this->originalValues[$field] ?? null)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $fields
     */
    private function saveSection(UpdateSystemSettings $updateSystemSettings, array $fields, string $message): void
    {
        /** @var array<string, mixed> $data */
        $data = $this->only($fields);
        $updateSystemSettings->handle($this->actor(), $data);

        foreach ($fields as $field) {
            $this->originalValues[$field] = $this->{$field};
        }

        ToastService::success(__($message));
    }
};
