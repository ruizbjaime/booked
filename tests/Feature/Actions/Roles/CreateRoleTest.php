<?php

use App\Actions\Roles\CreateRole;
use App\Models\Role;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('creates a role with valid input', function () {
    $admin = makeAdmin();

    $role = app(CreateRole::class)->handle($admin, [
        'name' => 'manager',
        'en_label' => 'Manager',
        'es_label' => 'Gerente',
        'color' => 'emerald',
        'sort_order' => 3,
        'is_active' => true,
    ]);

    expect($role->name)->toBe('manager')
        ->and($role->en_label)->toBe('Manager')
        ->and($role->es_label)->toBe('Gerente')
        ->and($role->color)->toBe('emerald')
        ->and($role->guard_name)->toBe('web')
        ->and($role->is_active)->toBeTrue();
});

it('rejects role names with uppercase or spaces', function () {
    $admin = makeAdmin();

    app(CreateRole::class)->handle($admin, [
        'name' => 'Super Admin',
        'en_label' => 'Super Admin',
        'es_label' => 'Super Admin',
        'color' => 'blue',
        'sort_order' => 1,
        'is_active' => true,
    ]);
})->throws(ValidationException::class);

it('rejects duplicate role names within the same guard', function () {
    $admin = makeAdmin();
    Role::factory()->create(['name' => 'editor', 'guard_name' => 'web']);

    app(CreateRole::class)->handle($admin, [
        'name' => 'editor',
        'en_label' => 'Editor',
        'es_label' => 'Editor',
        'color' => 'blue',
        'sort_order' => 1,
        'is_active' => true,
    ]);
})->throws(ValidationException::class);

it('rejects colors outside the allowed list', function () {
    $admin = makeAdmin();

    app(CreateRole::class)->handle($admin, [
        'name' => 'tester',
        'en_label' => 'Tester',
        'es_label' => 'Tester',
        'color' => 'magenta',
        'sort_order' => 1,
        'is_active' => true,
    ]);
})->throws(ValidationException::class);

it('supports every available role color', function (string $color) {
    $admin = makeAdmin();

    $role = app(CreateRole::class)->handle($admin, [
        'name' => "role-{$color}",
        'en_label' => "Role {$color}",
        'es_label' => "Rol {$color}",
        'color' => $color,
        'sort_order' => 1,
        'is_active' => true,
    ]);

    expect($role->color)->toBe($color);
})->with(CreateRole::AVAILABLE_COLORS);

it('throws authorization exception when non-admin creates a role', function () {
    $guest = makeGuest();

    app(CreateRole::class)->handle($guest, [
        'name' => 'hacker',
        'en_label' => 'Hacker',
        'es_label' => 'Hacker',
        'color' => 'red',
        'sort_order' => 1,
        'is_active' => true,
    ]);
})->throws(AuthorizationException::class);
