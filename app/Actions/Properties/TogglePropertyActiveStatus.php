<?php

namespace App\Actions\Properties;

use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class TogglePropertyActiveStatus
{
    public function handle(User $actor, Property $property, bool $isActive): void
    {
        Gate::forUser($actor)->authorize('update', $property);

        $property->update(['is_active' => $isActive]);
    }
}
