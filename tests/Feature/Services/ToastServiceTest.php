<?php

use App\Infrastructure\UiFeedback\ToastService;
use Flux\Flux;

describe('ToastService', function () {
    it('dispatches a success toast with correct heading and variant', function () {
        Flux::shouldReceive('toast')
            ->once()
            ->with('User created successfully.', __('toasts.headings.success'), 5000, 'success');

        ToastService::success('User created successfully.');
    });

    it('dispatches a warning toast with correct heading and variant', function () {
        Flux::shouldReceive('toast')
            ->once()
            ->with('This email is already in use.', __('toasts.headings.warning'), 5000, 'warning');

        ToastService::warning('This email is already in use.');
    });

    it('dispatches a danger toast with correct heading and variant', function () {
        Flux::shouldReceive('toast')
            ->once()
            ->with('Could not delete the record.', __('toasts.headings.danger'), 5000, 'danger');

        ToastService::danger('Could not delete the record.');
    });

    it('allows overriding the duration for success toasts', function () {
        Flux::shouldReceive('toast')
            ->once()
            ->with('Done.', __('toasts.headings.success'), 1000, 'success');

        ToastService::success('Done.', duration: 1000);
    });

    it('uses the current locale for the toast heading', function () {
        $originalLocale = app()->getLocale();

        app()->setLocale('es');

        try {
            Flux::shouldReceive('toast')
                ->once()
                ->with('Operacion completada.', __('toasts.headings.success'), 5000, 'success');

            ToastService::success('Operacion completada.');
        } finally {
            app()->setLocale($originalLocale);
        }
    });
});
