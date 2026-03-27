@props(['keyPrefix', 'description' => null])

<flux:field>
    <flux:label class="inline-flex items-center gap-1.5">
        <flux:icon.calendar-days class="size-4 text-sky-500 dark:text-sky-300" />
        {{ __('calendar.settings.rule_form.fields.day_of_week') }}
    </flux:label>
    @if ($description)
        <flux:description>{{ $description }}</flux:description>
    @endif
    <flux:checkbox.group wire:model.live="day_of_week" class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        @foreach ($this->dayOptions as $dayOption)
            <div wire:key="{{ $keyPrefix }}-{{ $dayOption['value'] }}" class="rounded-xl border border-zinc-200 bg-zinc-50 px-3 py-2.5 dark:border-white/8 dark:bg-white/3">
                <flux:checkbox :value="$dayOption['value']" :label="$dayOption['label']" />
            </div>
        @endforeach
    </flux:checkbox.group>
    <flux:error name="day_of_week" />
</flux:field>
