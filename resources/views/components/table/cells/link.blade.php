@php
    $value = $column->resolveValue($record);
    $href = $column->resolveHref($record);
@endphp

<flux:table.cell @class([$column->cellClass()])>
    @if ($value !== null && $href && $column->shouldWireNavigate())
        <flux:link :href="$href" wire:navigate @class([$column->linkClass()])>
            {{ $value }}
        </flux:link>
    @elseif ($value !== null && $href)
        <flux:link :href="$href" :target="$column->target()" @class([$column->linkClass()])>
            {{ $value }}
        </flux:link>
    @elseif ($value !== null)
        <span @class([$column->linkClass()])>{{ $value }}</span>
    @endif
</flux:table.cell>
