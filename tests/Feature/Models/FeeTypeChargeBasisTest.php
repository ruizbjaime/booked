<?php

use App\Models\ChargeBasis;
use App\Models\FeeType;
use App\Models\FeeTypeChargeBasis;

it('belongs to fee type and charge basis', function () {
    $feeType = FeeType::factory()->create();
    $basis = ChargeBasis::factory()->create();

    $feeType->chargeBases()->attach($basis->id, ['sort_order' => 1]);

    $pivot = FeeTypeChargeBasis::query()
        ->where('fee_type_id', $feeType->id)
        ->where('charge_basis_id', $basis->id)
        ->firstOrFail();

    expect($pivot->feeType->id)->toBe($feeType->id)
        ->and($pivot->chargeBasis->id)->toBe($basis->id);
});

it('has incrementing enabled', function () {
    $pivot = new FeeTypeChargeBasis;

    expect($pivot->incrementing)->toBeTrue();
});

it('uses custom table name fee_type_charge_basis', function () {
    $pivot = new FeeTypeChargeBasis;

    expect($pivot->getTable())->toBe('fee_type_charge_basis');
});
