<form wire:submit="save" autocomplete="off" class="space-y-5">
    <flux:field>
        <flux:label class="inline-flex items-center gap-1.5">
            <flux:icon.tag class="size-4 text-sky-500 dark:text-sky-300" />
            {{ __('fee_types.create.fields.name') }}
        </flux:label>

        <flux:input
            wire:model.live.blur="name"
            name="name"
            id="create-fee-type-name"
            required
        />

        <flux:description>{{ __('fee_types.create.fields.name_help') }}</flux:description>
        <flux:error name="name" />
    </flux:field>

    <div class="grid items-start gap-4 md:grid-cols-2">
        <flux:field>
            <flux:label class="inline-flex items-center gap-1.5">
                <flux:icon.globe-alt class="size-4 text-emerald-600 dark:text-emerald-300" />
                {{ __('fee_types.create.fields.en_name') }}
            </flux:label>

            <flux:input
                wire:model.live.blur="en_name"
                name="en_name"
                id="create-fee-type-en-name"
                required
            />

            <flux:error name="en_name" />
        </flux:field>

        <flux:field>
            <flux:label class="inline-flex items-center gap-1.5">
                <flux:icon.globe-alt class="size-4 text-emerald-600 dark:text-emerald-300" />
                {{ __('fee_types.create.fields.es_name') }}
            </flux:label>

            <flux:input
                wire:model.live.blur="es_name"
                name="es_name"
                id="create-fee-type-es-name"
                required
            />

            <flux:error name="es_name" />
        </flux:field>
    </div>

    <flux:field>
        <flux:label class="inline-flex items-center gap-1.5">
            <flux:icon.arrows-up-down class="size-4 text-violet-500 dark:text-violet-300" />
            {{ __('fee_types.create.fields.order') }}
        </flux:label>

        <flux:input
            wire:model.live.blur="order"
            name="order"
            id="create-fee-type-order"
            type="number"
            min="0"
            max="9999"
            required
        />

        <flux:error name="order" />
    </flux:field>

    <flux:separator variant="subtle" />

    <div class="flex flex-col-reverse gap-3 pt-1 sm:flex-row sm:items-center sm:justify-end">
        <flux:spacer class="hidden sm:block" />

        <flux:modal.close>
            <flux:button variant="ghost" size="sm" type="button" wire:click="$dispatch('close-form-modal')" class="modal-service-action w-full sm:w-auto">
                {{ __('actions.cancel') }}
            </flux:button>
        </flux:modal.close>

        <flux:button variant="primary" size="sm" type="submit" class="modal-service-action w-full sm:w-auto">
            {{ __('fee_types.create.submit') }}
        </flux:button>
    </div>
</form>
