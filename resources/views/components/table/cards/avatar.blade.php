@php
    $hasExplicitColor = $column->hasColor();
    $resolvedColor = $hasExplicitColor ? $column->resolveColor($record) : null;
@endphp

<div class="flex items-center gap-3">
    @if ($hasExplicitColor)
        <x-avatar
            size="md"
            :src="$column->resolveAvatarSrc($record)"
            :initials="$column->resolveInitials($record)"
            :color="$resolvedColor"
        />
    @else
        <flux:avatar
            size="md"
            :src="$column->resolveAvatarSrc($record)"
            :initials="$column->resolveInitials($record)"
            color="auto"
            :color:seed="$column->resolveColorSeed($record)"
        />
    @endif
    <div class="min-w-0">
        @php($avatarUrl = $column->resolveRecordUrl($record))
        @if ($avatarUrl && $column->shouldWireNavigate())
            <flux:link
                :href="$avatarUrl"
                wire:navigate
                class="font-semibold text-zinc-800 dark:text-zinc-200"
            >
                {{ $column->resolveValue($record) }}
            </flux:link>
        @elseif ($avatarUrl)
            <flux:link
                :href="$avatarUrl"
                class="font-semibold text-zinc-800 dark:text-zinc-200"
            >
                {{ $column->resolveValue($record) }}
            </flux:link>
        @else
            <span class="font-semibold text-zinc-800 dark:text-zinc-200">
                {{ $column->resolveValue($record) }}
            </span>
        @endif
    </div>
</div>
