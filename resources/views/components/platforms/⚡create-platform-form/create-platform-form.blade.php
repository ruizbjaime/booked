<form wire:submit="save" autocomplete="off" class="space-y-5">
    <flux:field>
        <flux:label class="inline-flex items-center gap-1.5">
            <flux:icon.tag class="size-4 text-sky-500 dark:text-sky-300" />
            {{ __('platforms.create.fields.name') }}
        </flux:label>

        <flux:input
            wire:model.live="name"
            name="name"
            id="create-platform-name"
            required
        />

        <flux:description>{{ __('platforms.create.fields.name_help') }}</flux:description>
        <flux:error name="name" />
    </flux:field>

    <div class="grid items-start gap-4 md:grid-cols-2">
        <flux:field>
            <flux:label class="inline-flex items-center gap-1.5">
                <flux:icon.globe-alt class="size-4 text-emerald-600 dark:text-emerald-300" />
                {{ __('platforms.create.fields.en_name') }}
            </flux:label>

            <flux:input
                wire:model.live="en_name"
                name="en_name"
                id="create-platform-en-name"
                required
            />

            <flux:error name="en_name" />
        </flux:field>

        <flux:field>
            <flux:label class="inline-flex items-center gap-1.5">
                <flux:icon.globe-alt class="size-4 text-emerald-600 dark:text-emerald-300" />
                {{ __('platforms.create.fields.es_name') }}
            </flux:label>

            <flux:input
                wire:model.live="es_name"
                name="es_name"
                id="create-platform-es-name"
                required
            />

            <flux:error name="es_name" />
        </flux:field>
    </div>

    <div class="grid items-start gap-4 md:grid-cols-2">
        <flux:field>
            <flux:label class="inline-flex items-center gap-1.5">
                <flux:icon.swatch class="size-4 text-fuchsia-500 dark:text-fuchsia-300" />
                {{ __('platforms.create.fields.color') }}
            </flux:label>

            <flux:select wire:model.live="colorMode" id="create-platform-color-mode">
                @foreach (\App\Actions\Platforms\CreatePlatform::AVAILABLE_COLORS as $presetColor)
                    <flux:select.option :value="$presetColor">{{ ucfirst($presetColor) }}</flux:select.option>
                @endforeach
                <flux:select.option value="custom">{{ __('platforms.create.fields.color_custom_option') }}</flux:select.option>
            </flux:select>

            <flux:error name="color" />
        </flux:field>

        @if ($colorMode === 'custom')
            <flux:field>
                <flux:label class="inline-flex items-center gap-1.5">
                    <flux:icon.swatch class="size-4 text-fuchsia-500 dark:text-fuchsia-300" />
                    {{ __('platforms.create.fields.color_custom') }}
                </flux:label>

                <flux:input
                    wire:model.live="customColor"
                    name="customColor"
                    id="create-platform-custom-color"
                    placeholder="#FF5733"
                />

                <flux:error name="customColor" />
            </flux:field>
        @else
            <flux:field>
                <flux:label class="inline-flex items-center gap-1.5">
                    <flux:icon.arrows-up-down class="size-4 text-violet-500 dark:text-violet-300" />
                    {{ __('platforms.create.fields.sort_order') }}
                </flux:label>

                <flux:input
                    wire:model.live="sort_order"
                    name="sort_order"
                    id="create-platform-sort-order"
                    type="number"
                    min="0"
                    required
                />

                <flux:error name="sort_order" />
            </flux:field>
        @endif
    </div>

    @if ($colorMode === 'custom')
        <div class="grid items-start gap-4 md:grid-cols-2">
            <flux:field>
                <flux:label class="inline-flex items-center gap-1.5">
                    <flux:icon.arrows-up-down class="size-4 text-violet-500 dark:text-violet-300" />
                    {{ __('platforms.create.fields.sort_order') }}
                </flux:label>

                <flux:input
                    wire:model.live="sort_order"
                    name="sort_order"
                    id="create-platform-sort-order"
                    type="number"
                    min="0"
                    required
                />

                <flux:error name="sort_order" />
            </flux:field>

            <div></div>
        </div>
    @endif

    <div class="grid items-start gap-4 md:grid-cols-2">
        <flux:field>
            <flux:label class="inline-flex items-center gap-1.5">
                <flux:icon.currency-dollar class="size-4 text-amber-500 dark:text-amber-300" />
                {{ __('platforms.create.fields.commission') }}
            </flux:label>

            <flux:input
                wire:model.blur="commission"
                name="commission"
                id="create-platform-commission"
                type="number"
                min="0"
                step="0.01"
                required
            />

            <flux:error name="commission" />
        </flux:field>

        <flux:field>
            <flux:label class="inline-flex items-center gap-1.5">
                <flux:icon.receipt-percent class="size-4 text-amber-500 dark:text-amber-300" />
                {{ __('platforms.create.fields.commission_tax') }}
            </flux:label>

            <flux:input
                wire:model.blur="commission_tax"
                name="commission_tax"
                id="create-platform-commission-tax"
                type="number"
                min="0"
                step="0.01"
                required
            />

            <flux:error name="commission_tax" />
        </flux:field>
    </div>

    <div class="rounded-2xl border border-white/8 bg-white/3 px-4 py-3.5 shadow-sm ring-1 ring-inset ring-white/4">
        <div class="flex items-start justify-between gap-4">
            <div class="space-y-1">
                <flux:heading size="sm" class="inline-flex items-center gap-1.5">
                    <flux:icon.power class="size-4 text-emerald-600 dark:text-emerald-300" />
                    {{ __('platforms.create.fields.active') }}
                </flux:heading>
                <flux:text size="sm" class="max-w-xl text-zinc-500 dark:text-white/60">
                    {{ __('platforms.create.active_help') }}
                </flux:text>
            </div>

            <flux:switch
                wire:model.live="is_active"
                name="is_active"
                id="create-platform-active"
                :aria-label="__('platforms.create.fields.active')"
            />
        </div>

        <div class="mt-3 flex items-center gap-2">
            <span class="inline-flex size-2.5 rounded-full {{ $is_active ? 'bg-emerald-400 shadow-[0_0_0_4px_rgb(52_211_153_/_0.12)]' : 'bg-zinc-500/80 shadow-[0_0_0_4px_rgb(113_113_122_/_0.12)]' }}"></span>
            <flux:text size="sm" class="font-medium text-zinc-200 dark:text-zinc-100">
                {{ $is_active ? __('platforms.create.active_enabled') : __('platforms.create.active_disabled') }}
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
            {{ __('platforms.create.submit') }}
        </flux:button>
    </div>
</form>
