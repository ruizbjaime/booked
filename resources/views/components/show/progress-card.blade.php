@props([
    'label' => '',
    'percentage' => 0,
    'badgeText' => '',
    'badgeClasses' => '',
    'barGradient' => 'from-sky-300 via-emerald-300 to-emerald-400',
])

<div {{ $attributes->class(['rounded-2xl border border-zinc-200 bg-zinc-50 p-3 sm:p-4 dark:border-white/8 dark:bg-black/10']) }}>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <flux:text class="text-xs font-semibold uppercase tracking-[0.24em] text-zinc-500 dark:text-zinc-400">{{ $label }}</flux:text>
            <flux:heading size="xl" class="mt-2">{{ $percentage }}%</flux:heading>
        </div>

        <span class="max-w-full rounded-full px-2.5 py-1 text-[0.65rem] font-semibold uppercase tracking-[0.12em] text-center {{ $badgeClasses }}">
            {{ $badgeText }}
        </span>
    </div>

    <div class="mt-4 h-2 overflow-hidden rounded-full bg-zinc-200 dark:bg-white/8">
        <div class="h-full rounded-full bg-linear-to-r {{ $barGradient }}" style="width: {{ $percentage }}%"></div>
    </div>
</div>
