@php
    $src = $column->resolveSrc($record);
    $alt = $column->resolveAlt($record);
@endphp

@if ($src)
    <x-table.card-field :label="$column->label()">
        <img
            src="{{ $src }}"
            alt="{{ $alt }}"
            width="{{ $column->width() }}"
            height="{{ $column->height() }}"
            loading="lazy"
            @class(['rounded-full' => $column->isRounded()])
        >
    </x-table.card-field>
@endif
