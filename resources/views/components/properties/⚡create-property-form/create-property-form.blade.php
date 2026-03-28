<form wire:submit="save" autocomplete="off" class="space-y-5">
    <flux:field>
        <flux:label class="inline-flex items-center gap-1.5">
            <flux:icon.tag class="size-4 text-sky-500 dark:text-sky-300" />
            {{ __('properties.create.fields.name') }}
        </flux:label>

        <flux:input
            wire:model.live.blur="name"
            name="name"
            id="create-property-name"
            required
        />

        <flux:description>{{ __('properties.create.fields.name_help') }}</flux:description>
        <flux:error name="name" />
    </flux:field>

    <div class="grid items-start gap-4 md:grid-cols-2">
        <flux:field>
            <flux:label class="inline-flex items-center gap-1.5">
                <flux:icon.globe-alt class="size-4 text-violet-500 dark:text-violet-300" />
                {{ __('properties.create.fields.country') }}
            </flux:label>

            <flux:select
                wire:model.live="country_id"
                variant="listbox"
                searchable
                :filter="false"
                name="country_id"
                id="create-property-country"
                required
            >
                <x-slot name="search">
                    <flux:select.search wire:model.live.debounce.200ms="countrySearch" :placeholder="__('actions.search')" />
                </x-slot>

                @foreach ($this->countries as $country)
                    <flux:select.option :value="$country->id" wire:key="create-property-country-{{ $country->id }}">
                        {{ $country->localizedName() }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:error name="country_id" />
        </flux:field>

        <flux:field>
            <flux:label class="inline-flex items-center gap-1.5">
                <flux:icon.map-pin class="size-4 text-amber-500 dark:text-amber-300" />
                {{ __('properties.create.fields.city') }}
            </flux:label>

            <flux:input
                wire:model.live.blur="city"
                name="city"
                id="create-property-city"
                required
            />

            <flux:error name="city" />
        </flux:field>
    </div>

    <flux:field>
        <flux:label class="inline-flex items-center gap-1.5">
            <flux:icon.map class="size-4 text-rose-500 dark:text-rose-300" />
            {{ __('properties.create.fields.address') }}
        </flux:label>

        <flux:textarea
            wire:model.live.blur="address"
            name="address"
            id="create-property-address"
            rows="3"
            required
        />

        <flux:error name="address" />
    </flux:field>

    <div class="rounded-2xl border border-white/8 bg-white/3 px-4 py-3.5 shadow-sm ring-1 ring-inset ring-white/4">
        <div class="flex items-start justify-between gap-4">
            <div class="space-y-1">
                <flux:heading size="sm" class="inline-flex items-center gap-1.5">
                    <flux:icon.power class="size-4 text-emerald-600 dark:text-emerald-300" />
                    {{ __('properties.create.fields.active') }}
                </flux:heading>
                <flux:text size="sm" class="max-w-xl text-zinc-500 dark:text-white/60">
                    {{ __('properties.create.active_help') }}
                </flux:text>
            </div>

            <flux:switch
                wire:model.live="is_active"
                name="is_active"
                id="create-property-active"
                :aria-label="__('properties.create.fields.active')"
            />
        </div>

        <div class="mt-3 flex items-center gap-2">
            <span class="inline-flex size-2.5 rounded-full {{ $is_active ? 'bg-emerald-400 shadow-[0_0_0_4px_rgb(52_211_153_/_0.12)]' : 'bg-zinc-500/80 shadow-[0_0_0_4px_rgb(113_113_122_/_0.12)]' }}"></span>
            <flux:text size="sm" class="font-medium text-zinc-200 dark:text-zinc-100">
                {{ $is_active ? __('properties.create.active_enabled') : __('properties.create.active_disabled') }}
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
            {{ __('properties.create.submit') }}
        </flux:button>
    </div>
</form>
