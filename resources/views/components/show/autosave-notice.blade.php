@props([
    'message' => '',
])

<div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 dark:border-emerald-400/15 dark:bg-emerald-400/8">
    <flux:text size="sm" class="font-medium text-emerald-700 dark:text-emerald-100">
        {{ $message }}
    </flux:text>
</div>
