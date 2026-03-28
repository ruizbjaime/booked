<?php

namespace App\Actions\Properties;

use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DeleteProperty
{
    public function handle(User $actor, Property $property): void
    {
        Gate::forUser($actor)->authorize('delete', $property);

        DB::transaction(function () use ($property): void {
            $locked = Property::query()->lockForUpdate()->findOrFail($property->id);

            $locked->delete();
        });
    }
}
