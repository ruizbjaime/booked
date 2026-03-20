<?php

use App\Models\User;
use Laravel\Fortify\Features;

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrorsIn('email');

    $this->assertGuest();
});

test('inactive users can not authenticate using the login screen', function () {
    $user = User::factory()->inactive()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors([
        'login' => __('auth.inactive'),
    ]);

    $this->assertGuest();
});

test('inactive users with invalid password see the default credentials error', function () {
    $user = User::factory()->inactive()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrorsIn('email');
    $response->assertSessionDoesntHaveErrors(['login']);

    $this->assertGuest();
});

test('login screen shows a prominent inactive account message', function () {
    $user = User::factory()->inactive()->create();

    $response = $this->from(route('login'))->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('login'));

    $this->followRedirects($response)
        ->assertSeeText(__('auth.inactive'));
});

test('users with two factor enabled are redirected to two factor challenge', function () {
    $this->skipUnlessFortifyFeature(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->withTwoFactor()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('two-factor.login'));
    $this->assertGuest();
});

test('inactive users with two factor enabled can not authenticate', function () {
    $this->skipUnlessFortifyFeature(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->inactive()->withTwoFactor()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors([
        'login' => __('auth.inactive'),
    ]);

    $this->assertGuest();
});

test('inactive users with two factor enabled and invalid password see the default credentials error', function () {
    $this->skipUnlessFortifyFeature(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->inactive()->withTwoFactor()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrorsIn('email');
    $response->assertSessionDoesntHaveErrors(['login']);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $response->assertRedirect(route('home'));

    $this->assertGuest();
});

test('inactive authenticated users are logged out on their next request', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $user->forceFill([
        'is_active' => false,
    ])->save();

    $this->get(route('dashboard'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

test('inactive authenticated users see the deactivated session message on login', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $user->forceFill([
        'is_active' => false,
    ])->save();

    $response = $this->get(route('dashboard'));

    $response->assertRedirect(route('login'));

    $this->followRedirects($response)
        ->assertSeeText(__('auth.inactive_session'));
});

test('inactive authenticated users do not keep the email prefilled on login', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $user->forceFill([
        'is_active' => false,
    ])->save();

    $response = $this->get(route('dashboard'));

    $response->assertRedirect(route('login'));

    $this->followRedirects($response)
        ->assertDontSee('value="'.$user->email.'"', false);
});
