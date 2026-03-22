<form wire:submit="save" autocomplete="off" class="space-y-5">
    <div class="grid items-start gap-4 md:grid-cols-2">
        <flux:field>
            <flux:label class="inline-flex items-center gap-1.5">
                <flux:icon.globe-alt class="size-4 text-emerald-600 dark:text-emerald-300" />
                {{ __('countries.create.fields.en_name') }}
            </flux:label>

            <flux:input
                wire:model.live.blur="en_name"
                name="en_name"
                id="create-country-en-name"
                required
            />

            <flux:error name="en_name" />
        </flux:field>

        <flux:field>
            <flux:label class="inline-flex items-center gap-1.5">
                <flux:icon.globe-alt class="size-4 text-emerald-600 dark:text-emerald-300" />
                {{ __('countries.create.fields.es_name') }}
            </flux:label>

            <flux:input
                wire:model.live.blur="es_name"
                name="es_name"
                id="create-country-es-name"
                required
            />

            <flux:error name="es_name" />
        </flux:field>
    </div>

    <div class="grid items-start gap-4 md:grid-cols-2">
        <flux:field>
            <flux:label class="inline-flex items-center gap-1.5">
                <flux:icon.hashtag class="size-4 text-sky-500 dark:text-sky-300" />
                {{ __('countries.create.fields.iso_alpha2') }}
            </flux:label>

            <flux:input
                wire:model.live.blur="iso_alpha2"
                name="iso_alpha2"
                id="create-country-iso-alpha2"
                required
                maxlength="2"
            />

            <flux:error name="iso_alpha2" />
        </flux:field>

        <flux:field>
            <flux:label class="inline-flex items-center gap-1.5">
                <flux:icon.hashtag class="size-4 text-sky-500 dark:text-sky-300" />
                {{ __('countries.create.fields.iso_alpha3') }}
            </flux:label>

            <flux:input
                wire:model.live.blur="iso_alpha3"
                name="iso_alpha3"
                id="create-country-iso-alpha3"
                required
                maxlength="3"
            />

            <flux:error name="iso_alpha3" />
        </flux:field>
    </div>

    <div class="grid items-start gap-4 md:grid-cols-2">
        <flux:field>
            <flux:label class="inline-flex items-center gap-1.5">
                <flux:icon.phone class="size-4 text-amber-500 dark:text-amber-300" />
                {{ __('countries.create.fields.phone_code') }}
            </flux:label>

            <flux:input
                wire:model.live.blur="phone_code"
                name="phone_code"
                id="create-country-phone-code"
                required
            />

            <flux:error name="phone_code" />
        </flux:field>

        <flux:field>
            <flux:label class="inline-flex items-center gap-1.5">
                <flux:icon.arrows-up-down class="size-4 text-violet-500 dark:text-violet-300" />
                {{ __('countries.create.fields.sort_order') }}
            </flux:label>

            <flux:input
                wire:model.live.blur="sort_order"
                name="sort_order"
                id="create-country-sort-order"
                type="number"
                min="0"
                required
            />

            <flux:error name="sort_order" />
        </flux:field>
    </div>

    <div class="rounded-2xl border border-white/8 bg-white/3 px-4 py-3.5 shadow-sm ring-1 ring-inset ring-white/4">
        <div class="flex items-start justify-between gap-4">
            <div class="space-y-1">
                <flux:heading size="sm" class="inline-flex items-center gap-1.5">
                    <flux:icon.power class="size-4 text-emerald-600 dark:text-emerald-300" />
                    {{ __('countries.create.fields.active') }}
                </flux:heading>
                <flux:text size="sm" class="max-w-xl text-zinc-500 dark:text-white/60">
                    {{ __('countries.create.active_help') }}
                </flux:text>
            </div>

            <flux:switch
                wire:model.live="is_active"
                name="is_active"
                id="create-country-active"
                :aria-label="__('countries.create.fields.active')"
            />
        </div>

        <div class="mt-3 flex items-center gap-2">
            <span class="inline-flex size-2.5 rounded-full {{ $is_active ? 'bg-emerald-400 shadow-[0_0_0_4px_rgb(52_211_153_/_0.12)]' : 'bg-zinc-500/80 shadow-[0_0_0_4px_rgb(113_113_122_/_0.12)]' }}"></span>
            <flux:text size="sm" class="font-medium text-zinc-200 dark:text-zinc-100">
                {{ $is_active ? __('countries.create.active_enabled') : __('countries.create.active_disabled') }}
            </flux:text>
        </div>
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
            {{ __('countries.create.submit') }}
        </flux:button>
    </div>
</form>
