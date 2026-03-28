<?php

use App\Concerns\HasRolePresentation;
use App\Domain\Users\RoleConfig;
use App\Models\Role;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    RoleConfig::clearCache();
});

function rolePresenter(): object
{
    return new class
    {
        use HasRolePresentation;
    };
}

it('delegates role colors and labels to role config', function () {
    Role::query()->where('name', 'guest')->update([
        'color' => 'emerald',
        'en_label' => 'Guest User',
        'es_label' => 'Usuario Invitado',
    ]);
    RoleConfig::clearCache();

    app()->setLocale('en');

    expect(rolePresenter()->roleColor('guest'))->toBe('emerald')
        ->and(rolePresenter()->roleLabel('guest'))->toBe('Guest User');

    app()->setLocale('es');

    expect(rolePresenter()->roleLabel('guest'))->toBe('Usuario Invitado');
});

it('uses fallback role presentation for unknown roles', function () {
    app()->setLocale('en');

    expect(rolePresenter()->roleColor('unknown_role'))->toBe(RoleConfig::defaultColor())
        ->and(rolePresenter()->roleLabel('unknown_role'))->toBe('Unknown Role');
});
