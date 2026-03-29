<?php

use App\Domain\Auth\PermissionRegistry;
use App\Models\SystemSetting;
use App\Providers\AppServiceProvider;
use Carbon\CarbonImmutable;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Validation\UncompromisedVerifier;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

function appServiceProviderForTests(): AppServiceProvider
{
    return new class(app()) extends AppServiceProvider
    {
        public function runConfigureRuntimeOverrides(): void
        {
            $this->configureRuntimeOverrides();
        }

        public function runConfigurePasswordPolicy(): void
        {
            $this->configurePasswordPolicy();
        }

        public function runSyncPermissionsIfStale(): void
        {
            $this->syncPermissionsIfStale();
        }

        public function runBoot(): void
        {
            $this->boot();
        }
    };
}

function appServiceProviderWithApplicationForTests($app): AppServiceProvider
{
    return new class($app) extends AppServiceProvider
    {
        public ?SystemSetting $settings = null;

        public ?Throwable $settingsException = null;

        public function runSyncPermissionsIfStale(): void
        {
            $this->syncPermissionsIfStale();
        }

        protected function systemSettings(): SystemSetting
        {
            if ($this->settingsException !== null) {
                throw $this->settingsException;
            }

            return $this->settings ?? parent::systemSettings();
        }

        public function runConfigurePasswordPolicy(): void
        {
            $this->configurePasswordPolicy();
        }
    };
}

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('date facade uses carbon immutable', function () {
    expect(Date::now())->toBeInstanceOf(CarbonImmutable::class);
});

test('runtime overrides use stored system settings values', function () {
    SystemSetting::instance()->update([
        'password_reset_expiry_minutes' => 45,
        'session_lifetime_minutes' => 90,
    ]);

    appServiceProviderForTests()->runConfigureRuntimeOverrides();

    expect(config('auth.passwords.users.expire'))->toBe(45)
        ->and(config('session.lifetime'))->toBe(90);
});

test('password defaults use lightweight rules outside production', function () {
    appServiceProviderForTests()->runConfigurePasswordPolicy();

    $tooShort = Validator::make(['password' => '1234567'], [
        'password' => ['required', 'string', Password::default()],
    ]);

    $valid = Validator::make(['password' => '12345678'], [
        'password' => ['required', 'string', Password::default()],
    ]);

    expect($tooShort->fails())->toBeTrue()
        ->and($valid->fails())->toBeFalse();
});

test('permission sync is skipped while running in console', function () {
    Artisan::spy();

    appServiceProviderForTests()->runSyncPermissionsIfStale();

    Artisan::shouldNotHaveReceived('call', ['permissions:sync', ['--force' => true]]);
});

test('migrations ended listener syncs permissions', function () {
    Artisan::spy();

    Event::dispatch(new MigrationsEnded('sqlite', ['create_users_table']));

    Artisan::shouldHaveReceived('call')->with('permissions:sync', ['--force' => true])->once();
});

test('provider password defaults become stricter in production', function () {
    app()->detectEnvironment(fn () => 'production');

    SystemSetting::instance()->update([
        'password_min_length' => 10,
        'password_require_mixed_case' => true,
        'password_require_numbers' => true,
        'password_require_symbols' => false,
        'password_require_uncompromised' => false,
    ]);

    appServiceProviderForTests()->runConfigurePasswordPolicy();

    $invalid = Validator::make(['password' => 'lowercase12'], [
        'password' => ['required', 'string', Password::default()],
    ]);
    $valid = Validator::make(['password' => 'ValidPass12'], [
        'password' => ['required', 'string', Password::default()],
    ]);

    expect($invalid->fails())->toBeTrue()
        ->and($valid->fails())->toBeFalse();
});

test('permission sync remains skipped during console boot even with a stale hash', function () {
    Cache::forget('permissions:discovered_hash');
    Artisan::spy();

    appServiceProviderForTests()->runBoot();

    Artisan::shouldNotHaveReceived('call', ['permissions:sync', ['--force' => true]]);
});

test('permission sync runs when the discovered hash is stale outside console', function () {
    Cache::forget('permissions:discovered_hash');
    Artisan::spy();

    $app = Mockery::mock(app())->makePartial();
    $app->shouldReceive('runningInConsole')->andReturnFalse();

    appServiceProviderWithApplicationForTests($app)->runSyncPermissionsIfStale();

    Artisan::shouldHaveReceived('call')->with('permissions:sync', ['--force' => true])->once();
});

test('permission sync is skipped when the discovered hash already matches outside console', function () {
    Cache::put('permissions:discovered_hash', PermissionRegistry::computeHash());
    Artisan::spy();

    $app = Mockery::mock(app())->makePartial();
    $app->shouldReceive('runningInConsole')->andReturnFalse();

    appServiceProviderWithApplicationForTests($app)->runSyncPermissionsIfStale();

    Artisan::shouldNotHaveReceived('call', ['permissions:sync', ['--force' => true]]);
});

test('provider password defaults enforce symbols and uncompromised when enabled in production', function () {
    app()->detectEnvironment(fn () => 'production');

    SystemSetting::instance()->update([
        'password_min_length' => 10,
        'password_require_mixed_case' => false,
        'password_require_numbers' => false,
        'password_require_symbols' => true,
        'password_require_uncompromised' => true,
    ]);

    $verifier = Mockery::mock(UncompromisedVerifier::class);
    $verifier->shouldReceive('verify')
        ->andReturnUsing(fn (array $data) => $data['value'] !== 'Compromised1!')
        ->twice();
    app()->instance(UncompromisedVerifier::class, $verifier);

    appServiceProviderForTests()->runConfigurePasswordPolicy();

    $missingSymbol = Validator::make(['password' => 'abcdefghij'], [
        'password' => ['required', 'string', Password::default()],
    ]);

    $compromised = Validator::make(['password' => 'Compromised1!'], [
        'password' => ['required', 'string', Password::default()],
    ]);

    $valid = Validator::make(['password' => 'ValidPass1!'], [
        'password' => ['required', 'string', Password::default()],
    ]);

    expect($missingSymbol->fails())->toBeTrue()
        ->and($compromised->fails())->toBeTrue()
        ->and($valid->fails())->toBeFalse();
});

test('provider password defaults fall back to strict production rules when settings cannot be loaded', function () {
    app()->detectEnvironment(fn () => 'production');

    $verifier = Mockery::mock(UncompromisedVerifier::class);
    $verifier->shouldReceive('verify')->once()->andReturnTrue();
    app()->instance(UncompromisedVerifier::class, $verifier);

    $provider = appServiceProviderWithApplicationForTests(app());
    $provider->settingsException = new RuntimeException('settings unavailable');
    $provider->runConfigurePasswordPolicy();

    $invalid = Validator::make(['password' => 'ValidPass12'], [
        'password' => ['required', 'string', Password::default()],
    ]);
    $valid = Validator::make(['password' => 'ValidPass12!'], [
        'password' => ['required', 'string', Password::default()],
    ]);

    expect($invalid->fails())->toBeTrue()
        ->and($valid->fails())->toBeFalse();
});
