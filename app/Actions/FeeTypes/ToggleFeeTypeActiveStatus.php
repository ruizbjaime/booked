<?php

namespace App\Actions\FeeTypes;

use App\Models\FeeType;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class ToggleFeeTypeActiveStatus
{
    public function handle(User $actor, FeeType $feeType, bool $isActive): void
    {
        Gate::forUser($actor)->authorize('update', $feeType);

        $feeType->update([
            'is_active' => $isActive,
        ]);
    }
}
