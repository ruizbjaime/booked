<?php

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function protectedRoleTestUri(): string
{
    return '/__tests/role-protected/'.Str::lower((string) Str::uuid());
}

it('forbids guests from accessing routes protected by the role middleware', function () {
    $uri = protectedRoleTestUri();

    Route::middleware('role:admin')->get($uri, fn () => 'ok');

    $this->get($uri)->assertForbidden();
});

it('forbids authenticated users without the required role', function () {
    $uri = protectedRoleTestUri();

    Route::middleware('role:admin')->get($uri, fn () => 'ok');

    $this->actingAs(makeGuest())
        ->get($uri)
        ->assertForbidden();
});

it('allows authenticated users with the required role', function () {
    $uri = protectedRoleTestUri();

    Route::middleware('role:admin')->get($uri, fn () => response('ok'));

    $this->actingAs(makeAdmin())
        ->get($uri)
        ->assertSuccessful()
        ->assertSeeText('ok');
});
