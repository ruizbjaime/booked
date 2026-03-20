@props([
    'section' => '',
    'editingSection' => null,
])

@if ($editingSection === $section)
    <flux:button size="sm" wire:click="cancelEditingSection">
        {{ __('actions.close') }}
    </flux:button>
@else
    <flux:button size="sm" icon="pencil-square" wire:click="startEditingSection('{{ $section }}')">
        {{ __('actions.edit') }}
    </flux:button>
@endif
