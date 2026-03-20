<x-show.layout
    :sr-title="__('countries.show.placeholder_title')"
    :heading="__('countries.show.title')"
    :subheading="__('countries.show.description')"
>
    <x-show.back-button :href="route('countries.index')" />

    <div class="space-y-3 sm:space-y-4">
        <x-show.panel>
            <div class="flex items-center gap-3 sm:gap-5">
                <div class="flex size-10 items-center justify-center rounded-lg bg-sky-500/15 sm:size-14">
                    <flux:icon.globe-alt class="size-5 text-sky-300 sm:size-7" />
                </div>

                <div class="min-w-0 space-y-1">
                    <flux:heading size="lg" class="min-w-0 leading-tight">{{ $this->country->localizedName() }}</flux:heading>
                    <div class="flex items-center gap-2">
                        <flux:badge size="sm" color="zinc">{{ $this->country->iso_alpha2 }}</flux:badge>
                        <flux:badge size="sm" color="zinc">{{ $this->country->iso_alpha3 }}</flux:badge>
                        <flux:text class="text-sm text-zinc-500">{{ $this->country->phone_code }}</flux:text>
                    </div>
                </div>
            </div>
        </x-show.panel>

        <x-show.panel>
            <x-show.section
                :title="__('countries.show.sections.details')"
                :description="__('countries.show.sections.details_description')"
            >
                <x-slot:icon class="bg-sky-500/15 text-sky-300">
                    <flux:icon.identification class="size-5" />
                </x-slot:icon>

                @if ($this->canEdit())
                    <x-slot:actions>
                        <x-show.section-toggle section="details" :editing-section="$editingSection" />
                    </x-slot:actions>
                @endif

                @if ($editingSection === 'details')
                    <div class="space-y-4">
                        <div class="grid items-start gap-4 sm:grid-cols-2">
                            <flux:input
                                wire:model.live.blur="en_name"
                                name="en_name"
                                id="country-show-en-name"
                                :label="__('countries.show.fields.en_name')"
                            />

                            <flux:input
                                wire:model.live.blur="es_name"
                                name="es_name"
                                id="country-show-es-name"
                                :label="__('countries.show.fields.es_name')"
                            />
                        </div>

                        <div class="grid items-start gap-4 sm:grid-cols-2">
                            <flux:input
                                wire:model.live.blur="iso_alpha2"
                                name="iso_alpha2"
                                id="country-show-iso-alpha2"
                                :label="__('countries.show.fields.iso_alpha2')"
                                maxlength="2"
                            />

                            <flux:input
                                wire:model.live.blur="iso_alpha3"
                                name="iso_alpha3"
                                id="country-show-iso-alpha3"
                                :label="__('countries.show.fields.iso_alpha3')"
                                maxlength="3"
                            />
                        </div>

                        <div class="grid items-start gap-4 sm:grid-cols-2">
                            <flux:input
                                wire:model.live.blur="phone_code"
                                name="phone_code"
                                id="country-show-phone-code"
                                :label="__('countries.show.fields.phone_code')"
                            />

                            <flux:input
                                wire:model.live.blur="sort_order"
                                name="sort_order"
                                id="country-show-sort-order"
                                :label="__('countries.show.fields.sort_order')"
                                type="number"
                                min="0"
                            />
                        </div>

                        <x-show.switch-card
                            :title="__('countries.show.fields.active')"
                            :status-text="$is_active ? __('countries.show.status.active') : __('countries.show.status.inactive')"
                            :active="$is_active"
                            status-color="emerald"
                        >
                            <x-slot:control>
                                <flux:switch
                                    wire:model.live="is_active"
                                    name="is_active"
                                    id="country-show-active"
                                    :aria-label="__('countries.show.fields.active')"
                                    class="self-start sm:self-auto"
                                />
                            </x-slot:control>
                        </x-show.switch-card>

                        <x-show.autosave-notice :message="__('countries.show.autosave.details')" />
                    </div>
                @else
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-show.detail-item :label="__('countries.show.fields.en_name')">
                            <x-slot:icon>
                                <flux:icon.globe-alt class="size-4 text-emerald-600 dark:text-emerald-300" />
                            </x-slot:icon>

                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $this->country->en_name }}</flux:text>
                        </x-show.detail-item>

                        <x-show.detail-item :label="__('countries.show.fields.es_name')">
                            <x-slot:icon>
                                <flux:icon.globe-alt class="size-4 text-emerald-600 dark:text-emerald-300" />
                            </x-slot:icon>

                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $this->country->es_name }}</flux:text>
                        </x-show.detail-item>

                        <x-show.detail-item :label="__('countries.show.fields.iso_alpha2')">
                            <x-slot:icon>
                                <flux:icon.hashtag class="size-4 text-sky-500 dark:text-sky-300" />
                            </x-slot:icon>

                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $this->country->iso_alpha2 }}</flux:text>
                        </x-show.detail-item>

                        <x-show.detail-item :label="__('countries.show.fields.iso_alpha3')">
                            <x-slot:icon>
                                <flux:icon.hashtag class="size-4 text-sky-500 dark:text-sky-300" />
                            </x-slot:icon>

                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $this->country->iso_alpha3 }}</flux:text>
                        </x-show.detail-item>

                        <x-show.detail-item :label="__('countries.show.fields.phone_code')">
                            <x-slot:icon>
                                <flux:icon.phone class="size-4 text-amber-500 dark:text-amber-300" />
                            </x-slot:icon>

                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $this->country->phone_code }}</flux:text>
                        </x-show.detail-item>

                        <x-show.detail-item :label="__('countries.show.fields.sort_order')">
                            <x-slot:icon>
                                <flux:icon.arrows-up-down class="size-4 text-violet-500 dark:text-violet-300" />
                            </x-slot:icon>

                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $this->country->sort_order }}</flux:text>
                        </x-show.detail-item>

                        <x-show.detail-item :label="__('countries.show.fields.active')">
                            <x-slot:icon>
                                <flux:icon.power class="size-4 text-emerald-600 dark:text-emerald-300" />
                            </x-slot:icon>

                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">
                                {{ $this->country->is_active ? __('countries.show.status.active') : __('countries.show.status.inactive') }}
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
                    :title="__('countries.show.quick_actions.title')"
                    class="space-y-2"
                >
                    <flux:button
                        variant="primary"
                        icon="arrow-left"
                        class="w-full"
                        :href="route('countries.index')"
                        wire:navigate
                    >
                        {{ __('actions.back') }}
                    </flux:button>

                    @if ($this->canDelete())
                        <flux:button
                            variant="danger"
                            icon="trash"
                            class="w-full"
                            wire:click="confirmCountryDeletion"
                        >
                            {{ __('countries.show.quick_actions.delete.action') }}
                        </flux:button>
                    @endif
                </x-show.sidebar-group>

                <flux:separator variant="subtle" />

                <x-show.sidebar-group :title="__('countries.show.stats.title')">
                    <div class="grid gap-2.5 sm:gap-3">
                        <x-show.stat-item :label="__('countries.show.stats.country_id')">
                            <x-slot:icon class="bg-zinc-100 text-zinc-700 dark:bg-zinc-400/14 dark:text-zinc-200">
                                <flux:icon.hashtag class="size-5" />
                            </x-slot:icon>

                            <flux:text class="font-medium text-zinc-900 dark:text-white">{{ $this->country->id }}</flux:text>
                        </x-show.stat-item>

                        <x-show.stat-item :label="__('countries.show.stats.associated_users')">
                            <x-slot:icon class="bg-sky-100 text-sky-700 dark:bg-sky-400/14 dark:text-sky-200">
                                <flux:icon.users class="size-5" />
                            </x-slot:icon>

                            <flux:text class="font-medium text-zinc-900 dark:text-white">{{ $this->associatedUsersCount }}</flux:text>
                        </x-show.stat-item>

                        <x-show.stat-item :label="__('countries.show.stats.updated')">
                            <x-slot:icon class="bg-amber-100 text-amber-700 dark:bg-amber-400/14 dark:text-amber-200">
                                <flux:icon.sparkles class="size-5" />
                            </x-slot:icon>

                            <flux:text class="font-medium text-zinc-900 dark:text-white" :title="$this->formatDateTooltip($this->country->updated_at)">{{ $this->formatDate($this->country->updated_at) }}</flux:text>
                        </x-show.stat-item>
                    </div>
                </x-show.sidebar-group>
            </div>
        </x-show.panel>
    </x-slot:aside>
</x-show.layout>
