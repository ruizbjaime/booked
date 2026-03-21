<?php

namespace App\Actions\Configuration;

use App\Domain\Configuration\Enums\ImageFormat;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UpdateSystemSettings
{
    /**
     * @var array<string, array<int, string>>
     */
    private const RULES = [
        'avatar_size' => ['required', 'integer', 'min:50', 'max:500'],
        'avatar_quality' => ['required', 'integer', 'min:1', 'max:100'],
        'default_per_page' => ['required', 'integer', 'in:10,15,25,50,100'],
        'password_min_length' => ['required', 'integer', 'min:8', 'max:128'],
        'password_require_mixed_case' => ['required', 'boolean'],
        'password_require_numbers' => ['required', 'boolean'],
        'password_require_symbols' => ['required', 'boolean'],
        'password_require_uncompromised' => ['required', 'boolean'],
        'login_rate_limit' => ['required', 'integer', 'min:1', 'max:60'],
        'form_rate_limit_enabled' => ['required', 'boolean'],
        'form_edit_rate_limit' => ['required', 'integer', 'min:1', 'max:120'],
        'form_action_rate_limit' => ['required', 'integer', 'min:1', 'max:60'],
        'password_reset_expiry_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
        'session_lifetime_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
    ];

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(User $actor, array $data): void
    {
        $setting = SystemSetting::instance();

        Gate::forUser($actor)->authorize('update', $setting);

        [$rules, $messages] = $this->buildValidation($data);

        /** @var array<string, mixed> $validated */
        $validated = Validator::make($data, $rules, $messages)->validate();

        $setting->update($validated);

        SystemSetting::clearCache();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{array<string, array<int, mixed>>, array<string, string>}
     */
    private function buildValidation(array $data): array
    {
        $rules = array_intersect_key(self::RULES, $data);
        $messages = [];

        if (array_key_exists('avatar_format', $data)) {
            $rules['avatar_format'] = ['required', 'string', Rule::enum(ImageFormat::class)];
        }

        if (array_key_exists('max_upload_size_mb', $data)) {
            $serverMaxMb = $this->serverMaxUploadMb();
            $rules['max_upload_size_mb'] = ['required', 'integer', 'min:1', "max:{$serverMaxMb}"];
            $messages['max_upload_size_mb.max'] = __('configuration.index.validation.max_upload_exceeds_server', [
                'limit' => $serverMaxMb,
            ]);
        }

        return [$rules, $messages];
    }

    private function serverMaxUploadMb(): int
    {
        $uploadMax = $this->phpIniToMb(ini_get('upload_max_filesize') ?: '2M');
        $postMax = $this->phpIniToMb(ini_get('post_max_size') ?: '8M');

        return (int) min($uploadMax, $postMax);
    }

    private function phpIniToMb(string $value): float
    {
        $number = (float) $value;

        return match (strtoupper(substr(trim($value), -1))) {
            'G' => $number * 1024,
            'K' => $number / 1024,
            default => $number,
        };
    }
}
