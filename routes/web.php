<?php

use App\Domain\Users\RoleConfig;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::middleware('role:'.RoleConfig::adminRole())->group(function (): void {
        Route::livewire('users', 'pages::users.index')->name('users.index');

        Route::livewire('users/{user}', 'pages::users.show')
            ->whereNumber('user')
            ->name('users.show');

        Route::livewire('countries', 'pages::countries.index')->name('countries.index');

        Route::livewire('countries/{country}', 'pages::countries.show')
            ->whereNumber('country')
            ->name('countries.show');

        Route::livewire('identification-document-types', 'pages::identification-document-types.index')
            ->name('identification-document-types.index');

        Route::livewire('identification-document-types/{identificationDocumentType}', 'pages::identification-document-types.show')
            ->whereNumber('identificationDocumentType')
            ->name('identification-document-types.show');

        Route::livewire('platforms', 'pages::platforms.index')->name('platforms.index');

        Route::livewire('platforms/{platform}', 'pages::platforms.show')
            ->whereNumber('platform')
            ->name('platforms.show');

        Route::livewire('roles', 'pages::roles.index')->name('roles.index');

        Route::livewire('roles/{role}', 'pages::roles.show')
            ->whereNumber('role')
            ->name('roles.show');

        Route::livewire('configuration', 'pages::configuration.index')->name('configuration.index');
    });
});

require __DIR__.'/settings.php';
