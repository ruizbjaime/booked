@props([
    'color' => null,
    'size' => 'md',
    'src' => null,
    'initials' => null,
    'name' => null,
])

@php
    $isHex = $color !== null && \App\Domain\Table\Columns\BadgeColumn::isHexColor($color);
    $hexClasses = $isHex ? \App\Domain\Table\Columns\AvatarColumn::hexAvatarClasses($size) : null;
@endphp

@if ($isHex)
    <div
        {{ $attributes->class("{$hexClasses['container']} {$hexClasses['after']}") }}
        style="background-color: color-mix(in srgb, {{ $color }} 25%, white); color: {{ $color }}"
    >
        <span class="select-none">{{ $initials ?? $slot }}</span>
    </div>
@else
    <flux:avatar :$size :$src :$initials :$color :$name :attributes="$attributes" />
@endif
