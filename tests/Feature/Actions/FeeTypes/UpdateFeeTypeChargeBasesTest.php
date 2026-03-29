<?php

use App\Actions\FeeTypes\UpdateFeeTypeChargeBases;
use App\Models\ChargeBasis;
use App\Models\FeeType;
use App\Models\FeeTypeChargeBasis;
use Database\Seeders\ChargeBasisSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed([RolesAndPermissionsSeeder::class, ChargeBasisSeeder::class]);
});

test('syncs selected charge bases with position-based order and default', function () {
    $admin = makeAdmin();
    $feeType = FeeType::factory()->create();

    $perStay = ChargeBasis::query()->where('slug', 'per-stay')->firstOrFail();
    $perNight = ChargeBasis::query()->where('slug', 'per-night')->firstOrFail();

    app(UpdateFeeTypeChargeBases::class)->handle($admin, $feeType, [$perStay->id, $perNight->id]);

    $feeType->refresh()->load('chargeBases');

    expect($feeType->chargeBases)->toHaveCount(2)
        ->and($feeType->chargeBases->firstWhere('id', $perStay->id)?->pivot?->sort_order)->toBe(1)
        ->and($feeType->chargeBases->firstWhere('id', $perStay->id)?->pivot?->is_default)->toBeTrue()
        ->and($feeType->chargeBases->firstWhere('id', $perNight->id)?->pivot?->sort_order)->toBe(2)
        ->and($feeType->chargeBases->firstWhere('id', $perNight->id)?->pivot?->is_default)->toBeFalse();
});

test('removes deselected charge bases on sync', function () {
    $admin = makeAdmin();
    $feeType = FeeType::factory()->create();

    $perStay = ChargeBasis::query()->where('slug', 'per-stay')->firstOrFail();
    $perNight = ChargeBasis::query()->where('slug', 'per-night')->firstOrFail();

    app(UpdateFeeTypeChargeBases::class)->handle($admin, $feeType, [$perStay->id, $perNight->id]);

    app(UpdateFeeTypeChargeBases::class)->handle($admin, $feeType->fresh(), [$perNight->id]);

    expect($feeType->fresh()->chargeBases()->pluck('charge_bases.id')->all())->toBe([$perNight->id]);
});

test('deduplicates charge bases silently', function () {
    $admin = makeAdmin();
    $feeType = FeeType::factory()->create();
    $perStay = ChargeBasis::query()->where('slug', 'per-stay')->firstOrFail();

    app(UpdateFeeTypeChargeBases::class)->handle($admin, $feeType, [$perStay->id, $perStay->id]);

    expect($feeType->fresh()->chargeBases)->toHaveCount(1);
});

test('non-admin user is denied from syncing charge bases', function () {
    $guest = makeGuest();
    $feeType = FeeType::factory()->create();
    $perStay = ChargeBasis::query()->where('slug', 'per-stay')->firstOrFail();

    app(UpdateFeeTypeChargeBases::class)->handle($guest, $feeType, [$perStay->id]);
})->throws(AuthorizationException::class);

test('validates non-existent charge basis id', function () {
    $admin = makeAdmin();
    $feeType = FeeType::factory()->create();

    app(UpdateFeeTypeChargeBases::class)->handle($admin, $feeType, [99999]);
})->throws(ValidationException::class);

test('validates inactive charge basis id', function () {
    $admin = makeAdmin();
    $feeType = FeeType::factory()->create();
    $inactiveBasis = ChargeBasis::factory()->create(['is_active' => false]);

    app(UpdateFeeTypeChargeBases::class)->handle($admin, $feeType, [$inactiveBasis->id]);
})->throws(ValidationException::class);

test('preserves existing metadata on re-sync', function () {
    $admin = makeAdmin();
    $feeType = FeeType::factory()->create();

    $perStay = ChargeBasis::query()->where('slug', 'per-stay')->firstOrFail();
    $perNight = ChargeBasis::query()->where('slug', 'per-night')->firstOrFail();

    app(UpdateFeeTypeChargeBases::class)->handle($admin, $feeType, [$perStay->id]);

    FeeTypeChargeBasis::query()
        ->where('fee_type_id', $feeType->id)
        ->where('charge_basis_id', $perStay->id)
        ->update(['metadata' => json_encode(['test' => true])]);

    app(UpdateFeeTypeChargeBases::class)->handle($admin, $feeType->fresh(), [$perStay->id, $perNight->id]);

    $pivot = FeeTypeChargeBasis::query()
        ->where('fee_type_id', $feeType->id)
        ->where('charge_basis_id', $perStay->id)
        ->firstOrFail();

    expect($pivot->is_default)->toBeTrue()
        ->and($pivot->sort_order)->toBe(1)
        ->and($pivot->metadata)->toBe(['test' => true]);
});

test('first item becomes default when order changes', function () {
    $admin = makeAdmin();
    $feeType = FeeType::factory()->create();

    $perStay = ChargeBasis::query()->where('slug', 'per-stay')->firstOrFail();
    $perNight = ChargeBasis::query()->where('slug', 'per-night')->firstOrFail();

    app(UpdateFeeTypeChargeBases::class)->handle($admin, $feeType, [$perStay->id, $perNight->id]);

    // Reverse order — perNight becomes default
    app(UpdateFeeTypeChargeBases::class)->handle($admin, $feeType->fresh(), [$perNight->id, $perStay->id]);

    $feeType->refresh()->load('chargeBases');

    expect($feeType->chargeBases->firstWhere('id', $perNight->id)?->pivot?->is_default)->toBeTrue()
        ->and($feeType->chargeBases->firstWhere('id', $perNight->id)?->pivot?->sort_order)->toBe(1)
        ->and($feeType->chargeBases->firstWhere('id', $perStay->id)?->pivot?->is_default)->toBeFalse()
        ->and($feeType->chargeBases->firstWhere('id', $perStay->id)?->pivot?->sort_order)->toBe(2);
});
