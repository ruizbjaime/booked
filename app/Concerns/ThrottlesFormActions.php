<?php

namespace App\Concerns;

use App\Infrastructure\UiFeedback\ModalService;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\RateLimiter;

trait ThrottlesFormActions
{
    private function throttle(string $action, int $maxAttempts = 10): bool
    {
        $settings = SystemSetting::instance();

        if (! $settings->form_rate_limit_enabled) {
            return false;
        }

        $limit = $this->resolveMaxAttempts($action, $settings);
        $key = static::THROTTLE_KEY_PREFIX.":{$action}:{$this->actor()->id}";

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            $seconds = RateLimiter::availableIn($key);

            ModalService::info(
                $this,
                __('rate_limit.title'),
                __('rate_limit.message', ['seconds' => $seconds]),
            );

            return true;
        }

        RateLimiter::hit($key, 60);

        return false;
    }

    private function resolveMaxAttempts(string $action, SystemSetting $settings): int
    {
        return match (true) {
            in_array($action, ['create', 'delete', 'confirmed-action'], true) => $settings->form_action_rate_limit,
            default => $settings->form_edit_rate_limit,
        };
    }
}
