<?php

use App\Actions\Platforms\CreatePlatform;
use App\Models\Platform;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

function validPlatformInput(array $overrides = []): array
{
    return array_merge([
        'name' => 'test-platform',
        'en_name' => 'Test Platform',
        'es_name' => 'Plataforma de Prueba',
        'color' => 'blue',
        'sort_order' => 100,
        'commission' => 10,
        'commission_tax' => 5,
        'is_active' => true,
    ], $overrides);
}

// ─── isValidColor (static, no DB needed) ────────────────────────────────

it('accepts preset colors', function (string $color) {
    expect(CreatePlatform::isValidColor($color))->toBeTrue();
})->with(['red', 'blue', 'zinc', 'emerald', 'rose']);

it('accepts valid 6-digit hex colors', function (string $hex) {
    expect(CreatePlatform::isValidColor($hex))->toBeTrue();
})->with(['#FF5733', '#ff5733', '#000000', '#FFFFFF']);

it('accepts valid 3-digit hex colors', function (string $hex) {
    expect(CreatePlatform::isValidColor($hex))->toBeTrue();
})->with(['#F00', '#abc', '#000', '#FFF']);

it('rejects invalid color formats', function (string $value) {
    expect(CreatePlatform::isValidColor($value))->toBeFalse();
})->with(['#GGGGGG', '#12345', '#1234567', 'red123', '#', '']);

// ─── Tests requiring DB + seeder ─────────────────────────────────────────

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

// ─── Authorization ───────────────────────────────────────────────────────

it('denies non-admin users from creating a platform', function () {
    $guest = makeGuest();

    app(CreatePlatform::class)->handle($guest, validPlatformInput());
})->throws(AuthorizationException::class);

// ─── Successful creation ─────────────────────────────────────────────────

it('creates a platform with valid input', function () {
    $admin = makeAdmin();

    $platform = app(CreatePlatform::class)->handle($admin, validPlatformInput());

    expect($platform)->toBeInstanceOf(Platform::class)
        ->and($platform->name)->toBe('test-platform')
        ->and($platform->en_name)->toBe('Test Platform')
        ->and($platform->es_name)->toBe('Plataforma de Prueba')
        ->and($platform->color)->toBe('blue')
        ->and($platform->sort_order)->toBe(100)
        ->and($platform->is_active)->toBeTrue();
});

// ─── Name regex validation ───────────────────────────────────────────────

it('rejects name starting with uppercase', function () {
    $admin = makeAdmin();

    app(CreatePlatform::class)->handle($admin, validPlatformInput(['name' => 'Airbnb']));
})->throws(ValidationException::class);

it('rejects name starting with a number', function () {
    $admin = makeAdmin();

    app(CreatePlatform::class)->handle($admin, validPlatformInput(['name' => '123test']));
})->throws(ValidationException::class);

it('rejects name with spaces', function () {
    $admin = makeAdmin();

    app(CreatePlatform::class)->handle($admin, validPlatformInput(['name' => 'my platform']));
})->throws(ValidationException::class);

it('rejects name with special characters', function () {
    $admin = makeAdmin();

    app(CreatePlatform::class)->handle($admin, validPlatformInput(['name' => 'my@platform']));
})->throws(ValidationException::class);

it('accepts valid name formats', function (string $name) {
    $admin = makeAdmin();

    $platform = app(CreatePlatform::class)->handle($admin, validPlatformInput(['name' => $name]));

    expect($platform->name)->toBe($name);
})->with(['my-platform', 'my_platform', 'a123']);

// ─── Max length validation ───────────────────────────────────────────────

it('rejects name exceeding 255 characters', function () {
    $admin = makeAdmin();

    app(CreatePlatform::class)->handle($admin, validPlatformInput(['name' => str_repeat('a', 256)]));
})->throws(ValidationException::class);

it('rejects en_name exceeding 255 characters', function () {
    $admin = makeAdmin();

    app(CreatePlatform::class)->handle($admin, validPlatformInput(['en_name' => str_repeat('A', 256)]));
})->throws(ValidationException::class);

it('rejects es_name exceeding 255 characters', function () {
    $admin = makeAdmin();

    app(CreatePlatform::class)->handle($admin, validPlatformInput(['es_name' => str_repeat('A', 256)]));
})->throws(ValidationException::class);

// ─── Unique validation ───────────────────────────────────────────────────

it('rejects duplicate name', function () {
    $admin = makeAdmin();
    Platform::factory()->create(['name' => 'test-platform']);

    app(CreatePlatform::class)->handle($admin, validPlatformInput());
})->throws(ValidationException::class);

it('rejects duplicate en_name', function () {
    $admin = makeAdmin();
    Platform::factory()->create(['en_name' => 'Test Platform']);

    app(CreatePlatform::class)->handle($admin, validPlatformInput());
})->throws(ValidationException::class);

it('rejects duplicate es_name', function () {
    $admin = makeAdmin();
    Platform::factory()->create(['es_name' => 'Plataforma de Prueba']);

    app(CreatePlatform::class)->handle($admin, validPlatformInput());
})->throws(ValidationException::class);

// ─── Commission boundaries ───────────────────────────────────────────────

it('rejects commission greater than 100', function () {
    $admin = makeAdmin();

    app(CreatePlatform::class)->handle($admin, validPlatformInput(['commission' => 101]));
})->throws(ValidationException::class);

it('rejects commission_tax greater than 100', function () {
    $admin = makeAdmin();

    app(CreatePlatform::class)->handle($admin, validPlatformInput(['commission_tax' => 101]));
})->throws(ValidationException::class);

it('accepts commission equal to zero', function () {
    $admin = makeAdmin();

    $platform = app(CreatePlatform::class)->handle($admin, validPlatformInput(['commission' => 0]));

    expect($platform->commission)->toBe('0.0000');
});

it('accepts commission equal to 100', function () {
    $admin = makeAdmin();

    $platform = app(CreatePlatform::class)->handle($admin, validPlatformInput(['commission' => 100]));

    expect($platform->commission)->toBe('1.0000');
});

// ─── Commission conversion ───────────────────────────────────────────────

it('converts percentage to decimal for storage', function () {
    $admin = makeAdmin();

    $platform = app(CreatePlatform::class)->handle($admin, validPlatformInput([
        'commission' => 15.5,
        'commission_tax' => 5,
    ]));

    expect($platform->commission)->toBe('0.1550')
        ->and($platform->commission_tax)->toBe('0.0500');
});

// ─── Color validation ────────────────────────────────────────────────────

it('rejects a non-preset non-hex color', function () {
    $admin = makeAdmin();

    app(CreatePlatform::class)->handle($admin, validPlatformInput(['color' => 'invalid-color']));
})->throws(ValidationException::class);

// ─── is_active default ───────────────────────────────────────────────────

it('stores is_active as false when explicitly set to false', function () {
    $admin = makeAdmin();

    $platform = app(CreatePlatform::class)->handle($admin, validPlatformInput(['is_active' => false]));

    expect($platform->is_active)->toBeFalse();
});

it('rejects missing is_active from input', function () {
    $admin = makeAdmin();

    $input = validPlatformInput();
    unset($input['is_active']);

    app(CreatePlatform::class)->handle($admin, $input);
})->throws(ValidationException::class);
