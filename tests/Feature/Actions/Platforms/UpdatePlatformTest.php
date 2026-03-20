<?php

use App\Actions\Platforms\UpdatePlatform;
use App\Models\Platform;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

// --- Authorization ---

it('throws AuthorizationException when non-admin user updates a platform', function () {
    $guest = makeGuest();
    $platform = Platform::factory()->create();

    app(UpdatePlatform::class)->handle($guest, $platform, 'en_name', 'New Name');
})->throws(AuthorizationException::class);

// --- Field updates (happy path) ---

it('updates en_name successfully', function () {
    $admin = makeAdmin();
    $platform = Platform::factory()->create(['en_name' => 'Old Name']);

    app(UpdatePlatform::class)->handle($admin, $platform, 'en_name', 'New English Name');

    expect($platform->fresh()->en_name)->toBe('New English Name');
});

it('updates es_name successfully', function () {
    $admin = makeAdmin();
    $platform = Platform::factory()->create(['es_name' => 'Nombre Viejo']);

    app(UpdatePlatform::class)->handle($admin, $platform, 'es_name', 'Nuevo Nombre');

    expect($platform->fresh()->es_name)->toBe('Nuevo Nombre');
});

it('updates color to a preset value', function () {
    $admin = makeAdmin();
    $platform = Platform::factory()->create(['color' => 'zinc']);

    app(UpdatePlatform::class)->handle($admin, $platform, 'color', 'indigo');

    expect($platform->fresh()->color)->toBe('indigo');
});

it('updates color to a hex value', function () {
    $admin = makeAdmin();
    $platform = Platform::factory()->create(['color' => 'zinc']);

    app(UpdatePlatform::class)->handle($admin, $platform, 'color', '#FF5733');

    expect($platform->fresh()->color)->toBe('#FF5733');
});

it('updates sort_order', function () {
    $admin = makeAdmin();
    $platform = Platform::factory()->create(['sort_order' => 10]);

    app(UpdatePlatform::class)->handle($admin, $platform, 'sort_order', 42);

    expect($platform->fresh()->sort_order)->toBe(42);
});

// --- Commission conversion ---

it('stores commission value divided by 100', function () {
    $admin = makeAdmin();
    $platform = Platform::factory()->create(['commission' => 0]);

    app(UpdatePlatform::class)->handle($admin, $platform, 'commission', 15.5);

    expect($platform->fresh()->commission)->toBe('0.1550');
});

it('stores commission_tax value divided by 100', function () {
    $admin = makeAdmin();
    $platform = Platform::factory()->create(['commission_tax' => 0]);

    app(UpdatePlatform::class)->handle($admin, $platform, 'commission_tax', 3.25);

    expect($platform->fresh()->commission_tax)->toBe('0.0325');
});

// --- Validation: name ---

it('rejects name with uppercase characters', function () {
    $admin = makeAdmin();
    $platform = Platform::factory()->create();

    app(UpdatePlatform::class)->handle($admin, $platform, 'name', 'InvalidName');
})->throws(ValidationException::class);

it('rejects duplicate name from another platform', function () {
    $admin = makeAdmin();
    Platform::factory()->create(['name' => 'taken-name']);
    $platform = Platform::factory()->create(['name' => 'my-name']);

    app(UpdatePlatform::class)->handle($admin, $platform, 'name', 'taken-name');
})->throws(ValidationException::class);

it('allows updating name to its own current value', function () {
    $admin = makeAdmin();
    $platform = Platform::factory()->create(['name' => 'self-name']);

    app(UpdatePlatform::class)->handle($admin, $platform, 'name', 'self-name');

    expect($platform->fresh()->name)->toBe('self-name');
});

// --- Validation: en_name ---

it('rejects duplicate en_name from another platform', function () {
    $admin = makeAdmin();
    Platform::factory()->create(['en_name' => 'Taken EN']);
    $platform = Platform::factory()->create(['en_name' => 'My EN']);

    app(UpdatePlatform::class)->handle($admin, $platform, 'en_name', 'Taken EN');
})->throws(ValidationException::class);

it('allows updating en_name to its own current value', function () {
    $admin = makeAdmin();
    $platform = Platform::factory()->create(['en_name' => 'Self EN']);

    app(UpdatePlatform::class)->handle($admin, $platform, 'en_name', 'Self EN');

    expect($platform->fresh()->en_name)->toBe('Self EN');
});

// --- Validation: es_name ---

it('rejects duplicate es_name from another platform', function () {
    $admin = makeAdmin();
    Platform::factory()->create(['es_name' => 'Nombre Tomado']);
    $platform = Platform::factory()->create(['es_name' => 'Mi Nombre']);

    app(UpdatePlatform::class)->handle($admin, $platform, 'es_name', 'Nombre Tomado');
})->throws(ValidationException::class);

it('allows updating es_name to its own current value', function () {
    $admin = makeAdmin();
    $platform = Platform::factory()->create(['es_name' => 'Nombre Propio']);

    app(UpdatePlatform::class)->handle($admin, $platform, 'es_name', 'Nombre Propio');

    expect($platform->fresh()->es_name)->toBe('Nombre Propio');
});

// --- Validation: color ---

it('rejects invalid color value', function () {
    $admin = makeAdmin();
    $platform = Platform::factory()->create();

    app(UpdatePlatform::class)->handle($admin, $platform, 'color', 'invalid-color');
})->throws(ValidationException::class);

// --- Validation: sort_order ---

it('rejects negative sort_order', function () {
    $admin = makeAdmin();
    $platform = Platform::factory()->create();

    app(UpdatePlatform::class)->handle($admin, $platform, 'sort_order', -1);
})->throws(ValidationException::class);

// --- Validation: commission ---

it('rejects commission greater than 100', function () {
    $admin = makeAdmin();
    $platform = Platform::factory()->create();

    app(UpdatePlatform::class)->handle($admin, $platform, 'commission', 100.01);
})->throws(ValidationException::class);

// --- Validation: commission_tax ---

it('rejects commission_tax greater than 100', function () {
    $admin = makeAdmin();
    $platform = Platform::factory()->create();

    app(UpdatePlatform::class)->handle($admin, $platform, 'commission_tax', 100.01);
})->throws(ValidationException::class);

// --- Unknown field ---

it('aborts with 422 for an unknown field', function () {
    $admin = makeAdmin();
    $platform = Platform::factory()->create();

    try {
        app(UpdatePlatform::class)->handle($admin, $platform, 'unknown_field', 'value');
        $this->fail('Expected HttpException was not thrown');
    } catch (HttpException $e) {
        expect($e->getStatusCode())->toBe(422);
    }
});
