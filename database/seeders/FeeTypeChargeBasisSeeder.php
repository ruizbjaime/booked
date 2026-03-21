<?php

namespace Database\Seeders;

use App\Models\ChargeBasis;
use App\Models\FeeType;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class FeeTypeChargeBasisSeeder extends Seeder
{
    public function run(): void
    {
        $chargeBasisIds = ChargeBasis::query()->pluck('id', 'name');

        foreach ($this->mappings() as $feeTypeName => $chargeBases) {
            $feeType = FeeType::query()->where('name', $feeTypeName)->first();

            if ($feeType === null) {
                continue;
            }

            $feeType->chargeBases()->syncWithoutDetaching(
                $this->resolvePivotPayload($chargeBases, $chargeBasisIds),
            );
        }
    }

    /**
     * @return array<string, list<array{name: string, is_active: bool, is_default: bool, sort_order: int}>>
     */
    private function mappings(): array
    {
        return [
            'cleaning-fee' => [
                ['name' => 'per_stay', 'is_active' => true, 'is_default' => true, 'sort_order' => 1],
                ['name' => 'one_time', 'is_active' => true, 'is_default' => false, 'sort_order' => 2],
            ],
            'short-stay-cleaning-fee' => [
                ['name' => 'per_stay', 'is_active' => true, 'is_default' => true, 'sort_order' => 1],
            ],
            'pet-fee' => [
                ['name' => 'per_stay', 'is_active' => true, 'is_default' => false, 'sort_order' => 1],
                ['name' => 'per_pet', 'is_active' => true, 'is_default' => true, 'sort_order' => 2],
                ['name' => 'per_pet_per_night', 'is_active' => true, 'is_default' => false, 'sort_order' => 3],
            ],
            'extra-guest-fee' => [
                ['name' => 'per_guest', 'is_active' => true, 'is_default' => false, 'sort_order' => 1],
                ['name' => 'per_guest_per_night', 'is_active' => true, 'is_default' => true, 'sort_order' => 2],
            ],
            'resort-fee' => [
                ['name' => 'per_stay', 'is_active' => true, 'is_default' => true, 'sort_order' => 1],
                ['name' => 'per_night', 'is_active' => true, 'is_default' => false, 'sort_order' => 2],
            ],
            'linen-fee' => [
                ['name' => 'per_stay', 'is_active' => true, 'is_default' => true, 'sort_order' => 1],
                ['name' => 'per_guest', 'is_active' => true, 'is_default' => false, 'sort_order' => 2],
            ],
            'towel-fee' => [
                ['name' => 'per_stay', 'is_active' => true, 'is_default' => false, 'sort_order' => 1],
                ['name' => 'per_guest', 'is_active' => true, 'is_default' => true, 'sort_order' => 2],
            ],
            'management-fee' => [
                ['name' => 'per_stay', 'is_active' => true, 'is_default' => true, 'sort_order' => 1],
            ],
            'community-fee' => [
                ['name' => 'per_stay', 'is_active' => true, 'is_default' => true, 'sort_order' => 1],
            ],
            'service-charge' => [
                ['name' => 'per_stay', 'is_active' => true, 'is_default' => true, 'sort_order' => 1],
                ['name' => 'per_night', 'is_active' => true, 'is_default' => false, 'sort_order' => 2],
            ],
            'destination-charge' => [
                ['name' => 'per_stay', 'is_active' => true, 'is_default' => true, 'sort_order' => 1],
            ],
            'destination-tax' => [
                ['name' => 'per_guest_per_night', 'is_active' => true, 'is_default' => true, 'sort_order' => 1],
            ],
            'tourism-fee' => [
                ['name' => 'per_guest_per_night', 'is_active' => true, 'is_default' => true, 'sort_order' => 1],
            ],
            'city-tax' => [
                ['name' => 'per_guest_per_night', 'is_active' => true, 'is_default' => true, 'sort_order' => 1],
            ],
            'municipality-fee' => [
                ['name' => 'per_guest_per_night', 'is_active' => true, 'is_default' => true, 'sort_order' => 1],
            ],
            'government-tax' => [
                ['name' => 'per_stay', 'is_active' => true, 'is_default' => true, 'sort_order' => 1],
            ],
            'vat-sales-tax' => [
                ['name' => 'per_stay', 'is_active' => true, 'is_default' => true, 'sort_order' => 1],
            ],
            'environment-fee' => [
                ['name' => 'per_guest_per_night', 'is_active' => true, 'is_default' => true, 'sort_order' => 1],
            ],
            'sustainability-fee' => [
                ['name' => 'per_stay', 'is_active' => true, 'is_default' => true, 'sort_order' => 1],
                ['name' => 'per_night', 'is_active' => true, 'is_default' => false, 'sort_order' => 2],
            ],
            'heritage-tax' => [
                ['name' => 'per_guest_per_night', 'is_active' => true, 'is_default' => true, 'sort_order' => 1],
            ],
            'local-conservation-fee' => [
                ['name' => 'per_guest_per_night', 'is_active' => true, 'is_default' => true, 'sort_order' => 1],
            ],
            'city-ticket-fee' => [
                ['name' => 'per_guest_per_night', 'is_active' => true, 'is_default' => true, 'sort_order' => 1],
            ],
            'hot-spring-tax' => [
                ['name' => 'per_guest_per_night', 'is_active' => true, 'is_default' => true, 'sort_order' => 1],
            ],
            'spa-tax' => [
                ['name' => 'per_guest_per_night', 'is_active' => true, 'is_default' => true, 'sort_order' => 1],
            ],
            'parking-fee' => [
                ['name' => 'per_vehicle', 'is_active' => true, 'is_default' => true, 'sort_order' => 1],
                ['name' => 'per_night', 'is_active' => true, 'is_default' => false, 'sort_order' => 2],
            ],
            'internet-wifi-fee' => [
                ['name' => 'per_stay', 'is_active' => true, 'is_default' => true, 'sort_order' => 1],
                ['name' => 'per_use', 'is_active' => true, 'is_default' => false, 'sort_order' => 2],
            ],
            'credit-card-fee' => [
                ['name' => 'per_request', 'is_active' => true, 'is_default' => true, 'sort_order' => 1],
            ],
            'smoking-fee' => [
                ['name' => 'per_request', 'is_active' => true, 'is_default' => true, 'sort_order' => 1],
                ['name' => 'one_time', 'is_active' => true, 'is_default' => false, 'sort_order' => 2],
            ],
            'early-check-in-fee' => [
                ['name' => 'per_request', 'is_active' => true, 'is_default' => true, 'sort_order' => 1],
                ['name' => 'one_time', 'is_active' => true, 'is_default' => false, 'sort_order' => 2],
            ],
            'late-check-out-fee' => [
                ['name' => 'per_request', 'is_active' => true, 'is_default' => true, 'sort_order' => 1],
                ['name' => 'one_time', 'is_active' => true, 'is_default' => false, 'sort_order' => 2],
            ],
            'facility-usage-fee' => [
                ['name' => 'per_use', 'is_active' => true, 'is_default' => true, 'sort_order' => 1],
                ['name' => 'per_request', 'is_active' => true, 'is_default' => false, 'sort_order' => 2],
            ],
        ];
    }

    /**
     * @param  list<array{name: string, is_active: bool, is_default: bool, sort_order: int}>  $chargeBases
     * @param  Collection<string, int>  $chargeBasisIds
     * @return array<int, array<string, mixed>>
     */
    private function resolvePivotPayload(array $chargeBases, Collection $chargeBasisIds): array
    {
        return collect($chargeBases)
            ->mapWithKeys(function (array $basis) use ($chargeBasisIds): array {
                $id = $chargeBasisIds->get($basis['name']);

                if ($id === null) {
                    throw new ModelNotFoundException("Charge basis [{$basis['name']}] not found.");
                }

                return [
                    $id => [
                        'is_active' => $basis['is_active'],
                        'is_default' => $basis['is_default'],
                        'sort_order' => $basis['sort_order'],
                        'metadata' => null,
                    ],
                ];
            })
            ->all();
    }
}
