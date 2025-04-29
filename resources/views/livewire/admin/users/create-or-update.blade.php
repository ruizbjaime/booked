<?php

use App\Models\User;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Flux\Flux;

new class extends Component {
    public ?User $user = null;

    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public array $rolesMap = [];
    public array $roles = [];
    public bool $isEditing = false;

    public function mount(?int $id = null): void
    {
        $this->rolesMap = Role::pluck('id', 'name')->all();

        if ($id) {
            $this->loadUserForEdit($id);
        }else{
            $this->initializeNewUser();
        }
    }

    protected function loadUserForEdit(int $id): void
    {
        try {
            $this->user = User::findOrFail($id);
            $this->name = $this->user->name;
            $this->email = $this->user->email;
            $this->roles = $this->user->roles()->pluck('id')->all();
            $this->isEditing = true;
        }catch (ModelNotFoundException $e) {
            Log::error(__("Couldn't find user with ID: [:id] for editing: :message", ['id' => $id, 'message' => $e->getMessage()]));
            $this->redirectRoute('admin.users.index', ['navigate' => true]);
        }
    }

    protected function initializeNewUser(): void
    {
        $this->user = new User();
        $this->roles = [];
    }

    public function rules(): array
    {
        $userIdToIgnore = $this->isEditing ? $this->user->id : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userIdToIgnore) // Regla unique simplificada
            ],
            // La contraseña es requerida solo si no se está editando
            'password' => [
                $this->isEditing ? 'nullable' : 'required', // Condicional aquí
                'string',
                'min:8',
                'confirmed' // 'confirmed' busca automáticamente 'password_confirmation'
            ],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['exists:roles,id'] // Validar cada ID en el array de roles
        ];
    }

    protected function createUser(array $validatedData): void
    {
        $this->authorize('create', User::class);
        $this->user = User::create($validatedData);
        Flux::toast(
            text: __("User ':name' has been created successfully", ['name' => $this->user->name]), // Más específico
            heading: __('Success'),
            variant: "success"
        );
    }

    protected function updateUser(array $validatedData): void
    {
        $this->authorize('update', $this->user);
        $this->user->update($validatedData);
        Flux::toast(
            text: __("User ':name' has been updated successfully", ['name' => $this->user->name]), // Más específico
            heading: __('Success'),
            variant: "success"
        );
    }

    protected function validateData(): array
    {
        $validatedData = $this->validate();

        // Hashear contraseña si se proporcionó y no está vacía
        if (!empty($validatedData['password'])) {
            $validatedData['password'] = Hash::make($validatedData['password']);
        } else {
            // Si está vacía (solo posible en edición), eliminarla para no sobreescribir la existente
            unset($validatedData['password']);
        }

        // Eliminar siempre el campo de confirmación después de la validación
        unset($validatedData['password_confirmation']);

        return $validatedData;
    }


    public function save(): void
    {
        $validatedData = $this->validateData();

        // Separar los roles antes de la transacción
        $userRoles = $validatedData['roles'];
        unset($validatedData['roles']);

        try {
            DB::transaction(function () use ($validatedData, $userRoles) {
                if ($this->isEditing) {
                    $this->updateUser($validatedData);
                } else {
                    $this->createUser($validatedData);
                }
                // Sincronizar roles después de crear/actualizar el usuario
                $this->user->roles()->sync($userRoles);
            });

            // Redirigir SOLO si la transacción fue exitosa
            $this->redirect(route('admin.users.index'), navigate: true);

        } catch (Exception $e) {
            // Usar parámetros para el log
            Log::error(__('Error saving user: :message', ['message' => $e->getMessage()]), [
                'user_id' => $this->user?->id, // Añadir contexto útil al log
                'is_editing' => $this->isEditing,
                'exception' => $e // Puedes loguear el objeto excepción completo si tu logger lo soporta bien
            ]);

            \Flux\Flux::toast(
            // Podrías incluir $e->getMessage() si es seguro mostrarlo al usuario
                text: __("An error occurred while saving the user."),
                heading: __('Error'),
                variant: "danger"
            );
            // No redirigir en caso de error, permitir al usuario ver el estado actual del formulario
        }
    }
}; ?>

<div class="container mx-auto">
    <section> {{-- Header tile--}}
        <flux:heading size="xl">{{ __('Users') }}</flux:heading>
        <flux:subheading
            size="lg">{{ $this->user?->id ?  __('Currently editing user \':name\'', ['name' => $this->user->name]) : __('Create user') }}</flux:subheading>
        <flux:separator variant="subtle" class="my-6"/>
    </section>

    <flux:card class="overflow-hidden shadow-lg">
        <section class="flex items-center gap-4 -mx-6 -mt-6 mb-6 bg-gray-100 dark:bg-neutral-800/20 p-6">
            <flux:avatar
                size="xl"
                name="{{ $user?->name }}"
                color="zinc"
            />
            <div class="flex-col">
                <flux:heading size="lg">{{$user?->name}}</flux:heading>
                <flux:text>{{$user?->email}}</flux:text>
                <flux:badge size="sm" :color="$user?->email_verified_at ? 'lime' : 'yellow'">
                    {{ $user?->email_verified_at ? __("Email Verified") : __("Email not Verified") }}
                </flux:badge>
            </div>
        </section>


        <section> {{-- Form --}}
            <form class="space-y-6" wire:submit.prevent="save">
                <flux:fieldset class="grid grid-cols-1 sm:grid-cols-6 xl:grid-cols-8 items-start gap-4">
                    <div class="sm:col-span-2 lg:col-span-2 xl:col-span-2">
                        <flux:legend>{{__("Profile information")}}</flux:legend>
                        <flux:text>{{__("Update user profile details.")}}</flux:text>
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
                        <flux:text>{{__("Set a strong password to secure this account.")}}</flux:text>
                    </div>
                    <div class="sm:col-span-4 lg:col-span-4 xl:col-span-6 max-w-md space-y-6">
                        <flux:input :label="__('Password')" name="password" type="password" wire:model="password"/>
                        <flux:input :label="__('Confirm Password')" name="password_confirmation" type="password"
                                    wire:model="password_confirmation"/>
                    </div>
                </flux:fieldset>

                <flux:separator variant="subtle" class="my-6"/>

                <flux:fieldset class="grid grid-cols-1 sm:grid-cols-6 xl:grid-cols-8 items-center gap-4">
                    <div class="sm:col-span-2 lg:col-span-2 xl:col-span-2">
                        <flux:legend>{{__("Roles")}}</flux:legend>
                        <flux:text>{{__("Select one or more roles to grant permissions to the user.")}}</flux:text>
                    </div>
                    <div class="sm:col-span-4 lg:col-span-4 xl:col-span-6 max-w-md space-y-6">
                        <flux:checkbox.group wire:model="roles">
                            <div class="flex gap-2">
                                @foreach($rolesMap as $name => $id)
                                    <flux:checkbox :label="$name" :value="$id" wire:key="role-{{$id}}}"/>
                                @endforeach
                            </div>
                        </flux:checkbox.group>
                        <flux:error name="roles"/>
                    </div>
                </flux:fieldset>

                <flux:separator variant="subtle" class="my-6"/>

                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" :href="route('admin.users.index')"
                                 :wire:navigate="true">{{ __('Cancel') }}</flux:button>
                    <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
                </div>
            </form>
        </section>

    </flux:card>
</div>
