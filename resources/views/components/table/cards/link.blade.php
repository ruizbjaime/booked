@php
    $value = $column->resolveValue($record);
    $href = $column->resolveHref($record);
@endphp

@if ($value !== null)
    <x-table.card-field :label="$column->label()">
        @if ($column->shouldWireNavigate())
            <flux:link :href="$href" wire:navigate @class([$column->linkClass()])>
                {{ $value }}
            </flux:link>
        @else
            <flux:link :href="$href" :target="$column->target()" @class([$column->linkClass()])>
                {{ $value }}
            </flux:link>
        @endif
    </x-table.card-field>
@endif
