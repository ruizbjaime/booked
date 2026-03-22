@php
    $value = $column->resolveValue($record);
    $href = $column->resolveHref($record);
@endphp

@if ($value !== null)
    <x-table.card-field :label="$column->label()">
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
    </x-table.card-field>
@endif
