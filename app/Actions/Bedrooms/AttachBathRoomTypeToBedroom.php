<?php

namespace App\Actions\Bedrooms;

use App\Models\Bedroom;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AttachBathRoomTypeToBedroom
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function handle(User $actor, Bedroom $bedroom, array $input): void
    {
        Gate::forUser($actor)->authorize('update', $bedroom->property);

        $validated = $this->validate($input);

        $bedroom->bathRoomTypes()->syncWithoutDetaching([
            $validated['bath_room_type_id'] => ['quantity' => $validated['quantity']],
        ]);

        $bedroom->property?->flushAccommodationTotals();
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{bath_room_type_id: int, quantity: int}
     */
    private function validate(array $input): array
    {
        /** @var array{bath_room_type_id: int|numeric-string, quantity: int|numeric-string} $validated */
        $validated = Validator::make($input, [
            'bath_room_type_id' => ['required', 'integer', Rule::exists('bath_room_types', 'id')],
            'quantity' => ['required', 'integer', 'min:1', 'max:50'],
        ])->validate();

        $bathRoomTypeId = $validated['bath_room_type_id'];
        $quantity = $validated['quantity'];

        abort_unless(is_int($bathRoomTypeId), 422);
        abort_unless(is_int($quantity), 422);

        return [
            'bath_room_type_id' => $bathRoomTypeId,
            'quantity' => $quantity,
        ];
    }
}
