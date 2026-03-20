@props([
    'title' => '',
    'titleClasses' => 'text-sky-700 dark:text-sky-200/75',
])

<div {{ $attributes->class(['space-y-5']) }}>
    <div>
        <flux:text class="text-[0.7rem] font-semibold uppercase tracking-[0.28em] {{ $titleClasses }}">{{ $title }}</flux:text>
    </div>

    {{ $slot }}
</div>
