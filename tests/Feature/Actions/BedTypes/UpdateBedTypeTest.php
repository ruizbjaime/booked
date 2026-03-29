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

it('normalizes the slug before updating a bed type', function () {
    $admin = makeAdmin();
    $bedType = BedType::factory()->create(['name' => 'old-bed']);

    app(UpdateBedType::class)->handle($admin, $bedType, 'name', '  KING-BED  ');

    expect($bedType->fresh()->name)->toBe('king-bed');
});

it('rejects duplicate names from another bed type', function () {
    $admin = makeAdmin();
    BedType::factory()->create(['name' => 'king-bed']);
    $bedType = BedType::factory()->create(['name' => 'single-bed']);

    app(UpdateBedType::class)->handle($admin, $bedType, 'name', 'king-bed');
})->throws(ValidationException::class);

it('allows updating the slug to its current value', function () {
    $admin = makeAdmin();
    $bedType = BedType::factory()->create(['name' => 'queen-bed']);

    app(UpdateBedType::class)->handle($admin, $bedType, 'name', 'queen-bed');

    expect($bedType->fresh()->name)->toBe('queen-bed');
});

it('rejects invalid slug formats', function (string $name) {
    $admin = makeAdmin();
    $bedType = BedType::factory()->create();

    app(UpdateBedType::class)->handle($admin, $bedType, 'name', $name);
})->with(['123-bed', 'queen bed', 'queen@bed', 'queen.bed'])
    ->throws(ValidationException::class);

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
