<div class="flex items-center gap-3">
    <flux:avatar
        size="md"
        :src="$column->resolveAvatarSrc($record)"
        :initials="$column->resolveInitials($record)"
        color="auto"
        :color:seed="$column->resolveColorSeed($record)"
    />
    <div class="min-w-0">
        @if ($column->hasRecordUrl() && $column->shouldWireNavigate())
            <flux:link
                :href="$column->resolveRecordUrl($record)"
                wire:navigate
                class="font-semibold text-zinc-800 dark:text-zinc-200"
            >
                {{ $column->resolveValue($record) }}
            </flux:link>
        @elseif ($column->hasRecordUrl())
            <flux:link
                :href="$column->resolveRecordUrl($record)"
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
