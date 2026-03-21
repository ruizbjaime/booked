<?php

namespace App\Actions\FeeTypes;

use App\Models\ChargeBasis;
use App\Models\FeeType;
use App\Models\FeeTypeChargeBasis;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UpdateFeeTypeChargeBases
{
    /**
     * @param  list<array{charge_basis_id: int, is_active: bool}>  $items
     */
    public function handle(User $actor, FeeType $feeType, array $items): void
    {
        Gate::forUser($actor)->authorize('update', $feeType);

        $normalized = $this->normalize($items);

        $this->validate($normalized);

        /** @var Collection<int, FeeTypeChargeBasis> $existingPivots */
        $existingPivots = FeeTypeChargeBasis::query()
            ->where('fee_type_id', $feeType->getKey())
            ->get()
            ->keyBy('charge_basis_id');

        $chargeBasisOrders = ChargeBasis::query()
            ->whereKey(array_column($normalized, 'charge_basis_id'))
            ->pluck('order', 'id');

        $payload = [];

        foreach ($normalized as $item) {
            /** @var FeeTypeChargeBasis|null $existing */
            $existing = $existingPivots->get($item['charge_basis_id']);

            $payload[$item['charge_basis_id']] = $existing instanceof FeeTypeChargeBasis
                ? [
                    'is_active' => $item['is_active'],
                    'is_default' => $existing->getAttribute('is_default'),
                    'sort_order' => $this->normalizeSortOrder($existing->getAttribute('sort_order')),
                    'metadata' => $existing->getAttribute('metadata'),
                ]
                : [
                    'is_active' => $item['is_active'],
                    'is_default' => false,
                    'sort_order' => $this->normalizeSortOrder($chargeBasisOrders->get($item['charge_basis_id'], 999)),
                    'metadata' => null,
                ];
        }

        $feeType->chargeBases()->sync($payload);
    }

    /**
     * @param  list<array{charge_basis_id: int|string, is_active: bool}>  $items
     * @return list<array{charge_basis_id: int, is_active: bool}>
     */
    private function normalize(array $items): array
    {
        return array_map(fn (array $item): array => [
            'charge_basis_id' => (int) $item['charge_basis_id'],
            'is_active' => (bool) $item['is_active'],
        ], $items);
    }

    /**
     * @param  list<array{charge_basis_id: int, is_active: bool}>  $items
     */
    private function validate(array $items): void
    {
        Validator::make([
            'items' => $items,
        ], [
            'items' => ['array'],
            'items.*.charge_basis_id' => ['required', 'integer', Rule::exists('charge_bases', 'id')],
            'items.*.is_active' => ['required', 'boolean'],
        ])->after($this->duplicateChargeBasesValidator($items))->validate();
    }

    /**
     * @param  list<array{charge_basis_id: int, is_active: bool}>  $items
     */
    private function duplicateChargeBasesValidator(array $items): Closure
    {
        return function (ValidatorContract $validator) use ($items): void {
            $chargeBasisIds = array_column($items, 'charge_basis_id');

            if (count($chargeBasisIds) !== count(array_unique($chargeBasisIds))) {
                $validator->errors()->add('items', __('fee_types.validation.duplicate_charge_bases'));
            }
        };
    }

    private function normalizeSortOrder(mixed $value): int
    {
        return is_int($value) ? $value : 999;
    }
}
