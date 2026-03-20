<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->configureActions();
        $this->configureAuthentication();
        $this->configureViews();
        $this->configureRateLimiting();
    }

    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);
    }

    private function configureAuthentication(): void
    {
        Fortify::authenticateUsing(function (Request $request): ?User {
            $user = User::query()
                ->where('email', $request->string('email')->toString())
                ->first();

            if (! $user instanceof User || ! Hash::check($request->string('password')->toString(), $user->password)) {
                return null;
            }

            if (! $user->is_active) {
                throw ValidationException::withMessages([
                    'login' => [__('auth.inactive')],
                ]);
            }

            return $user;
        });
    }

    private function configureViews(): void
    {
        Fortify::loginView(fn () => view('livewire.auth.login'));
        Fortify::verifyEmailView(fn () => view('livewire.auth.verify-email'));
        Fortify::twoFactorChallengeView(fn () => view('livewire.auth.two-factor-challenge'));
        Fortify::confirmPasswordView(fn () => view('livewire.auth.confirm-password'));
        Fortify::registerView(fn () => view('livewire.auth.register'));
        Fortify::resetPasswordView(fn () => view('livewire.auth.reset-password'));
        Fortify::requestPasswordResetLinkView(fn () => view('livewire.auth.forgot-password'));
    }

    private function configureRateLimiting(): void
    {
        $rateLimit = $this->loginRateLimit();

        RateLimiter::for('two-factor', fn (Request $request) => Limit::perMinute($rateLimit)->by($request->session()->get('login.id')));

        RateLimiter::for('login', function (Request $request) use ($rateLimit): Limit {
            $throttleKey = Str::transliterate(Str::lower($request->string(Fortify::username())->toString().'|'.$request->ip()));

            return Limit::perMinute($rateLimit)->by($throttleKey);
        });
    }

    private function loginRateLimit(): int
    {
        try {
            return SystemSetting::instance()->login_rate_limit;
        } catch (\Throwable) {
            return 5;
        }
    }
}
