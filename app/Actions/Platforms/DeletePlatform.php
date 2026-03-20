<?php

namespace App\Actions\Platforms;

use App\Models\Platform;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DeletePlatform
{
    /**
     * Delete the platform.
     */
    public function handle(User $actor, Platform $platform): void
    {
        Gate::forUser($actor)->authorize('delete', $platform);

        DB::transaction(function () use ($platform): void {
            $locked = Platform::query()->lockForUpdate()->findOrFail($platform->id);

            $locked->delete();
        });
    }
}
