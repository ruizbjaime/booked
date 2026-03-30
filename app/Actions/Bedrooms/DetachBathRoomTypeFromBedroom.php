<?php

namespace App\Actions\Bedrooms;

use App\Models\BathRoomType;
use App\Models\Bedroom;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class DetachBathRoomTypeFromBedroom
{
    public function handle(User $actor, Bedroom $bedroom, BathRoomType $bathRoomType): void
    {
        Gate::forUser($actor)->authorize('update', $bedroom->property);

        $bedroom->bathRoomTypes()->detach($bathRoomType->getKey());

        $bedroom->property?->flushAccommodationTotals();
    }
}
