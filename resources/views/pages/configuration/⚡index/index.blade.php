<section class="container mx-auto space-y-6">
    <x-heading :heading="__('configuration.index.title')" :subheading="__('configuration.index.description')" />

    {{-- Images --}}
    <form wire:submit="saveImages" class="space-y-6">
        <flux:card class="space-y-6">
            <div class="flex items-start gap-3">
                <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-sky-500/15 text-sky-300">
                    <flux:icon.photo class="size-5" />
                </div>

                <div>
                    <flux:heading size="lg">{{ __('configuration.index.sections.images') }}</flux:heading>
                    <flux:subheading>{{ __('configuration.index.sections.images_description') }}</flux:subheading>
                </div>
            </div>

            <flux:separator variant="subtle" />

            <div class="grid items-start gap-4 sm:grid-cols-2">
                <flux:input
                    wire:model.live.blur="avatar_size"
                    type="number"
                    min="50"
                    max="500"
                    :label="__('configuration.index.fields.avatar_size')"
                    :description="__('configuration.index.fields.avatar_size_help')"
                    suffix="px"
                />

                <flux:input
                    wire:model.live.blur="avatar_quality"
                    type="number"
                    min="1"
                    max="100"
                    :label="__('configuration.index.fields.avatar_quality')"
                    :description="__('configuration.index.fields.avatar_quality_help')"
                    suffix="%"
                />
            </div>

            <div class="grid items-start gap-4 sm:grid-cols-2">
                <flux:select
                    wire:model.live="avatar_format"
                    variant="listbox"
                    :label="__('configuration.index.fields.avatar_format')"
                    :description="__('configuration.index.fields.avatar_format_help')"
                >
                    @foreach (\App\Domain\Configuration\Enums\ImageFormat::cases() as $format)
                        <flux:select.option :value="$format->value" wire:key="format-{{ $format->value }}">
                            {{ strtoupper($format->value) }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input
                    wire:model.live.blur="max_upload_size_mb"
                    type="number"
                    min="1"
                    max="20"
                    :label="__('configuration.index.fields.max_upload_size_mb')"
                    :description="__('configuration.index.fields.max_upload_size_mb_help')"
                    suffix="MB"
                />
            </div>

            <flux:callout icon="server" color="sky" inline>
                <flux:callout.heading>{{ __('configuration.index.server_limits.title') }}</flux:callout.heading>
                <flux:callout.text>
                    {{ __('configuration.index.server_limits.upload_max_filesize') }}: <strong>{{ $this->serverLimits['upload_max_filesize'] }}</strong>
                    &nbsp;&middot;&nbsp;
                    {{ __('configuration.index.server_limits.post_max_size') }}: <strong>{{ $this->serverLimits['post_max_size'] }}</strong>
                </flux:callout.text>
            </flux:callout>

            <div class="flex justify-end">
                <flux:button variant="primary" type="submit" :disabled="! $this->imagesChanged">{{ __('actions.save') }}</flux:button>
            </div>
        </flux:card>
    </form>

    {{-- Tables --}}
    <form wire:submit="saveTables" class="space-y-6">
        <flux:card class="space-y-6">
            <div class="flex items-start gap-3">
                <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-emerald-500/15 text-emerald-300">
                    <flux:icon.table-cells class="size-5" />
                </div>

                <div>
                    <flux:heading size="lg">{{ __('configuration.index.sections.tables') }}</flux:heading>
                    <flux:subheading>{{ __('configuration.index.sections.tables_description') }}</flux:subheading>
                </div>
            </div>

            <flux:separator variant="subtle" />

            <flux:select
                wire:model.live="default_per_page"
                variant="listbox"
                :label="__('configuration.index.fields.default_per_page')"
                :description="__('configuration.index.fields.default_per_page_help')"
                class="sm:max-w-xs"
            >
                @foreach ([10, 15, 25, 50, 100] as $option)
                    <flux:select.option :value="$option" wire:key="per-page-{{ $option }}">
                        {{ $option }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex justify-end">
                <flux:button variant="primary" type="submit" :disabled="! $this->tablesChanged">{{ __('actions.save') }}</flux:button>
            </div>
        </flux:card>
    </form>

    {{-- Security --}}
    <form wire:submit="saveSecurity" class="space-y-6">
        <flux:card class="space-y-6">
            <div class="flex items-start gap-3">
                <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-amber-500/15 text-amber-300">
                    <flux:icon.shield-check class="size-5" />
                </div>

                <div>
                    <flux:heading size="lg">{{ __('configuration.index.sections.security') }}</flux:heading>
                    <flux:subheading>{{ __('configuration.index.sections.security_description') }}</flux:subheading>
                </div>
            </div>

            <flux:separator variant="subtle" />

            <div>
                <flux:heading>{{ __('configuration.index.sections.password_policy') }}</flux:heading>
                <flux:subheading>{{ __('configuration.index.sections.password_policy_description') }}</flux:subheading>
            </div>

            <flux:input
                wire:model.live.blur="password_min_length"
                type="number"
                min="8"
                max="128"
                :label="__('configuration.index.fields.password_min_length')"
                :description="__('configuration.index.fields.password_min_length_help')"
                class="sm:max-w-xs"
            />

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:switch
                    wire:model.live="password_require_mixed_case"
                    :label="__('configuration.index.fields.password_require_mixed_case')"
                    :description="__('configuration.index.fields.password_require_mixed_case_help')"
                />

                <flux:switch
                    wire:model.live="password_require_numbers"
                    :label="__('configuration.index.fields.password_require_numbers')"
                    :description="__('configuration.index.fields.password_require_numbers_help')"
                />

                <flux:switch
                    wire:model.live="password_require_symbols"
                    :label="__('configuration.index.fields.password_require_symbols')"
                    :description="__('configuration.index.fields.password_require_symbols_help')"
                />

                <flux:switch
                    wire:model.live="password_require_uncompromised"
                    :label="__('configuration.index.fields.password_require_uncompromised')"
                    :description="__('configuration.index.fields.password_require_uncompromised_help')"
                />
            </div>

            <flux:separator variant="subtle" />

            <div class="grid items-start gap-4 sm:grid-cols-2">
                <flux:input
                    wire:model.live.blur="login_rate_limit"
                    type="number"
                    min="1"
                    max="60"
                    :label="__('configuration.index.fields.login_rate_limit')"
                    :description="__('configuration.index.fields.login_rate_limit_help')"
                    suffix="/min"
                />

                <flux:input
                    wire:model.live.blur="password_reset_expiry_minutes"
                    type="number"
                    min="5"
                    max="1440"
                    :label="__('configuration.index.fields.password_reset_expiry_minutes')"
                    :description="__('configuration.index.fields.password_reset_expiry_minutes_help')"
                    suffix="min"
                />
            </div>

            <flux:separator variant="subtle" />

            <div>
                <flux:heading>{{ __('configuration.index.sections.form_rate_limiting') }}</flux:heading>
                <flux:subheading>{{ __('configuration.index.sections.form_rate_limiting_description') }}</flux:subheading>
            </div>

            <flux:switch
                wire:model.live="form_rate_limit_enabled"
                :label="__('configuration.index.fields.form_rate_limit_enabled')"
                :description="__('configuration.index.fields.form_rate_limit_enabled_help')"
            />

            <div class="grid items-start gap-4 sm:grid-cols-2">
                <flux:input
                    wire:model.live.blur="form_edit_rate_limit"
                    type="number"
                    min="1"
                    max="120"
                    :label="__('configuration.index.fields.form_edit_rate_limit')"
                    :description="__('configuration.index.fields.form_edit_rate_limit_help')"
                    suffix="/min"
                    :disabled="! $this->form_rate_limit_enabled"
                />

                <flux:input
                    wire:model.live.blur="form_action_rate_limit"
                    type="number"
                    min="1"
                    max="60"
                    :label="__('configuration.index.fields.form_action_rate_limit')"
                    :description="__('configuration.index.fields.form_action_rate_limit_help')"
                    suffix="/min"
                    :disabled="! $this->form_rate_limit_enabled"
                />
            </div>

            <div class="flex justify-end">
                <flux:button variant="primary" type="submit" :disabled="! $this->securityChanged">{{ __('actions.save') }}</flux:button>
            </div>
        </flux:card>
    </form>

    {{-- Session --}}
    <form wire:submit="saveSession" class="space-y-6">
        <flux:card class="space-y-6">
            <div class="flex items-start gap-3">
                <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-violet-500/15 text-violet-300">
                    <flux:icon.clock class="size-5" />
                </div>

                <div>
                    <flux:heading size="lg">{{ __('configuration.index.sections.session') }}</flux:heading>
                    <flux:subheading>{{ __('configuration.index.sections.session_description') }}</flux:subheading>
                </div>
            </div>

            <flux:separator variant="subtle" />

            <flux:input
                wire:model.live.blur="session_lifetime_minutes"
                type="number"
                min="5"
                max="1440"
                :label="__('configuration.index.fields.session_lifetime_minutes')"
                :description="__('configuration.index.fields.session_lifetime_minutes_help')"
                suffix="min"
                class="sm:max-w-xs"
            />

            <flux:callout icon="exclamation-triangle" variant="warning" inline>
                <flux:callout.text>{{ __('configuration.index.session_notice') }}</flux:callout.text>
            </flux:callout>

            <div class="flex justify-end">
                <flux:button variant="primary" type="submit" :disabled="! $this->sessionChanged">{{ __('actions.save') }}</flux:button>
            </div>
        </flux:card>
    </form>
</section>
