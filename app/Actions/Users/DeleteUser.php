<?php

namespace App\Actions\Users;

use App\Models\User;
use Illuminate\Support\Facades\Gate;

class DeleteUser
{
    public function handle(User $actor, User $target): void
    {
        abort_if($actor->is($target), 403);

        Gate::forUser($actor)->authorize('delete', $target);

        $target->delete();
    }
}
