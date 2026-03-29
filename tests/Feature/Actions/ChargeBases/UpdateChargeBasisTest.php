<?php

use App\Actions\ChargeBases\UpdateChargeBasis;
use App\Models\ChargeBasis;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('throws authorization exception when non-admin updates a charge basis', function () {
    $guest = makeGuest();
    $chargeBasis = ChargeBasis::factory()->create();

    app(UpdateChargeBasis::class)->handle($guest, $chargeBasis, 'en_name', 'Updated');
})->throws(AuthorizationException::class);

it('updates scalar fields successfully', function () {
    $admin = makeAdmin();
    $chargeBasis = ChargeBasis::factory()->create(['en_name' => 'Old']);

    app(UpdateChargeBasis::class)->handle($admin, $chargeBasis, 'en_name', 'New Name');

    expect($chargeBasis->fresh()->en_name)->toBe('New Name');
});

it('updates en_description successfully', function () {
    $admin = makeAdmin();
    $chargeBasis = ChargeBasis::factory()->create(['en_description' => 'Old']);

    app(UpdateChargeBasis::class)->handle($admin, $chargeBasis, 'en_description', 'New description');

    expect($chargeBasis->fresh()->en_description)->toBe('New description');
});

it('updates es_description successfully', function () {
    $admin = makeAdmin();
    $chargeBasis = ChargeBasis::factory()->create(['es_description' => 'Vieja']);

    app(UpdateChargeBasis::class)->handle($admin, $chargeBasis, 'es_description', 'Nueva descripción');

    expect($chargeBasis->fresh()->es_description)->toBe('Nueva descripción');
});

it('updates metadata fields successfully', function () {
    $admin = makeAdmin();
    $chargeBasis = ChargeBasis::factory()->create(['metadata' => ['requires_quantity' => false, 'quantity_subject' => null]]);

    app(UpdateChargeBasis::class)->handle($admin, $chargeBasis, 'metadata.requires_quantity', true);
    app(UpdateChargeBasis::class)->handle($admin, $chargeBasis->fresh(), 'metadata.quantity_subject', 'pet');

    expect($chargeBasis->fresh()->metadata['requires_quantity'])->toBeTrue()
        ->and($chargeBasis->fresh()->metadata['quantity_subject'])->toBe('pet');
});

it('rejects null quantity subject when quantity is required', function () {
    $admin = makeAdmin();
    $chargeBasis = ChargeBasis::factory()->create(['metadata' => ['requires_quantity' => true, 'quantity_subject' => 'guest']]);

    app(UpdateChargeBasis::class)->handle($admin, $chargeBasis, 'metadata.quantity_subject', null);
})->throws(ValidationException::class);

it('regenerates slug when en_name is updated', function () {
    $admin = makeAdmin();
    $chargeBasis = ChargeBasis::factory()->create(['en_name' => 'Per Night']);

    app(UpdateChargeBasis::class)->handle($admin, $chargeBasis, 'en_name', 'Per Guest Per Night');

    expect($chargeBasis->fresh()->slug)->toBe('per-guest-per-night');
});

it('rejects blank localized labels', function (string $field) {
    $admin = makeAdmin();
    $chargeBasis = ChargeBasis::factory()->create();

    app(UpdateChargeBasis::class)->handle($admin, $chargeBasis, $field, '');
})->with(['en_name', 'es_name'])
    ->throws(ValidationException::class);

it('rejects negative order', function () {
    $admin = makeAdmin();
    $chargeBasis = ChargeBasis::factory()->create();

    app(UpdateChargeBasis::class)->handle($admin, $chargeBasis, 'order', -1);
})->throws(ValidationException::class);

it('aborts with 422 for an unknown field', function () {
    $admin = makeAdmin();
    $chargeBasis = ChargeBasis::factory()->create();

    try {
        app(UpdateChargeBasis::class)->handle($admin, $chargeBasis, 'unknown_field', 'value');
        $this->fail('Expected HttpException was not thrown');
    } catch (HttpException $exception) {
        expect($exception->getStatusCode())->toBe(422);
    }
});
