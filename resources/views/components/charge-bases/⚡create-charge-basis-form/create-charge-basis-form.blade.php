<form wire:submit="save" autocomplete="off" class="space-y-5">
    <flux:field>
        <flux:label class="inline-flex items-center gap-1.5">
            <flux:icon.tag class="size-4 text-sky-500 dark:text-sky-300" />
            {{ __('charge_bases.create.fields.name') }}
        </flux:label>

        <flux:input
            wire:model.live.blur="name"
            name="name"
            id="create-charge-basis-name"
            required
        />

        <flux:description>{{ __('charge_bases.create.fields.name_help') }}</flux:description>
        <flux:error name="name" />
    </flux:field>

    <div class="grid items-start gap-4 md:grid-cols-2">
        <flux:field>
            <flux:label class="inline-flex items-center gap-1.5">
                <flux:icon.globe-alt class="size-4 text-emerald-600 dark:text-emerald-300" />
                {{ __('charge_bases.create.fields.en_name') }}
            </flux:label>

            <flux:input
                wire:model.live.blur="en_name"
                name="en_name"
                id="create-charge-basis-en-name"
                required
            />

            <flux:error name="en_name" />
        </flux:field>

        <flux:field>
            <flux:label class="inline-flex items-center gap-1.5">
                <flux:icon.globe-alt class="size-4 text-emerald-600 dark:text-emerald-300" />
                {{ __('charge_bases.create.fields.es_name') }}
            </flux:label>

            <flux:input
                wire:model.live.blur="es_name"
                name="es_name"
                id="create-charge-basis-es-name"
                required
            />

            <flux:error name="es_name" />
        </flux:field>
    </div>

    <div>
        <flux:label class="mb-2 inline-flex items-center gap-1.5">
            <flux:icon.information-circle class="size-4 text-amber-500 dark:text-amber-300" />
            {{ __('charge_bases.create.fields.description') }}
        </flux:label>

        <flux:tab.group>
            <flux:tabs variant="segmented" size="sm">
                <flux:tab name="en">{{ __('charge_bases.tabs.en') }}</flux:tab>
                <flux:tab name="es">{{ __('charge_bases.tabs.es') }}</flux:tab>
            </flux:tabs>

            <flux:tab.panel name="en">
                <flux:textarea
                    wire:model.live.blur="en_description"
                    name="en_description"
                    id="create-charge-basis-en-description"
                    rows="3"
                />

                <flux:error name="en_description" />
            </flux:tab.panel>

            <flux:tab.panel name="es">
                <flux:textarea
                    wire:model.live.blur="es_description"
                    name="es_description"
                    id="create-charge-basis-es-description"
                    rows="3"
                />

                <flux:error name="es_description" />
            </flux:tab.panel>
        </flux:tab.group>
    </div>

    <div class="grid items-start gap-4 md:grid-cols-2">
        <flux:field>
            <flux:label class="inline-flex items-center gap-1.5">
                <flux:icon.arrows-up-down class="size-4 text-violet-500 dark:text-violet-300" />
                {{ __('charge_bases.create.fields.order') }}
            </flux:label>

            <flux:input
                wire:model.live.blur="order"
                name="order"
                id="create-charge-basis-order"
                type="number"
                min="0"
                max="9999"
                required
            />

            <flux:error name="order" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('charge_bases.create.fields.is_active') }}</flux:label>
            <flux:switch wire:model.live="is_active" :label="$is_active ? __('charge_bases.create.active_enabled') : __('charge_bases.create.active_disabled')" />
            <flux:error name="is_active" />
        </flux:field>
    </div>

    <div class="grid items-start gap-4 md:grid-cols-2">
        <flux:field>
            <flux:label>{{ __('charge_bases.create.fields.requires_quantity') }}</flux:label>
            <flux:switch wire:model.live="requires_quantity" :label="__('charge_bases.create.fields.requires_quantity')" />
            <flux:error name="metadata.requires_quantity" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('charge_bases.create.fields.quantity_subject') }}</flux:label>
            <flux:select
                wire:model.live="quantity_subject"
                name="quantity_subject"
                id="create-charge-basis-quantity-subject"
                :disabled="! $requires_quantity"
            >
                <option value="">{{ __('actions.select') }}</option>
                @foreach (__('charge_bases.quantity_subjects') as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </flux:select>

            <flux:error name="metadata.quantity_subject" />
            <flux:error name="quantity_subject" />
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
            {{ __('charge_bases.create.submit') }}
        </flux:button>
    </div>
</form>
