<?php

use App\Actions\Configuration\UpdateSystemSettings;
use App\Domain\Configuration\Enums\ImageFormat;
use App\Models\SystemSetting;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('updates only the provided subset of system settings', function () {
    $admin = makeAdmin();
    $initial = SystemSetting::instance()->replicate();

    app(UpdateSystemSettings::class)->handle($admin, [
        'default_per_page' => 25,
        'avatar_format' => ImageFormat::Png->value,
        'password_require_symbols' => false,
    ]);

    $setting = SystemSetting::instance()->fresh();

    expect($setting->default_per_page)->toBe(25)
        ->and($setting->avatar_format)->toBe(ImageFormat::Png)
        ->and($setting->password_require_symbols)->toBeFalse()
        ->and($setting->avatar_size)->toBe($initial->avatar_size);
});

it('rejects upload sizes that exceed the server limit', function () {
    $admin = makeAdmin();
    $serverMax = min((int) ini_get('upload_max_filesize'), (int) ini_get('post_max_size'));

    expect(fn () => app(UpdateSystemSettings::class)->handle($admin, [
        'max_upload_size_mb' => $serverMax + 1,
    ]))->toThrow(ValidationException::class);
});

it('accepts upload sizes within the server limit', function () {
    $admin = makeAdmin();
    $serverMax = min((int) ini_get('upload_max_filesize'), (int) ini_get('post_max_size'));

    app(UpdateSystemSettings::class)->handle($admin, [
        'max_upload_size_mb' => max(1, $serverMax),
    ]);

    expect(SystemSetting::instance()->fresh()->max_upload_size_mb)->toBe(max(1, $serverMax));
});

it('requires authorization to update system settings', function () {
    $guest = makeGuest();

    expect(fn () => app(UpdateSystemSettings::class)->handle($guest, [
        'default_per_page' => 25,
    ]))->toThrow(AuthorizationException::class);
});
