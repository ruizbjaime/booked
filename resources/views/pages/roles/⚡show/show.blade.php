<x-show.layout
    :sr-title="__('roles.show.placeholder_title')"
    :heading="__('roles.show.title')"
    :subheading="__('roles.show.description')"
>
    <x-show.back-button :href="route('roles.index')" />

    <div class="space-y-3 sm:space-y-4">
        <x-show.panel>
            <div class="flex items-center gap-3 sm:gap-5">
                <div class="flex size-10 items-center justify-center rounded-lg bg-sky-500/15 sm:size-14">
                    <flux:icon.shield-check class="size-5 text-sky-300 sm:size-7" />
                </div>

                <div class="min-w-0 space-y-1">
                    <flux:heading size="lg" class="min-w-0 leading-tight">{{ $this->role->localizedLabel() }}</flux:heading>
                    <div class="flex items-center gap-2">
                        <flux:badge size="sm" :color="$this->role->color">{{ $this->role->name }}</flux:badge>
                    </div>
                </div>
            </div>
        </x-show.panel>

        <x-show.panel>
            <x-show.section
                :title="__('roles.show.sections.details')"
                :description="__('roles.show.sections.details_description')"
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
                        <flux:input
                            wire:model="targetRole.name"
                            name="name"
                            id="role-show-name"
                            :label="__('roles.show.fields.name')"
                            disabled
                        />

                        <div class="grid items-start gap-4 sm:grid-cols-2">
                            <flux:input
                                wire:model.live.blur="en_label"
                                name="en_label"
                                id="role-show-en-label"
                                :label="__('roles.show.fields.en_label')"
                            />

                            <flux:input
                                wire:model.live.blur="es_label"
                                name="es_label"
                                id="role-show-es-label"
                                :label="__('roles.show.fields.es_label')"
                            />
                        </div>

                        <div class="grid items-start gap-4 sm:grid-cols-2">
                            <flux:select
                                wire:model.live.blur="color"
                                name="color"
                                id="role-show-color"
                                :label="__('roles.show.fields.color')"
                            >
                                @foreach (\App\Actions\Roles\CreateRole::AVAILABLE_COLORS as $colorOption)
                                    <flux:select.option :value="$colorOption">{{ ucfirst($colorOption) }}</flux:select.option>
                                @endforeach
                            </flux:select>

                            <flux:input
                                wire:model.live.blur="sort_order"
                                name="sort_order"
                                id="role-show-sort-order"
                                :label="__('roles.show.fields.sort_order')"
                                type="number"
                                min="0"
                            />
                        </div>

                        <x-show.switch-card
                            :title="__('roles.show.fields.active')"
                            :status-text="$is_active ? __('roles.show.status.active') : __('roles.show.status.inactive')"
                            :active="$is_active"
                            status-color="emerald"
                        >
                            <x-slot:control>
                                <flux:switch
                                    wire:model.live="is_active"
                                    name="is_active"
                                    id="role-show-active"
                                    :aria-label="__('roles.show.fields.active')"
                                    :disabled="($this->associatedUsersCount > 0 && $is_active) || ($this->isSystemRole && $is_active)"
                                    class="self-start sm:self-auto"
                                />
                            </x-slot:control>
                        </x-show.switch-card>

                        <x-show.switch-card
                            :title="__('roles.show.fields.default')"
                            :status-text="$is_default ? __('roles.show.status.is_default') : __('roles.show.status.not_default')"
                            :active="$is_default"
                            status-color="sky"
                        >
                            <x-slot:control>
                                <flux:switch
                                    wire:model.live="is_default"
                                    name="is_default"
                                    id="role-show-default"
                                    :aria-label="__('roles.show.fields.default')"
                                    :disabled="$this->isDefaultSwitchDisabled"
                                    class="self-start sm:self-auto"
                                />
                            </x-slot:control>
                        </x-show.switch-card>

                        <x-show.autosave-notice :message="__('roles.show.autosave.details')" />
                    </div>
                @else
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-show.detail-item :label="__('roles.show.fields.name')">
                            <x-slot:icon>
                                <flux:icon.shield-check class="size-4 text-sky-500 dark:text-sky-300" />
                            </x-slot:icon>

                            <flux:badge size="sm" :color="$this->role->color">{{ $this->role->name }}</flux:badge>
                        </x-show.detail-item>

                        <x-show.detail-item :label="__('roles.show.fields.en_label')">
                            <x-slot:icon>
                                <flux:icon.globe-alt class="size-4 text-emerald-600 dark:text-emerald-300" />
                            </x-slot:icon>

                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $this->role->en_label }}</flux:text>
                        </x-show.detail-item>

                        <x-show.detail-item :label="__('roles.show.fields.es_label')">
                            <x-slot:icon>
                                <flux:icon.globe-alt class="size-4 text-emerald-600 dark:text-emerald-300" />
                            </x-slot:icon>

                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $this->role->es_label }}</flux:text>
                        </x-show.detail-item>

                        <x-show.detail-item :label="__('roles.show.fields.color')">
                            <x-slot:icon>
                                <flux:icon.swatch class="size-4 text-violet-500 dark:text-violet-300" />
                            </x-slot:icon>

                            <flux:badge size="sm" :color="$this->role->color">{{ ucfirst($this->role->color) }}</flux:badge>
                        </x-show.detail-item>

                        <x-show.detail-item :label="__('roles.show.fields.sort_order')">
                            <x-slot:icon>
                                <flux:icon.arrows-up-down class="size-4 text-violet-500 dark:text-violet-300" />
                            </x-slot:icon>

                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $this->role->sort_order }}</flux:text>
                        </x-show.detail-item>

                        <x-show.detail-item :label="__('roles.show.fields.active')">
                            <x-slot:icon>
                                <flux:icon.power class="size-4 text-emerald-600 dark:text-emerald-300" />
                            </x-slot:icon>

                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">
                                {{ $this->role->is_active ? __('roles.show.status.active') : __('roles.show.status.inactive') }}
                            </flux:text>
                        </x-show.detail-item>

                        <x-show.detail-item :label="__('roles.show.fields.default')">
                            <x-slot:icon>
                                <flux:icon.star class="size-4 text-sky-500 dark:text-sky-300" />
                            </x-slot:icon>

                            @if ($this->role->is_default)
                                <flux:badge size="sm" color="sky">{{ __('roles.index.columns.default') }}</flux:badge>
                            @else
                                <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">&mdash;</flux:text>
                            @endif
                        </x-show.detail-item>
                    </div>
                @endif
            </x-show.section>
        </x-show.panel>

        <x-show.panel>
            <x-show.section
                :title="__('roles.show.sections.permissions')"
                :description="__('roles.show.sections.permissions_description')"
            >
                <x-slot:icon class="bg-amber-500/15 text-amber-300">
                    <flux:icon.key class="size-5" />
                </x-slot:icon>

                @if ($this->canEdit())
                    <x-slot:actions>
                        <x-show.section-toggle section="permissions" :editing-section="$editingSection" />
                    </x-slot:actions>
                @endif

                @if ($editingSection === 'permissions')
                    <div class="space-y-6">
                        @foreach ($this->permissionsByModel as $modelKey => $permissions)
                            <div class="space-y-3">
                                <flux:heading size="sm" class="inline-flex items-center gap-1.5">
                                    <flux:icon.shield-check class="size-4 text-sky-500 dark:text-sky-300" />
                                    {{ \App\Domain\Auth\PermissionRegistry::modelLabel($modelKey) }}
                                </flux:heading>

                                <flux:checkbox.group wire:model.live="selectedPermissions" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                    @foreach ($permissions as $permission)
                                        @php
                                            $isProtected = $this->isProtectedPermission($permission);
                                            $isSelected = in_array($permission, $selectedPermissions, true);
                                            $ability = \Illuminate\Support\Str::after($permission, '.');
                                        @endphp

                                        <div
                                            wire:key="perm-{{ $permission }}"
                                            @class([
                                                'group flex items-start gap-3 rounded-xl border px-3 py-2.5 transition',
                                                'border-zinc-300 bg-white shadow-sm ring-1 ring-inset ring-zinc-200 dark:border-white/20 dark:bg-white/6 dark:ring-white/6' => $isSelected,
                                                'border-zinc-200 bg-zinc-50 hover:border-zinc-300 hover:bg-white dark:border-white/8 dark:bg-white/3 dark:hover:border-white/14 dark:hover:bg-white/4' => ! $isSelected,
                                                'opacity-60' => $isProtected,
                                            ])
                                        >
                                            <flux:checkbox
                                                value="{{ $permission }}"
                                                :disabled="$isProtected"
                                                :label="\App\Domain\Auth\PermissionRegistry::abilityLabel($ability)"
                                            />
                                        </div>
                                    @endforeach
                                </flux:checkbox.group>
                            </div>
                        @endforeach

                        <div class="flex justify-stretch sm:justify-end">
                            <flux:button
                                variant="primary"
                                size="sm"
                                wire:click="savePermissions"
                                class="w-full sm:w-auto"
                            >
                                {{ __('roles.show.permissions.save') }}
                            </flux:button>
                        </div>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach ($this->permissionsByModel as $modelKey => $permissions)
                            <div class="space-y-2">
                                <flux:heading size="sm">{{ \App\Domain\Auth\PermissionRegistry::modelLabel($modelKey) }}</flux:heading>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($permissions as $permission)
                                        @php
                                            $isGranted = in_array($permission, $selectedPermissions, true);
                                            $ability = \Illuminate\Support\Str::after($permission, '.');
                                        @endphp

                                        <flux:badge size="sm" :color="$isGranted ? 'emerald' : 'zinc'">
                                            {{ \App\Domain\Auth\PermissionRegistry::abilityLabel($ability) }}
                                        </flux:badge>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-show.section>
        </x-show.panel>
    </div>

    <x-slot:aside>
        <x-show.panel class="relative overflow-hidden border">
            <div class="relative space-y-4 p-3 sm:space-y-6 sm:p-5">
                <x-show.sidebar-group
                    :title="__('roles.show.quick_actions.title')"
                    class="space-y-2"
                >
                    <flux:button
                        variant="primary"
                        icon="arrow-left"
                        class="w-full"
                        :href="route('roles.index')"
                        wire:navigate
                    >
                        {{ __('actions.back') }}
                    </flux:button>

                    @if ($this->canDelete())
                        <flux:button
                            variant="danger"
                            icon="trash"
                            class="w-full"
                            wire:click="confirmRoleDeletion"
                        >
                            {{ __('roles.show.quick_actions.delete.action') }}
                        </flux:button>
                    @endif
                </x-show.sidebar-group>

                <flux:separator variant="subtle" />

                <x-show.sidebar-group :title="__('roles.show.stats.title')">
                    <div class="grid gap-2.5 sm:gap-3">
                        <x-show.stat-item :label="__('roles.show.stats.role_id')">
                            <x-slot:icon class="bg-zinc-100 text-zinc-700 dark:bg-zinc-400/14 dark:text-zinc-200">
                                <flux:icon.hashtag class="size-5" />
                            </x-slot:icon>

                            <flux:text class="font-medium text-zinc-900 dark:text-white">{{ $this->role->id }}</flux:text>
                        </x-show.stat-item>

                        <x-show.stat-item :label="__('roles.show.stats.assigned_users')">
                            <x-slot:icon class="bg-sky-100 text-sky-700 dark:bg-sky-400/14 dark:text-sky-200">
                                <flux:icon.users class="size-5" />
                            </x-slot:icon>

                            <flux:text class="font-medium text-zinc-900 dark:text-white">{{ $this->associatedUsersCount }}</flux:text>
                        </x-show.stat-item>

                        <x-show.stat-item :label="__('roles.show.stats.updated')">
                            <x-slot:icon class="bg-amber-100 text-amber-700 dark:bg-amber-400/14 dark:text-amber-200">
                                <flux:icon.sparkles class="size-5" />
                            </x-slot:icon>

                            <flux:text class="font-medium text-zinc-900 dark:text-white" :title="$this->formatDateTooltip($this->role->updated_at)">{{ $this->formatDate($this->role->updated_at) }}</flux:text>
                        </x-show.stat-item>
                    </div>
                </x-show.sidebar-group>
            </div>
        </x-show.panel>
    </x-slot:aside>
</x-show.layout>
