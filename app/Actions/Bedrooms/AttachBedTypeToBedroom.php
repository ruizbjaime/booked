<?php

namespace App\Actions\Bedrooms;

use App\Models\Bedroom;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AttachBedTypeToBedroom
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function handle(User $actor, Bedroom $bedroom, array $input): void
    {
        Gate::forUser($actor)->authorize('update', $bedroom->property);

        $validated = $this->validate($input);

        $bedroom->bedTypes()->syncWithoutDetaching([
            $validated['bed_type_id'] => ['quantity' => $validated['quantity']],
        ]);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{bed_type_id: int, quantity: int}
     */
    private function validate(array $input): array
    {
        $validated = Validator::make($input, [
            'bed_type_id' => ['required', 'integer', Rule::exists('bed_types', 'id')->where('is_active', true)],
            'quantity' => ['required', 'integer', 'min:1', 'max:50'],
        ])->validate();

        $bedTypeId = $validated['bed_type_id'];
        $quantity = $validated['quantity'];

        abort_unless(is_int($bedTypeId), 422);
        abort_unless(is_int($quantity), 422);

        return [
            'bed_type_id' => $bedTypeId,
            'quantity' => $quantity,
        ];
    }
}
