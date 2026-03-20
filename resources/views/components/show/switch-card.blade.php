@props([
    'title' => '',
    'description' => '',
    'statusText' => '',
    'active' => false,
    'statusColor' => 'emerald',
    'inactiveColor' => 'zinc',
])

@php
    $dotClasses = match (true) {
        $active => match ($statusColor) {
            'emerald' => 'bg-emerald-400 shadow-[0_0_0_4px_rgb(52_211_153_/_0.12)]',
            'amber' => 'bg-amber-400 shadow-[0_0_0_4px_rgb(251_191_36_/_0.12)]',
            'sky' => 'bg-sky-400 shadow-[0_0_0_4px_rgb(56_189_248_/_0.12)]',
            'rose' => 'bg-rose-400 shadow-[0_0_0_4px_rgb(251_113_133_/_0.12)]',
            default => 'bg-emerald-400 shadow-[0_0_0_4px_rgb(52_211_153_/_0.12)]',
        },
        default => match ($inactiveColor) {
            'zinc' => 'bg-zinc-500/80 shadow-[0_0_0_4px_rgb(113_113_122_/_0.12)]',
            default => 'bg-zinc-500/80 shadow-[0_0_0_4px_rgb(113_113_122_/_0.12)]',
        },
    };
@endphp

<div {{ $attributes->class(['rounded-2xl border border-zinc-200 bg-white px-4 py-3.5 shadow-sm ring-1 ring-inset ring-zinc-200 dark:border-white/8 dark:bg-white/3 dark:ring-white/4']) }}>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="space-y-1">
            <flux:heading size="sm">{{ $title }}</flux:heading>
            <flux:text size="sm" class="max-w-xl text-zinc-500 dark:text-white/60">
                {{ $description }}
            </flux:text>
        </div>

        {{ $control }}
    </div>

    <div class="mt-3 flex items-center gap-2">
        <span class="inline-flex size-2.5 rounded-full {{ $dotClasses }}"></span>
        <flux:text size="sm" class="font-medium text-zinc-800 dark:text-zinc-100">
            {{ $statusText }}
        </flux:text>
    </div>
</div>
