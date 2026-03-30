<form wire:submit="save" autocomplete="off" class="space-y-5">
    <div class="space-y-1">
        <flux:heading size="sm">{{ $bedroom->en_name }}</flux:heading>
        <flux:text size="sm" class="text-zinc-500 dark:text-white/60">{{ $bedroom->es_name }}</flux:text>
    </div>

    <flux:field>
        <flux:label>{{ __('properties.show.accommodation.bath_room_types.fields.bath_room_type') }}</flux:label>

        <flux:select
            wire:model.live="bath_room_type_id"
            variant="listbox"
            name="bath_room_type_id"
            id="attach-bath-room-type-id"
            required
        >
            @foreach ($this->bathRoomTypes as $bathRoomType)
                <flux:select.option :value="$bathRoomType->id" wire:key="attach-bath-room-type-option-{{ $bathRoomType->id }}">
                    {{ $bathRoomType->localizedName() }}
                </flux:select.option>
            @endforeach
        </flux:select>

        <flux:error name="bath_room_type_id" />
    </flux:field>

    <flux:field>
        <flux:label>{{ __('properties.show.accommodation.bath_room_types.fields.quantity') }}</flux:label>

        <flux:input
            wire:model.live.blur="quantity"
            name="quantity"
            id="attach-bath-room-type-quantity"
            type="number"
            min="1"
            max="50"
            required
        />

        <flux:error name="quantity" />
    </flux:field>

    <div class="flex flex-col-reverse gap-3 pt-1 sm:flex-row sm:items-center sm:justify-end">
        <flux:spacer class="hidden sm:block" />

        <flux:modal.close>
            <flux:button variant="ghost" size="sm" type="button" wire:click="$dispatch('close-form-modal')" class="modal-service-action w-full sm:w-auto">
                {{ __('actions.cancel') }}
            </flux:button>
        </flux:modal.close>

        <flux:button variant="primary" size="sm" type="submit" class="modal-service-action w-full sm:w-auto">
            {{ __('properties.show.accommodation.bath_room_types.form.submit') }}
        </flux:button>
    </div>
</form>
