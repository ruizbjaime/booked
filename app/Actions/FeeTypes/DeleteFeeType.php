<?php

namespace App\Actions\FeeTypes;

use App\Models\FeeType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DeleteFeeType
{
    public function handle(User $actor, FeeType $feeType): void
    {
        Gate::forUser($actor)->authorize('delete', $feeType);

        DB::transaction(function () use ($feeType): void {
            FeeType::query()->lockForUpdate()->findOrFail($feeType->id)->delete();
        });
    }
}
