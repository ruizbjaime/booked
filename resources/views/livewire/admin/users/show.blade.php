<?php

use Livewire\Volt\Component;

new class extends Component {
    public ?\App\Models\User $user = null;

    public function mount(int $id): void
    {
        try {
            $this->user = \App\Models\User::findOrFail($id);
        }catch (Exception $e){
            Log::error(__("Can't fetch user with ID ':id' for show", ['id' => $id]) . ": " . $e->getMessage());
            throw $e;
        }
    }
}; ?>

<div class="container mx-auto">
    <section> {{-- Header tile--}}
        <flux:heading size="xl">{{ __('Users') }}</flux:heading>
        <flux:subheading size="lg">{{ __('Show user') }}</flux:subheading>
        <flux:separator variant="subtle" class="my-6"/>
    </section>

    <flux:card class="overflow-hidden shadow-lg">
        <section class="flex items-center gap-4 -mx-6 -mt-6 mb-6 bg-gray-100 dark:bg-neutral-800/20 p-6">
            <flux:avatar
                size="xl"
                name="{{ $user->name }}"
                color="zinc"
            />
            <div class="flex-col">
                <flux:heading size="lg">{{$user->name}}</flux:heading>
                <flux:text>{{$user->email}}</flux:text>
                <flux:badge size="sm" :color="$user->email_verified_at ? 'lime' : 'yellow'">
                    {{ $user->email_verified_at ? __("Email Verified") : __("Email not Verified") }}
                </flux:badge>
            </div>

        </section>
        <dl class="space-y-6 mb-6">

            <div class="">
                <dt class="flex gap-1.5 text-gray-400 dark:text-gray-400 text-sm">{{__('Roles')}}</dt>
                <dd>
                    @foreach($user->roles as $role)
                        <flux:badge color="zinc">{{$role->name}}</flux:badge>
                    @endforeach
                </dd>
            </div>

            <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                <div>
                    <dt class="text-gray-400 dark:text-gray-400 text-sm">{{__('Registered since')}}</dt>
                    <dd class="text-gray-900 dark:text-gray-100">{{$user->created_at->isoFormat('LL')}}</dd>
                </div>
                <div class="">
                    <dt class="text-gray-400 dark:text-gray-400 text-sm">{{__('Last update')}}</dt>
                    <dd class="text-gray-900 dark:text-gray-100">{{$user->updated_at->isoFormat('LLL')}}</dd>
                </div>
            </div>
        </dl>
        <div class="flex justify-end gap-2">
            <flux:button variant="ghost"
                         icon:trailing="arrow-uturn-left"
                         href="{{route('admin.users.index')}}"
                         wire:navigate>
                {{__('Back to users')}}
            </flux:button>

            <flux:button variant="primary"
                         icon:trailing="pencil-square"
                         href="{{route('admin.users.edit', $user->id)}}"
                         wire:navigate>
                {{__('Edit')}}
            </flux:button>
        </div>
    </flux:card>


</div>
