@php
    $value = $column->resolveValue($record);
    $href = $column->resolveHref($record);
@endphp

<flux:table.cell @class([$column->cellClass()])>
    @if ($value !== null)
        @if ($column->shouldWireNavigate())
            <flux:link :href="$href" wire:navigate @class([$column->linkClass()])>
                {{ $value }}
            </flux:link>
        @else
            <flux:link :href="$href" :target="$column->target()" @class([$column->linkClass()])>
                {{ $value }}
            </flux:link>
        @endif
    @endif
</flux:table.cell>
