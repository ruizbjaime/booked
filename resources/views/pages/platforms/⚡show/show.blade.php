<x-show.layout
    :sr-title="__('platforms.show.placeholder_title')"
    :heading="__('platforms.show.title')"
    :subheading="__('platforms.show.description')"
>
    <x-show.back-button :href="route('platforms.index')" />

    <div class="space-y-3 sm:space-y-4">
        <x-show.panel>
            <div class="flex items-center gap-3 sm:gap-5">
                <div class="flex size-10 items-center justify-center rounded-lg bg-indigo-500/15 sm:size-14">
                    <flux:icon.building-storefront class="size-5 text-indigo-300 sm:size-7" />
                </div>

                <div class="min-w-0 space-y-1">
                    <flux:heading size="lg" class="min-w-0 leading-tight">{{ $this->platform->localizedName() }}</flux:heading>
                    <div class="flex items-center gap-2">
                        <x-badge size="sm" :color="$this->platform->color">{{ $this->platform->slug }}</x-badge>
                    </div>
                </div>
            </div>
        </x-show.panel>

        <x-show.panel>
            <x-show.section
                :title="__('platforms.show.sections.details')"
                :description="__('platforms.show.sections.details_description')"
            >
                <x-slot:icon class="bg-indigo-500/15 text-indigo-300">
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
                            <flux:label>{{ __('platforms.show.fields.name') }}</flux:label>
                            <flux:input
                                readonly
                                :value="$this->platform->slug"
                                id="platform-show-slug"
                            />
                            <flux:description>{{ __('platforms.show.fields.name_help') }}</flux:description>
                        </flux:field>

                        <div class="grid items-start gap-4 sm:grid-cols-2">
                            <flux:input
                                wire:model.live.blur="en_name"
                                name="en_name"
                                id="platform-show-en-name"
                                :label="__('platforms.show.fields.en_name')"
                            />

                            <flux:input
                                wire:model.live.blur="es_name"
                                name="es_name"
                                id="platform-show-es-name"
                                :label="__('platforms.show.fields.es_name')"
                            />
                        </div>

                        <div class="grid items-start gap-4 sm:grid-cols-2">
                            <flux:field>
                                <flux:label>{{ __('platforms.show.fields.color') }}</flux:label>
                                <flux:select wire:model.live="colorMode" id="platform-show-color-mode">
                                    @foreach (\App\Actions\Platforms\CreatePlatform::AVAILABLE_COLORS as $presetColor)
                                        <flux:select.option :value="$presetColor">{{ ucfirst($presetColor) }}</flux:select.option>
                                    @endforeach
                                    <flux:select.option value="custom">{{ __('platforms.show.fields.color_custom_option') }}</flux:select.option>
                                </flux:select>
                                <flux:error name="color" />
                            </flux:field>

                            @if ($colorMode === 'custom')
                                <flux:input
                                    wire:model.live.blur="customColor"
                                    name="customColor"
                                    id="platform-show-custom-color"
                                    :label="__('platforms.show.fields.color_custom')"
                                    placeholder="#FF5733"
                                />
                            @else
                                <flux:input
                                    wire:model.live.blur="sort_order"
                                    name="sort_order"
                                    id="platform-show-sort-order"
                                    :label="__('platforms.show.fields.sort_order')"
                                    type="number"
                                    min="0"
                                />
                            @endif
                        </div>

                        @if ($colorMode === 'custom')
                            <div class="grid items-start gap-4 sm:grid-cols-2">
                                <flux:input
                                    wire:model.live.blur="sort_order"
                                    name="sort_order"
                                    id="platform-show-sort-order"
                                    :label="__('platforms.show.fields.sort_order')"
                                    type="number"
                                    min="0"
                                />

                                <div></div>
                            </div>
                        @endif

                        <div class="grid items-start gap-4 sm:grid-cols-2">
                            <flux:input
                                wire:model.live.blur="commission"
                                name="commission"
                                id="platform-show-commission"
                                :label="__('platforms.show.fields.commission')"
                                type="number"
                                min="0"
                                step="0.01"
                            />

                            <flux:input
                                wire:model.live.blur="commission_tax"
                                name="commission_tax"
                                id="platform-show-commission-tax"
                                :label="__('platforms.show.fields.commission_tax')"
                                type="number"
                                min="0"
                                step="0.01"
                            />
                        </div>

                        <x-show.switch-card
                            :title="__('platforms.show.fields.active')"
                            :status-text="$is_active ? __('platforms.show.status.active') : __('platforms.show.status.inactive')"
                            :active="$is_active"
                            status-color="emerald"
                        >
                            <x-slot:control>
                                <flux:switch
                                    wire:model.live="is_active"
                                    name="is_active"
                                    id="platform-show-active"
                                    :aria-label="__('platforms.show.fields.active')"
                                    class="self-start sm:self-auto"
                                />
                            </x-slot:control>
                        </x-show.switch-card>

                        <x-show.autosave-notice :message="__('platforms.show.autosave.details')" />
                    </div>
                @else
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-show.detail-item :label="__('platforms.show.fields.name')" class="sm:col-span-2">
                            <x-slot:icon>
                                <flux:icon.tag class="size-4 text-sky-500 dark:text-sky-300" />
                            </x-slot:icon>

                            <x-badge size="sm" :color="$this->platform->color">{{ $this->platform->slug }}</x-badge>
                        </x-show.detail-item>

                        <x-show.detail-item :label="__('platforms.show.fields.en_name')">
                            <x-slot:icon>
                                <flux:icon.globe-alt class="size-4 text-emerald-600 dark:text-emerald-300" />
                            </x-slot:icon>

                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $this->platform->en_name }}</flux:text>
                        </x-show.detail-item>

                        <x-show.detail-item :label="__('platforms.show.fields.es_name')">
                            <x-slot:icon>
                                <flux:icon.globe-alt class="size-4 text-emerald-600 dark:text-emerald-300" />
                            </x-slot:icon>

                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $this->platform->es_name }}</flux:text>
                        </x-show.detail-item>

                        <x-show.detail-item :label="__('platforms.show.fields.color')">
                            <x-slot:icon>
                                <flux:icon.swatch class="size-4 text-fuchsia-500 dark:text-fuchsia-300" />
                            </x-slot:icon>

                            <x-badge size="sm" :color="$this->platform->color">{{ \App\Domain\Table\Columns\BadgeColumn::isHexColor($this->platform->color) ? $this->platform->color : ucfirst($this->platform->color) }}</x-badge>
                        </x-show.detail-item>

                        <x-show.detail-item :label="__('platforms.show.fields.sort_order')">
                            <x-slot:icon>
                                <flux:icon.arrows-up-down class="size-4 text-violet-500 dark:text-violet-300" />
                            </x-slot:icon>

                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $this->platform->sort_order }}</flux:text>
                        </x-show.detail-item>

                        <x-show.detail-item :label="__('platforms.show.fields.commission')">
                            <x-slot:icon>
                                <flux:icon.currency-dollar class="size-4 text-amber-500 dark:text-amber-300" />
                            </x-slot:icon>

                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">{{ number_format($this->platform->commission * 100, 2) }}%</flux:text>
                        </x-show.detail-item>

                        <x-show.detail-item :label="__('platforms.show.fields.commission_tax')">
                            <x-slot:icon>
                                <flux:icon.receipt-percent class="size-4 text-amber-500 dark:text-amber-300" />
                            </x-slot:icon>

                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">{{ number_format($this->platform->commission_tax * 100, 2) }}%</flux:text>
                        </x-show.detail-item>

                        <x-show.detail-item :label="__('platforms.show.fields.active')">
                            <x-slot:icon>
                                <flux:icon.power class="size-4 text-emerald-600 dark:text-emerald-300" />
                            </x-slot:icon>

                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">
                                {{ $this->platform->is_active ? __('platforms.show.status.active') : __('platforms.show.status.inactive') }}
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
                    :title="__('platforms.show.quick_actions.title')"
                    class="space-y-2"
                >
                    <flux:button
                        variant="primary"
                        icon="arrow-left"
                        class="w-full"
                        :href="route('platforms.index')"
                        wire:navigate
                    >
                        {{ __('actions.back') }}
                    </flux:button>

                    @if ($this->canDelete())
                        <flux:button
                            variant="danger"
                            icon="trash"
                            class="w-full"
                            wire:click="confirmPlatformDeletion"
                        >
                            {{ __('platforms.show.quick_actions.delete.action') }}
                        </flux:button>
                    @endif
                </x-show.sidebar-group>

                <flux:separator variant="subtle" />

                <x-show.sidebar-group :title="__('platforms.show.stats.title')">
                    <div class="grid gap-2.5 sm:gap-3">
                        <x-show.stat-item :label="__('platforms.show.stats.platform_id')">
                            <x-slot:icon class="bg-zinc-100 text-zinc-700 dark:bg-zinc-400/14 dark:text-zinc-200">
                                <flux:icon.hashtag class="size-5" />
                            </x-slot:icon>

                            <flux:text class="font-medium text-zinc-900 dark:text-white">{{ $this->platform->id }}</flux:text>
                        </x-show.stat-item>

                        <x-show.stat-item :label="__('platforms.show.stats.updated')">
                            <x-slot:icon class="bg-amber-100 text-amber-700 dark:bg-amber-400/14 dark:text-amber-200">
                                <flux:icon.sparkles class="size-5" />
                            </x-slot:icon>

                            <flux:text class="font-medium text-zinc-900 dark:text-white" :title="$this->formatDateTooltip($this->platform->updated_at)">{{ $this->formatDate($this->platform->updated_at) }}</flux:text>
                        </x-show.stat-item>
                    </div>
                </x-show.sidebar-group>
            </div>
        </x-show.panel>
    </x-slot:aside>
</x-show.layout>
