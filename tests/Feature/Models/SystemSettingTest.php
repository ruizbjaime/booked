<?php

use App\Domain\Configuration\Enums\ImageFormat;
use App\Models\SystemSetting;

it('creates a singleton instance with default values', function () {
    $instance = SystemSetting::instance();

    expect($instance)->toBeInstanceOf(SystemSetting::class)
        ->and($instance->avatar_size)->toBe(100)
        ->and($instance->avatar_quality)->toBe(80)
        ->and($instance->avatar_format)->toBe(ImageFormat::Webp)
        ->and($instance->max_upload_size_mb)->toBe(2)
        ->and($instance->default_per_page)->toBe(10)
        ->and($instance->password_min_length)->toBe(12)
        ->and($instance->password_require_mixed_case)->toBeTrue()
        ->and($instance->login_rate_limit)->toBe(5)
        ->and($instance->session_lifetime_minutes)->toBe(120);
});

it('returns the same instance on subsequent calls', function () {
    $first = SystemSetting::instance();
    $second = SystemSetting::instance();

    expect($first->id)->toBe($second->id)
        ->and(SystemSetting::query()->count())->toBe(1);
});

it('clears cache and re-fetches from database', function () {
    $instance = SystemSetting::instance();
    $instance->update(['avatar_size' => 200]);

    SystemSetting::clearCache();
    $refreshed = SystemSetting::instance();

    expect($refreshed->avatar_size)->toBe(200);
});

it('casts avatar_format to ImageFormat enum', function () {
    $instance = SystemSetting::instance();

    expect($instance->avatar_format)->toBeInstanceOf(ImageFormat::class)
        ->and($instance->avatar_format)->toBe(ImageFormat::Webp);
});

it('casts boolean settings correctly', function () {
    $instance = SystemSetting::instance();

    expect($instance->password_require_mixed_case)->toBeBool()
        ->and($instance->password_require_numbers)->toBeBool()
        ->and($instance->form_rate_limit_enabled)->toBeBool();
});

it('casts integer settings correctly', function () {
    $instance = SystemSetting::instance();

    expect($instance->avatar_size)->toBeInt()
        ->and($instance->form_edit_rate_limit)->toBeInt()
        ->and($instance->password_reset_expiry_minutes)->toBeInt();
});
