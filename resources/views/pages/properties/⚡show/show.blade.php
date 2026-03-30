<x-show.layout
    :sr-title="__('properties.show.placeholder_title')"
    :heading="__('properties.show.title')"
    :subheading="__('properties.show.description')"
>
    <x-show.back-button :href="route('properties.index')" />

    <div class="space-y-3 sm:space-y-4">
        <x-show.panel>
            <div class="grid min-w-0 grid-cols-[auto_minmax(0,1fr)] items-center gap-x-3 sm:gap-x-5">
                <div class="relative" wire:loading.class="pointer-events-none" wire:target="photo">
                    <flux:avatar
                        size="lg"
                        :src="$this->propertyAvatarUrl"
                        :initials="$this->property->initials()"
                        color="auto"
                        :color:seed="$this->property->id"
                        class="sm:!size-14"
                    />

                    @if ($this->canEdit())
                        <x-image-editor wire:model="photo" aspect-ratio="1:1" input-id="property-avatar-upload" :maxSizeMb="$this->maxUploadSizeMb">
                            <label for="property-avatar-upload" class="group absolute inset-0 z-[5] cursor-pointer overflow-hidden rounded-[var(--radius-lg)]" wire:loading.class="!hidden" wire:target="photo">
                                <div class="flex size-full items-center justify-center bg-black/40 opacity-0 backdrop-blur-xs transition-opacity duration-200 group-hover:opacity-100">
                                    <flux:icon.camera class="size-4.5 text-white drop-shadow" />
                                </div>
                            </label>

                            <div class="absolute inset-0 z-[5] flex items-center justify-center overflow-hidden rounded-[var(--radius-lg)] bg-black/40 backdrop-blur-xs" wire:loading.flex wire:target="photo">
                                <flux:icon.loading class="size-5 text-white" />
                            </div>

                            <div wire:loading.class="!hidden" wire:target="photo">
                                @if ($this->propertyAvatarUrl)
                                    <button
                                        type="button"
                                        wire:click="deleteAvatar"
                                        class="absolute bottom-0 right-0 z-20 flex size-5 translate-x-1/4 translate-y-1/4 cursor-pointer items-center justify-center rounded-full bg-rose-500 shadow-md transition-all duration-150 hover:scale-110 hover:bg-rose-600 dark:bg-rose-400 dark:hover:bg-rose-500"
                                        aria-label="{{ __('properties.show.avatar_delete_label') }}"
                                    >
                                        <flux:icon.x-mark variant="micro" class="size-3 text-white" />
                                    </button>
                                @else
                                    <label
                                        for="property-avatar-upload"
                                        class="absolute bottom-0 right-0 z-20 flex size-5 translate-x-1/4 translate-y-1/4 cursor-pointer items-center justify-center rounded-full bg-sky-500 shadow-md transition-all duration-150 hover:scale-110 hover:bg-sky-600 dark:bg-sky-400 dark:hover:bg-sky-500"
                                        aria-label="{{ __('properties.show.avatar_add_label') }}"
                                    >
                                        <flux:icon.plus variant="micro" class="size-3 text-white" />
                                    </label>
                                @endif
                            </div>
                        </x-image-editor>
                    @endif
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

                        <div>
                            <flux:editor
                                wire:model="description"
                                :label="__('properties.show.fields.description')"
                                name="description"
                                toolbar="bold italic | link"
                            />

                            <div class="mt-2 flex justify-end">
                                <flux:button wire:click="saveDescription" size="sm">
                                    {{ __('actions.save') }}
                                </flux:button>
                            </div>
                        </div>

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

                        <x-show.detail-item :label="__('properties.show.fields.description')" class="sm:col-span-2">
                            <x-slot:icon>
                                <flux:icon.document-text class="size-4 text-sky-500 dark:text-sky-300" />
                            </x-slot:icon>

                            @if ($this->property->description)
                                <div class="prose prose-sm dark:prose-invert">{!! $this->property->description !!}</div>
                            @else
                                <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">—</flux:text>
                            @endif
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
        <x-show.panel>
            <x-show.section
                :title="__('properties.show.sections.capacity')"
                :description="__('properties.show.sections.capacity_description')"
            >
                <x-slot:icon class="bg-indigo-500/15 text-indigo-300">
                    <flux:icon.user-group class="size-5" />
                </x-slot:icon>

                @if ($this->canEdit())
                    <x-slot:actions>
                        <x-show.section-toggle section="capacity" :editing-section="$editingSection" />
                    </x-slot:actions>
                @endif

                @if ($editingSection === 'capacity')
                    <div class="space-y-4">
                        <div class="grid items-start gap-4 sm:grid-cols-2">
                            <flux:input
                                wire:model.live.blur="base_capacity"
                                name="base_capacity"
                                id="property-show-base-capacity"
                                :label="__('properties.show.fields.base_capacity')"
                                type="number"
                                min="1"
                                max="255"
                            />

                            <flux:input
                                wire:model.live.blur="max_capacity"
                                name="max_capacity"
                                id="property-show-max-capacity"
                                :label="__('properties.show.fields.max_capacity')"
                                type="number"
                                min="1"
                                max="255"
                            />
                        </div>

                        <x-show.autosave-notice :message="__('properties.show.autosave.capacity')" />
                    </div>
                @else
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-show.detail-item :label="__('properties.show.fields.base_capacity')">
                            <x-slot:icon>
                                <flux:icon.user class="size-4 text-indigo-500 dark:text-indigo-300" />
                            </x-slot:icon>

                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">
                                {{ $this->property->base_capacity ?? '—' }}
                            </flux:text>
                        </x-show.detail-item>

                        <x-show.detail-item :label="__('properties.show.fields.max_capacity')">
                            <x-slot:icon>
                                <flux:icon.user-group class="size-4 text-indigo-500 dark:text-indigo-300" />
                            </x-slot:icon>

                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">
                                {{ $this->property->max_capacity ?? '—' }}
                            </flux:text>
                        </x-show.detail-item>
                    </div>
                @endif
            </x-show.section>
        </x-show.panel>

        <x-show.panel>
            <x-show.section
                :title="__('properties.show.sections.accommodation')"
                :description="__('properties.show.sections.accommodation_description')"
            >
                <x-slot:icon class="bg-emerald-500/15 text-emerald-300">
                    <flux:icon.home class="size-5" />
                </x-slot:icon>

                @if ($this->canEdit())
                    <x-slot:actions>
                        <x-show.section-toggle section="accommodation" :editing-section="$editingSection" />
                    </x-slot:actions>
                @endif

                @if ($editingSection === 'accommodation')
                    <div class="space-y-5">
                        <div class="rounded-2xl border border-zinc-200/80 bg-zinc-50/70 p-4 shadow-sm dark:border-white/10 dark:bg-white/3">
                            <div class="mb-4 space-y-1">
                                <flux:heading size="sm">{{ __('properties.show.accommodation.form.title') }}</flux:heading>
                                <flux:text size="sm" class="text-zinc-500 dark:text-white/60">
                                    {{ __('properties.show.accommodation.form.description') }}
                                </flux:text>
                            </div>

                            <div class="space-y-4">
                                <div class="grid items-start gap-4 md:grid-cols-2">
                                    <flux:field>
                                        <flux:label>{{ __('properties.show.accommodation.fields.en_name') }}</flux:label>
                                        <flux:input wire:model.live.blur="bedroom_en_name" name="bedroom_en_name" id="property-bedroom-en-name" required />
                                        <flux:error name="bedroom_en_name" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>{{ __('properties.show.accommodation.fields.es_name') }}</flux:label>
                                        <flux:input wire:model.live.blur="bedroom_es_name" name="bedroom_es_name" id="property-bedroom-es-name" required />
                                        <flux:error name="bedroom_es_name" />
                                    </flux:field>
                                </div>

                                <div class="grid items-start gap-4 md:grid-cols-2">
                                    <flux:field>
                                        <flux:label>{{ __('properties.show.accommodation.fields.en_description') }}</flux:label>
                                        <flux:textarea wire:model.live.blur="bedroom_en_description" name="bedroom_en_description" id="property-bedroom-en-description" rows="3" />
                                        <flux:error name="bedroom_en_description" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>{{ __('properties.show.accommodation.fields.es_description') }}</flux:label>
                                        <flux:textarea wire:model.live.blur="bedroom_es_description" name="bedroom_es_description" id="property-bedroom-es-description" rows="3" />
                                        <flux:error name="bedroom_es_description" />
                                    </flux:field>
                                </div>

                                <div class="flex justify-end">
                                    <flux:button wire:click="createBedroom" variant="primary" icon="plus" size="sm">
                                        {{ __('properties.show.accommodation.form.submit') }}
                                    </flux:button>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                            @forelse ($accommodationBedrooms as $bedroom)
                                <div wire:key="property-bedroom-{{ $bedroom->id }}" class="rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/3">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0 space-y-1">
                                            <flux:heading size="sm">{{ $bedroom->en_name }}</flux:heading>
                                            <flux:text size="sm" class="text-zinc-500 dark:text-white/60">{{ $bedroom->es_name }}</flux:text>
                                        </div>

                                        <flux:button
                                            wire:click="openAttachBedTypeModal({{ $bedroom->id }})"
                                            variant="ghost"
                                            icon="plus"
                                            size="sm"
                                        >
                                            {{ __('properties.show.accommodation.bed_types.form.trigger') }}
                                        </flux:button>
                                    </div>

                                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                                        <div class="space-y-1.5">
                                            <flux:text size="sm" class="font-medium text-zinc-700 dark:text-zinc-200">
                                                {{ __('properties.show.accommodation.fields.en_description') }}
                                            </flux:text>
                                            <flux:text class="whitespace-pre-line text-zinc-600 dark:text-white/70">
                                                {{ $bedroom->en_description ?: '—' }}
                                            </flux:text>
                                        </div>

                                        <div class="space-y-1.5">
                                            <flux:text size="sm" class="font-medium text-zinc-700 dark:text-zinc-200">
                                                {{ __('properties.show.accommodation.fields.es_description') }}
                                            </flux:text>
                                            <flux:text class="whitespace-pre-line text-zinc-600 dark:text-white/70">
                                                {{ $bedroom->es_description ?: '—' }}
                                            </flux:text>
                                        </div>
                                    </div>

                                    <div class="mt-4 space-y-2">
                                        <flux:text size="sm" class="font-medium text-zinc-700 dark:text-zinc-200">
                                            {{ __('properties.show.accommodation.bed_types.title') }}
                                        </flux:text>

                                        @forelse ($bedroom->bedTypes as $bedType)
                                            <div
                                                wire:key="property-bedroom-bed-type-{{ $bedroom->id }}-{{ $bedType->id }}"
                                                class="flex items-center justify-between gap-3 rounded-xl border border-zinc-200/70 bg-zinc-50/70 px-3 py-2 dark:border-white/10 dark:bg-white/3"
                                            >
                                                <div class="min-w-0">
                                                    <flux:text class="font-medium text-zinc-900 dark:text-white">{{ $bedType->localizedName() }}</flux:text>
                                                </div>

                                                <div class="flex items-center gap-2">
                                                    <flux:badge size="sm" color="sky">
                                                        {{ __('properties.show.accommodation.bed_types.quantity_badge', ['quantity' => $bedType->pivot->quantity]) }}
                                                    </flux:badge>

                                                    @if ($this->canEdit())
                                                        <flux:button
                                                            wire:click="confirmBedTypeRemoval({{ $bedroom->id }}, {{ $bedType->id }})"
                                                            variant="ghost"
                                                            size="xs"
                                                            square
                                                            icon="trash"
                                                            :aria-label="__('properties.show.accommodation.bed_types.delete.aria_label', ['bed_type' => $bedType->localizedName()])"
                                                        />
                                                    @endif
                                                </div>
                                            </div>
                                        @empty
                                            <flux:text size="sm" class="text-zinc-500 dark:text-white/60">
                                                {{ __('properties.show.accommodation.bed_types.empty') }}
                                            </flux:text>
                                        @endforelse
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-2xl border border-dashed border-zinc-300/80 bg-zinc-50/60 px-4 py-5 text-center dark:border-white/12 dark:bg-white/3">
                                    <flux:text class="text-zinc-500 dark:text-white/60">
                                        {{ __('properties.show.accommodation.empty') }}
                                    </flux:text>
                                </div>
                            @endforelse
                        </div>
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                        @forelse ($accommodationBedrooms as $bedroom)
                            <div wire:key="property-bedroom-summary-{{ $bedroom->id }}" class="rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/3">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0 space-y-1">
                                        <flux:heading size="sm">{{ $bedroom->en_name }}</flux:heading>
                                        <flux:text size="sm" class="text-zinc-500 dark:text-white/60">{{ $bedroom->es_name }}</flux:text>
                                    </div>

                                    @if ($this->canEdit())
                                        <flux:button
                                            wire:click="openAttachBedTypeModal({{ $bedroom->id }})"
                                            variant="ghost"
                                            icon="plus"
                                            size="sm"
                                        >
                                            {{ __('properties.show.accommodation.bed_types.form.trigger') }}
                                        </flux:button>
                                    @endif
                                </div>

                                <div class="mt-3 space-y-2">
                                    @forelse ($bedroom->bedTypes as $bedType)
                                        <div
                                            wire:key="property-bedroom-summary-bed-type-{{ $bedroom->id }}-{{ $bedType->id }}"
                                            class="flex items-center justify-between gap-3 rounded-xl border border-zinc-200/70 bg-zinc-50/70 px-3 py-2 dark:border-white/10 dark:bg-white/3"
                                        >
                                            <flux:text class="font-medium text-zinc-900 dark:text-white">{{ $bedType->localizedName() }}</flux:text>

                                            <div class="flex items-center gap-2">
                                                <flux:badge size="sm" color="sky">
                                                    {{ __('properties.show.accommodation.bed_types.quantity_badge', ['quantity' => $bedType->pivot->quantity]) }}
                                                </flux:badge>

                                                @if ($this->canEdit())
                                                    <flux:button
                                                        wire:click="confirmBedTypeRemoval({{ $bedroom->id }}, {{ $bedType->id }})"
                                                        variant="ghost"
                                                        size="xs"
                                                        square
                                                        icon="trash"
                                                        :aria-label="__('properties.show.accommodation.bed_types.delete.aria_label', ['bed_type' => $bedType->localizedName()])"
                                                    />
                                                @endif
                                            </div>
                                        </div>
                                    @empty
                                        <flux:text size="sm" class="text-zinc-500 dark:text-white/60">
                                            {{ __('properties.show.accommodation.bed_types.empty') }}
                                        </flux:text>
                                    @endforelse
                                </div>
                            </div>
                        @empty
                            <x-show.detail-item :label="__('properties.show.sections.accommodation')">
                                <x-slot:icon>
                                    <flux:icon.home class="size-4 text-emerald-500 dark:text-emerald-300" />
                                </x-slot:icon>

                                <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">—</flux:text>
                            </x-show.detail-item>
                        @endforelse
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
