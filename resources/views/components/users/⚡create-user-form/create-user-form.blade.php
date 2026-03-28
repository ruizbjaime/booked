<form wire:submit="save" autocomplete="off" class="space-y-5">
    <div class="grid items-start gap-4 md:grid-cols-2">
        <flux:field>
            <flux:label class="inline-flex items-center gap-1.5">
                <flux:icon.user class="size-4 text-emerald-600 dark:text-emerald-300" />
                {{ __('users.create.fields.name') }}
            </flux:label>

            <flux:input
                wire:model.live.blur="name"
                name="name"
                id="create-user-name"
                required
                autocomplete="name"
            />

            <flux:error name="name" />
        </flux:field>

        <flux:field>
            <flux:label class="inline-flex items-center gap-1.5">
                <flux:icon.envelope class="size-4 text-amber-500 dark:text-amber-300" />
                {{ __('users.create.fields.email') }}
            </flux:label>

            <flux:input
                wire:model.live.blur="email"
                type="email"
                name="create_user_email"
                id="create-user-email"
                required
                autocomplete="section-create-user email"
            />

            <flux:error name="email" />
        </flux:field>
    </div>

    <div class="grid items-start gap-4 md:grid-cols-2">
        <flux:field>
            <flux:label class="inline-flex items-center gap-1.5">
                <flux:icon.lock-closed class="size-4 text-rose-500 dark:text-rose-300" />
                {{ __('users.create.fields.password') }}
            </flux:label>

            <flux:input
                wire:model.live.blur="password"
                type="password"
                name="new_password"
                id="create-user-password"
                error:name="password"
                required
                autocomplete="section-create-user new-password"
                viewable
            />

            <flux:error name="password" />
        </flux:field>

        <flux:field>
            <flux:label class="inline-flex items-center gap-1.5">
                <flux:icon.lock-closed class="size-4 text-rose-500 dark:text-rose-300" />
                {{ __('users.create.fields.password_confirmation') }}
            </flux:label>

            <flux:input
                wire:model.live.blur="password_confirmation"
                type="password"
                name="new_password_confirmation"
                id="create-user-password-confirmation"
                error:name="password_confirmation"
                required
                autocomplete="section-create-user new-password"
                viewable
            />

            <flux:error name="password_confirmation" />
        </flux:field>
    </div>

    <x-users.role-selector
        :available-roles="$availableRoles"
        :selected-roles="$roles"
        key-prefix="create-user-role"
        :label="__('users.create.fields.roles')"
        :description="__('users.create.roles_help')"
        icon="users"
        icon-class="text-sky-500 dark:text-sky-300"
    />

    <div class="grid items-start gap-4 md:grid-cols-2">
        <div class="rounded-2xl border border-white/8 bg-white/3 px-4 py-3.5 shadow-sm ring-1 ring-inset ring-white/4">
            <div class="flex items-start justify-between gap-4">
                <div class="space-y-1">
                    <flux:heading size="sm" class="inline-flex items-center gap-1.5">
                        <flux:icon.power class="size-4 text-emerald-600 dark:text-emerald-300" />
                        {{ __('users.create.fields.active') }}
                    </flux:heading>
                    <flux:text size="sm" class="max-w-xl text-zinc-500 dark:text-white/60">
                        {{ __('users.create.active_help') }}
                    </flux:text>
                </div>

                <flux:switch
                    wire:model.live="active"
                    name="active"
                    id="create-user-active"
                    :aria-label="__('users.create.fields.active')"
                />
            </div>

            <div class="mt-3 flex items-center gap-2">
                <span class="inline-flex size-2.5 rounded-full {{ $active ? 'bg-emerald-400 shadow-[0_0_0_4px_rgb(52_211_153_/_0.12)]' : 'bg-zinc-500/80 shadow-[0_0_0_4px_rgb(113_113_122_/_0.12)]' }}"></span>
                <flux:text size="sm" class="font-medium text-zinc-200 dark:text-zinc-100">
                    {{ $active ? __('users.create.active_enabled') : __('users.create.active_disabled') }}
                </flux:text>
            </div>
        </div>

        <div class="rounded-2xl border border-white/8 bg-white/3 px-4 py-3.5 shadow-sm ring-1 ring-inset ring-white/4">
            <div class="flex items-start justify-between gap-4">
                <div class="space-y-1">
                    <flux:heading size="sm" class="inline-flex items-center gap-1.5">
                        <flux:icon.check-badge class="size-4 text-sky-500 dark:text-sky-300" />
                        {{ __('users.create.fields.email_verified') }}
                    </flux:heading>
                    <flux:text size="sm" class="max-w-xl text-zinc-500 dark:text-white/60">
                        {{ __('users.create.email_verified_help') }}
                    </flux:text>
                </div>

                <flux:switch
                    wire:model.live="emailVerified"
                    name="email_verified"
                    id="create-user-email-verified"
                    :aria-label="__('users.create.fields.email_verified')"
                />
            </div>

            <div class="mt-3 flex items-center gap-2">
                <span class="inline-flex size-2.5 rounded-full {{ $emailVerified ? 'bg-sky-400 shadow-[0_0_0_4px_rgb(56_189_248_/_0.12)]' : 'bg-amber-400 shadow-[0_0_0_4px_rgb(251_191_36_/_0.12)]' }}"></span>
                <flux:text size="sm" class="font-medium text-zinc-200 dark:text-zinc-100">
                    {{ $emailVerified ? __('users.create.email_verified_enabled') : __('users.create.email_verified_disabled') }}
                </flux:text>
            </div>
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
            {{ __('users.create.submit') }}
        </flux:button>
    </div>
</form>
