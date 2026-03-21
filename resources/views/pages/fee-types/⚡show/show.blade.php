<x-show.layout
    :sr-title="__('fee_types.show.placeholder_title')"
    :heading="__('fee_types.show.title')"
    :subheading="__('fee_types.show.description')"
>
    <x-show.back-button :href="route('fee-types.index')" />

    <div class="space-y-3 sm:space-y-4">
        <x-show.panel>
            <div class="flex items-center gap-3 sm:gap-5">
                <div class="flex size-10 items-center justify-center rounded-lg bg-sky-500/15 sm:size-14">
                    <flux:icon.tag class="size-5 text-sky-300 sm:size-7" />
                </div>

                <div class="min-w-0 space-y-1">
                    <flux:heading size="lg" class="min-w-0 leading-tight">{{ $this->feeType->localizedName() }}</flux:heading>
                    <div class="flex items-center gap-2">
                        <flux:badge size="sm" color="zinc">{{ $this->feeType->name }}</flux:badge>
                    </div>
                </div>
            </div>
        </x-show.panel>

        <x-show.panel>
            <x-show.section
                :title="__('fee_types.show.sections.details')"
                :description="__('fee_types.show.sections.details_description')"
            >
                <x-slot:icon class="bg-sky-500/15 text-sky-300">
                    <flux:icon.tag class="size-5" />
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
                            id="fee-type-show-name"
                            :label="__('fee_types.show.fields.name')"
                        />

                        <div class="grid items-start gap-4 sm:grid-cols-2">
                            <flux:input
                                wire:model.live.blur="en_name"
                                name="en_name"
                                id="fee-type-show-en-name"
                                :label="__('fee_types.show.fields.en_name')"
                            />

                            <flux:input
                                wire:model.live.blur="es_name"
                                name="es_name"
                                id="fee-type-show-es-name"
                                :label="__('fee_types.show.fields.es_name')"
                            />
                        </div>

                        <flux:input
                            wire:model.live.blur="order"
                            name="order"
                            id="fee-type-show-order"
                            :label="__('fee_types.show.fields.order')"
                            type="number"
                            min="0"
                            max="9999"
                        />

                        <x-show.autosave-notice :message="__('fee_types.show.autosave.details')" />
                    </div>
                @else
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-show.detail-item :label="__('fee_types.show.fields.name')">
                            <x-slot:icon>
                                <flux:icon.tag class="size-4 text-sky-500 dark:text-sky-300" />
                            </x-slot:icon>

                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $this->feeType->name }}</flux:text>
                        </x-show.detail-item>

                        <x-show.detail-item :label="__('fee_types.show.fields.en_name')">
                            <x-slot:icon>
                                <flux:icon.globe-alt class="size-4 text-emerald-600 dark:text-emerald-300" />
                            </x-slot:icon>

                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $this->feeType->en_name }}</flux:text>
                        </x-show.detail-item>

                        <x-show.detail-item :label="__('fee_types.show.fields.es_name')">
                            <x-slot:icon>
                                <flux:icon.globe-alt class="size-4 text-emerald-600 dark:text-emerald-300" />
                            </x-slot:icon>

                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $this->feeType->es_name }}</flux:text>
                        </x-show.detail-item>

                        <x-show.detail-item :label="__('fee_types.show.fields.order')">
                            <x-slot:icon>
                                <flux:icon.arrows-up-down class="size-4 text-violet-500 dark:text-violet-300" />
                            </x-slot:icon>

                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $this->feeType->order }}</flux:text>
                        </x-show.detail-item>
                    </div>
                @endif
            </x-show.section>
        </x-show.panel>

        <x-show.panel>
            <x-show.section
                :title="__('fee_types.show.sections.charge_bases')"
                :description="__('fee_types.show.sections.charge_bases_description')"
            >
                <x-slot:icon class="bg-emerald-500/15 text-emerald-300">
                    <flux:icon.adjustments-horizontal class="size-5" />
                </x-slot:icon>

                @if ($this->canEdit())
                    <x-slot:actions>
                        <x-show.section-toggle section="charge_bases" :editing-section="$editingSection" />
                    </x-slot:actions>
                @endif

                @if ($editingSection === 'charge_bases')
                    <div class="space-y-6">
                        <flux:callout icon="information-circle" variant="secondary">
                            <flux:callout.text>{{ __('fee_types.show.charge_bases.managed_in_catalog') }}</flux:callout.text>
                        </flux:callout>

                        <flux:field>
                            <flux:checkbox.group wire:model.live="selectedChargeBases" class="grid gap-3 sm:grid-cols-2">
                                @foreach ($this->availableChargeBases as $chargeBasis)
                                    @php
                                        $isSelected = in_array($chargeBasis->id, $selectedChargeBases, true);
                                        $metadata = $chargeBasis->metadata ?? [];
                                        $requiresQuantity = $metadata['requires_quantity'] ?? false;
                                        $quantitySubject = $metadata['quantity_subject'] ?? null;
                                    @endphp

                                    <div
                                        wire:key="fee-type-charge-basis-{{ $chargeBasis->id }}"
                                        @class([
                                            'group flex items-start gap-3 rounded-xl border px-3 py-2.5 transition',
                                            'border-zinc-300 bg-white shadow-sm ring-1 ring-inset ring-zinc-200 dark:border-white/20 dark:bg-white/6 dark:ring-white/6' => $isSelected,
                                            'border-zinc-200 bg-zinc-50 hover:border-zinc-300 hover:bg-white dark:border-white/8 dark:bg-white/3 dark:hover:border-white/14 dark:hover:bg-white/4' => ! $isSelected,
                                        ])
                                    >
                                        <div class="w-full space-y-2">
                                            <flux:checkbox value="{{ $chargeBasis->id }}" :label="$chargeBasis->localizedName()" />

                                            <div class="space-y-2 pl-7">
                                                @if ($chargeBasis->description)
                                                    <flux:text size="sm" class="text-zinc-600 dark:text-zinc-300">{{ $chargeBasis->description }}</flux:text>
                                                @endif

                                                <div class="flex flex-wrap gap-2">
                                                    <flux:badge size="sm" :color="$chargeBasis->is_active ? 'emerald' : 'zinc'">
                                                        {{ $chargeBasis->statusLabel() }}
                                                    </flux:badge>

                                                    <flux:badge size="sm" :color="$requiresQuantity ? 'sky' : 'zinc'">
                                                        {{ $requiresQuantity ? __('charge_bases.show.status.quantity_required') : __('charge_bases.show.status.quantity_not_required') }}
                                                    </flux:badge>

                                                    @if ($quantitySubject !== null)
                                                        <flux:badge size="sm" color="amber">
                                                            {{ __('charge_bases.quantity_subjects.'.$quantitySubject) }}
                                                        </flux:badge>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </flux:checkbox.group>

                            <flux:error name="selectedChargeBases" />
                        </flux:field>

                        <div class="flex justify-stretch sm:justify-end">
                            <flux:button variant="primary" size="sm" wire:click="saveChargeBases" class="w-full sm:w-auto">
                                {{ __('fee_types.show.charge_bases.save') }}
                            </flux:button>
                        </div>
                    </div>
                @else
                    @if ($this->feeType->chargeBases->isEmpty())
                        <flux:text class="text-zinc-600 dark:text-zinc-300">{{ __('fee_types.show.charge_bases.empty') }}</flux:text>
                    @else
                        <div class="space-y-4">
                            @foreach ($this->feeType->chargeBases as $chargeBasis)
                                @php
                                    $metadata = $chargeBasis->metadata ?? [];
                                    $requiresQuantity = $metadata['requires_quantity'] ?? false;
                                    $quantitySubject = $metadata['quantity_subject'] ?? null;
                                @endphp

                                <div class="rounded-2xl border border-zinc-200 bg-zinc-50/70 p-4 dark:border-white/10 dark:bg-white/3">
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <div class="space-y-1">
                                            <flux:heading size="sm">{{ $chargeBasis->localizedName() }}</flux:heading>
                                            @if ($chargeBasis->description)
                                                <flux:text size="sm" class="text-zinc-600 dark:text-zinc-300">{{ $chargeBasis->description }}</flux:text>
                                            @endif
                                        </div>

                                        <div class="flex flex-wrap gap-2">
                                            <flux:badge size="sm" :color="$chargeBasis->pivot->is_active ? 'emerald' : 'zinc'">
                                                {{ __('charge_bases.fields.is_active') }}
                                            </flux:badge>

                                            @if (! $chargeBasis->is_active)
                                                <flux:badge size="sm" color="zinc">{{ __('fee_types.show.charge_bases.inactive_badge') }}</flux:badge>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                                        <x-show.detail-item :label="__('charge_bases.fields.sort_order')">
                                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $chargeBasis->pivot->sort_order }}</flux:text>
                                        </x-show.detail-item>

                                        <x-show.detail-item :label="__('charge_bases.fields.requires_quantity')">
                                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">
                                                {{ $requiresQuantity ? __('charge_bases.show.status.quantity_required') : __('charge_bases.show.status.quantity_not_required') }}
                                            </flux:text>
                                        </x-show.detail-item>

                                        <x-show.detail-item :label="__('charge_bases.fields.quantity_subject')">
                                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">
                                                {{ $quantitySubject ? __('charge_bases.quantity_subjects.'.$quantitySubject) : __('charge_bases.show.status.not_applicable') }}
                                            </flux:text>
                                        </x-show.detail-item>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endif
            </x-show.section>
        </x-show.panel>
    </div>

    <x-slot:aside>
        <x-show.panel class="relative overflow-hidden border">
            <div class="relative space-y-4 p-3 sm:space-y-6 sm:p-5">
                <x-show.sidebar-group
                    :title="__('fee_types.show.quick_actions.title')"
                    class="space-y-2"
                >
                    <flux:button
                        variant="primary"
                        icon="arrow-left"
                        class="w-full"
                        :href="route('fee-types.index')"
                        wire:navigate
                    >
                        {{ __('actions.back') }}
                    </flux:button>

                    @if ($this->canDelete())
                        <flux:button
                            variant="danger"
                            icon="trash"
                            class="w-full"
                            wire:click="confirmFeeTypeDeletion"
                        >
                            {{ __('fee_types.show.quick_actions.delete.action') }}
                        </flux:button>
                    @endif
                </x-show.sidebar-group>

                <flux:separator variant="subtle" />

                <x-show.sidebar-group :title="__('fee_types.show.stats.title')">
                    <div class="grid gap-2.5 sm:gap-3">
                        <x-show.stat-item :label="__('fee_types.show.stats.fee_type_id')">
                            <x-slot:icon class="bg-zinc-100 text-zinc-700 dark:bg-zinc-400/14 dark:text-zinc-200">
                                <flux:icon.hashtag class="size-5" />
                            </x-slot:icon>

                            <flux:text class="font-medium text-zinc-900 dark:text-white">{{ $this->feeType->id }}</flux:text>
                        </x-show.stat-item>

                        <x-show.stat-item :label="__('fee_types.show.stats.order')">
                            <x-slot:icon class="bg-violet-100 text-violet-700 dark:bg-violet-400/14 dark:text-violet-200">
                                <flux:icon.arrows-up-down class="size-5" />
                            </x-slot:icon>

                            <flux:text class="font-medium text-zinc-900 dark:text-white">{{ $this->feeType->order }}</flux:text>
                        </x-show.stat-item>

                        <x-show.stat-item :label="__('fee_types.show.stats.updated')">
                            <x-slot:icon class="bg-sky-100 text-sky-700 dark:bg-sky-400/14 dark:text-sky-200">
                                <flux:icon.sparkles class="size-5" />
                            </x-slot:icon>

                            <flux:text class="font-medium text-zinc-900 dark:text-white" :title="$this->formatDateTooltip($this->feeType->updated_at)">{{ $this->formatDate($this->feeType->updated_at) }}</flux:text>
                        </x-show.stat-item>
                    </div>
                </x-show.sidebar-group>
            </div>
        </x-show.panel>
    </x-slot:aside>
</x-show.layout>
