@php
    $modalClass = 'modal-service-sheet w-full max-w-100 md:max-w-none md:w-md';
    $formModalClass = 'modal-service-sheet w-full max-w-100 md:max-w-none '.$formWidth;
    $modalBodyClass = 'space-y-5 md:space-y-6';
    $modalMessageClass = 'block whitespace-pre-line text-sm leading-6 text-zinc-600 dark:text-white/70 md:text-[0.95rem]';
    $modalFooterClass = 'flex flex-col-reverse gap-3 pt-1 sm:flex-row sm:items-center sm:justify-end';
@endphp

<div>
    <flux:modal name="modal-confirm" :dismissible="false" :class="$modalClass">
        <form wire:submit="confirm" autocomplete="off" class="{{ $modalBodyClass }}">
            <div class="space-y-4 md:space-y-5">
                <flux:heading size="lg">{{ $confirmTitle }}</flux:heading>

                <flux:separator variant="subtle" class="my-3 md:my-5" />

                <flux:text class="{{ $modalMessageClass }}">{{ $confirmMessage }}</flux:text>
            </div>

            @if ($this->requiresPasswordConfirmation())
                <input
                    type="email"
                    name="username"
                    id="modal-confirm-username"
                    autocomplete="username"
                    value="{{ $confirmUsername }}"
                    tabindex="-1"
                    class="sr-only"
                    aria-hidden="true"
                    readonly
                />

                <flux:input
                    wire:model.live.blur="confirmPassword"
                    :label="__('Current password')"
                    type="password"
                    name="current_password"
                    id="modal-confirm-current-password"
                    error:name="confirmPassword"
                    required
                    autocomplete="current-password"
                    viewable
                />
            @endif

            <flux:separator variant="subtle" />

            <div class="{{ $modalFooterClass }}">
                <flux:spacer class="hidden sm:block" />

                <flux:modal.close>
                    <flux:button variant="ghost" size="sm" type="button" wire:click="closeConfirm" class="modal-service-action w-full sm:w-auto">
                        {{ __('actions.cancel') }}
                    </flux:button>
                </flux:modal.close>

                <flux:button variant="danger" size="sm" type="submit" class="modal-service-action w-full sm:w-auto">
                    {{ $confirmLabel }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="modal-info" :class="$modalClass">
        <div class="{{ $modalBodyClass }}">
            <div class="space-y-4 md:space-y-5">
                <flux:heading size="lg">{{ $infoTitle }}</flux:heading>

                <flux:separator variant="subtle" class="my-3 md:my-5" />

                <flux:text class="{{ $modalMessageClass }}">{{ $infoMessage }}</flux:text>
            </div>

            <flux:separator variant="subtle" />

            <div class="{{ $modalFooterClass }}">
                <flux:spacer class="hidden sm:block" />

                <flux:modal.close>
                    <flux:button variant="primary" size="sm" wire:click="closeInfo" class="modal-service-action w-full sm:w-auto">
                        {{ __('actions.close') }}
                    </flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="modal-form" :class="$formModalClass" @cancel="$wire.closeForm()">
        <div class="{{ $modalBodyClass }}">
            <div class="space-y-4 md:space-y-5">
                <flux:heading size="lg">{{ $formTitle }}</flux:heading>

                @if (filled($formDescription))
                    <flux:text class="{{ $modalMessageClass }}">{{ $formDescription }}</flux:text>
                @endif
            </div>

            <flux:separator variant="subtle" class="my-3 md:my-5" />

            @if ($formComponent = $this->formComponent())
                <livewire:is
                    :component="$formComponent"
                    :context="$formContext"
                    :wire:key="'form-modal-'.$formModalName.'-'.md5(json_encode($formContext))"
                />
            @endif
        </div>
    </flux:modal>
</div>
