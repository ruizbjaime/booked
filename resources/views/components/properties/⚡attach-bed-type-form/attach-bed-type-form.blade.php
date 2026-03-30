<form wire:submit="save" autocomplete="off" class="space-y-5">
    <div class="space-y-1">
        <flux:heading size="sm">{{ $bedroom->en_name }}</flux:heading>
        <flux:text size="sm" class="text-zinc-500 dark:text-white/60">{{ $bedroom->es_name }}</flux:text>
    </div>

    <flux:field>
        <flux:label>{{ __('properties.show.accommodation.bed_types.fields.bed_type') }}</flux:label>

        <flux:select
            wire:model.live="bed_type_id"
            variant="listbox"
            name="bed_type_id"
            id="attach-bed-type-id"
            required
        >
            @foreach ($this->bedTypes as $bedType)
                <flux:select.option :value="$bedType->id" wire:key="attach-bed-type-option-{{ $bedType->id }}">
                    {{ $bedType->localizedName() }}
                </flux:select.option>
            @endforeach
        </flux:select>

        <flux:error name="bed_type_id" />
    </flux:field>

    <flux:field>
        <flux:label>{{ __('properties.show.accommodation.bed_types.fields.quantity') }}</flux:label>

        <flux:input
            wire:model.live.blur="quantity"
            name="quantity"
            id="attach-bed-type-quantity"
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
            {{ __('properties.show.accommodation.bed_types.form.submit') }}
        </flux:button>
    </div>
</form>
