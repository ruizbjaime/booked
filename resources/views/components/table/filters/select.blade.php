<flux:select
    variant="listbox"
    :multiple="$filter->isMultiple()"
    wire:model.live="{{ $filter->name() }}"
    class="w-auto!"
    :placeholder="$filter->placeholder()"
>
    @foreach ($filter->resolveOptions() as $value => $label)
        <flux:select.option :value="$value">{{ $label }}</flux:select.option>
    @endforeach
</flux:select>
