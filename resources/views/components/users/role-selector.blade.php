@props([
    'availableRoles' => [],
    'selectedRoles' => [],
    'keyPrefix' => 'role',
    'label' => null,
    'description' => null,
    'roleLabeler' => null,
    'icon' => null,
    'iconClass' => '',
    'disabled' => false,
])

<flux:field>
    @if ($label)
        <flux:label @class(['inline-flex items-center gap-1.5' => $icon])>
            @if ($icon)
                <flux:icon :name="$icon" @class(['size-4', $iconClass]) />
            @endif
            {{ $label }}
        </flux:label>
    @endif

    @if ($description)
        <flux:description>{{ $description }}</flux:description>
    @endif

    @php $adminRole = \App\Domain\Users\RoleConfig::adminRole() @endphp

    <flux:checkbox.group wire:model.live="roles" :invalid="$errors->has('roles')" class="grid gap-4 md:grid-cols-2">
        @foreach ($availableRoles as $availableRole)
            @php
                $isSelected = in_array($availableRole, $selectedRoles, true);
                $isDisabled = $disabled || ($availableRole !== $adminRole && in_array($adminRole, $selectedRoles, true));
            @endphp

            <div
                wire:key="{{ $keyPrefix }}-{{ $availableRole }}"
                @class([
                    'group flex items-start gap-4 rounded-2xl border px-4 py-3.5 transition',
                    'border-zinc-300 bg-white shadow-sm ring-1 ring-inset ring-zinc-200 dark:border-white/20 dark:bg-white/6 dark:ring-white/6' => $isSelected,
                    'border-zinc-200 bg-zinc-50 hover:border-zinc-300 hover:bg-white dark:border-white/8 dark:bg-white/3 dark:hover:border-white/14 dark:hover:bg-white/4' => ! $isSelected,
                    'opacity-50' => $isDisabled,
                ])
            >
                <flux:checkbox
                    value="{{ $availableRole }}"
                    :disabled="$isDisabled"
                    :label="$roleLabeler ? $roleLabeler($availableRole) : \App\Domain\Users\RoleConfig::label($availableRole)"
                />
            </div>
        @endforeach
    </flux:checkbox.group>

    <flux:error name="roles" />
</flux:field>
