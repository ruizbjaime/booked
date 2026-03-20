@props([
    'color' => null,
    'size' => null,
    'icon' => null,
    'variant' => null,
])

@php
    $isHex = $color !== null && \App\Domain\Table\Columns\BadgeColumn::isHexColor($color);
@endphp

@if ($isHex)
    <span {{ $attributes->class(\App\Domain\Table\Columns\BadgeColumn::hexBadgeClasses($size ?? 'sm')) }} style="background-color: {{ $color }}">{{ $slot }}</span>
@else
    <flux:badge :$color :$size :icon="$icon ?: null" :$variant :attributes="$attributes">{{ $slot }}</flux:badge>
@endif
