<?php

use App\Actions\FeeTypes\UpdateFeeType;
use App\Models\FeeType;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('throws authorization exception when non-admin user updates a fee type', function () {
    $guest = makeGuest();
    $feeType = FeeType::factory()->create();

    app(UpdateFeeType::class)->handle($guest, $feeType, 'en_name', 'New Name');
})->throws(AuthorizationException::class);

it('updates the localized labels successfully', function () {
    $admin = makeAdmin();
    $feeType = FeeType::factory()->create([
        'en_name' => 'Old Name',
        'es_name' => 'Nombre Viejo',
    ]);

    app(UpdateFeeType::class)->handle($admin, $feeType, 'en_name', 'New Name');
    app(UpdateFeeType::class)->handle($admin, $feeType, 'es_name', 'Nombre Nuevo');

    expect($feeType->fresh()->en_name)->toBe('New Name')
        ->and($feeType->fresh()->es_name)->toBe('Nombre Nuevo');
});

it('updates order successfully', function () {
    $admin = makeAdmin();
    $feeType = FeeType::factory()->create(['order' => 10]);

    app(UpdateFeeType::class)->handle($admin, $feeType, 'order', 42);

    expect($feeType->fresh()->order)->toBe(42);
});

it('normalizes the slug before updating a fee type', function () {
    $admin = makeAdmin();
    $feeType = FeeType::factory()->create(['name' => 'old-fee']);

    app(UpdateFeeType::class)->handle($admin, $feeType, 'name', '  SERVICE-FEE  ');

    expect($feeType->fresh()->name)->toBe('service-fee');
});

it('rejects duplicate names from another fee type', function () {
    $admin = makeAdmin();
    FeeType::factory()->create(['name' => 'service-fee']);
    $feeType = FeeType::factory()->create(['name' => 'cleaning-fee']);

    app(UpdateFeeType::class)->handle($admin, $feeType, 'name', 'service-fee');
})->throws(ValidationException::class);

it('allows updating the slug to its current value', function () {
    $admin = makeAdmin();
    $feeType = FeeType::factory()->create(['name' => 'cleaning-fee']);

    app(UpdateFeeType::class)->handle($admin, $feeType, 'name', 'cleaning-fee');

    expect($feeType->fresh()->name)->toBe('cleaning-fee');
});

it('rejects invalid slug formats', function (string $name) {
    $admin = makeAdmin();
    $feeType = FeeType::factory()->create();

    app(UpdateFeeType::class)->handle($admin, $feeType, 'name', $name);
})->with(['123-fee', 'cleaning fee', 'cleaning@fee', 'cleaning.fee'])
    ->throws(ValidationException::class);

it('rejects blank localized labels', function (string $field) {
    $admin = makeAdmin();
    $feeType = FeeType::factory()->create();

    app(UpdateFeeType::class)->handle($admin, $feeType, $field, '');
})->with(['en_name', 'es_name'])
    ->throws(ValidationException::class);

it('rejects negative order', function () {
    $admin = makeAdmin();
    $feeType = FeeType::factory()->create();

    app(UpdateFeeType::class)->handle($admin, $feeType, 'order', -1);
})->throws(ValidationException::class);

it('aborts with 422 for an unknown field', function () {
    $admin = makeAdmin();
    $feeType = FeeType::factory()->create();

    try {
        app(UpdateFeeType::class)->handle($admin, $feeType, 'unknown_field', 'value');
        $this->fail('Expected HttpException was not thrown');
    } catch (HttpException $exception) {
        expect($exception->getStatusCode())->toBe(422);
    }
});
