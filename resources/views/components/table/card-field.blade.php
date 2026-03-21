@props(['label' => ''])

<div class="flex min-w-0 items-center justify-between gap-2 text-sm">
    @if ($label)
        <span class="shrink-0 text-zinc-500 dark:text-zinc-400">{{ $label }}</span>
    @endif
    <span class="min-w-0 truncate text-right text-zinc-800 dark:text-zinc-200">{{ $slot }}</span>
</div>
