<?php

use App\Actions\ChargeBases\CreateChargeBasis;
use App\Models\ChargeBasis;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

function validChargeBasisInput(array $overrides = []): array
{
    return array_merge([
        'name' => 'per_child',
        'en_name' => 'Per Child',
        'es_name' => 'Por menor',
        'description' => 'Applied for each child.',
        'order' => 100,
        'is_active' => true,
        'metadata' => [
            'requires_quantity' => true,
            'quantity_subject' => 'guest',
        ],
    ], $overrides);
}

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('denies non-admin users from creating a charge basis', function () {
    $guest = makeGuest();

    app(CreateChargeBasis::class)->handle($guest, validChargeBasisInput());
})->throws(AuthorizationException::class);

it('creates a charge basis with valid input', function () {
    $admin = makeAdmin();

    $chargeBasis = app(CreateChargeBasis::class)->handle($admin, validChargeBasisInput());

    expect($chargeBasis)->toBeInstanceOf(ChargeBasis::class)
        ->and($chargeBasis->name)->toBe('per_child')
        ->and($chargeBasis->metadata['requires_quantity'])->toBeTrue()
        ->and($chargeBasis->metadata['quantity_subject'])->toBe('guest');
});

it('normalizes the slug to lowercase', function () {
    $admin = makeAdmin();

    $chargeBasis = app(CreateChargeBasis::class)->handle($admin, validChargeBasisInput(['name' => 'PER_CHILD']));

    expect($chargeBasis->name)->toBe('per_child');
});

it('rejects duplicate slug', function () {
    $admin = makeAdmin();

    ChargeBasis::factory()->create(['name' => 'per_child']);

    app(CreateChargeBasis::class)->handle($admin, validChargeBasisInput());
})->throws(ValidationException::class);

it('requires quantity subject when quantity is required', function () {
    $admin = makeAdmin();

    app(CreateChargeBasis::class)->handle($admin, validChargeBasisInput([
        'metadata' => ['requires_quantity' => true, 'quantity_subject' => null],
    ]));
})->throws(ValidationException::class);

it('allows null quantity subject when quantity is not required', function () {
    $admin = makeAdmin();

    $chargeBasis = app(CreateChargeBasis::class)->handle($admin, validChargeBasisInput([
        'metadata' => ['requires_quantity' => false, 'quantity_subject' => null],
    ]));

    expect($chargeBasis->metadata['requires_quantity'])->toBeFalse()
        ->and($chargeBasis->metadata['quantity_subject'])->toBeNull();
});

it('rejects invalid slug formats', function (string $name) {
    $admin = makeAdmin();

    app(CreateChargeBasis::class)->handle($admin, validChargeBasisInput(['name' => $name]));
})->with(['123_test', 'per child', 'per@child', 'per.child'])
    ->throws(ValidationException::class);

it('rejects missing translated labels', function () {
    $admin = makeAdmin();

    app(CreateChargeBasis::class)->handle($admin, validChargeBasisInput([
        'en_name' => '',
        'es_name' => '',
    ]));
})->throws(ValidationException::class);

it('rejects negative order', function () {
    $admin = makeAdmin();

    app(CreateChargeBasis::class)->handle($admin, validChargeBasisInput([
        'order' => -1,
    ]));
})->throws(ValidationException::class);

it('rejects order over 9999', function () {
    $admin = makeAdmin();

    app(CreateChargeBasis::class)->handle($admin, validChargeBasisInput([
        'order' => 10000,
    ]));
})->throws(ValidationException::class);

it('rejects invalid quantity subject not in allowed list', function () {
    $admin = makeAdmin();

    app(CreateChargeBasis::class)->handle($admin, validChargeBasisInput([
        'metadata' => ['requires_quantity' => true, 'quantity_subject' => 'invalid_subject'],
    ]));
})->throws(ValidationException::class);
