<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
    Route::middleware(['role:admin'])->group(function () {

        Route::prefix('admin/users')->name('admin.users.')->group(function () {
            Volt::route('', 'admin.users.index')->name('index');
            Volt::route('create', 'admin.users.create-or-update')->name('create');
            Volt::route('/{id}/show', 'admin.users.show')->name('show');
            Volt::route('/{id}/edit', 'admin.users.create-or-update')->name('edit');
        });

    });
});

require __DIR__.'/auth.php';
