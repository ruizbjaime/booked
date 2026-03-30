<form wire:submit="save" autocomplete="off" class="space-y-5">
    <div class="grid items-start gap-4 md:grid-cols-2">
        <flux:field>
            <flux:label>{{ __('properties.show.accommodation.fields.en_name') }}</flux:label>
            <flux:input wire:model.live.blur="en_name" name="en_name" id="create-bedroom-en-name" required />
            <flux:error name="en_name" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('properties.show.accommodation.fields.es_name') }}</flux:label>
            <flux:input wire:model.live.blur="es_name" name="es_name" id="create-bedroom-es-name" required />
            <flux:error name="es_name" />
        </flux:field>
    </div>

    <div class="grid items-start gap-4 md:grid-cols-2">
        <flux:field>
            <flux:label>{{ __('properties.show.accommodation.fields.en_description') }}</flux:label>
            <flux:textarea wire:model.live.blur="en_description" name="en_description" id="create-bedroom-en-description" rows="3" />
            <flux:error name="en_description" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('properties.show.accommodation.fields.es_description') }}</flux:label>
            <flux:textarea wire:model.live.blur="es_description" name="es_description" id="create-bedroom-es-description" rows="3" />
            <flux:error name="es_description" />
        </flux:field>
    </div>

    <div class="flex flex-col-reverse gap-3 pt-1 sm:flex-row sm:items-center sm:justify-end">
        <flux:spacer class="hidden sm:block" />

        <flux:modal.close>
            <flux:button variant="ghost" size="sm" type="button" wire:click="$dispatch('close-form-modal')" class="modal-service-action w-full sm:w-auto">
                {{ __('actions.cancel') }}
            </flux:button>
        </flux:modal.close>

        <flux:button variant="primary" size="sm" type="submit" class="modal-service-action w-full sm:w-auto">
            {{ __('properties.show.accommodation.form.submit') }}
        </flux:button>
    </div>
</form>
