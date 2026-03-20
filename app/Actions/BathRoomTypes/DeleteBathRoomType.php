<?php

namespace App\Actions\BathRoomTypes;

use App\Models\BathRoomType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DeleteBathRoomType
{
    public function handle(User $actor, BathRoomType $bathRoomType): void
    {
        Gate::forUser($actor)->authorize('delete', $bathRoomType);

        DB::transaction(function () use ($bathRoomType): void {
            BathRoomType::query()->lockForUpdate()->findOrFail($bathRoomType->id)->delete();
        });
    }
}
