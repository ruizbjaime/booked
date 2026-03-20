@props([
    'label' => '',
])

<div {{ $attributes->class(['rounded-xl border border-zinc-200 bg-white p-3 sm:p-4 dark:border-white/8 dark:bg-white/4']) }}>
    <div class="flex items-start gap-3">
        @isset($icon)
            <span {{ $icon->attributes->class(['inline-flex size-10 items-center justify-center rounded-xl']) }}>
                {{ $icon }}
            </span>
        @endisset

        <div class="min-w-0 space-y-1">
            <flux:text class="text-xs font-semibold uppercase tracking-[0.24em] text-zinc-500 dark:text-zinc-400">{{ $label }}</flux:text>
            {{ $slot }}
        </div>
    </div>
</div>
