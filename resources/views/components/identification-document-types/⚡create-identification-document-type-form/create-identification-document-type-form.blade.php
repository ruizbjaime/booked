<form wire:submit="save" autocomplete="off" class="space-y-5">
    <div class="grid items-start gap-4 md:grid-cols-2">
        <flux:field>
            <flux:label class="inline-flex items-center gap-1.5">
                <flux:icon.hashtag class="size-4 text-sky-500 dark:text-sky-300" />
                {{ __('identification_document_types.create.fields.code') }}
            </flux:label>

            <flux:input
                wire:model.live="code"
                name="code"
                id="create-doc-type-code"
                required
                maxlength="20"
            />

            <flux:error name="code" />
        </flux:field>

        <flux:field>
            <flux:label class="inline-flex items-center gap-1.5">
                <flux:icon.arrows-up-down class="size-4 text-violet-500 dark:text-violet-300" />
                {{ __('identification_document_types.create.fields.sort_order') }}
            </flux:label>

            <flux:input
                wire:model.live="sort_order"
                name="sort_order"
                id="create-doc-type-sort-order"
                type="number"
                min="0"
                required
            />

            <flux:error name="sort_order" />
        </flux:field>
    </div>

    <div class="grid items-start gap-4 md:grid-cols-2">
        <flux:field>
            <flux:label class="inline-flex items-center gap-1.5">
                <flux:icon.globe-alt class="size-4 text-emerald-600 dark:text-emerald-300" />
                {{ __('identification_document_types.create.fields.en_name') }}
            </flux:label>

            <flux:input
                wire:model.live="en_name"
                name="en_name"
                id="create-doc-type-en-name"
                required
            />

            <flux:error name="en_name" />
        </flux:field>

        <flux:field>
            <flux:label class="inline-flex items-center gap-1.5">
                <flux:icon.globe-alt class="size-4 text-emerald-600 dark:text-emerald-300" />
                {{ __('identification_document_types.create.fields.es_name') }}
            </flux:label>

            <flux:input
                wire:model.live="es_name"
                name="es_name"
                id="create-doc-type-es-name"
                required
            />

            <flux:error name="es_name" />
        </flux:field>
    </div>

    <div class="rounded-2xl border border-white/8 bg-white/3 px-4 py-3.5 shadow-sm ring-1 ring-inset ring-white/4">
        <div class="flex items-start justify-between gap-4">
            <div class="space-y-1">
                <flux:heading size="sm" class="inline-flex items-center gap-1.5">
                    <flux:icon.power class="size-4 text-emerald-600 dark:text-emerald-300" />
                    {{ __('identification_document_types.create.fields.active') }}
                </flux:heading>
                <flux:text size="sm" class="max-w-xl text-zinc-500 dark:text-white/60">
                    {{ __('identification_document_types.create.active_help') }}
                </flux:text>
            </div>

            <flux:switch
                wire:model.live="is_active"
                name="is_active"
                id="create-doc-type-active"
                :aria-label="__('identification_document_types.create.fields.active')"
            />
        </div>

        <div class="mt-3 flex items-center gap-2">
            <span class="inline-flex size-2.5 rounded-full {{ $is_active ? 'bg-emerald-400 shadow-[0_0_0_4px_rgb(52_211_153_/_0.12)]' : 'bg-zinc-500/80 shadow-[0_0_0_4px_rgb(113_113_122_/_0.12)]' }}"></span>
            <flux:text size="sm" class="font-medium text-zinc-200 dark:text-zinc-100">
                {{ $is_active ? __('identification_document_types.create.active_enabled') : __('identification_document_types.create.active_disabled') }}
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
            {{ __('identification_document_types.create.submit') }}
        </flux:button>
    </div>
</form>
