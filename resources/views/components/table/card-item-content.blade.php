@php
    $zones = [
        'header' => 'space-y-2',
        'body' => 'space-y-2',
        'footer' => 'space-y-1',
    ];

    $hasRenderedZone = false;
@endphp

@foreach ($zones as $zone => $spacing)
    @continue($columnsByZone[$zone] === [])

    @if ($hasRenderedZone)
        <flux:separator variant="subtle" class="my-3" />
    @endif

    <div class="{{ $spacing }}">
        @foreach ($columnsByZone[$zone] as $column)
            @include("components.table.cards.{$column->type()}", [
                'column' => $column,
                'record' => $record,
            ])
        @endforeach
    </div>

    @php($hasRenderedZone = true)
@endforeach
