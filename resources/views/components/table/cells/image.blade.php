@php
    $src = $column->resolveSrc($record);
    $alt = $column->resolveAlt($record);
@endphp

<flux:table.cell @class([$column->cellClass()])>
    @if ($src)
        <img
            src="{{ $src }}"
            alt="{{ $alt }}"
            width="{{ $column->width() }}"
            height="{{ $column->height() }}"
            loading="lazy"
            @class(['rounded-full' => $column->isRounded()])
        >
    @endif
</flux:table.cell>
