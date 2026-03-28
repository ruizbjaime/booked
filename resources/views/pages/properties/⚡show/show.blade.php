<x-show.layout
    :sr-title="__('properties.show.placeholder_title')"
    :heading="__('properties.show.title')"
    :subheading="__('properties.show.description')"
>
    <x-show.back-button :href="route('properties.index')" />

    <div class="space-y-3 sm:space-y-4">
        <x-show.panel>
            <div class="flex items-center gap-3 sm:gap-5">
                <div class="flex size-10 items-center justify-center rounded-lg bg-cyan-500/15 sm:size-14">
                    <flux:icon.home class="size-5 text-cyan-300 sm:size-7" />
                </div>

                <div class="min-w-0 space-y-1">
                    <flux:heading size="lg" class="min-w-0 leading-tight">{{ $this->property->name }}</flux:heading>
                    <div class="flex items-center gap-2">
                        <flux:badge size="sm" color="zinc">{{ $this->property->slug }}</flux:badge>
                    </div>
                </div>
            </div>
        </x-show.panel>

        <x-show.panel>
            <x-show.section
                :title="__('properties.show.sections.details')"
                :description="__('properties.show.sections.details_description')"
            >
                <x-slot:icon class="bg-cyan-500/15 text-cyan-300">
                    <flux:icon.identification class="size-5" />
                </x-slot:icon>

                @if ($this->canEdit())
                    <x-slot:actions>
                        <x-show.section-toggle section="details" :editing-section="$editingSection" />
                    </x-slot:actions>
                @endif

                @if ($editingSection === 'details')
                    <div class="space-y-4">
                        <flux:field>
                            <flux:label>{{ __('properties.show.fields.name') }}</flux:label>
                            <flux:input
                                wire:model.live.blur="name"
                                name="name"
                                id="property-show-name"
                                required
                            />
                            <flux:error name="name" />
                        </flux:field>

                        <div class="grid items-start gap-4 sm:grid-cols-2">
                            <flux:select
                                wire:model.live="country_id"
                                variant="listbox"
                                searchable
                                :filter="false"
                                name="country_id"
                                id="property-show-country"
                                :label="__('properties.show.fields.country')"
                                required
                            >
                                <x-slot name="search">
                                    <flux:select.search wire:model.live.debounce.200ms="countrySearch" :placeholder="__('actions.search')" />
                                </x-slot>

                                @foreach ($this->countries as $country)
                                    <flux:select.option :value="$country->id" wire:key="property-show-country-{{ $country->id }}">
                                        {{ $country->localizedName() }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>

                            <flux:input
                                wire:model.live.blur="city"
                                name="city"
                                id="property-show-city"
                                :label="__('properties.show.fields.city')"
                                required
                            />
                        </div>

                        <flux:field>
                            <flux:label>{{ __('properties.show.fields.address') }}</flux:label>
                            <flux:textarea
                                wire:model.live.blur="address"
                                name="address"
                                id="property-show-address"
                                rows="3"
                                required
                            />
                            <flux:error name="address" />
                        </flux:field>

                        <x-show.switch-card
                            :title="__('properties.show.fields.active')"
                            :status-text="$is_active ? __('properties.show.status.active') : __('properties.show.status.inactive')"
                            :active="$is_active"
                            status-color="emerald"
                        >
                            <x-slot:control>
                                <flux:switch
                                    wire:model.live="is_active"
                                    name="is_active"
                                    id="property-show-active"
                                    :aria-label="__('properties.show.fields.active')"
                                    class="self-start sm:self-auto"
                                />
                            </x-slot:control>
                        </x-show.switch-card>

                        <x-show.autosave-notice :message="__('properties.show.autosave.details')" />
                    </div>
                @else
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-show.detail-item :label="__('properties.show.fields.slug')">
                            <x-slot:icon>
                                <flux:icon.tag class="size-4 text-cyan-500 dark:text-cyan-300" />
                            </x-slot:icon>

                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $this->property->slug }}</flux:text>
                        </x-show.detail-item>

                        <x-show.detail-item :label="__('properties.show.fields.name')">
                            <x-slot:icon>
                                <flux:icon.globe-alt class="size-4 text-emerald-600 dark:text-emerald-300" />
                            </x-slot:icon>

                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $this->property->name }}</flux:text>
                        </x-show.detail-item>

                        <x-show.detail-item :label="__('properties.show.fields.country')">
                            <x-slot:icon>
                                <flux:icon.globe-alt class="size-4 text-violet-500 dark:text-violet-300" />
                            </x-slot:icon>

                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $this->property->country?->localizedName() }}</flux:text>
                        </x-show.detail-item>

                        <x-show.detail-item :label="__('properties.show.fields.city')">
                            <x-slot:icon>
                                <flux:icon.map-pin class="size-4 text-amber-500 dark:text-amber-300" />
                            </x-slot:icon>

                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $this->property->city }}</flux:text>
                        </x-show.detail-item>

                        <x-show.detail-item :label="__('properties.show.fields.address')" class="sm:col-span-2">
                            <x-slot:icon>
                                <flux:icon.map class="size-4 text-rose-500 dark:text-rose-300" />
                            </x-slot:icon>

                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white whitespace-pre-line">{{ $this->property->address }}</flux:text>
                        </x-show.detail-item>

                        <x-show.detail-item :label="__('properties.show.fields.active')">
                            <x-slot:icon>
                                <flux:icon.power class="size-4 text-emerald-600 dark:text-emerald-300" />
                            </x-slot:icon>

                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">
                                {{ $this->property->is_active ? __('properties.show.status.active') : __('properties.show.status.inactive') }}
                            </flux:text>
                        </x-show.detail-item>
                    </div>
                @endif
            </x-show.section>
        </x-show.panel>
    </div>

    <x-slot:aside>
        <x-show.panel class="relative overflow-hidden border">
            <div class="relative space-y-4 p-3 sm:space-y-6 sm:p-5">
                <x-show.sidebar-group
                    :title="__('properties.show.quick_actions.title')"
                    class="space-y-2"
                >
                    <flux:button
                        variant="primary"
                        icon="arrow-left"
                        class="w-full"
                        :href="route('properties.index')"
                        wire:navigate
                    >
                        {{ __('actions.back') }}
                    </flux:button>

                    @if ($this->canDelete())
                        <flux:button
                            variant="danger"
                            icon="trash"
                            class="w-full"
                            wire:click="confirmPropertyDeletion"
                        >
                            {{ __('properties.show.quick_actions.delete.action') }}
                        </flux:button>
                    @endif
                </x-show.sidebar-group>

                <flux:separator variant="subtle" />

                <x-show.sidebar-group
                    :title="__('properties.show.stats.title')"
                >
                    <div class="grid gap-2.5 sm:gap-3">
                        <x-show.stat-item :label="__('properties.show.stats.property_id')">
                            <x-slot:icon class="bg-zinc-100 text-zinc-700 dark:bg-zinc-400/14 dark:text-zinc-200">
                                <flux:icon.hashtag class="size-5" />
                            </x-slot:icon>

                            <flux:text class="font-medium text-zinc-900 dark:text-white">{{ $this->property->id }}</flux:text>
                        </x-show.stat-item>

                        <x-show.stat-item :label="__('properties.show.stats.updated')">
                            <x-slot:icon class="bg-cyan-100 text-cyan-700 dark:bg-cyan-400/14 dark:text-cyan-200">
                                <flux:icon.sparkles class="size-5" />
                            </x-slot:icon>

                            <flux:text class="font-medium text-zinc-900 dark:text-white" :title="$this->formatDateTooltip($this->property->updated_at)">{{ $this->formatDate($this->property->updated_at) }}</flux:text>
                        </x-show.stat-item>
                    </div>
                </x-show.sidebar-group>
            </div>
        </x-show.panel>
    </x-slot:aside>
</x-show.layout>
