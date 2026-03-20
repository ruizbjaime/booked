<?php

namespace App\Infrastructure\UiFeedback;

use Livewire\Component;

final class ModalService
{
    public const string VARIANT_STANDARD = 'standard';

    public const string VARIANT_PASSWORD = 'password';

    public const string WIDTH_DEFAULT = 'md:w-[42.75rem]';

    public static function confirm(
        Component $component,
        string $title,
        string $message,
        string $confirmLabel,
        string $variant = self::VARIANT_STANDARD,
    ): void {
        self::dispatch($component, 'open-confirm-modal', [
            'title' => $title,
            'message' => $message,
            'confirmLabel' => $confirmLabel,
            'variant' => $variant,
        ]);
    }

    public static function info(
        Component $component,
        string $title,
        string $message,
    ): void {
        self::dispatch($component, 'open-info-modal', [
            'title' => $title,
            'message' => $message,
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function form(
        Component $component,
        string $name,
        string $title,
        ?string $description = null,
        array $context = [],
        string $width = self::WIDTH_DEFAULT,
    ): void {
        self::dispatch($component, 'open-form-modal', [
            'name' => $name,
            'title' => $title,
            'description' => $description ?? '',
            'context' => $context,
            'width' => $width,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function dispatch(Component $component, string $event, array $payload): void
    {
        $component->dispatch($event, ...$payload);
    }
}
