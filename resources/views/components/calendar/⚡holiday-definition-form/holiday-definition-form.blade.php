
<form wire:submit="save" autocomplete="off" class="space-y-5">
    <flux:field>
        <flux:label class="inline-flex items-center gap-1.5">
            <flux:icon.tag class="size-4 text-sky-500 dark:text-sky-300" />
            {{ __('calendar.settings.fields.name') }}
        </flux:label>

        <flux:input wire:model.live.blur="name" name="name" id="holiday-definition-name" required />

        <flux:description>{{ __('calendar.settings.holiday_definition_form.fields.name_help') }}</flux:description>
        <flux:error name="name" />
    </flux:field>

    <div class="grid items-start gap-4 md:grid-cols-2">
        <flux:field>
            <flux:label class="inline-flex items-center gap-1.5">
                <flux:icon.globe-alt class="size-4 text-emerald-600 dark:text-emerald-300" />
                {{ __('calendar.settings.fields.en_name') }}
            </flux:label>

            <flux:input wire:model.live.blur="en_name" name="en_name" id="holiday-definition-en-name" required />
            <flux:error name="en_name" />
        </flux:field>

        <flux:field>
            <flux:label class="inline-flex items-center gap-1.5">
                <flux:icon.globe-alt class="size-4 text-emerald-600 dark:text-emerald-300" />
                {{ __('calendar.settings.fields.es_name') }}
            </flux:label>

            <flux:input wire:model.live.blur="es_name" name="es_name" id="holiday-definition-es-name" required />
            <flux:error name="es_name" />
        </flux:field>
    </div>

    <flux:field>
        <flux:label class="inline-flex items-center gap-1.5">
            <flux:icon.calendar class="size-4 text-red-500 dark:text-red-300" />
            {{ __('calendar.settings.fields.group') }}
        </flux:label>

        <flux:select wire:model.live="group" name="group" id="holiday-definition-group" variant="listbox">
            <flux:select.option value="fixed">{{ __('calendar.holiday_groups.fixed') }}</flux:select.option>
            <flux:select.option value="emiliani">{{ __('calendar.holiday_groups.emiliani') }}</flux:select.option>
            <flux:select.option value="easter_based">{{ __('calendar.holiday_groups.easter_based') }}</flux:select.option>
        </flux:select>

        <flux:description>{{ __('calendar.settings.holiday_definition_form.fields.group_help') }}</flux:description>
        <flux:error name="group" />
    </flux:field>

    @if ($this->isFixedOrEmiliani())
        <div class="grid items-start gap-4 md:grid-cols-2">
            <flux:field>
                <flux:label class="inline-flex items-center gap-1.5">
                    <flux:icon.calendar-days class="size-4 text-violet-500 dark:text-violet-300" />
                    {{ __('calendar.settings.holiday_definition_form.fields.month') }}
                </flux:label>

                <flux:input wire:model.live.blur="month" name="month" id="holiday-definition-month" type="number" min="1" max="12" required />
                <flux:error name="month" />
            </flux:field>

            <flux:field>
                <flux:label class="inline-flex items-center gap-1.5">
                    <flux:icon.calendar-days class="size-4 text-violet-500 dark:text-violet-300" />
                    {{ __('calendar.settings.holiday_definition_form.fields.day') }}
                </flux:label>

                <flux:input wire:model.live.blur="day" name="day" id="holiday-definition-day" type="number" min="1" max="31" required />
                <flux:error name="day" />
            </flux:field>
        </div>
    @endif

    @if ($this->isEasterBased())
        <flux:field>
            <flux:label class="inline-flex items-center gap-1.5">
                <flux:icon.calendar-days class="size-4 text-violet-500 dark:text-violet-300" />
                {{ __('calendar.settings.holiday_definition_form.fields.easter_offset') }}
            </flux:label>

            <flux:input wire:model.live.blur="easter_offset" name="easter_offset" id="holiday-definition-easter-offset" type="number" min="-100" max="100" required />

            <flux:description>{{ __('calendar.settings.holiday_definition_form.fields.easter_offset_help') }}</flux:description>
            <flux:error name="easter_offset" />
        </flux:field>
    @endif

    @if ($this->isEasterBased())
        <div class="rounded-2xl border border-white/8 bg-white/3 px-4 py-3.5 shadow-sm ring-1 ring-inset ring-white/4">
            <div class="flex items-start justify-between gap-4">
                <div class="space-y-1">
                    <flux:heading size="sm" class="inline-flex items-center gap-1.5">
                        <flux:icon.arrow-right class="size-4 text-amber-500 dark:text-amber-300" />
                        {{ __('calendar.settings.holiday_definition_form.fields.moves_to_monday') }}
                    </flux:heading>
                    <flux:text size="sm" class="max-w-xl text-zinc-500 dark:text-white/60">
                        {{ __('calendar.settings.holiday_definition_form.fields.moves_to_monday_help') }}
                    </flux:text>
                </div>

                <flux:switch wire:model.live="moves_to_monday" name="moves_to_monday" id="holiday-definition-moves-to-monday" :aria-label="__('calendar.settings.holiday_definition_form.fields.moves_to_monday')" />
            </div>
        </div>
    @endif

    <flux:field>
        <flux:label class="inline-flex items-center gap-1.5">
            <flux:icon.bars-3-bottom-left class="size-4 text-sky-500 dark:text-sky-300" />
            {{ __('calendar.settings.fields.sort_order') }}
        </flux:label>

        <flux:input wire:model.live.blur="sort_order" name="sort_order" id="holiday-definition-sort-order" type="number" min="0" required />
        <flux:error name="sort_order" />
    </flux:field>

    <flux:field>
        <flux:label class="inline-flex items-center gap-1.5">
            <flux:icon.scale class="size-4 text-amber-500 dark:text-amber-300" />
            {{ __('calendar.settings.holiday_definition_form.fields.base_impact_weights') }}
        </flux:label>

        <flux:textarea wire:model.live.blur="base_impact_weights_json" name="base_impact_weights" id="holiday-definition-impact-weights" rows="6" class="font-mono text-sm" required />

        <flux:description>{{ __('calendar.settings.holiday_definition_form.fields.base_impact_weights_help') }}</flux:description>
        <flux:error name="base_impact_weights" />
    </flux:field>

    <flux:field>
        <flux:label class="inline-flex items-center gap-1.5">
            <flux:icon.map-pin class="size-4 text-amber-500 dark:text-amber-300" />
            {{ __('calendar.settings.holiday_definition_form.fields.special_overrides') }}
        </flux:label>

        <flux:textarea wire:model.live.blur="special_overrides_json" name="special_overrides" id="holiday-definition-special-overrides" rows="4" class="font-mono text-sm" />

        <flux:description>{{ __('calendar.settings.holiday_definition_form.fields.special_overrides_help') }}</flux:description>
        <flux:error name="special_overrides" />
    </flux:field>

    <div class="rounded-2xl border border-white/8 bg-white/3 px-4 py-3.5 shadow-sm ring-1 ring-inset ring-white/4">
        <div class="flex items-start justify-between gap-4">
            <div class="space-y-1">
                <flux:heading size="sm" class="inline-flex items-center gap-1.5">
                    <flux:icon.power class="size-4 text-emerald-600 dark:text-emerald-300" />
                    {{ __('calendar.settings.fields.is_active') }}
                </flux:heading>
                <flux:text size="sm" class="max-w-xl text-zinc-500 dark:text-white/60">
                    {{ __('calendar.settings.holiday_definition_form.active_help') }}
                </flux:text>
            </div>

            <flux:switch wire:model.live="is_active" name="is_active" id="holiday-definition-is-active" :aria-label="__('calendar.settings.fields.is_active')" />
        </div>

        <div class="mt-3 flex items-center gap-2">
            <span class="inline-flex size-2.5 rounded-full {{ $is_active ? 'bg-emerald-400 shadow-[0_0_0_4px_rgb(52_211_153_/_0.12)]' : 'bg-zinc-500/80 shadow-[0_0_0_4px_rgb(113_113_122_/_0.12)]' }}"></span>
            <flux:text size="sm" class="font-medium text-zinc-200 dark:text-zinc-100">
                {{ $is_active ? __('calendar.settings.holiday_definition_form.active_enabled') : __('calendar.settings.holiday_definition_form.active_disabled') }}
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
            {{ __('calendar.settings.holiday_definition_form.submit') }}
        </flux:button>
    </div>
</form>
