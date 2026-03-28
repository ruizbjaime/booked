<?php

use App\Concerns\ThrottlesFormActions;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function throttleableComponent(User $user): object
{
    return new class($user)
    {
        use ThrottlesFormActions;

        private const string THROTTLE_KEY_PREFIX = 'test-form';

        public function __construct(private User $user) {}

        public function actor(): User
        {
            return $this->user;
        }

        public function callThrottle(string $action, int $maxAttempts = 10): bool
        {
            return $this->throttle($action, $maxAttempts);
        }
    };
}

it('returns false immediately when form rate limiting is disabled', function () {
    $settings = SystemSetting::instance();
    $settings->update(['form_rate_limit_enabled' => false]);
    SystemSetting::clearCache();

    $user = User::factory()->create();
    $component = throttleableComponent($user);

    expect($component->callThrottle('create'))->toBeFalse();
});
