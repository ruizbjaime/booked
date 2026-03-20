<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Profile settings') }}</flux:heading>

    <x-settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        <div class="my-6 flex flex-col items-start gap-2">
            <div class="relative" wire:loading.class="pointer-events-none" wire:target="photo">
                <flux:avatar
                    size="xl"
                    :src="$this->userAvatarUrl"
                    :initials="Auth::user()->initials()"
                    color="auto"
                    :color:seed="Auth::user()->id"
                />

                <x-image-editor wire:model="photo" aspect-ratio="1:1" input-id="settings-avatar-upload" :maxSizeMb="$this->maxUploadSizeMb">
                    <label for="settings-avatar-upload" class="group absolute inset-0 z-[5] cursor-pointer overflow-hidden rounded-[var(--radius-lg)]" wire:loading.class="!hidden" wire:target="photo">
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
                                for="settings-avatar-upload"
                                class="absolute bottom-0 right-0 z-20 flex size-5 translate-x-1/4 translate-y-1/4 cursor-pointer items-center justify-center rounded-full bg-sky-500 shadow-md transition-all duration-150 hover:scale-110 hover:bg-sky-600 dark:bg-sky-400 dark:hover:bg-sky-500"
                                aria-label="{{ __('users.show.avatar_add_label') }}"
                            >
                                <flux:icon.plus variant="micro" class="size-3 text-white" />
                            </label>
                        @endif
                    </div>
                </x-image-editor>
            </div>
        </div>

        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <div class="grid items-start gap-4 sm:grid-cols-2">
                <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />
                <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />
            </div>

            @if ($this->hasUnverifiedEmail)
                <div>
                    <flux:text class="mt-4">
                        {{ __('Your email address is unverified.') }}

                        <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                            {{ __('Click here to re-send the verification email.') }}
                        </flux:link>
                    </flux:text>

                    @if (session('status') === 'verification-link-sent')
                        <flux:text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </flux:text>
                    @endif
                </div>
            @endif

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full">{{ __('Save') }}</flux:button>
                </div>

                <x-action-message class="me-3" on="profile-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>

        <flux:separator class="my-8" />

        <div>
            <flux:heading>{{ __('settings.personal_information.heading') }}</flux:heading>
            <flux:subheading>{{ __('settings.personal_information.subheading') }}</flux:subheading>
        </div>

        <form
            wire:submit="updatePersonalInformation"
            class="my-6 w-full space-y-6"
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
                    :label="__('settings.personal_information.fields.phone')"
                    :placeholder="__('settings.personal_information.fields.phone_placeholder')"
                    autocomplete="tel"
                />

                <flux:select
                    wire:model.live="country_id"
                    variant="listbox"
                    searchable
                    clearable
                    :filter="false"
                    :label="__('settings.personal_information.fields.country')"
                    :placeholder="__('settings.personal_information.fields.country_placeholder')"
                    x-on:change="autoFillPrefix($event.target.value)"
                >
                    <x-slot name="search">
                        <flux:select.search wire:model.live.debounce.200ms="countrySearch" :placeholder="__('actions.search')" />
                    </x-slot>

                    @foreach ($this->countries as $country)
                        <flux:select.option :value="$country->id" wire:key="country-{{ $country->id }}">
                            {{ $country->localizedName() }} ({{ $country->phone_code }})
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="grid items-start gap-4 sm:grid-cols-2">
                <flux:select
                    wire:model="document_type_id"
                    variant="listbox"
                    clearable
                    :label="__('settings.personal_information.fields.document_type')"
                    :placeholder="__('settings.personal_information.fields.document_type_placeholder')"
                >
                    @foreach ($this->documentTypes as $docType)
                        <flux:select.option :value="$docType->id" wire:key="doc-type-{{ $docType->id }}">
                            {{ $docType->localizedName() }} ({{ $docType->code }})
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input
                    wire:model="document_number"
                    :label="__('settings.personal_information.fields.document_number')"
                    :placeholder="__('settings.personal_information.fields.document_number_placeholder')"
                />
            </div>

            <div class="grid items-start gap-4 sm:grid-cols-2">
                <flux:input
                    wire:model="state"
                    :label="__('settings.personal_information.fields.state')"
                    :placeholder="__('settings.personal_information.fields.state_placeholder')"
                />

                <flux:input
                    wire:model="city"
                    :label="__('settings.personal_information.fields.city')"
                    :placeholder="__('settings.personal_information.fields.city_placeholder')"
                />
            </div>

            <flux:textarea
                wire:model="address"
                rows="2"
                :label="__('settings.personal_information.fields.address')"
                :placeholder="__('settings.personal_information.fields.address_placeholder')"
            />

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full">{{ __('Save') }}</flux:button>
                </div>

                <x-action-message class="me-3" on="personal-info-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>

        @if ($this->showDeleteUser)
            <livewire:settings.delete-user-form />
        @endif
    </x-settings.layout>
</section>
