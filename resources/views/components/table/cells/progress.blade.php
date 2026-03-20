@php
    $value = $column->resolveValue($record) ?? 0;
    $percentage = $column->max() > 0 ? min(100, ($value / $column->max()) * 100) : 0;
    $color = $column->resolveColor($record);
    $barClass = match ($color) {
        'red' => 'bg-red-500',
        'orange' => 'bg-orange-500',
        'amber' => 'bg-amber-500',
        'yellow' => 'bg-yellow-500',
        'green' => 'bg-green-500',
        'emerald' => 'bg-emerald-500',
        'teal' => 'bg-teal-500',
        'cyan' => 'bg-cyan-500',
        'sky' => 'bg-sky-500',
        'indigo' => 'bg-indigo-500',
        'violet' => 'bg-violet-500',
        'purple' => 'bg-purple-500',
        'pink' => 'bg-pink-500',
        'rose' => 'bg-rose-500',
        'zinc' => 'bg-zinc-500',
        default => 'bg-blue-500',
    };
@endphp

<flux:table.cell @class([$column->cellClass()])>
    <div class="flex items-center gap-2">
        <div class="h-2 w-full rounded-full bg-zinc-200 dark:bg-zinc-700" role="progressbar" aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100">
            <div
                @class(['h-2 rounded-full', $barClass])
                style="width: {{ $percentage }}%"
            ></div>
        </div>
        @if ($column->shouldShowLabel())
            <span class="text-xs tabular-nums text-zinc-500">{{ number_format($percentage) }}%</span>
        @endif
    </div>
</flux:table.cell>
