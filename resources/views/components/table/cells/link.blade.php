@php
    $value = $column->resolveValue($record);
    $href = $column->resolveHref($record);
@endphp

<flux:table.cell @class([$column->cellClass()])>
    @if ($value !== null)
        @if ($href && $column->shouldWireNavigate())
            <flux:link :href="$href" wire:navigate @class([$column->linkClass()])>
                {{ $value }}
            </flux:link>
        @elseif ($href)
            <flux:link :href="$href" :target="$column->target()" @class([$column->linkClass()])>
                {{ $value }}
            </flux:link>
        @else
            <span @class([$column->linkClass()])>{{ $value }}</span>
        @endif
    @endif
</flux:table.cell>
