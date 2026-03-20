@props([
    'label' => '',
])

<div {{ $attributes->class(['rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-white/8 dark:bg-black/10']) }}>
    <div class="mb-3 flex items-center gap-2 text-zinc-500 dark:text-zinc-400">
        @isset($icon)
            {{ $icon }}
        @endisset

        <flux:text class="text-xs font-semibold uppercase tracking-[0.24em]">{{ $label }}</flux:text>
    </div>

    {{ $slot }}
</div>
