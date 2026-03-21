<x-show.layout
    :sr-title="__('charge_bases.show.placeholder_title')"
    :heading="__('charge_bases.show.title')"
    :subheading="__('charge_bases.show.description')"
>
    <x-show.back-button :href="route('charge-bases.index')" />

    <div class="space-y-3 sm:space-y-4">
        <x-show.panel>
            <div class="flex items-center gap-3 sm:gap-5">
                <div class="flex size-10 items-center justify-center rounded-lg bg-emerald-500/15 sm:size-14">
                    <flux:icon.adjustments-horizontal class="size-5 text-emerald-300 sm:size-7" />
                </div>

                <div class="min-w-0 space-y-1">
                    <flux:heading size="lg" class="min-w-0 leading-tight">{{ $this->chargeBasis->localizedName() }}</flux:heading>
                    <div class="flex flex-wrap items-center gap-2">
                        <flux:badge size="sm" color="zinc">{{ $this->chargeBasis->name }}</flux:badge>
                        <flux:badge size="sm" :color="$this->chargeBasis->is_active ? 'emerald' : 'zinc'">{{ $this->chargeBasis->statusLabel() }}</flux:badge>
                    </div>
                </div>
            </div>
        </x-show.panel>

        <x-show.panel>
            <x-show.section
                :title="__('charge_bases.show.sections.details')"
                :description="__('charge_bases.show.sections.details_description')"
            >
                <x-slot:icon class="bg-emerald-500/15 text-emerald-300">
                    <flux:icon.adjustments-horizontal class="size-5" />
                </x-slot:icon>

                @if ($this->canEdit())
                    <x-slot:actions>
                        <x-show.section-toggle section="details" :editing-section="$editingSection" />
                    </x-slot:actions>
                @endif

                @if ($editingSection === 'details')
                    <div class="space-y-4">
                        <flux:input
                            wire:model.live.blur="name"
                            name="name"
                            id="charge-basis-show-name"
                            :label="__('charge_bases.show.fields.name')"
                        />

                        <div class="grid items-start gap-4 sm:grid-cols-2">
                            <flux:input
                                wire:model.live.blur="en_name"
                                name="en_name"
                                id="charge-basis-show-en-name"
                                :label="__('charge_bases.show.fields.en_name')"
                            />

                            <flux:input
                                wire:model.live.blur="es_name"
                                name="es_name"
                                id="charge-basis-show-es-name"
                                :label="__('charge_bases.show.fields.es_name')"
                            />
                        </div>

                        <div>
                            <flux:label class="mb-2">{{ __('charge_bases.show.fields.description') }}</flux:label>

                            <flux:tab.group>
                                <flux:tabs variant="segmented" size="sm">
                                    <flux:tab name="en">{{ __('charge_bases.tabs.en') }}</flux:tab>
                                    <flux:tab name="es">{{ __('charge_bases.tabs.es') }}</flux:tab>
                                </flux:tabs>

                                <flux:tab.panel name="en">
                                    <flux:textarea
                                        wire:model.live.blur="en_description"
                                        name="en_description"
                                        id="charge-basis-show-en-description"
                                        rows="3"
                                    />
                                </flux:tab.panel>

                                <flux:tab.panel name="es">
                                    <flux:textarea
                                        wire:model.live.blur="es_description"
                                        name="es_description"
                                        id="charge-basis-show-es-description"
                                        rows="3"
                                    />
                                </flux:tab.panel>
                            </flux:tab.group>
                        </div>

                        <flux:input
                            wire:model.live.blur="order"
                            name="order"
                            id="charge-basis-show-order"
                            :label="__('charge_bases.show.fields.order')"
                            type="number"
                            min="0"
                            max="9999"
                        />

                        <x-show.autosave-notice :message="__('charge_bases.show.autosave.details')" />
                    </div>
                @else
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-show.detail-item :label="__('charge_bases.show.fields.name')">
                            <x-slot:icon>
                                <flux:icon.tag class="size-4 text-sky-500 dark:text-sky-300" />
                            </x-slot:icon>

                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $this->chargeBasis->name }}</flux:text>
                        </x-show.detail-item>

                        <x-show.detail-item :label="__('charge_bases.show.fields.en_name')">
                            <x-slot:icon>
                                <flux:icon.globe-alt class="size-4 text-emerald-600 dark:text-emerald-300" />
                            </x-slot:icon>

                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $this->chargeBasis->en_name }}</flux:text>
                        </x-show.detail-item>

                        <x-show.detail-item :label="__('charge_bases.show.fields.es_name')">
                            <x-slot:icon>
                                <flux:icon.globe-alt class="size-4 text-emerald-600 dark:text-emerald-300" />
                            </x-slot:icon>

                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $this->chargeBasis->es_name }}</flux:text>
                        </x-show.detail-item>

                        <x-show.detail-item :label="__('charge_bases.show.fields.en_description')">
                            <x-slot:icon>
                                <flux:icon.information-circle class="size-4 text-amber-500 dark:text-amber-300" />
                            </x-slot:icon>

                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $this->chargeBasis->en_description ?: __('charge_bases.show.status.not_applicable') }}</flux:text>
                        </x-show.detail-item>

                        <x-show.detail-item :label="__('charge_bases.show.fields.es_description')">
                            <x-slot:icon>
                                <flux:icon.information-circle class="size-4 text-amber-500 dark:text-amber-300" />
                            </x-slot:icon>

                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $this->chargeBasis->es_description ?: __('charge_bases.show.status.not_applicable') }}</flux:text>
                        </x-show.detail-item>

                        <x-show.detail-item :label="__('charge_bases.show.fields.order')">
                            <x-slot:icon>
                                <flux:icon.arrows-up-down class="size-4 text-violet-500 dark:text-violet-300" />
                            </x-slot:icon>

                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $this->chargeBasis->order }}</flux:text>
                        </x-show.detail-item>
                    </div>
                @endif
            </x-show.section>
        </x-show.panel>

        <x-show.panel>
            <x-show.section
                :title="__('charge_bases.show.sections.configuration')"
                :description="__('charge_bases.show.sections.configuration_description')"
            >
                <x-slot:icon class="bg-sky-500/15 text-sky-300">
                    <flux:icon.cog-6-tooth class="size-5" />
                </x-slot:icon>

                @if ($this->canEdit())
                    <x-slot:actions>
                        <x-show.section-toggle section="configuration" :editing-section="$editingSection" />
                    </x-slot:actions>
                @endif

                @if ($editingSection === 'configuration')
                    <div class="space-y-5">
                        <flux:switch wire:model.live="is_active" :label="__('charge_bases.show.fields.is_active')" />

                        <flux:separator variant="subtle" />

                        <flux:switch wire:model.live="requires_quantity" :label="__('charge_bases.show.fields.requires_quantity')" />

                        <flux:separator variant="subtle" />

                        <flux:select
                            wire:model.live="quantity_subject"
                            :label="__('charge_bases.show.fields.quantity_subject')"
                            :disabled="! $requires_quantity"
                        >
                            <option value="">{{ __('actions.select') }}</option>
                            @foreach (__('charge_bases.quantity_subjects') as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </flux:select>

                        <x-show.autosave-notice :message="__('charge_bases.show.autosave.configuration')" />
                    </div>
                @else
                    @php
                        $metadata = $this->chargeBasis->metadata ?? [];
                        $requiresQuantity = $metadata['requires_quantity'] ?? false;
                        $quantitySubject = $metadata['quantity_subject'] ?? null;
                    @endphp

                    <div class="grid gap-4 sm:grid-cols-3">
                        <x-show.detail-item :label="__('charge_bases.show.fields.is_active')">
                            <x-slot:icon>
                                <flux:icon.check-circle class="size-4 text-emerald-500 dark:text-emerald-300" />
                            </x-slot:icon>

                            <flux:badge size="sm" :color="$this->chargeBasis->is_active ? 'emerald' : 'zinc'">
                                {{ $this->chargeBasis->is_active ? __('charge_bases.show.status.active') : __('charge_bases.show.status.inactive') }}
                            </flux:badge>
                        </x-show.detail-item>

                        <x-show.detail-item :label="__('charge_bases.show.fields.requires_quantity')">
                            <x-slot:icon>
                                <flux:icon.calculator class="size-4 text-sky-500 dark:text-sky-300" />
                            </x-slot:icon>

                            <flux:badge size="sm" :color="$requiresQuantity ? 'sky' : 'zinc'">
                                {{ $requiresQuantity ? __('charge_bases.show.status.quantity_required') : __('charge_bases.show.status.quantity_not_required') }}
                            </flux:badge>
                        </x-show.detail-item>

                        <x-show.detail-item :label="__('charge_bases.show.fields.quantity_subject')">
                            <x-slot:icon>
                                <flux:icon.users class="size-4 text-amber-500 dark:text-amber-300" />
                            </x-slot:icon>

                            <flux:badge size="sm" :color="$quantitySubject ? 'amber' : 'zinc'">
                                {{ $quantitySubject ? __('charge_bases.quantity_subjects.'.$quantitySubject) : __('charge_bases.show.status.not_applicable') }}
                            </flux:badge>
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
                    :title="__('charge_bases.show.quick_actions.title')"
                    class="space-y-2"
                >
                    <flux:button
                        variant="primary"
                        icon="arrow-left"
                        class="w-full"
                        :href="route('charge-bases.index')"
                        wire:navigate
                    >
                        {{ __('actions.back') }}
                    </flux:button>

                    @if ($this->canDelete())
                        <flux:button
                            variant="danger"
                            icon="trash"
                            class="w-full"
                            wire:click="confirmChargeBasisDeletion"
                        >
                            {{ __('charge_bases.show.quick_actions.delete.action') }}
                        </flux:button>
                    @endif
                </x-show.sidebar-group>

                <flux:separator variant="subtle" />

                <x-show.sidebar-group :title="__('charge_bases.show.stats.title')">
                    <div class="grid gap-2.5 sm:gap-3">
                        <x-show.stat-item :label="__('charge_bases.show.stats.charge_basis_id')">
                            <x-slot:icon class="bg-zinc-100 text-zinc-700 dark:bg-zinc-400/14 dark:text-zinc-200">
                                <flux:icon.hashtag class="size-5" />
                            </x-slot:icon>

                            <flux:text class="font-medium text-zinc-900 dark:text-white">{{ $this->chargeBasis->id }}</flux:text>
                        </x-show.stat-item>

                        <x-show.stat-item :label="__('charge_bases.show.stats.order')">
                            <x-slot:icon class="bg-violet-100 text-violet-700 dark:bg-violet-400/14 dark:text-violet-200">
                                <flux:icon.arrows-up-down class="size-5" />
                            </x-slot:icon>

                            <flux:text class="font-medium text-zinc-900 dark:text-white">{{ $this->chargeBasis->order }}</flux:text>
                        </x-show.stat-item>

                        <x-show.stat-item :label="__('charge_bases.show.stats.updated')">
                            <x-slot:icon class="bg-sky-100 text-sky-700 dark:bg-sky-400/14 dark:text-sky-200">
                                <flux:icon.sparkles class="size-5" />
                            </x-slot:icon>

                            <flux:text class="font-medium text-zinc-900 dark:text-white" :title="$this->formatDateTooltip($this->chargeBasis->updated_at)">{{ $this->formatDate($this->chargeBasis->updated_at) }}</flux:text>
                        </x-show.stat-item>
                    </div>
                </x-show.sidebar-group>
            </div>
        </x-show.panel>
    </x-slot:aside>
</x-show.layout>
