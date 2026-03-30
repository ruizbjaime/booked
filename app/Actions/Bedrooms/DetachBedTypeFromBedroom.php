<?php

namespace App\Actions\Bedrooms;

use App\Models\Bedroom;
use App\Models\BedType;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class DetachBedTypeFromBedroom
{
    public function handle(User $actor, Bedroom $bedroom, BedType $bedType): void
    {
        Gate::forUser($actor)->authorize('update', $bedroom->property);

        $bedroom->bedTypes()->detach($bedType->getKey());
    }
}
