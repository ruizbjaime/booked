<?php

namespace App\Actions\Platforms;

use App\Models\Platform;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class TogglePlatformActiveStatus
{
    public function handle(User $actor, Platform $platform, bool $isActive): void
    {
        Gate::forUser($actor)->authorize('update', $platform);

        $platform->update([
            'is_active' => $isActive,
        ]);
    }
}
