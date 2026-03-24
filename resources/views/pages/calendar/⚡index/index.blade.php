<section class="container mx-auto space-y-6">
    <x-heading :heading="__('calendar.index.title')" :subheading="__('calendar.index.description')" />

    {{-- Year Navigation --}}
    <div class="flex items-center justify-between">
        <flux:button variant="ghost" icon="chevron-left" wire:click="previousYear" size="sm">
            {{ __('calendar.index.previous_year') }}
        </flux:button>

        <flux:heading size="xl">{{ $selectedYear }}</flux:heading>

        <flux:button variant="ghost" icon-trailing="chevron-right" wire:click="nextYear" size="sm">
            {{ __('calendar.index.next_year') }}
        </flux:button>
    </div>

    @if ($this->days->isEmpty())
        <flux:card class="py-12 text-center">
            <flux:icon.calendar-days class="mx-auto mb-3 size-12 text-zinc-400" />
            <flux:heading>{{ __('calendar.index.no_data') }}</flux:heading>
            <flux:subheading>{{ __('calendar.index.generate_prompt') }}</flux:subheading>
        </flux:card>
    @else
        {{-- Legend --}}
        <flux:card class="space-y-3">
            <flux:heading size="sm">{{ __('calendar.index.legend.title') }}</flux:heading>
            <div class="flex flex-wrap gap-4">
                @foreach ($this->categories as $category)
                    <div class="flex items-center gap-2" wire:key="legend-{{ $category->id }}">
                        <span class="inline-block size-4 rounded" style="background-color: {{ $category->color }}"></span>
                        <flux:text size="sm">{{ $category->localizedName() }} ({{ $category->multiplier }}x)</flux:text>
                    </div>
                @endforeach
            </div>
        </flux:card>

        {{-- Stats --}}
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
            <flux:card class="text-center">
                <flux:text size="sm" class="text-zinc-500">{{ __('calendar.index.stats.total_holidays') }}</flux:text>
                <flux:heading size="xl">{{ $this->stats['holidays'] }}</flux:heading>
            </flux:card>
            <flux:card class="text-center">
                <flux:text size="sm" class="text-zinc-500">{{ __('calendar.index.stats.bridge_weekends') }}</flux:text>
                <flux:heading size="xl">{{ $this->stats['bridges'] }}</flux:heading>
            </flux:card>
            <flux:card class="text-center">
                <flux:text size="sm" class="text-zinc-500">{{ __('calendar.index.stats.cat_1') }}</flux:text>
                <flux:heading size="xl" style="color: {{ $this->categoryColor(1) }}">{{ $this->stats['cat_1'] }}</flux:heading>
            </flux:card>
            <flux:card class="text-center">
                <flux:text size="sm" class="text-zinc-500">{{ __('calendar.index.stats.cat_2') }}</flux:text>
                <flux:heading size="xl" style="color: {{ $this->categoryColor(2) }}">{{ $this->stats['cat_2'] }}</flux:heading>
            </flux:card>
            <flux:card class="text-center">
                <flux:text size="sm" class="text-zinc-500">{{ __('calendar.index.stats.cat_3') }}</flux:text>
                <flux:heading size="xl" style="color: {{ $this->categoryColor(3) }}">{{ $this->stats['cat_3'] }}</flux:heading>
            </flux:card>
            <flux:card class="text-center">
                <flux:text size="sm" class="text-zinc-500">{{ __('calendar.index.stats.cat_4') }}</flux:text>
                <flux:heading size="xl" style="color: {{ $this->categoryColor(4) }}">{{ $this->stats['cat_4'] }}</flux:heading>
            </flux:card>
        </div>

        {{-- 12-Month Grid (4x3) --}}
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
            @foreach ($this->monthGrids as $month => $weeks)
                <flux:card class="space-y-2 p-3" wire:key="month-{{ $month }}">
                    <flux:heading size="sm" class="text-center">{{ __("calendar.index.months.{$month}") }}</flux:heading>

                    <table class="w-full text-center text-xs">
                        <thead>
                            <tr>
                                @foreach (['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'] as $wd)
                                    <th class="p-0.5 font-medium text-zinc-400">{{ __("calendar.index.weekdays.{$wd}") }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($weeks as $weekIdx => $week)
                                <tr wire:key="month-{{ $month }}-week-{{ $weekIdx }}">
                                    @foreach ($week as $cellIdx => $cell)
                                        <td class="p-0.5" wire:key="month-{{ $month }}-week-{{ $weekIdx }}-cell-{{ $cellIdx }}">
                                            @if ($cell)
                                                <a
                                                    href="{{ route('calendar.show', $cell['date']) }}"
                                                    wire:navigate
                                                    class="flex size-7 items-center justify-center rounded text-xs font-medium transition-opacity hover:opacity-80 {{ $cell['isHoliday'] ? 'ring-1 ring-white/50' : '' }}"
                                                    style="background-color: {{ $cell['level'] ? $this->categoryColor($cell['level']) : '#374151' }}; color: white;"
                                                    title="{{ $cell['date'] }}"
                                                >
                                                    {{ $cell['day'] }}
                                                </a>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </flux:card>
            @endforeach
        </div>
    @endif
</section>
