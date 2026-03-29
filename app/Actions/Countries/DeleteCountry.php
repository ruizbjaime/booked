<?php

namespace App\Actions\Countries;

use App\Models\Country;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DeleteCountry
{
    /**
     * Delete the country if it has no associated users or properties, otherwise deactivate it.
     *
     * @return bool True if deleted, false if deactivated.
     */
    public function handle(User $actor, Country $country): bool
    {
        Gate::forUser($actor)->authorize('delete', $country);

        return DB::transaction(function () use ($country): bool {
            $locked = Country::query()->lockForUpdate()->findOrFail($country->id);

            if ($locked->users()->exists() || $locked->properties()->exists()) {
                $locked->update(['is_active' => false]);

                return false;
            }

            $locked->delete();

            return true;
        });
    }
}
