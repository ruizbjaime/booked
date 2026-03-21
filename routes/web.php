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

        Route::livewire('bed-types', 'pages::bed-types.index')->name('bed-types.index');

        Route::livewire('bed-types/{bedType}', 'pages::bed-types.show')
            ->whereNumber('bedType')
            ->name('bed-types.show');

        Route::livewire('fee-types', 'pages::fee-types.index')->name('fee-types.index');

        Route::livewire('fee-types/{feeType}', 'pages::fee-types.show')
            ->whereNumber('feeType')
            ->name('fee-types.show');

        Route::livewire('bath-room-types', 'pages::bath-room-types.index')->name('bath-room-types.index');

        Route::livewire('bath-room-types/{bathRoomType}', 'pages::bath-room-types.show')
            ->whereNumber('bathRoomType')
            ->name('bath-room-types.show');

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
