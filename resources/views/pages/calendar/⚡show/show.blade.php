@php $day = $calendarDay; @endphp

<section class="container mx-auto max-w-3xl space-y-6">
    <x-heading
        :heading="__('calendar.show.title')"
        :subheading="__('calendar.show.description', ['date' => $day->date->translatedFormat('l, j F Y')])"
    />

    {{-- Navigation --}}
    <div class="flex items-center justify-between">
        @if ($this->previousDate)
            <flux:button variant="ghost" icon="chevron-left" :href="route('calendar.show', $this->previousDate)" wire:navigate size="sm">
                {{ $this->previousDate }}
            </flux:button>
        @else
            <div></div>
        @endif

        <flux:button variant="ghost" icon="arrow-left" :href="route('calendar.index', ['year' => $day->year])" wire:navigate size="sm">
            {{ __('calendar.show.back') }}
        </flux:button>

        @if ($this->nextDate)
            <flux:button variant="ghost" icon-trailing="chevron-right" :href="route('calendar.show', $this->nextDate)" wire:navigate size="sm">
                {{ $this->nextDate }}
            </flux:button>
        @else
            <div></div>
        @endif
    </div>

    {{-- Pricing Badge --}}
    <flux:card class="flex items-center gap-4">
        <div class="flex size-12 items-center justify-center rounded-lg" style="background-color: {{ $this->categoryColor }}">
            <flux:icon.calendar-days class="size-6 text-white" />
        </div>
        <div>
            <flux:heading size="lg">{{ $day->date->translatedFormat('l, j F Y') }}</flux:heading>
            <div class="flex items-center gap-2">
                @if ($day->pricingCategory)
                    <flux:badge size="sm" style="background-color: {{ $this->categoryColor }}; color: white;">
                        {{ $day->pricingCategory->localizedName() }} ({{ $day->pricingCategory->multiplier }}x)
                    </flux:badge>
                @endif
                @if ($day->is_holiday)
                    <flux:badge size="sm" color="red">{{ __('calendar.show.fields.is_holiday') }}</flux:badge>
                @endif
                @if ($day->is_bridge_day)
                    <flux:badge size="sm" color="amber">{{ __('calendar.show.fields.is_bridge_day') }}</flux:badge>
                @endif
                @if ($day->is_quincena_adjacent)
                    <flux:badge size="sm" color="sky">{{ __('calendar.show.fields.is_quincena') }}</flux:badge>
                @endif
            </div>
        </div>
    </flux:card>

    {{-- General --}}
    <flux:card class="space-y-4">
        <flux:heading size="sm">{{ __('calendar.show.sections.general') }}</flux:heading>
        <flux:separator variant="subtle" />

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <flux:text size="sm" class="text-zinc-500">{{ __('calendar.show.fields.date') }}</flux:text>
                <flux:text class="font-medium">{{ $day->date->toDateString() }}</flux:text>
            </div>
            <div>
                <flux:text size="sm" class="text-zinc-500">{{ __('calendar.show.fields.day_of_week') }}</flux:text>
                <flux:text class="font-medium">{{ ucfirst($day->day_of_week_name) }}</flux:text>
            </div>
        </div>
    </flux:card>

    {{-- Holiday --}}
    @if ($day->is_holiday && $day->holidayDefinition)
        <flux:card class="space-y-4">
            <flux:heading size="sm">{{ __('calendar.show.sections.holiday') }}</flux:heading>
            <flux:separator variant="subtle" />

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <flux:text size="sm" class="text-zinc-500">{{ __('calendar.show.fields.holiday_name') }}</flux:text>
                    <flux:text class="font-medium">{{ $day->holidayDefinition->localizedName() }}</flux:text>
                </div>
                <div>
                    <flux:text size="sm" class="text-zinc-500">{{ __('calendar.show.fields.holiday_group') }}</flux:text>
                    <flux:text class="font-medium">{{ __('calendar.holiday_groups.' . $day->holiday_group) }}</flux:text>
                </div>
                <div>
                    <flux:text size="sm" class="text-zinc-500">{{ __('calendar.show.fields.holiday_impact') }}</flux:text>
                    <flux:text class="font-medium">{{ $day->holiday_impact }}</flux:text>
                </div>
                @if ($day->holiday_original_date && $day->holiday_observed_date && $day->holiday_original_date->ne($day->holiday_observed_date))
                    <div>
                        <flux:text size="sm" class="text-zinc-500">{{ __('calendar.show.fields.original_date') }}</flux:text>
                        <flux:text class="font-medium">{{ $day->holiday_original_date->toDateString() }}</flux:text>
                    </div>
                @endif
            </div>
        </flux:card>
    @endif

    {{-- Season --}}
    @if ($day->seasonBlock)
        <flux:card class="space-y-4">
            <flux:heading size="sm">{{ __('calendar.show.sections.season') }}</flux:heading>
            <flux:separator variant="subtle" />

            <div>
                <flux:text size="sm" class="text-zinc-500">{{ __('calendar.show.fields.season') }}</flux:text>
                <flux:text class="font-medium">{{ $day->seasonBlock->localizedName() }}</flux:text>
            </div>
        </flux:card>
    @endif

    {{-- Pricing --}}
    @if ($day->pricingCategory)
        <flux:card class="space-y-4">
            <flux:heading size="sm">{{ __('calendar.show.sections.pricing') }}</flux:heading>
            <flux:separator variant="subtle" />

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <flux:text size="sm" class="text-zinc-500">{{ __('calendar.show.fields.pricing_category') }}</flux:text>
                    <div class="flex items-center gap-2">
                        <span class="inline-block size-3 rounded" style="background-color: {{ $this->categoryColor }}"></span>
                        <flux:text class="font-medium">{{ $day->pricingCategory->localizedName() }}</flux:text>
                    </div>
                </div>
                <div>
                    <flux:text size="sm" class="text-zinc-500">{{ __('calendar.show.fields.pricing_level') }}</flux:text>
                    <flux:text class="font-medium">{{ $day->pricing_category_level }}</flux:text>
                </div>
            </div>
        </flux:card>
    @endif

    {{-- Notes --}}
    @if ($day->notes)
        <flux:card class="space-y-4">
            <flux:heading size="sm">{{ __('calendar.show.fields.notes') }}</flux:heading>
            <flux:separator variant="subtle" />
            <flux:text>{{ $day->notes }}</flux:text>
        </flux:card>
    @endif
</section>
