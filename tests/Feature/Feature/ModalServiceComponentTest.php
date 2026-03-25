<?php

use App\Infrastructure\UiFeedback\ModalService;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

describe('modal-service component', function () {
    it('renders successfully with mobile sheet classes and responsive actions', function () {
        Livewire::test('modal-service')
            ->assertOk()
            ->assertSee('modal-service-sheet', false)
            ->assertSee('md:w-md', false)
            ->assertSee('space-y-5 md:space-y-6', false)
            ->assertSee('flex flex-col-reverse gap-3 pt-1 sm:flex-row sm:items-center sm:justify-end', false)
            ->assertSee('modal-service-action w-full sm:w-auto', false)
            ->assertSee('data-modal="modal-form"', false)
            ->assertDontSee('data-flux-flyout', false)
            ->assertDontSee('[--flux-flyout-translate:translateY(50px)]', false)
            ->assertSee('modal-service-sheet', false)
            ->assertSee('rounded-xl', false);
    });

    it('sets confirm modal state when open-confirm-modal event is received', function () {
        Livewire::test('modal-service')
            ->dispatch('open-confirm-modal', title: 'Delete User', message: 'Are you sure?', confirmLabel: 'Delete')
            ->assertSet('confirmTitle', 'Delete User')
            ->assertSet('confirmMessage', 'Are you sure?')
            ->assertSet('confirmLabel', 'Delete')
            ->assertSet('confirmVariant', ModalService::VARIANT_STANDARD);
    });

    it('sets password confirm modal state when requested', function () {
        /** @var User $user */
        $user = User::factory()->create([
            'email' => 'admin@example.com',
        ]);

        actingAs($user);

        Livewire::test('modal-service')
            ->dispatch('open-confirm-modal', title: 'Delete User', message: 'Are you sure?', confirmLabel: 'Delete', variant: ModalService::VARIANT_PASSWORD)
            ->assertSet('confirmVariant', ModalService::VARIANT_PASSWORD)
            ->assertSet('confirmUsername', 'admin@example.com');
    });

    it('uses default confirm label when none is provided', function () {
        Livewire::test('modal-service')
            ->dispatch('open-confirm-modal', title: 'Remove', message: 'Cannot be undone.')
            ->assertSet('confirmLabel', __('actions.confirm'));
    });

    it('sets info modal state when open-info-modal event is received', function () {
        Livewire::test('modal-service')
            ->dispatch('open-info-modal', title: 'Process Done', message: 'The task completed successfully.')
            ->assertSet('infoTitle', 'Process Done')
            ->assertSet('infoMessage', 'The task completed successfully.');
    });

    it('sets form modal state when openForm is called', function () {
        $component = Livewire::test('modal-service');

        $component->instance()->openForm(
            'users.create',
            'Create User',
            'Create a new account.',
            ['origin' => 'users.index'],
            'md:w-xl',
        );

        $component
            ->assertSet('formModalName', 'users.create')
            ->assertSet('formTitle', 'Create User')
            ->assertSet('formDescription', 'Create a new account.')
            ->assertSet('formContext', ['origin' => 'users.index'])
            ->assertSet('formWidth', 'md:w-xl');

        expect($component->instance()->formComponent())->toBe('users.create-user-form');
    });

    it('resolves the pricing rule form component', function () {
        $component = Livewire::test('modal-service');

        $component->instance()->openForm(
            'calendar.pricing-rules.form',
            'Create pricing rule',
            'Configure a pricing rule.',
        );

        expect($component->instance()->formComponent())->toBe('calendar.pricing-rule-form');
    });

    it('renders the form modal description when present', function () {
        Livewire::test('modal-service')
            ->set('formTitle', 'Create User')
            ->set('formDescription', 'Create a new account.')
            ->assertSee('Create a new account.');
    });

    it('dispatches modal-confirmed event when confirm action is called', function () {
        Livewire::test('modal-service')
            ->dispatch('open-confirm-modal', title: 'Delete', message: 'Sure?')
            ->call('confirm')
            ->assertDispatched('modal-confirmed');
    });

    it('requires the current password for password confirm modals', function () {
        /** @var User $user */
        $user = User::factory()->create();

        actingAs($user);

        Livewire::test('modal-service')
            ->dispatch('open-confirm-modal', title: 'Delete', message: 'Sure?', variant: ModalService::VARIANT_PASSWORD)
            ->set('confirmPassword', 'wrong-password')
            ->call('confirm')
            ->assertHasErrors(['confirmPassword'])
            ->assertNotDispatched('modal-confirmed');
    });

    it('dispatches modal-confirmed for password confirm modals with a valid password', function () {
        /** @var User $user */
        $user = User::factory()->create();

        actingAs($user);

        Livewire::test('modal-service')
            ->dispatch('open-confirm-modal', title: 'Delete', message: 'Sure?', variant: ModalService::VARIANT_PASSWORD)
            ->set('confirmPassword', 'password')
            ->call('confirm')
            ->assertHasNoErrors()
            ->assertDispatched('modal-confirmed');
    });

    it('resets confirm modal state when it closes', function () {
        Livewire::test('modal-service')
            ->dispatch('open-confirm-modal', title: 'Delete User', message: 'Are you sure?', confirmLabel: 'Delete', variant: ModalService::VARIANT_PASSWORD)
            ->set('confirmPassword', 'password')
            ->call('closeConfirm')
            ->assertSet('confirmTitle', '')
            ->assertSet('confirmMessage', '')
            ->assertSet('confirmLabel', __('actions.confirm'))
            ->assertSet('confirmVariant', ModalService::VARIANT_STANDARD)
            ->assertSet('confirmPassword', '')
            ->assertDispatched('modal-confirm-cancelled');
    });

    it('resets info modal state when it closes', function () {
        Livewire::test('modal-service')
            ->dispatch('open-info-modal', title: 'Process Done', message: 'The task completed successfully.')
            ->call('closeInfo')
            ->assertSet('infoTitle', '')
            ->assertSet('infoMessage', '');
    });

    it('resets form modal state when it closes', function () {
        $component = Livewire::test('modal-service');

        $component->instance()->openForm(
            'users.create',
            'Create User',
            'Create a new account.',
            ['origin' => 'users.index'],
        );

        $component->instance()->closeForm();

        $component
            ->assertSet('formModalName', '')
            ->assertSet('formTitle', '')
            ->assertSet('formDescription', '')
            ->assertSet('formContext', [])
            ->assertSet('formWidth', ModalService::WIDTH_DEFAULT);
    });
});
