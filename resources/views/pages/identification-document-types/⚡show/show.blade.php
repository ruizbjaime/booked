<x-show.layout
    :sr-title="__('identification_document_types.show.placeholder_title')"
    :heading="__('identification_document_types.show.title')"
    :subheading="__('identification_document_types.show.description')"
>
    <x-show.back-button :href="route('identification-document-types.index')" />

    <div class="space-y-3 sm:space-y-4">
        <x-show.panel>
            <div class="flex items-center gap-3 sm:gap-5">
                <div class="flex size-10 items-center justify-center rounded-lg bg-sky-500/15 sm:size-14">
                    <flux:icon.identification class="size-5 text-sky-300 sm:size-7" />
                </div>

                <div class="min-w-0 space-y-1">
                    <flux:heading size="lg" class="min-w-0 leading-tight">{{ $this->docType->localizedName() }}</flux:heading>
                    <div class="flex items-center gap-2">
                        <flux:badge size="sm" color="zinc">{{ $this->docType->code }}</flux:badge>
                    </div>
                </div>
            </div>
        </x-show.panel>

        <x-show.panel>
            <x-show.section
                :title="__('identification_document_types.show.sections.details')"
                :description="__('identification_document_types.show.sections.details_description')"
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
                                wire:model.live.blur="code"
                                name="code"
                                id="doc-type-show-code"
                                :label="__('identification_document_types.show.fields.code')"
                                maxlength="20"
                            />

                            <flux:input
                                wire:model.live.blur="sort_order"
                                name="sort_order"
                                id="doc-type-show-sort-order"
                                :label="__('identification_document_types.show.fields.sort_order')"
                                type="number"
                                min="0"
                            />
                        </div>

                        <div class="grid items-start gap-4 sm:grid-cols-2">
                            <flux:input
                                wire:model.live.blur="en_name"
                                name="en_name"
                                id="doc-type-show-en-name"
                                :label="__('identification_document_types.show.fields.en_name')"
                            />

                            <flux:input
                                wire:model.live.blur="es_name"
                                name="es_name"
                                id="doc-type-show-es-name"
                                :label="__('identification_document_types.show.fields.es_name')"
                            />
                        </div>

                        <x-show.switch-card
                            :title="__('identification_document_types.show.fields.active')"
                            :status-text="$is_active ? __('identification_document_types.show.status.active') : __('identification_document_types.show.status.inactive')"
                            :active="$is_active"
                            status-color="emerald"
                        >
                            <x-slot:control>
                                <flux:switch
                                    wire:model.live="is_active"
                                    name="is_active"
                                    id="doc-type-show-active"
                                    :aria-label="__('identification_document_types.show.fields.active')"
                                    class="self-start sm:self-auto"
                                />
                            </x-slot:control>
                        </x-show.switch-card>

                        <x-show.autosave-notice :message="__('identification_document_types.show.autosave.details')" />
                    </div>
                @else
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-show.detail-item :label="__('identification_document_types.show.fields.code')">
                            <x-slot:icon>
                                <flux:icon.hashtag class="size-4 text-sky-500 dark:text-sky-300" />
                            </x-slot:icon>

                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $this->docType->code }}</flux:text>
                        </x-show.detail-item>

                        <x-show.detail-item :label="__('identification_document_types.show.fields.en_name')">
                            <x-slot:icon>
                                <flux:icon.globe-alt class="size-4 text-emerald-600 dark:text-emerald-300" />
                            </x-slot:icon>

                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $this->docType->en_name }}</flux:text>
                        </x-show.detail-item>

                        <x-show.detail-item :label="__('identification_document_types.show.fields.es_name')">
                            <x-slot:icon>
                                <flux:icon.globe-alt class="size-4 text-emerald-600 dark:text-emerald-300" />
                            </x-slot:icon>

                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $this->docType->es_name }}</flux:text>
                        </x-show.detail-item>

                        <x-show.detail-item :label="__('identification_document_types.show.fields.sort_order')">
                            <x-slot:icon>
                                <flux:icon.arrows-up-down class="size-4 text-violet-500 dark:text-violet-300" />
                            </x-slot:icon>

                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $this->docType->sort_order }}</flux:text>
                        </x-show.detail-item>

                        <x-show.detail-item :label="__('identification_document_types.show.fields.active')">
                            <x-slot:icon>
                                <flux:icon.power class="size-4 text-emerald-600 dark:text-emerald-300" />
                            </x-slot:icon>

                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">
                                {{ $this->docType->is_active ? __('identification_document_types.show.status.active') : __('identification_document_types.show.status.inactive') }}
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
                    :title="__('identification_document_types.show.quick_actions.title')"
                    class="space-y-2"
                >
                    <flux:button
                        variant="primary"
                        icon="arrow-left"
                        class="w-full"
                        :href="route('identification-document-types.index')"
                        wire:navigate
                    >
                        {{ __('actions.back') }}
                    </flux:button>

                    @if ($this->canDelete())
                        <flux:button
                            variant="danger"
                            icon="trash"
                            class="w-full"
                            wire:click="confirmDocTypeDeletion"
                        >
                            {{ __('identification_document_types.show.quick_actions.delete.action') }}
                        </flux:button>
                    @endif
                </x-show.sidebar-group>

                <flux:separator variant="subtle" />

                <x-show.sidebar-group :title="__('identification_document_types.show.stats.title')">
                    <div class="grid gap-2.5 sm:gap-3">
                        <x-show.stat-item :label="__('identification_document_types.show.stats.doc_type_id')">
                            <x-slot:icon class="bg-zinc-100 text-zinc-700 dark:bg-zinc-400/14 dark:text-zinc-200">
                                <flux:icon.hashtag class="size-5" />
                            </x-slot:icon>

                            <flux:text class="font-medium text-zinc-900 dark:text-white">{{ $this->docType->id }}</flux:text>
                        </x-show.stat-item>

                        <x-show.stat-item :label="__('identification_document_types.show.stats.associated_users')">
                            <x-slot:icon class="bg-sky-100 text-sky-700 dark:bg-sky-400/14 dark:text-sky-200">
                                <flux:icon.users class="size-5" />
                            </x-slot:icon>

                            <flux:text class="font-medium text-zinc-900 dark:text-white">{{ $this->associatedUsersCount }}</flux:text>
                        </x-show.stat-item>

                        <x-show.stat-item :label="__('identification_document_types.show.stats.updated')">
                            <x-slot:icon class="bg-amber-100 text-amber-700 dark:bg-amber-400/14 dark:text-amber-200">
                                <flux:icon.sparkles class="size-5" />
                            </x-slot:icon>

                            <flux:text class="font-medium text-zinc-900 dark:text-white" :title="$this->formatDateTooltip($this->docType->updated_at)">{{ $this->formatDate($this->docType->updated_at) }}</flux:text>
                        </x-show.stat-item>
                    </div>
                </x-show.sidebar-group>
            </div>
        </x-show.panel>
    </x-slot:aside>
</x-show.layout>
