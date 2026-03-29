<?php

use App\Actions\BedTypes\UpdateBedType;
use App\Models\BedType;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('throws authorization exception when non-admin user updates a bed type', function () {
    $guest = makeGuest();
    $bedType = BedType::factory()->create();

    app(UpdateBedType::class)->handle($guest, $bedType, 'en_name', 'New Name');
})->throws(AuthorizationException::class);

it('updates the localized labels successfully', function () {
    $admin = makeAdmin();
    $bedType = BedType::factory()->create([
        'en_name' => 'Old Name',
        'es_name' => 'Nombre Viejo',
    ]);

    app(UpdateBedType::class)->handle($admin, $bedType, 'en_name', 'New Name');
    app(UpdateBedType::class)->handle($admin, $bedType, 'es_name', 'Nombre Nuevo');

    expect($bedType->fresh()->en_name)->toBe('New Name')
        ->and($bedType->fresh()->es_name)->toBe('Nombre Nuevo');
});

it('updates bed capacity successfully', function () {
    $admin = makeAdmin();
    $bedType = BedType::factory()->create(['bed_capacity' => 1]);

    app(UpdateBedType::class)->handle($admin, $bedType, 'bed_capacity', 4);

    expect($bedType->fresh()->bed_capacity)->toBe(4);
});

it('updates sort order successfully', function () {
    $admin = makeAdmin();
    $bedType = BedType::factory()->create(['sort_order' => 10]);

    app(UpdateBedType::class)->handle($admin, $bedType, 'sort_order', 42);

    expect($bedType->fresh()->sort_order)->toBe(42);
});

it('regenerates slug when en_name is updated', function () {
    $admin = makeAdmin();
    $bedType = BedType::factory()->create(['en_name' => 'Old Bed']);

    app(UpdateBedType::class)->handle($admin, $bedType, 'en_name', 'King Size Bed');

    expect($bedType->fresh()->slug)->toBe('king-size-bed');
});

it('rejects blank localized labels', function (string $field) {
    $admin = makeAdmin();
    $bedType = BedType::factory()->create();

    app(UpdateBedType::class)->handle($admin, $bedType, $field, '');
})->with(['en_name', 'es_name'])
    ->throws(ValidationException::class);

it('rejects bed capacity below one', function () {
    $admin = makeAdmin();
    $bedType = BedType::factory()->create();

    app(UpdateBedType::class)->handle($admin, $bedType, 'bed_capacity', 0);
})->throws(ValidationException::class);

it('rejects negative sort order', function () {
    $admin = makeAdmin();
    $bedType = BedType::factory()->create();

    app(UpdateBedType::class)->handle($admin, $bedType, 'sort_order', -1);
})->throws(ValidationException::class);

it('aborts with 422 for an unknown field', function () {
    $admin = makeAdmin();
    $bedType = BedType::factory()->create();

    try {
        app(UpdateBedType::class)->handle($admin, $bedType, 'unknown_field', 'value');
        $this->fail('Expected HttpException was not thrown');
    } catch (HttpException $exception) {
        expect($exception->getStatusCode())->toBe(422);
    }
});
