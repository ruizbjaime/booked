<div>
    <flux:card @class(['!bg-gray-50 dark:!bg-neutral-700 shadow-lg'])>
        @if(!empty($searchableFields) || !empty($tableActions))
            <div class="flex gap-2 py-6">
                @if(!empty($searchableFields))
                    <flux:input
                        wire:model.live.debounce="search"
                        @class(["max-w-[250px]"])
                        name="search"
                        icon="magnifying-glass"
                        :placeholder="__('Search') . '...'"
                    />
                @endif

                @if(!empty($tableActions) && isset($tableActions['create']))
                    <flux:spacer/>
                    <flux:button
                        :variant="$tableActions['create']['variant']"
                        :icon:trailing="$tableActions['create']['icon']"
                        wire:click="{{$tableActions['create']['action']}}()"
                    >{{__($tableActions['create']['label'])}}</flux:button>
                @endif
            </div>
        @endif

        <flux:table :paginate="$items">
            <flux:table.columns>
                @foreach($columnMap as $path => $confData)
                    <flux:table.column
                        @class(['first:ps-2 last:pe-2'])
                        :sortable="isset($confData['sortable']) && $confData['sortable']"
                        :sorted="$sortBy === $path"
                        :direction="$sortDirection"
                        wire:key="{{$path}}-column"
                        wire:click="{{ isset($confData['sortable']) && $confData['sortable'] ? 'sort(\'' . $path . '\')' : 'noOp()' }}"
                    >
                        {{ __($confData['label']) }}
                    </flux:table.column>
                @endforeach
                @if(!empty($tableActions))
                    <flux:table.column @class(['flex justify-end first:ps-2 last:pe-2'])>
                        {{ __('Actions') }}
                    </flux:table.column>
                @endif
            </flux:table.columns>

            <flux:table.rows>
                @foreach($items as $index => $item)
                    <flux:table.row :key="$item->id" @class(['bg-gray-100/90 dark:bg-neutral-600/20' => $this->isEven($index)])>
                        @foreach(array_keys($columnMap) as $path)
                            <flux:table.cell :key="$item->id . '-' . $path" @class(['first:ps-2 last:pe-2'])>
                                {{ $this->getValueForColumn($item, $path) }}
                            </flux:table.cell>
                        @endforeach
                        @if(!empty($tableActions) && is_array($tableActions))
                            <flux:table.cell @class(['flex justify-end gap-1 first:ps-2 last:pe-2'])>
                                @foreach($tableActions as $action => $configData)
                                    @if(in_array($action, ['show', 'edit', 'delete']))
                                        <flux:button
                                            wire:key="{{$item->id}}-{{$action}}"
                                            :variant="$configData['variant']"
                                            size="sm"
                                            :icon="$configData['icon']"
                                            icon:variant="outline"
                                            :tooltip="__('' . $configData['label'])"
                                            wire:click="{{$configData['action']}}('{{in_array($action, ['show','edit','delete']) ? $item->id : null}}')"
                                        />
                                    @endif
                                @endforeach
                            </flux:table.cell>
                        @endif
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
