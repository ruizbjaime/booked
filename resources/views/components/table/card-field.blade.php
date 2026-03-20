@props(['label' => ''])

<div class="flex items-center justify-between gap-2 text-sm">
    @if ($label)
        <span class="text-zinc-500 dark:text-zinc-400">{{ $label }}</span>
    @endif
    <span class="text-right text-zinc-800 dark:text-zinc-200">{{ $slot }}</span>
</div>
