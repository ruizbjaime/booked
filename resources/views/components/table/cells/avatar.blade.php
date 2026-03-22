@php
    $hasExplicitColor = $column->hasColor();
    $resolvedColor = $hasExplicitColor ? $column->resolveColor($record) : null;
@endphp

<flux:table.cell @class([$column->cellClass()])>
    <div class="flex items-center gap-3">
        @if ($hasExplicitColor)
            <x-avatar
                size="sm"
                :src="$column->resolveAvatarSrc($record)"
                :initials="$column->resolveInitials($record)"
                :color="$resolvedColor"
            />
        @else
            <flux:avatar
                size="sm"
                :src="$column->resolveAvatarSrc($record)"
                :initials="$column->resolveInitials($record)"
                color="auto"
                :color:seed="$column->resolveColorSeed($record)"
            />
        @endif
        @php($avatarUrl = $column->resolveRecordUrl($record))
        @if ($avatarUrl && $column->shouldWireNavigate())
            <flux:link
                :href="$avatarUrl"
                wire:navigate
                class="font-medium text-zinc-800 dark:text-zinc-200"
            >
                {{ $column->resolveValue($record) }}
            </flux:link>
        @elseif ($avatarUrl)
            <flux:link
                :href="$avatarUrl"
                class="font-medium text-zinc-800 dark:text-zinc-200"
            >
                {{ $column->resolveValue($record) }}
            </flux:link>
        @else
            <span class="font-medium text-zinc-800 dark:text-zinc-200">
                {{ $column->resolveValue($record) }}
            </span>
        @endif
    </div>
</flux:table.cell>
