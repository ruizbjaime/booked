<x-show.layout
    :sr-title="__('users.show.placeholder_title')"
    :heading="__('users.show.title')"
    :subheading="__('users.show.description')"
>
    <x-show.back-button :href="route('users.index')" />

    <div class="space-y-3 sm:space-y-4">
        <x-show.panel>
            <div class="grid min-w-0 grid-cols-[auto_minmax(0,1fr)] items-center gap-x-3 sm:gap-x-5">
                <div class="relative" wire:loading.class="pointer-events-none" wire:target="photo">
                    <flux:avatar
                        size="lg"
                        :src="$this->userAvatarUrl"
                        :initials="$this->user->initials()"
                        color="auto"
                        :color:seed="$this->user->id"
                        class="sm:!size-14"
                    />

                    @if ($this->canEdit())
                        <x-image-editor wire:model="photo" aspect-ratio="1:1" input-id="avatar-upload" :maxSizeMb="$this->maxUploadSizeMb">
                            <label for="avatar-upload" class="group absolute inset-0 z-[5] cursor-pointer overflow-hidden rounded-[var(--radius-lg)]" wire:loading.class="!hidden" wire:target="photo">
                                <div class="flex size-full items-center justify-center bg-black/40 opacity-0 backdrop-blur-xs transition-opacity duration-200 group-hover:opacity-100">
                                    <flux:icon.camera class="size-4.5 text-white drop-shadow" />
                                </div>
                            </label>

                            <div class="absolute inset-0 z-[5] flex items-center justify-center overflow-hidden rounded-[var(--radius-lg)] bg-black/40 backdrop-blur-xs" wire:loading.flex wire:target="photo">
                                <flux:icon.loading class="size-5 text-white" />
                            </div>

                            <div wire:loading.class="!hidden" wire:target="photo">
                                @if ($this->userAvatarUrl)
                                    <button
                                        type="button"
                                        wire:click="deleteAvatar"
                                        class="absolute bottom-0 right-0 z-20 flex size-5 translate-x-1/4 translate-y-1/4 cursor-pointer items-center justify-center rounded-full bg-rose-500 shadow-md transition-all duration-150 hover:scale-110 hover:bg-rose-600 dark:bg-rose-400 dark:hover:bg-rose-500"
                                        aria-label="{{ __('users.show.avatar_delete_label') }}"
                                    >
                                        <flux:icon.x-mark variant="micro" class="size-3 text-white" />
                                    </button>
                                @else
                                    <label
                                        for="avatar-upload"
                                        class="absolute bottom-0 right-0 z-20 flex size-5 translate-x-1/4 translate-y-1/4 cursor-pointer items-center justify-center rounded-full bg-sky-500 shadow-md transition-all duration-150 hover:scale-110 hover:bg-sky-600 dark:bg-sky-400 dark:hover:bg-sky-500"
                                        aria-label="{{ __('users.show.avatar_add_label') }}"
                                    >
                                        <flux:icon.plus variant="micro" class="size-3 text-white" />
                                    </label>
                                @endif
                            </div>
                        </x-image-editor>
                    @endif
                </div>

                <div class="min-w-0 space-y-1 sm:space-y-2">
                    <div>
                        <flux:heading size="lg" class="min-w-0 leading-tight">{{ $this->user->name }}</flux:heading>
                    </div>

                    <span class="flex min-w-0 items-start gap-2 sm:items-center">
                        <flux:icon.envelope class="mt-0.5 size-4 shrink-0 text-amber-400 dark:text-amber-300 sm:mt-0" />
                        <flux:link class="block min-w-0 truncate text-sm" variant="subtle" href="mailto:{{ $this->user->email }}">
                            {{ $this->user->email }}
                        </flux:link>
                    </span>
                </div>
            </div>
        </x-show.panel>

        <x-show.panel>
            <x-show.section
                :title="__('users.show.sections.account')"
                :description="__('users.show.sections.account_description')"
            >
                <x-slot:icon class="bg-sky-500/15 text-sky-300">
                    <flux:icon.identification class="size-5" />
                </x-slot:icon>

                @if ($this->canEdit())
                    <x-slot:actions>
                        <x-show.section-toggle section="account" :editing-section="$editingSection" />
                    </x-slot:actions>
                @endif

                @if ($editingSection === 'account')
                    <div class="grid items-start gap-4 sm:grid-cols-2">
                        <flux:input
                            wire:model.live.blur="name"
                            name="name"
                            id="user-show-name"
                            :label="__('users.show.fields.name')"
                            autocomplete="name"
                        />

                        <flux:input
                            wire:model.live.blur="email"
                            type="email"
                            name="user_show_email"
                            id="user-show-email"
                            :label="__('users.show.fields.email')"
                            autocomplete="section-user-show email"
                        />
                    </div>

                    <x-show.autosave-notice :message="__('users.show.autosave.account')" />
                @else
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-show.detail-item :label="__('users.show.fields.name')">
                            <x-slot:icon>
                                <flux:icon.user class="size-4 text-emerald-600 dark:text-emerald-300" />
                            </x-slot:icon>

                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $this->user->name }}</flux:text>
                        </x-show.detail-item>

                        <x-show.detail-item :label="__('users.show.fields.email')">
                            <x-slot:icon>
                                <flux:icon.envelope class="size-4 text-amber-500 dark:text-amber-300" />
                            </x-slot:icon>

                            <flux:link href="mailto:{{ $this->user->email }}" class="block min-w-0 break-all text-base font-semibold text-zinc-900 decoration-zinc-300 underline-offset-4 hover:text-amber-600 dark:text-white dark:decoration-white/30 dark:hover:text-amber-200">
                                {{ $this->user->email }}
                            </flux:link>
                        </x-show.detail-item>
                    </div>
                @endif
            </x-show.section>
        </x-show.panel>

        <x-show.panel>
            <x-show.section
                :title="__('users.show.sections.personal')"
                :description="__('users.show.sections.personal_description')"
            >
                <x-slot:icon class="bg-sky-500/15 text-sky-300">
                    <flux:icon.user-circle class="size-5" />
                </x-slot:icon>

                @if ($this->canEdit())
                    <x-slot:actions>
                        <x-show.section-toggle section="personal" :editing-section="$editingSection" />
                    </x-slot:actions>
                @endif

                @if ($editingSection === 'personal')
                    <div
                        class="space-y-4"
                        x-data="{
                            countryCodes: @js($this->countries->pluck('phone_code', 'id')),
                            autoFillPrefix(countryId) {
                                if ($wire.phone === '' || $wire.phone === null) {
                                    const code = this.countryCodes[countryId];
                                    if (code) {
                                        $wire.phone = code;
                                    }
                                }
                            }
                        }"
                    >
                        <div class="grid items-start gap-4 sm:grid-cols-2">
                            <flux:input
                                wire:model.live.blur="phone"
                                type="tel"
                                :label="__('users.show.fields.phone')"
                                autocomplete="tel"
                            />

                            <flux:select
                                wire:model.live="country_id"
                                variant="listbox"
                                searchable
                                clearable
                                :filter="false"
                                :label="__('users.show.fields.country')"
                                :placeholder="__('users.show.fields.country')"
                                x-on:change="autoFillPrefix($event.target.value)"
                            >
                                <x-slot name="search">
                                    <flux:select.search wire:model.live.debounce.200ms="countrySearch" :placeholder="__('actions.search')" />
                                </x-slot>

                                @foreach ($this->countries as $country)
                                    <flux:select.option :value="$country->id" wire:key="show-country-{{ $country->id }}">
                                        {{ $country->localizedName() }} ({{ $country->phone_code }})
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>

                        <div class="grid items-start gap-4 sm:grid-cols-2">
                            <flux:select
                                wire:model.live="document_type_id"
                                variant="listbox"
                                clearable
                                :label="__('users.show.fields.document_type')"
                            >
                                @foreach ($this->documentTypes as $docType)
                                    <flux:select.option :value="$docType->id" wire:key="show-doc-type-{{ $docType->id }}">
                                        {{ $docType->localizedName() }} ({{ $docType->code }})
                                    </flux:select.option>
                                @endforeach
                            </flux:select>

                            <flux:input
                                wire:model.live.blur="document_number"
                                :label="__('users.show.fields.document_number')"
                            />
                        </div>

                        <div class="grid items-start gap-4 sm:grid-cols-2">
                            <flux:input
                                wire:model.live.blur="state"
                                :label="__('users.show.fields.state')"
                            />

                            <flux:input
                                wire:model.live.blur="city"
                                :label="__('users.show.fields.city')"
                            />
                        </div>

                        <flux:textarea
                            wire:model.live.blur="address"
                            rows="2"
                            :label="__('users.show.fields.address')"
                        />

                        <x-show.autosave-notice :message="__('users.show.autosave.personal')" />
                    </div>
                @else
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-show.detail-item :label="__('users.show.fields.phone')">
                            <x-slot:icon>
                                <flux:icon.phone class="size-4 text-sky-500 dark:text-sky-300" />
                            </x-slot:icon>

                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">
                                {{ $this->user->phone ?: '—' }}
                            </flux:text>
                        </x-show.detail-item>

                        <x-show.detail-item :label="__('users.show.fields.country')">
                            <x-slot:icon>
                                <flux:icon.globe-alt class="size-4 text-emerald-500 dark:text-emerald-300" />
                            </x-slot:icon>

                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">
                                {{ $this->user->country?->localizedName() ?: '—' }}
                            </flux:text>
                        </x-show.detail-item>

                        <x-show.detail-item :label="__('users.show.fields.document_type')">
                            <x-slot:icon>
                                <flux:icon.document-text class="size-4 text-amber-500 dark:text-amber-300" />
                            </x-slot:icon>

                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">
                                {{ $this->user->documentType?->localizedName() ?: '—' }}
                            </flux:text>
                        </x-show.detail-item>

                        <x-show.detail-item :label="__('users.show.fields.document_number')">
                            <x-slot:icon>
                                <flux:icon.hashtag class="size-4 text-rose-500 dark:text-rose-300" />
                            </x-slot:icon>

                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">
                                {{ $this->user->document_number ?: '—' }}
                            </flux:text>
                        </x-show.detail-item>

                        <x-show.detail-item :label="__('users.show.fields.state')">
                            <x-slot:icon>
                                <flux:icon.map-pin class="size-4 text-violet-500 dark:text-violet-300" />
                            </x-slot:icon>

                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">
                                {{ $this->user->state ?: '—' }}
                            </flux:text>
                        </x-show.detail-item>

                        <x-show.detail-item :label="__('users.show.fields.city')">
                            <x-slot:icon>
                                <flux:icon.building-office class="size-4 text-violet-500 dark:text-violet-300" />
                            </x-slot:icon>

                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">
                                {{ $this->user->city ?: '—' }}
                            </flux:text>
                        </x-show.detail-item>
                    </div>

                    @if ($this->user->address)
                        <x-show.detail-item :label="__('users.show.fields.address')">
                            <x-slot:icon>
                                <flux:icon.home class="size-4 text-zinc-500 dark:text-zinc-300" />
                            </x-slot:icon>

                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">
                                {{ $this->user->address }}
                            </flux:text>
                        </x-show.detail-item>
                    @endif
                @endif
            </x-show.section>
        </x-show.panel>

        <x-show.panel>
            <x-show.section
                :title="__('users.show.sections.access')"
                :description="__('users.show.sections.access_description')"
            >
                <x-slot:icon class="bg-sky-500/15 text-sky-300">
                    <flux:icon.lock-closed class="size-5" />
                </x-slot:icon>

                @if ($this->canEdit())
                    <x-slot:actions>
                        <x-show.section-toggle section="access" :editing-section="$editingSection" />
                    </x-slot:actions>
                @endif

                @if ($editingSection === 'access')
                    <div class="space-y-4">
                        <div class="grid items-start gap-4 sm:grid-cols-2">
                            <flux:input
                                wire:model.live="password"
                                type="password"
                                name="new_password"
                                id="show-user-password"
                                error:name="password"
                                :label="__('users.show.fields.password')"
                                autocomplete="new-password"
                                viewable
                            />

                            <flux:input
                                wire:model.live="password_confirmation"
                                type="password"
                                name="new_password_confirmation"
                                id="show-user-password-confirmation"
                                error:name="password_confirmation"
                                :label="__('users.show.fields.password_confirmation')"
                                autocomplete="new-password"
                                viewable
                            />
                        </div>

                        <div class="flex justify-stretch sm:justify-end">
                            <flux:button
                                variant="primary"
                                size="sm"
                                wire:click="updatePassword"
                                :disabled="blank($password) || blank($password_confirmation)"
                                class="w-full sm:w-auto"
                            >
                                {{ __('users.show.actions.update_password') }}
                            </flux:button>
                        </div>

                        <x-users.role-selector
                            :available-roles="$availableRoles"
                            :selected-roles="$roles"
                            key-prefix="show-user-role"
                            :label="__('users.show.fields.roles')"
                            :description="__('users.show.roles_help')"
                            :role-labeler="fn (string $role) => $this->roleLabel($role)"
                            icon="users"
                            icon-class="text-sky-500 dark:text-sky-300"
                        />

                        <div class="flex justify-stretch sm:justify-end">
                            <flux:button
                                variant="primary"
                                size="sm"
                                wire:click="saveRoles"
                                :disabled="! $this->rolesChanged()"
                                class="w-full sm:w-auto"
                            >
                                {{ __('users.show.actions.update_roles') }}
                            </flux:button>
                        </div>

                        <x-show.switch-card
                            :title="__('users.show.fields.active')"
                            :description="__('users.show.active_help')"
                            :status-text="$active ? __('users.show.status.active') : __('users.show.status.inactive')"
                            :active="$active"
                            status-color="emerald"
                        >
                            <x-slot:control>
                                <flux:switch
                                    wire:model.live="active"
                                    name="active"
                                    id="show-user-active"
                                    :disabled="! $this->canToggleActive()"
                                    :aria-label="__('users.show.fields.active')"
                                    class="self-start sm:self-auto"
                                />
                            </x-slot:control>
                        </x-show.switch-card>

                        <x-show.switch-card
                            :title="__('users.show.fields.two_factor')"
                            :description="__('users.show.two_factor.help')"
                            :status-text="$this->twoFactorStatusLabel()"
                            :active="$twoFactorValue"
                            status-color="amber"
                        >
                            <x-slot:control>
                                <flux:switch
                                    wire:model.live="twoFactorValue"
                                    name="two_factor"
                                    id="show-user-two-factor"
                                    :disabled="! $this->canManageTwoFactor()"
                                    :aria-label="__('users.show.fields.two_factor')"
                                    class="self-start sm:self-auto"
                                />
                            </x-slot:control>
                        </x-show.switch-card>

                        <x-show.autosave-notice :message="__('users.show.autosave.access_partial')" />
                    </div>
                @else
                    <div class="grid gap-4">
                        <x-show.detail-item :label="__('users.show.fields.password')">
                            <x-slot:icon>
                                <flux:icon.lock-closed class="size-4 text-rose-500 dark:text-rose-300" />
                            </x-slot:icon>

                            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">
                                {{ __('users.show.status.password_hidden') }}
                            </flux:text>
                        </x-show.detail-item>

                        <x-show.detail-item :label="__('users.show.fields.roles')">
                            <x-slot:icon>
                                <flux:icon.users class="size-4 text-sky-500 dark:text-sky-300" />
                            </x-slot:icon>

                            <div class="flex flex-wrap gap-2">
                                @forelse ($this->user->roles as $role)
                                    <flux:badge size="sm" :color="$role->color">
                                        {{ $role->localizedLabel() }}
                                    </flux:badge>
                                @empty
                                    <flux:badge size="sm" color="zinc">{{ __('users.show.status.no_role') }}</flux:badge>
                                @endforelse
                            </div>
                        </x-show.detail-item>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-show.detail-item :label="__('users.show.fields.active')">
                                <x-slot:icon>
                                    <flux:icon.power class="size-4 text-emerald-600 dark:text-emerald-300" />
                                </x-slot:icon>

                                <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">
                                    {{ $this->user->is_active ? __('users.show.status.active') : __('users.show.status.inactive') }}
                                </flux:text>
                            </x-show.detail-item>

                            <x-show.detail-item :label="__('users.show.fields.two_factor')">
                                <x-slot:icon>
                                    <flux:icon.key class="size-4 text-amber-500 dark:text-amber-300" />
                                </x-slot:icon>

                                <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">
                                    {{ $this->twoFactorStatusLabel() }}
                                </flux:text>
                            </x-show.detail-item>
                        </div>
                    </div>
                @endif
            </x-show.section>
        </x-show.panel>
    </div>

    <x-slot:aside>
        <x-show.panel class="relative overflow-hidden border">
            <div class="relative space-y-4 p-3 sm:space-y-6 sm:p-5">
                <x-show.sidebar-group
                    :title="__('users.show.quick_actions.title')"
                    class="space-y-2"
                >
                    <flux:button
                        variant="primary"
                        icon="arrow-left"
                        class="w-full"
                        :href="route('users.index')"
                        wire:navigate
                    >
                        {{ __('actions.back') }}
                    </flux:button>

                    @if ($this->canDelete())
                        <flux:button
                            variant="danger"
                            icon="trash"
                            class="w-full"
                            wire:click="confirmUserDeletion"
                        >
                            {{ __('users.show.quick_actions.delete.action') }}
                        </flux:button>
                    @endif
                </x-show.sidebar-group>

                <flux:separator variant="subtle" />

                <x-show.sidebar-group :title="__('users.show.stats.title')">
                    <x-show.progress-card
                        :label="__('users.show.stats.profile_completion')"
                        :percentage="$this->profileCompletionPercentage()"
                        :badge-text="$this->profileReadinessText()"
                        :badge-classes="$this->completionToneClasses()"
                    />

                    <div class="grid gap-2.5 sm:gap-3">
                        <x-show.stat-item :label="__('users.show.stats.user_id')">
                            <x-slot:icon class="bg-zinc-100 text-zinc-700 dark:bg-zinc-400/14 dark:text-zinc-200">
                                <flux:icon.hashtag class="size-5" />
                            </x-slot:icon>

                            <flux:text class="font-medium text-zinc-900 dark:text-white">{{ $this->user->id }}</flux:text>
                        </x-show.stat-item>

                        <x-show.stat-item :label="__('users.show.stats.last_access')">
                            <x-slot:icon class="bg-sky-100 text-sky-700 dark:bg-sky-400/14 dark:text-sky-200">
                                <flux:icon.clock class="size-5" />
                            </x-slot:icon>

                            <flux:text class="font-medium text-zinc-900 dark:text-white">{{ $this->lastAccessText() }}</flux:text>
                        </x-show.stat-item>

                        <x-show.stat-item :label="__('users.show.stats.security_score')">
                            <x-slot:icon class="bg-emerald-100 text-emerald-700 dark:bg-emerald-400/14 dark:text-emerald-200">
                                <flux:icon.shield-check class="size-5" />
                            </x-slot:icon>

                            <flux:text class="font-medium text-zinc-900 dark:text-white">{{ $this->securityScoreText() }}</flux:text>
                        </x-show.stat-item>

                        <x-show.stat-item :label="__('users.show.stats.updated')">
                            <x-slot:icon class="bg-amber-100 text-amber-700 dark:bg-amber-400/14 dark:text-amber-200">
                                <flux:icon.sparkles class="size-5" />
                            </x-slot:icon>

                            <flux:text class="font-medium text-zinc-900 dark:text-white" :title="$this->formatDateTooltip($this->user->updated_at)">{{ $this->formatDate($this->user->updated_at) }}</flux:text>
                        </x-show.stat-item>
                    </div>
                </x-show.sidebar-group>
            </div>
        </x-show.panel>
    </x-slot:aside>

    <x-slot:after>
        <flux:modal
            name="user-two-factor-setup"
            class="max-w-md md:min-w-md"
            @close="closeTwoFactorModal"
            wire:model="showTwoFactorModal"
        >
            @php($twoFactorModal = $this->twoFactorModalConfig())

            <div class="space-y-6">
                <div class="flex flex-col items-center space-y-4">
                    <div class="w-auto rounded-full border border-zinc-100 bg-white p-0.5 shadow-sm dark:border-zinc-600 dark:bg-zinc-800">
                        <div class="relative overflow-hidden rounded-full border border-zinc-200 bg-zinc-100 p-2.5 dark:border-zinc-600 dark:bg-zinc-200">
                            <flux:icon.qr-code class="relative z-20 dark:text-accent-foreground" />
                        </div>
                    </div>

                    <div class="space-y-2 text-center">
                        <flux:heading size="lg">{{ $twoFactorModal['title'] }}</flux:heading>
                        <flux:text>{{ $twoFactorModal['description'] }}</flux:text>
                    </div>
                </div>

                @if ($showTwoFactorVerificationStep)
                    <div class="space-y-6">
                        <div class="flex justify-center">
                            <flux:otp
                                name="two_factor_code"
                                wire:model="twoFactorCode"
                                error:name="twoFactorCode"
                                length="6"
                                :label="__('users.show.two_factor.code_label')"
                                label:sr-only
                                class="mx-auto"
                            />
                        </div>

                        <div class="flex items-center space-x-3">
                            <flux:button variant="outline" class="flex-1" wire:click="resetTwoFactorVerification">
                                {{ __('actions.back') }}
                            </flux:button>

                            <flux:button variant="primary" class="flex-1" wire:click="confirmTwoFactor" x-bind:disabled="$wire.twoFactorCode.length < 6">
                                {{ __('actions.confirm') }}
                            </flux:button>
                        </div>
                    </div>
                @else
                    <div class="flex justify-center">
                        <div class="relative aspect-square w-full max-w-64 overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                            @empty($twoFactorQrCodeSvg)
                                <div class="absolute inset-0 flex animate-pulse items-center justify-center bg-white dark:bg-zinc-700">
                                    <flux:icon.loading />
                                </div>
                            @else
                                <div x-data class="flex h-full items-center justify-center p-4">
                                    <div
                                        class="rounded bg-white p-3"
                                        :style="($flux.appearance === 'dark' || ($flux.appearance === 'system' && $flux.dark)) ? 'filter: invert(1) brightness(1.5)' : ''"
                                    >
                                        {!! $twoFactorQrCodeSvg !!}
                                    </div>
                                </div>
                            @endempty
                        </div>
                    </div>

                    <div>
                        <flux:button variant="primary" class="w-full" wire:click="advanceTwoFactorSetup">
                            {{ $twoFactorModal['buttonText'] }}
                        </flux:button>
                    </div>

                    <div class="space-y-4">
                        <div class="relative flex w-full items-center justify-center">
                            <div class="absolute inset-0 top-1/2 h-px w-full bg-zinc-200 dark:bg-zinc-600"></div>
                            <span class="relative bg-white px-2 text-sm text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                                {{ __('users.show.two_factor.manual_label') }}
                            </span>
                        </div>

                        <div
                            class="flex items-center space-x-2"
                            x-data="{
                                copied: false,
                                async copy() {
                                    try {
                                        await navigator.clipboard.writeText(@js($twoFactorManualSetupKey));
                                        this.copied = true;
                                        setTimeout(() => this.copied = false, 1500);
                                    } catch (e) {
                                        console.warn('Could not copy to clipboard');
                                    }
                                }
                            }"
                        >
                            <div class="flex w-full items-stretch rounded-xl border dark:border-zinc-700">
                                @empty($twoFactorManualSetupKey)
                                    <div class="flex w-full items-center justify-center bg-zinc-100 p-3 dark:bg-zinc-700">
                                        <flux:icon.loading variant="mini" />
                                    </div>
                                @else
                                    <input
                                        type="text"
                                        readonly
                                        value="{{ $twoFactorManualSetupKey }}"
                                        class="w-full bg-transparent p-3 text-zinc-900 outline-none dark:text-zinc-100"
                                    />

                                    <button @click="copy()" class="cursor-pointer border-l border-zinc-200 px-3 transition-colors dark:border-zinc-600">
                                        <flux:icon.document-duplicate x-show="!copied" variant="outline" />
                                        <flux:icon.check x-show="copied" variant="solid" class="text-emerald-500" />
                                    </button>
                                @endempty
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </flux:modal>
    </x-slot:after>
</x-show.layout>
