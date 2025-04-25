<?php

use App\Models\User;
use Livewire\Volt\Component;

new class extends Component {
    public string $modelClass = User::class;
    public array $columnMap = [
        'id' => ['label' => '#', 'sortable' => true],
        'name' => ['label' => 'Name', 'searchable' => true],
        'email' => ['label' => 'Email', 'searchable' => true],
        'roles.name' => ['label' => 'Roles', 'searchable' => true],
        'created_at' => ['label' => 'Created at', 'sortable' => true],
    ];

    public array $tableActions = [
        'create' => [
            'variant' => 'primary',
            'icon' => 'plus',
            'label' => 'Create',
            'action' => '$parent.create'
        ],
        'show' => [
            'variant' => 'ghost',
            'icon' => 'eye',
            'label' => 'Show',
            'action' => '$parent.show'

        ],
        'edit' => [
            'variant' => 'ghost',
            'icon' => 'pencil-square',
            'label' => 'Edit',
            'action' => '$parent.edit'
        ],
        'delete' => [
            'variant' => 'danger',
            'icon' => 'trash',
            'label' => 'Delete',
            'action' => '$parent.confirmDelete'
        ],
    ];

    public string $sortBy = 'created_at';
    public string $sortDirection = 'desc';

    public ?User $userToDelete = null;
    public bool $showConfirmModal = false;

    public function show(int $id): void
    {
        $this->redirect(route('admin.users.show', $id), ['navigate' => true]);
    }

    public function create(): void
    {
        $this->redirect(route('admin.users.create'), ['navigate' => true]);
    }

    public function edit(int $id)
    {
        $this->redirect(route('admin.users.edit', $id), ['navigate' => true]);
    }

    public function delete(): void
    {
        try {
            $this->authorize('delete', $this->userToDelete);
            $this->userToDelete->delete();
            $this->dispatch('refresh-table')->to('dynamic-table');

            \Flux\Flux::toast(
                text: __("User ':name' with ID: ':id' has been deleted successfully.", ['name' => $this->userToDelete->name, 'id' => $this->userToDelete->id]),
                heading: __('Success'),
                variant: 'success'
            );
        }catch (Exception $e){
            Log::error(__("Unable to delete user with ID: ':id': ", ['id' => $this->userToDelete->id]) . " {$e->getMessage()}");
            \Flux\Flux::toast(
                text: __("Unable to delete user with ID: ':id': ", ['id' => $this->userToDelete->id]),
                heading: __('Error'),
                variant: 'danger'
            );
        }

        $this->showConfirmModal= false;
    }

    public function resetUserToDelete(): void
    {
        $this->userToDelete = null;
    }

    public function confirmDelete(int $id): void
    {
        try {
            $this->userToDelete = User::findOrFail($id);
            $this->showConfirmModal = true;

        } catch (Exception $e) {
            Log::error(__("Unable to fetch user with ID ':id' for deletion:", ['id' => $id]) . " {$e->getMessage()}");
            \Flux\Flux::toast(
                text: __("Unable to fetch user with ID ':id' for deletion.", ['id' => $id]),
                heading: __('Error'),
                variant: 'danger'
            );
        }
    }

}; ?>

<div class="container mx-auto">

    <section> {{-- Header tile--}}
        <flux:heading size="xl">{{ __('Users') }}</flux:heading>
        <flux:subheading size="lg">{{ __('Manage platform users') }}</flux:subheading>
        <flux:separator variant="subtle" class="my-6"/>
    </section>

    <section> {{-- Table --}}
        @livewire('dynamic-table', [
            'modelClass' => $modelClass,
            'columnMap' => $columnMap,
            'sortBy' => $sortBy,
            'sortDirection' => $sortDirection,
            'tableActions' => $tableActions,
        ])
    </section>

    <flux:modal wire:model.self="showConfirmModal" class="max-w-[450px]" @close="resetUserToDelete"> {{-- Delete confirmation modal. --}}
        <div class="space-y-4">
            <div class="flex gap-1.5">
                <flux:icon.exclamation-triangle class="text-yellow-500"/>
                <flux:heading size="lg">{{__('Warning')}}</flux:heading>
            </div>
            <flux:text>
                {{ __("You are about to delete user ':name' with ID: ':id'. This action can't be undone.", ['name' => $userToDelete?->name, 'id' => $userToDelete?->id]) }}
            </flux:text>
            <div class="flex justify-end gap-1">
                <flux:button size="sm" variant="ghost"
                             wire:click="$set('showConfirmModal', false)">{{__('Cancel')}}</flux:button>
                <flux:button size="sm" variant="danger" wire:click="delete">{{__('Delete')}}</flux:button>
            </div>
        </div>
    </flux:modal>

</div>
