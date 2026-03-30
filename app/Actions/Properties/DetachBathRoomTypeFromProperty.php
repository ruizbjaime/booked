<?php

namespace App\Actions\Properties;

use App\Models\BathRoomType;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class DetachBathRoomTypeFromProperty
{
    public function handle(User $actor, Property $property, BathRoomType $bathRoomType): void
    {
        Gate::forUser($actor)->authorize('update', $property);

        $property->bathRoomTypes()->detach($bathRoomType->getKey());

        $property->flushAccommodationTotals();
    }
}
