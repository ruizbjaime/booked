@php
    use Illuminate\Support\Js;

    $recordKey = $record->getKey();
@endphp

<flux:table.cell align="end">
    <flux:dropdown position="{{ $column->dropdownPosition() }}" align="{{ $column->dropdownAlign() }}">
        <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" inset="top bottom" />

        <flux:menu>
            <flux:menu.radio.group>
                @foreach ($column->resolveActions($record) as $action)
                    @if ($action->isSeparator())
                        <flux:menu.separator />
                    @elseif ($action->isLink() && $action->shouldWireNavigate())
                        <flux:menu.item :href="$action->href()" wire:navigate icon="{{ $action->icon() }}">
                            {{ $action->label() }}
                        </flux:menu.item>
                    @elseif ($action->isLink())
                        <flux:menu.item :href="$action->href()" icon="{{ $action->icon() }}">
                            {{ $action->label() }}
                        </flux:menu.item>
                    @elseif ($action->isButton())
                        <flux:menu.item
                            type="button"
                            :variant="$action->variant() !== 'default' ? $action->variant() : null"
                            icon="{{ $action->icon() }}"
                            wire:click="{{ $action->wireClick() }}({{ Js::from($recordKey) }})"
                        >
                            {{ $action->label() }}
                        </flux:menu.item>
                    @endif
                @endforeach
            </flux:menu.radio.group>
        </flux:menu>
    </flux:dropdown>
</flux:table.cell>
