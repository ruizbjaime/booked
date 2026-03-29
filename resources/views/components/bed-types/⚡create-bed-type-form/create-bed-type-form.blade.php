<form wire:submit="save" autocomplete="off" class="space-y-5">
    <div class="grid items-start gap-4 md:grid-cols-2">
        <flux:field>
            <flux:label class="inline-flex items-center gap-1.5">
                <flux:icon.globe-alt class="size-4 text-emerald-600 dark:text-emerald-300" />
                {{ __('bed_types.create.fields.en_name') }}
            </flux:label>

            <flux:input
                wire:model.live.blur="en_name"
                name="en_name"
                id="create-bed-type-name-en"
                required
            />

            <flux:error name="en_name" />
        </flux:field>

        <flux:field>
            <flux:label class="inline-flex items-center gap-1.5">
                <flux:icon.globe-alt class="size-4 text-emerald-600 dark:text-emerald-300" />
                {{ __('bed_types.create.fields.es_name') }}
            </flux:label>

            <flux:input
                wire:model.live.blur="es_name"
                name="es_name"
                id="create-bed-type-name-es"
                required
            />

            <flux:error name="es_name" />
        </flux:field>
    </div>

    <div class="grid items-start gap-4 md:grid-cols-2">
        <flux:field>
            <flux:label class="inline-flex items-center gap-1.5">
                <flux:icon.users class="size-4 text-amber-500 dark:text-amber-300" />
                {{ __('bed_types.create.fields.bed_capacity') }}
            </flux:label>

            <flux:input
                wire:model.live.blur="bed_capacity"
                name="bed_capacity"
                id="create-bed-type-bed-capacity"
                type="number"
                min="1"
                max="20"
                required
            />

            <flux:error name="bed_capacity" />
        </flux:field>

        <flux:field>
            <flux:label class="inline-flex items-center gap-1.5">
                <flux:icon.arrows-up-down class="size-4 text-violet-500 dark:text-violet-300" />
                {{ __('bed_types.create.fields.sort_order') }}
            </flux:label>

            <flux:input
                wire:model.live.blur="sort_order"
                name="sort_order"
                id="create-bed-type-sort-order"
                type="number"
                min="0"
                max="9999"
                required
            />

            <flux:error name="sort_order" />
        </flux:field>
    </div>

    <flux:separator variant="subtle" />

    <div class="flex flex-col-reverse gap-3 pt-1 sm:flex-row sm:items-center sm:justify-end">
        <flux:spacer class="hidden sm:block" />

        <flux:modal.close>
            <flux:button variant="ghost" size="sm" type="button" wire:click="$dispatch('close-form-modal')" class="modal-service-action w-full sm:w-auto">
                {{ __('actions.cancel') }}
            </flux:button>
        </flux:modal.close>

        <flux:button variant="primary" size="sm" type="submit" class="modal-service-action w-full sm:w-auto">
            {{ __('bed_types.create.submit') }}
        </flux:button>
    </div>
</form>
