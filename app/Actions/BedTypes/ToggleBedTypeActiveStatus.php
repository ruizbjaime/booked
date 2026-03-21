<?php

namespace App\Actions\BedTypes;

use App\Models\BedType;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class ToggleBedTypeActiveStatus
{
    public function handle(User $actor, BedType $bedType, bool $isActive): void
    {
        Gate::forUser($actor)->authorize('update', $bedType);

        $bedType->update([
            'is_active' => $isActive,
        ]);
    }
}
