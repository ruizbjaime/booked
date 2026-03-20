@props([
    'title' => '',
    'description' => '',
])

<div {{ $attributes->class(['space-y-5 sm:space-y-6']) }}>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex min-w-0 items-center gap-3 sm:items-start">
            @isset($icon)
                <span {{ $icon->attributes->class(['inline-flex size-11 items-center justify-center rounded-2xl']) }}>
                    {{ $icon }}
                </span>
            @endisset

            <div class="min-w-0 space-y-1">
                <flux:heading>{{ $title }}</flux:heading>

                @if (filled($description))
                    <flux:text class="hidden text-zinc-400 sm:block">{{ $description }}</flux:text>
                @endif
            </div>
        </div>

        @isset($actions)
            <div class="flex w-full items-center justify-end gap-2 sm:w-auto sm:self-start">
                {{ $actions }}
            </div>
        @endisset
    </div>

    <flux:separator variant="subtle" />

    {{ $slot }}
</div>
