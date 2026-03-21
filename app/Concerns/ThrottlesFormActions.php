<?php

namespace App\Concerns;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\RateLimiter;

trait ThrottlesFormActions
{
    private function throttle(string $action, int $maxAttempts = 10): void
    {
        $settings = SystemSetting::instance();

        if (! $settings->form_rate_limit_enabled) {
            return;
        }

        $limit = $this->resolveMaxAttempts($action, $settings);
        $key = static::THROTTLE_KEY_PREFIX.":{$action}:{$this->actor()->id}";

        abort_if(RateLimiter::tooManyAttempts($key, $limit), 429);

        RateLimiter::hit($key, 60);
    }

    private function resolveMaxAttempts(string $action, SystemSetting $settings): int
    {
        return match (true) {
            in_array($action, ['create', 'delete', 'confirmed-action'], true) => $settings->form_action_rate_limit,
            default => $settings->form_edit_rate_limit,
        };
    }
}
