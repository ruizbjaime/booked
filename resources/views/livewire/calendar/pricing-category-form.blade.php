<form wire:submit="save" autocomplete="off" class="space-y-5">
    <flux:field>
        <flux:label class="inline-flex items-center gap-1.5">
            <flux:icon.tag class="size-4 text-sky-500 dark:text-sky-300" />
            {{ __('calendar.settings.fields.name') }}
        </flux:label>

        <flux:input wire:model.live.blur="name" name="name" id="pricing-category-name" required />

        <flux:description>{{ __('calendar.settings.pricing_category_form.fields.name_help') }}</flux:description>
        <flux:error name="name" />
    </flux:field>

    <div class="grid items-start gap-4 md:grid-cols-2">
        <flux:field>
            <flux:label class="inline-flex items-center gap-1.5">
                <flux:icon.globe-alt class="size-4 text-emerald-600 dark:text-emerald-300" />
                {{ __('calendar.settings.fields.en_name') }}
            </flux:label>

            <flux:input wire:model.live.blur="en_name" name="en_name" id="pricing-category-en-name" required />
            <flux:error name="en_name" />
        </flux:field>

        <flux:field>
            <flux:label class="inline-flex items-center gap-1.5">
                <flux:icon.globe-alt class="size-4 text-emerald-600 dark:text-emerald-300" />
                {{ __('calendar.settings.fields.es_name') }}
            </flux:label>

            <flux:input wire:model.live.blur="es_name" name="es_name" id="pricing-category-es-name" required />
            <flux:error name="es_name" />
        </flux:field>
    </div>

    <div class="grid items-start gap-4 md:grid-cols-2">
        <flux:field>
            <flux:label class="inline-flex items-center gap-1.5">
                <flux:icon.hashtag class="size-4 text-violet-500 dark:text-violet-300" />
                {{ __('calendar.settings.fields.level') }}
            </flux:label>

            <flux:input wire:model.live.blur="level" name="level" id="pricing-category-level" type="number" min="1" max="10" required />
            <flux:error name="level" />
        </flux:field>

        <flux:field>
            <flux:label class="inline-flex items-center gap-1.5">
                <flux:icon.swatch class="size-4 text-amber-500 dark:text-amber-300" />
                {{ __('calendar.settings.fields.color') }}
            </flux:label>

            <div class="flex items-center gap-3 rounded-2xl border border-white/8 bg-white/3 px-3 py-2.5 shadow-sm ring-1 ring-inset ring-white/4">
                <input wire:model.live.blur="color" id="pricing-category-color-picker" type="color" class="size-10 rounded-lg border border-white/10 bg-transparent p-1" />
                <flux:input wire:model.live.blur="color" name="color" id="pricing-category-color" required />
            </div>
            <flux:error name="color" />
        </flux:field>
    </div>

    <div class="grid items-start gap-4 md:grid-cols-2">
        <flux:field>
            <flux:label class="inline-flex items-center gap-1.5">
                <flux:icon.currency-dollar class="size-4 text-sky-500 dark:text-sky-300" />
                {{ __('calendar.settings.fields.multiplier') }}
            </flux:label>

            <flux:input wire:model.live.blur="multiplier" name="multiplier" id="pricing-category-multiplier" type="number" min="0.01" max="99.99" step="0.01" required />
            <flux:error name="multiplier" />
        </flux:field>

        <flux:field>
            <flux:label class="inline-flex items-center gap-1.5">
                <flux:icon.bars-3-bottom-left class="size-4 text-sky-500 dark:text-sky-300" />
                {{ __('calendar.settings.fields.sort_order') }}
            </flux:label>

            <flux:input wire:model.live.blur="sort_order" name="sort_order" id="pricing-category-sort-order" type="number" min="0" max="9999" required />
            <flux:error name="sort_order" />
        </flux:field>
    </div>

    <div class="rounded-2xl border border-white/8 bg-white/3 px-4 py-3.5 shadow-sm ring-1 ring-inset ring-white/4">
        <div class="flex items-start justify-between gap-4">
            <div class="space-y-1">
                <flux:heading size="sm" class="inline-flex items-center gap-1.5">
                    <flux:icon.power class="size-4 text-emerald-600 dark:text-emerald-300" />
                    {{ __('calendar.settings.fields.is_active') }}
                </flux:heading>
                <flux:text size="sm" class="max-w-xl text-zinc-500 dark:text-white/60">
                    {{ __('calendar.settings.pricing_category_form.active_help') }}
                </flux:text>
            </div>

            <flux:switch wire:model.live="is_active" name="is_active" id="pricing-category-is-active" :aria-label="__('calendar.settings.fields.is_active')" />
        </div>

        <div class="mt-3 flex items-center gap-2">
            <span class="inline-flex size-2.5 rounded-full {{ $is_active ? 'bg-emerald-400 shadow-[0_0_0_4px_rgb(52_211_153_/_0.12)]' : 'bg-zinc-500/80 shadow-[0_0_0_4px_rgb(113_113_122_/_0.12)]' }}"></span>
            <flux:text size="sm" class="font-medium text-zinc-200 dark:text-zinc-100">
                {{ $is_active ? __('calendar.settings.pricing_category_form.active_enabled') : __('calendar.settings.pricing_category_form.active_disabled') }}
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
            {{ __('calendar.settings.pricing_category_form.submit') }}
        </flux:button>
    </div>
</form>
