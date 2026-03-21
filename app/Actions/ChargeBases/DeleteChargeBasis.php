<?php

namespace App\Actions\ChargeBases;

use App\Models\ChargeBasis;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DeleteChargeBasis
{
    public function handle(User $actor, ChargeBasis $chargeBasis): void
    {
        Gate::forUser($actor)->authorize('delete', $chargeBasis);

        DB::transaction(function () use ($chargeBasis): void {
            ChargeBasis::query()->lockForUpdate()->findOrFail($chargeBasis->id)->delete();
        });
    }
}
