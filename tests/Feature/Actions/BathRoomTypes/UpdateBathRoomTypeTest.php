<?php

use App\Actions\BathRoomTypes\UpdateBathRoomType;
use App\Models\BathRoomType;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('throws authorization exception when non-admin user updates a bathroom type', function () {
    $guest = makeGuest();
    $bathRoomType = BathRoomType::factory()->create();

    app(UpdateBathRoomType::class)->handle($guest, $bathRoomType, 'en_name', 'New Name');
})->throws(AuthorizationException::class);

it('updates the localized labels successfully', function () {
    $admin = makeAdmin();
    $bathRoomType = BathRoomType::factory()->create([
        'en_name' => 'Old Name',
        'es_name' => 'Nombre Viejo',
    ]);

    app(UpdateBathRoomType::class)->handle($admin, $bathRoomType, 'en_name', 'New Name');
    app(UpdateBathRoomType::class)->handle($admin, $bathRoomType, 'es_name', 'Nombre Nuevo');

    expect($bathRoomType->fresh()->en_name)->toBe('New Name')
        ->and($bathRoomType->fresh()->es_name)->toBe('Nombre Nuevo');
});

it('updates description successfully', function () {
    $admin = makeAdmin();
    $bathRoomType = BathRoomType::factory()->create(['description' => 'Old description']);

    app(UpdateBathRoomType::class)->handle($admin, $bathRoomType, 'description', 'New description');

    expect($bathRoomType->fresh()->description)->toBe('New description');
});

it('updates sort order successfully', function () {
    $admin = makeAdmin();
    $bathRoomType = BathRoomType::factory()->create(['sort_order' => 10]);

    app(UpdateBathRoomType::class)->handle($admin, $bathRoomType, 'sort_order', 42);

    expect($bathRoomType->fresh()->sort_order)->toBe(42);
});

it('regenerates slug when en_name is updated', function () {
    $admin = makeAdmin();
    $bathRoomType = BathRoomType::factory()->create(['en_name' => 'Old Bathroom']);

    app(UpdateBathRoomType::class)->handle($admin, $bathRoomType, 'en_name', 'Shared Bathroom');

    expect($bathRoomType->fresh()->slug)->toBe('shared-bathroom');
});

it('rejects blank localized labels', function (string $field) {
    $admin = makeAdmin();
    $bathRoomType = BathRoomType::factory()->create();

    app(UpdateBathRoomType::class)->handle($admin, $bathRoomType, $field, '');
})->with(['en_name', 'es_name'])
    ->throws(ValidationException::class);

it('rejects blank description', function () {
    $admin = makeAdmin();
    $bathRoomType = BathRoomType::factory()->create();

    app(UpdateBathRoomType::class)->handle($admin, $bathRoomType, 'description', '');
})->throws(ValidationException::class);

it('rejects negative sort order', function () {
    $admin = makeAdmin();
    $bathRoomType = BathRoomType::factory()->create();

    app(UpdateBathRoomType::class)->handle($admin, $bathRoomType, 'sort_order', -1);
})->throws(ValidationException::class);

it('aborts with 422 for an unknown field', function () {
    $admin = makeAdmin();
    $bathRoomType = BathRoomType::factory()->create();

    try {
        app(UpdateBathRoomType::class)->handle($admin, $bathRoomType, 'unknown_field', 'value');
        $this->fail('Expected HttpException was not thrown');
    } catch (HttpException $exception) {
        expect($exception->getStatusCode())->toBe(422);
    }
});
