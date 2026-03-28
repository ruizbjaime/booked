@props([
    'record',
    'columnsByZone',
    'actionColumns' => [],
    'sortable' => false,
])

@php
    use App\Domain\Table\CardLayout;
    use Illuminate\Support\Js;

    $recordKey = $record->getKey();
    $actionItems = CardLayout::actionItems($actionColumns, $record);
    $hasActions = $actionItems !== [];
@endphp

<div
    wire:key="card-{{ $recordKey }}"
    @if ($sortable) wire:sort:item="{{ $recordKey }}" @endif
    @if ($hasActions)
        x-data="cardSwipe({{ Js::from($recordKey) }}, {{ count($actionItems) }})"
    @endif
    class="relative overflow-hidden rounded-xl"
>
    @if ($hasActions)
        {{-- Action buttons revealed behind card (hidden until swipe starts) --}}
        <div
            class="absolute inset-y-0 right-0 flex items-stretch"
            role="group"
            :aria-hidden="offsetX >= 0"
            aria-label="{{ __('actions.actions') }}"
            x-show="offsetX < 0"
            x-cloak
        >
            @foreach ($actionItems as $action)
                @php
                    $actionClasses = [
                        'flex w-14 flex-col items-center justify-center gap-1 text-xs font-medium text-white',
                        'bg-red-600' => $action->variant() === 'danger',
                        'bg-zinc-600 dark:bg-zinc-500' => $action->variant() !== 'danger',
                    ];
                @endphp

                @if ($action->isLink())
                    <a
                        href="{{ $action->href() }}"
                        @if ($action->shouldWireNavigate()) wire:navigate @endif
                        @class($actionClasses)
                        aria-label="{{ $action->label() }}"
                    >
                        <flux:icon :name="$action->icon()" variant="outline" class="size-5" />
                        <span class="text-[10px] leading-tight">{{ $action->label() }}</span>
                    </a>
                @elseif ($action->isButton())
                    <button
                        type="button"
                        wire:click="{{ $action->wireClick() }}({{ Js::from($recordKey) }})"
                        @class($actionClasses)
                        aria-label="{{ $action->label() }}"
                    >
                        <flux:icon :name="$action->icon()" variant="outline" class="size-5" />
                        <span class="text-[10px] leading-tight">{{ $action->label() }}</span>
                    </button>
                @endif
            @endforeach
        </div>

        {{-- Card face (swipeable) -- opaque background to fully cover action buttons --}}
        <div
            x-on:touchstart.passive="onTouchStart"
            x-on:touchmove.passive="onTouchMove"
            x-on:touchend="onTouchEnd"
            x-bind:class="{ 'is-snapping': snapping }"
            x-bind:style="'transform: translateX(' + offsetX + 'px)'"
            class="card-swipe-face relative rounded-xl bg-white dark:bg-zinc-800"
        >
            <flux:card class="bg-zinc-50 shadow-md dark:bg-white/10">
                @if ($sortable)
                    <div wire:sort:handle class="mb-3 flex justify-end text-zinc-400 hover:text-zinc-600 active:cursor-grabbing dark:text-zinc-500 dark:hover:text-zinc-300">
                        <flux:icon.grip-vertical class="size-5 cursor-grab" aria-hidden="true" />
                        <span class="sr-only">{{ __('actions.reorder') }}</span>
                    </div>
                @endif

                @include('components.table.card-item-content', [
                    'columnsByZone' => $columnsByZone,
                    'record' => $record,
                ])
            </flux:card>
        </div>
    @else
        <flux:card class="bg-zinc-50 shadow-md dark:bg-white/10">
            @if ($sortable)
                <div wire:sort:handle class="mb-3 flex justify-end text-zinc-400 hover:text-zinc-600 active:cursor-grabbing dark:text-zinc-500 dark:hover:text-zinc-300">
                    <flux:icon.grip-vertical class="size-5 cursor-grab" aria-hidden="true" />
                    <span class="sr-only">{{ __('actions.reorder') }}</span>
                </div>
            @endif

            @include('components.table.card-item-content', [
                'columnsByZone' => $columnsByZone,
                'record' => $record,
            ])
        </flux:card>
    @endif
</div>
