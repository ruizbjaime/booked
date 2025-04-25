<?php

use App\Models\User;
use Livewire\Volt\Component;

new class extends Component {
    public ?User $user = null;

    public string  $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public array $rolesMap = [];
    public array $roles = [];

    public function mount(?int $id = null): void
    {
        try {
            $this->user = $id
                ? User::findOrFail($id)
                : new User();

        }catch(Exception $e){
            Log::error(__("Can´t fetch or create user for creating/updating") . ": {$e->getMessage()}");
            throw $e;
        }

        if($this->user->id){
            $this->name = $this->user->name;
            $this->email = $this->user->email;
        }

        $this->rolesMap = \Spatie\Permission\Models\Role::pluck('id', 'name')->all();
        $this->roles = $this->user->roles()->pluck('id')->all();
    }

    public function rules()
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'roles' => ['required', 'array', 'exists:roles,id', 'min:1']
        ];

        if ($this->user) {
            $rules['email'] = ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $this->user->id];
            $rules['password'] = ['nullable', 'string', 'min:8', 'confirmed'];
        }

        return $rules;
    }

    public function save() {}

}; ?>

<div class="container mx-auto">
    <section> {{-- Header tile--}}
        <flux:heading size="xl">{{ __('Users') }}</flux:heading>
        <flux:subheading size="lg">{{ $this->user->id ?  __('Currently editing user \':name\'', ['name' => $this->user->name]) : __('Create user') }}</flux:subheading>
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


        <section> {{-- Form --}}
            <form class="space-y-6">
                <flux:fieldset class="grid grid-cols-1 sm:grid-cols-6 xl:grid-cols-8 items-start gap-4">
                    <div class="sm:col-span-2 lg:col-span-2 xl:col-span-2" >
                        <flux:legend>{{__("Profile information")}}</flux:legend>
                        <flux:text>{{__("Update your profile details.")}}</flux:text>
                    </div>
                    <div class="sm:col-span-4 lg:col-span-4 xl:col-span-6 max-w-md space-y-6">
                        <flux:input :label="__('Name')" name="name" wire:model="name"/>
                        <flux:input :label="__('Email')" name="email" type="email" wire:model="email"/>
                    </div>
                </flux:fieldset>

                <flux:separator variant="subtle" class="my-6"/>

                <flux:fieldset class="grid grid-cols-1 sm:grid-cols-6 xl:grid-cols-8 items-start gap-4">
                    <div class="sm:col-span-2 lg:col-span-2 xl:col-span-2">
                        <flux:legend>{{__("Security")}}</flux:legend>
                        <flux:text>{{__("Set a strong password to secure your account.")}}</flux:text>
                    </div>
                    <div class="sm:col-span-4 lg:col-span-4 xl:col-span-6 max-w-md space-y-6">
                        <flux:input :label="__('Password')" name="password" type="password" wire:model="password"/>
                        <flux:input :label="__('Confirm Password')" name="password_confirmation" type="password"
                                    wire:model="password_confirmation"/>
                    </div>
                </flux:fieldset>

                <flux:separator variant="subtle" class="my-6"/>

                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" :href="route('admin.users.index')"
                                 :wire:navigate="true">{{ __('Cancel') }}</flux:button>
                    <flux:button variant="primary" type="submit" wire:click="save">{{ __('Save') }}</flux:button>
                </div>
            </form>
        </section>

    </flux:card>
</div>
