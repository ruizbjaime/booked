<?php

namespace App\Infrastructure\UiFeedback;

use Flux\Flux;

final class ToastService
{
    private const DEFAULT_DURATION = 5000;

    public static function success(string $message, int $duration = self::DEFAULT_DURATION): void
    {
        self::toast($message, 'success', $duration);
    }

    public static function warning(string $message, int $duration = self::DEFAULT_DURATION): void
    {
        self::toast($message, 'warning', $duration);
    }

    public static function danger(string $message, int $duration = self::DEFAULT_DURATION): void
    {
        self::toast($message, 'danger', $duration);
    }

    private static function toast(string $message, string $variant, int $duration): void
    {
        Flux::toast(
            text: $message,
            heading: self::heading($variant),
            duration: $duration,
            variant: $variant,
        );
    }

    private static function heading(string $variant): string
    {
        return __('toasts.headings.'.$variant);
    }
}
