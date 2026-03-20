<form wire:submit="save" autocomplete="off" class="space-y-5">
    <flux:field>
        <flux:label class="inline-flex items-center gap-1.5">
            <flux:icon.shield-check class="size-4 text-sky-500 dark:text-sky-300" />
            {{ __('roles.create.fields.name') }}
        </flux:label>

        <flux:input
            wire:model.live="name"
            name="name"
            id="create-role-name"
            required
        />

        <flux:description>{{ __('roles.create.fields.name_help') }}</flux:description>
        <flux:error name="name" />
    </flux:field>

    <div class="grid items-start gap-4 md:grid-cols-2">
        <flux:field>
            <flux:label class="inline-flex items-center gap-1.5">
                <flux:icon.globe-alt class="size-4 text-emerald-600 dark:text-emerald-300" />
                {{ __('roles.create.fields.en_label') }}
            </flux:label>

            <flux:input
                wire:model.live="en_label"
                name="en_label"
                id="create-role-en-label"
                required
            />

            <flux:error name="en_label" />
        </flux:field>

        <flux:field>
            <flux:label class="inline-flex items-center gap-1.5">
                <flux:icon.globe-alt class="size-4 text-emerald-600 dark:text-emerald-300" />
                {{ __('roles.create.fields.es_label') }}
            </flux:label>

            <flux:input
                wire:model.live="es_label"
                name="es_label"
                id="create-role-es-label"
                required
            />

            <flux:error name="es_label" />
        </flux:field>
    </div>

    <div class="grid items-start gap-4 md:grid-cols-2">
        <flux:field>
            <flux:label class="inline-flex items-center gap-1.5">
                <flux:icon.swatch class="size-4 text-violet-500 dark:text-violet-300" />
                {{ __('roles.create.fields.color') }}
            </flux:label>

            <flux:select
                wire:model.live="color"
                name="color"
                id="create-role-color"
            >
                @foreach (\App\Actions\Roles\CreateRole::AVAILABLE_COLORS as $colorOption)
                    <flux:select.option :value="$colorOption">{{ ucfirst($colorOption) }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:error name="color" />
        </flux:field>

        <flux:field>
            <flux:label class="inline-flex items-center gap-1.5">
                <flux:icon.arrows-up-down class="size-4 text-violet-500 dark:text-violet-300" />
                {{ __('roles.create.fields.sort_order') }}
            </flux:label>

            <flux:input
                wire:model.live="sort_order"
                name="sort_order"
                id="create-role-sort-order"
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
                    {{ __('roles.create.fields.active') }}
                </flux:heading>
                <flux:text size="sm" class="max-w-xl text-zinc-500 dark:text-white/60">
                    {{ __('roles.create.active_help') }}
                </flux:text>
            </div>

            <flux:switch
                wire:model.live="is_active"
                name="is_active"
                id="create-role-active"
                :aria-label="__('roles.create.fields.active')"
            />
        </div>

        <div class="mt-3 flex items-center gap-2">
            <span class="inline-flex size-2.5 rounded-full {{ $is_active ? 'bg-emerald-400 shadow-[0_0_0_4px_rgb(52_211_153_/_0.12)]' : 'bg-zinc-500/80 shadow-[0_0_0_4px_rgb(113_113_122_/_0.12)]' }}"></span>
            <flux:text size="sm" class="font-medium text-zinc-200 dark:text-zinc-100">
                {{ $is_active ? __('roles.create.active_enabled') : __('roles.create.active_disabled') }}
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
            {{ __('roles.create.submit') }}
        </flux:button>
    </div>
</form>
