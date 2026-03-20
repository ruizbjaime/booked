@props([
    'href' => '',
])

<div class="my-2 flex items-center xl:hidden">
    <flux:button class="ps-0!" variant="subtle" icon="arrow-left" :href="$href" wire:navigate>
        {{ __('actions.back') }}
    </flux:button>
</div>
