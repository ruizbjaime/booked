<?php

namespace App\Providers;

use App\Domain\Auth\PermissionRegistry;
use App\Domain\Users\RoleConfig;
use App\Models\SystemSetting;
use App\Models\User;
use App\Policies\UserPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Livewire\Blaze\Blaze;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);

        Gate::before(fn (User $user, string $ability): ?bool => $user->hasRole(RoleConfig::adminRole()) ? true : null);

        Blaze::optimize()
            ->in(resource_path('views/components'))
            ->in(resource_path('views/components/table'), compile: false);

        Event::listen(MigrationsEnded::class, function (): void {
            Artisan::call('permissions:sync', ['--force' => true]);
        });

        $this->syncPermissionsIfStale();
        $this->configureDefaults();
    }

    protected function syncPermissionsIfStale(): void
    {
        if (app()->runningInConsole()) {
            return;
        }

        try {
            $hash = PermissionRegistry::computeHash();

            if ($hash === Cache::get('permissions:discovered_hash')) {
                return;
            }

            Artisan::call('permissions:sync', ['--force' => true]);
        } catch (\Throwable) {
            // Silently skip if DB is unavailable (e.g., during initial setup)
        }
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        $this->configurePasswordPolicy();
        $this->configureRuntimeOverrides();
    }

    protected function configurePasswordPolicy(): void
    {
        Password::defaults(function (): Password {
            if (! app()->isProduction()) {
                return Password::min(8);
            }

            try {
                $settings = SystemSetting::instance();
            } catch (\Throwable) {
                return Password::min(12)->mixedCase()->letters()->numbers()->symbols()->uncompromised();
            }

            $password = Password::min($settings->password_min_length)->letters();

            if ($settings->password_require_mixed_case) {
                $password->mixedCase();
            }

            if ($settings->password_require_numbers) {
                $password->numbers();
            }

            if ($settings->password_require_symbols) {
                $password->symbols();
            }

            if ($settings->password_require_uncompromised) {
                $password->uncompromised();
            }

            return $password;
        });
    }

    protected function configureRuntimeOverrides(): void
    {
        try {
            $settings = SystemSetting::instance();

            config()->set('auth.passwords.users.expire', $settings->password_reset_expiry_minutes);
            config()->set('session.lifetime', $settings->session_lifetime_minutes);
        } catch (\Throwable) {
            // Skip if DB is unavailable
        }
    }
}
