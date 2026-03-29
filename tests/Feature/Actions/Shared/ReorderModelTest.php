<?php

use App\Actions\Shared\ReorderModel;
use App\Models\ChargeBasis;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('reorders a record to a new position', function () {
    $admin = makeAdmin();

    $a = ChargeBasis::factory()->create(['order' => 1, 'slug' => 'basis-a']);
    $b = ChargeBasis::factory()->create(['order' => 2, 'slug' => 'basis-b']);
    $c = ChargeBasis::factory()->create(['order' => 3, 'slug' => 'basis-c']);

    app(ReorderModel::class)->handle($admin, $c, 'order', 0);

    expect($c->fresh()->order)->toBe(1)
        ->and($a->fresh()->order)->toBe(2)
        ->and($b->fresh()->order)->toBe(3);
});

it('moves a record down in the list', function () {
    $admin = makeAdmin();

    $a = ChargeBasis::factory()->create(['order' => 1, 'slug' => 'basis-a']);
    $b = ChargeBasis::factory()->create(['order' => 2, 'slug' => 'basis-b']);
    $c = ChargeBasis::factory()->create(['order' => 3, 'slug' => 'basis-c']);

    app(ReorderModel::class)->handle($admin, $a, 'order', 2);

    expect($b->fresh()->order)->toBe(1)
        ->and($c->fresh()->order)->toBe(2)
        ->and($a->fresh()->order)->toBe(3);
});

it('clamps position to valid range', function () {
    $admin = makeAdmin();

    $a = ChargeBasis::factory()->create(['order' => 1, 'slug' => 'basis-a']);
    $b = ChargeBasis::factory()->create(['order' => 2, 'slug' => 'basis-b']);

    app(ReorderModel::class)->handle($admin, $a, 'order', 99);

    expect($b->fresh()->order)->toBe(1)
        ->and($a->fresh()->order)->toBe(2);
});

it('throws authorization exception for non-admin', function () {
    $guest = makeGuest();

    $record = ChargeBasis::factory()->create(['order' => 1]);

    app(ReorderModel::class)->handle($guest, $record, 'order', 0);
})->throws(AuthorizationException::class);
