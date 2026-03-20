<?php

namespace App\Actions\BedTypes;

use App\Models\BedType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DeleteBedType
{
    public function handle(User $actor, BedType $bedType): void
    {
        Gate::forUser($actor)->authorize('delete', $bedType);

        DB::transaction(function () use ($bedType): void {
            BedType::query()->lockForUpdate()->findOrFail($bedType->id)->delete();
        });
    }
}
