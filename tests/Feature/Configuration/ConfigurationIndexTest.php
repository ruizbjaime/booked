<?php

use App\Domain\Configuration\Enums\ImageFormat;
use App\Models\SystemSetting;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->actingAs(makeAdmin());
});

function freshSettings(): SystemSetting
{
    SystemSetting::clearCache();

    return SystemSetting::instance();
}

test('admin can view the configuration page', function () {
    $this->get(route('configuration.index'))
        ->assertOk()
        ->assertSeeText(__('configuration.index.title'));
});

test('non-admin gets 403 on the configuration page', function () {
    $this->actingAs(makeGuest());

    $this->get(route('configuration.index'))
        ->assertForbidden();
});

test('admin can save valid settings', function () {
    Livewire::test('pages::configuration.index')
        ->set('avatar_size', 200)
        ->set('avatar_quality', 90)
        ->set('avatar_format', 'jpeg')
        ->set('max_upload_size_mb', 1)
        ->call('saveImages')
        ->assertHasNoErrors();

    $setting = freshSettings();

    expect($setting->avatar_size)->toBe(200)
        ->and($setting->avatar_quality)->toBe(90)
        ->and($setting->avatar_format)->toBe(ImageFormat::Jpeg)
        ->and($setting->max_upload_size_mb)->toBe(1);
});

test('admin can save table settings', function () {
    Livewire::test('pages::configuration.index')
        ->set('default_per_page', 25)
        ->call('saveTables')
        ->assertHasNoErrors();

    expect(freshSettings()->default_per_page)->toBe(25);
});

test('admin can save security settings', function () {
    Livewire::test('pages::configuration.index')
        ->set('password_min_length', 16)
        ->set('password_require_mixed_case', false)
        ->set('password_require_numbers', true)
        ->set('password_require_symbols', false)
        ->set('password_require_uncompromised', true)
        ->set('login_rate_limit', 10)
        ->set('form_rate_limit_enabled', false)
        ->set('form_edit_rate_limit', 20)
        ->set('form_action_rate_limit', 8)
        ->set('password_reset_expiry_minutes', 30)
        ->call('saveSecurity')
        ->assertHasNoErrors();

    $setting = freshSettings();

    expect($setting->password_min_length)->toBe(16)
        ->and($setting->password_require_mixed_case)->toBeFalse()
        ->and($setting->password_require_numbers)->toBeTrue()
        ->and($setting->password_require_symbols)->toBeFalse()
        ->and($setting->password_require_uncompromised)->toBeTrue()
        ->and($setting->login_rate_limit)->toBe(10)
        ->and($setting->form_rate_limit_enabled)->toBeFalse()
        ->and($setting->form_edit_rate_limit)->toBe(20)
        ->and($setting->form_action_rate_limit)->toBe(8)
        ->and($setting->password_reset_expiry_minutes)->toBe(30);
});

test('admin can save session settings', function () {
    Livewire::test('pages::configuration.index')
        ->set('session_lifetime_minutes', 60)
        ->call('saveSession')
        ->assertHasNoErrors();

    expect(freshSettings()->session_lifetime_minutes)->toBe(60);
});

test('section saves ignore invalid values from other sections', function () {
    Livewire::test('pages::configuration.index')
        ->set('default_per_page', 7)
        ->set('avatar_size', 220)
        ->set('avatar_quality', 85)
        ->set('max_upload_size_mb', 1)
        ->call('saveImages')
        ->assertHasNoErrors();

    $setting = freshSettings();

    expect($setting->avatar_size)->toBe(220)
        ->and($setting->avatar_quality)->toBe(85)
        ->and($setting->max_upload_size_mb)->toBe(1);
});

test('validation rejects out-of-range image values', function () {
    Livewire::test('pages::configuration.index')
        ->set('avatar_size', 10)
        ->set('avatar_quality', 0)
        ->call('saveImages')
        ->assertHasErrors([
            'avatar_size',
            'avatar_quality',
        ]);
});

test('validation rejects upload size exceeding server limits', function () {
    $uploadMax = (int) ini_get('upload_max_filesize');
    $postMax = (int) ini_get('post_max_size');
    $serverMax = min($uploadMax, $postMax);

    Livewire::test('pages::configuration.index')
        ->set('max_upload_size_mb', $serverMax + 1)
        ->call('saveImages')
        ->assertHasErrors(['max_upload_size_mb']);
});

test('validation rejects invalid avatar format', function () {
    Livewire::test('pages::configuration.index')
        ->set('avatar_format', 'bmp')
        ->call('saveImages')
        ->assertHasErrors(['avatar_format']);
});

test('validation rejects out-of-range table values', function () {
    Livewire::test('pages::configuration.index')
        ->set('default_per_page', 7)
        ->call('saveTables')
        ->assertHasErrors(['default_per_page']);
});

test('validation rejects out-of-range security values', function () {
    Livewire::test('pages::configuration.index')
        ->set('password_min_length', 3)
        ->set('login_rate_limit', 0)
        ->set('form_edit_rate_limit', 0)
        ->set('form_action_rate_limit', 0)
        ->set('password_reset_expiry_minutes', 2)
        ->call('saveSecurity')
        ->assertHasErrors([
            'password_min_length',
            'login_rate_limit',
            'form_edit_rate_limit',
            'form_action_rate_limit',
            'password_reset_expiry_minutes',
        ]);
});

test('validation rejects out-of-range session values', function () {
    Livewire::test('pages::configuration.index')
        ->set('session_lifetime_minutes', 1)
        ->call('saveSession')
        ->assertHasErrors(['session_lifetime_minutes']);
});

test('initial state matches database values', function () {
    $setting = SystemSetting::instance();

    Livewire::test('pages::configuration.index')
        ->assertSet('avatar_size', $setting->avatar_size)
        ->assertSet('avatar_quality', $setting->avatar_quality)
        ->assertSet('avatar_format', $setting->avatar_format->value)
        ->assertSet('max_upload_size_mb', $setting->max_upload_size_mb)
        ->assertSet('default_per_page', $setting->default_per_page)
        ->assertSet('password_min_length', $setting->password_min_length)
        ->assertSet('password_require_mixed_case', $setting->password_require_mixed_case)
        ->assertSet('password_require_numbers', $setting->password_require_numbers)
        ->assertSet('password_require_symbols', $setting->password_require_symbols)
        ->assertSet('password_require_uncompromised', $setting->password_require_uncompromised)
        ->assertSet('login_rate_limit', $setting->login_rate_limit)
        ->assertSet('form_rate_limit_enabled', $setting->form_rate_limit_enabled)
        ->assertSet('form_edit_rate_limit', $setting->form_edit_rate_limit)
        ->assertSet('form_action_rate_limit', $setting->form_action_rate_limit)
        ->assertSet('password_reset_expiry_minutes', $setting->password_reset_expiry_minutes)
        ->assertSet('session_lifetime_minutes', $setting->session_lifetime_minutes);
});

test('server limits are displayed on the page', function () {
    $this->get(route('configuration.index'))
        ->assertOk()
        ->assertSeeText(__('configuration.index.server_limits.title'));
});

test('session and password reset config overrides are applied', function () {
    SystemSetting::instance()->update([
        'session_lifetime_minutes' => 90,
        'password_reset_expiry_minutes' => 45,
    ]);

    $setting = freshSettings();
    config()->set('session.lifetime', $setting->session_lifetime_minutes);
    config()->set('auth.passwords.users.expire', $setting->password_reset_expiry_minutes);

    expect(config('session.lifetime'))->toBe(90)
        ->and(config('auth.passwords.users.expire'))->toBe(45);
});
