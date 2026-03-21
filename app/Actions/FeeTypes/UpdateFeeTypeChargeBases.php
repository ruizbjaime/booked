<?php

namespace App\Actions\FeeTypes;

use App\Models\FeeType;
use App\Models\FeeTypeChargeBasis;
use App\Models\User;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UpdateFeeTypeChargeBases
{
    /**
     * @param  list<int>  $orderedChargeBasisIds
     */
    public function handle(User $actor, FeeType $feeType, array $orderedChargeBasisIds): void
    {
        Gate::forUser($actor)->authorize('update', $feeType);

        $normalized = array_values(array_unique(array_map('intval', $orderedChargeBasisIds)));

        $this->validate($normalized);

        /** @var Collection<int, FeeTypeChargeBasis> $existingPivots */
        $existingPivots = FeeTypeChargeBasis::query()
            ->where('fee_type_id', $feeType->getKey())
            ->get()
            ->keyBy('charge_basis_id');

        $payload = [];

        foreach ($normalized as $position => $chargeBasisId) {
            /** @var FeeTypeChargeBasis|null $existing */
            $existing = $existingPivots->get($chargeBasisId);

            $payload[$chargeBasisId] = [
                'is_active' => true,
                'is_default' => $position === 0,
                'sort_order' => $position + 1,
                'metadata' => $existing instanceof FeeTypeChargeBasis ? $existing->getAttribute('metadata') : null,
            ];
        }

        $feeType->chargeBases()->sync($payload);
    }

    /**
     * @param  list<int>  $chargeBasisIds
     */
    private function validate(array $chargeBasisIds): void
    {
        Validator::make([
            'items' => $chargeBasisIds,
        ], [
            'items' => ['array'],
            'items.*' => ['required', 'integer', Rule::exists('charge_bases', 'id')],
        ])->after(function (ValidatorContract $validator) use ($chargeBasisIds): void {
            if (count($chargeBasisIds) !== count(array_unique($chargeBasisIds))) {
                $validator->errors()->add('items', __('fee_types.validation.duplicate_charge_bases'));
            }
        })->validate();
    }
}
