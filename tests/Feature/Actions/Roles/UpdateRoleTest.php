<?php

use App\Actions\Roles\CreateRole;
use App\Actions\Roles\UpdateRole;
use App\Models\Role;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('throws authorization exception when non-admin user updates a role', function () {
    $guest = makeGuest();
    $role = Role::factory()->create();

    app(UpdateRole::class)->handle($guest, $role, 'en_label', 'Updated');
})->throws(AuthorizationException::class);

it('updates role labels, color, and sort order', function () {
    $admin = makeAdmin();
    $role = Role::factory()->create([
        'en_label' => 'Old Label',
        'es_label' => 'Etiqueta Vieja',
        'color' => 'blue',
        'sort_order' => 2,
    ]);

    app(UpdateRole::class)->handle($admin, $role, 'en_label', 'Manager');
    app(UpdateRole::class)->handle($admin, $role, 'es_label', 'Gerente');
    app(UpdateRole::class)->handle($admin, $role, 'color', 'emerald');
    app(UpdateRole::class)->handle($admin, $role, 'sort_order', 8);

    expect($role->fresh()->en_label)->toBe('Manager')
        ->and($role->fresh()->es_label)->toBe('Gerente')
        ->and($role->fresh()->color)->toBe('emerald')
        ->and($role->fresh()->sort_order)->toBe(8);
});

it('rejects colors outside the allowed list', function () {
    $admin = makeAdmin();
    $role = Role::factory()->create();

    app(UpdateRole::class)->handle($admin, $role, 'color', 'infrared');
})->throws(ValidationException::class);

it('supports every available role color using datasets', function (string $color) {
    $admin = makeAdmin();
    $role = Role::factory()->create(['color' => 'blue']);

    app(UpdateRole::class)->handle($admin, $role, 'color', $color);

    expect($role->fresh()->color)->toBe($color);
})->with(CreateRole::AVAILABLE_COLORS);

it('aborts with 422 for an unknown role field', function () {
    $admin = makeAdmin();
    $role = Role::factory()->create();

    try {
        app(UpdateRole::class)->handle($admin, $role, 'unknown_field', 'value');
        $this->fail('Expected HttpException was not thrown');
    } catch (HttpException $exception) {
        expect($exception->getStatusCode())->toBe(422);
    }
});
