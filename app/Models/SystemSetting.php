<?php

namespace App\Models;

use App\Domain\Configuration\Enums\ImageFormat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * @property ImageFormat $avatar_format
 */
class SystemSetting extends Model
{
    private const string CACHE_KEY = 'system_settings';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'avatar_size',
        'avatar_quality',
        'avatar_format',
        'max_upload_size_mb',
        'default_per_page',
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
        'session_lifetime_minutes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'avatar_size' => 'integer',
            'avatar_quality' => 'integer',
            'avatar_format' => ImageFormat::class,
            'max_upload_size_mb' => 'integer',
            'default_per_page' => 'integer',
            'password_min_length' => 'integer',
            'password_require_mixed_case' => 'boolean',
            'password_require_numbers' => 'boolean',
            'password_require_symbols' => 'boolean',
            'password_require_uncompromised' => 'boolean',
            'login_rate_limit' => 'integer',
            'form_rate_limit_enabled' => 'boolean',
            'form_edit_rate_limit' => 'integer',
            'form_action_rate_limit' => 'integer',
            'password_reset_expiry_minutes' => 'integer',
            'session_lifetime_minutes' => 'integer',
        ];
    }

    public static function instance(): self
    {
        $cached = Cache::get(self::CACHE_KEY);

        if ($cached instanceof self) {
            return $cached;
        }

        $instance = self::query()->firstOrCreate([], [
            'avatar_size' => 100,
            'avatar_quality' => 80,
            'avatar_format' => ImageFormat::Webp,
            'max_upload_size_mb' => 2,
            'default_per_page' => 10,
            'password_min_length' => 12,
            'password_require_mixed_case' => true,
            'password_require_numbers' => true,
            'password_require_symbols' => true,
            'password_require_uncompromised' => true,
            'login_rate_limit' => 5,
            'form_rate_limit_enabled' => true,
            'form_edit_rate_limit' => 10,
            'form_action_rate_limit' => 5,
            'password_reset_expiry_minutes' => 60,
            'session_lifetime_minutes' => 120,
        ]);

        Cache::forever(self::CACHE_KEY, $instance);

        return $instance;
    }

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
