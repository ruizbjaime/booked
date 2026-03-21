<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        @php $user = auth()->user() @endphp

        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>

                    @can('viewAny', \App\Models\User::class)
                        <flux:sidebar.item icon="users" :href="route('users.index')" :current="request()->routeIs('users.*')" wire:navigate>
                            {{ __('users.navigation.label') }}
                        </flux:sidebar.item>
                    @endcan

                    @can('viewAny', \App\Models\Platform::class)
                        <flux:sidebar.item icon="building-storefront" :href="route('platforms.index')" :current="request()->routeIs('platforms.*')" wire:navigate>
                            {{ __('platforms.navigation.label') }}
                        </flux:sidebar.item>
                    @endcan
                </flux:sidebar.group>

                @can('viewAny', \App\Models\Role::class)
                    <flux:sidebar.group :heading="__('Security')" class="grid">
                        <flux:sidebar.item icon="shield-check" :href="route('roles.index')" :current="request()->routeIs('roles.*')" wire:navigate>
                            {{ __('roles.navigation.label') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endcan

                @if (
                    $user->can('viewAny', \App\Models\Country::class)
                    || $user->can('viewAny', \App\Models\IdentificationDocumentType::class)
                    || $user->can('viewAny', \App\Models\BedType::class)
                    || $user->can('viewAny', \App\Models\FeeType::class)
                    || $user->can('viewAny', \App\Models\ChargeBasis::class)
                    || $user->can('viewAny', \App\Models\BathRoomType::class)
                )
                    <flux:sidebar.group :heading="__('Parameterization')" class="grid">
                        

                        @can('viewAny', \App\Models\BedType::class)
                            <flux:sidebar.item icon="bed-double" :href="route('bed-types.index')" :current="request()->routeIs('bed-types.*')" wire:navigate>
                                {{ __('bed_types.navigation.label') }}
                            </flux:sidebar.item>
                        @endcan

                         @can('viewAny', \App\Models\BathRoomType::class)
                            <flux:sidebar.item icon="bath" :href="route('bath-room-types.index')" :current="request()->routeIs('bath-room-types.*')" wire:navigate>
                                {{ __('bath_room_types.navigation.label') }}
                            </flux:sidebar.item>
                        @endcan

                        @if ($user->can('viewAny', \App\Models\FeeType::class) || $user->can('viewAny', \App\Models\ChargeBasis::class))
                                @can('viewAny', \App\Models\FeeType::class)
                                    <flux:sidebar.item icon="banknotes" :href="route('fee-types.index')" :current="request()->routeIs('fee-types.*')" wire:navigate>
                                        {{ __('fee_types.navigation.label') }}
                                    </flux:sidebar.item>
                                @endcan

                                @can('viewAny', \App\Models\ChargeBasis::class)
                                    <flux:sidebar.item icon="calculator" :href="route('charge-bases.index')" :current="request()->routeIs('charge-bases.*')" wire:navigate>
                                        {{ __('charge_bases.navigation.label') }}
                                    </flux:sidebar.item>
                                @endcan
                        @endif

                        @can('viewAny', \App\Models\Country::class)
                            <flux:sidebar.item icon="globe-alt" :href="route('countries.index')" :current="request()->routeIs('countries.*')" wire:navigate>
                                {{ __('countries.navigation.label') }}
                            </flux:sidebar.item>
                        @endcan

                        @can('viewAny', \App\Models\IdentificationDocumentType::class)
                            <flux:sidebar.item icon="identification" :href="route('identification-document-types.index')" :current="request()->routeIs('identification-document-types.*')" wire:navigate>
                                {{ __('identification_document_types.navigation.label') }}
                            </flux:sidebar.item>
                        @endcan

                       
                    </flux:sidebar.group>
                @endif

                @can('viewAny', \App\Models\SystemSetting::class)
                    <flux:sidebar.group :heading="__('System')" class="grid">
                        <flux:sidebar.item icon="cog-6-tooth" :href="route('configuration.index')" :current="request()->routeIs('configuration.*')" wire:navigate>
                            {{ __('configuration.navigation.label') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endcan
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav>
                <flux:sidebar.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit" target="_blank">
                    {{ __('Repository') }}
                </flux:sidebar.item>

                <flux:sidebar.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank">
                    {{ __('Documentation') }}
                </flux:sidebar.item>
            </flux:sidebar.nav>

            <x-desktop-user-menu class="hidden lg:block" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :avatar="$user->avatarUrl()"
                    :initials="$user->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :src="$user->avatarUrl()"
                                    :name="$user->name"
                                    :initials="$user->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ $user->name }}</flux:heading>
                                    <flux:text class="truncate">{{ $user->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        <livewire:modal-service />

        @persist('toast')
            <flux:toast />
        @endpersist

        @fluxScripts
    </body>
</html>
