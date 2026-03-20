<?php

namespace App\Actions\Countries;

use App\Models\Country;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class ToggleCountryActiveStatus
{
    public function handle(User $actor, Country $country, bool $isActive): void
    {
        Gate::forUser($actor)->authorize('update', $country);

        $country->update([
            'is_active' => $isActive,
        ]);
    }
}
