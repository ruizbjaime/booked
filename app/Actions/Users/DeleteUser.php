<?php

namespace App\Actions\Users;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class DeleteUser
{
    public function handle(User $actor, User $target): void
    {
        abort_if($actor->is($target), 403);

        Gate::forUser($actor)->authorize('delete', $target);

        if ($target->properties()->exists()) {
            throw ValidationException::withMessages([
                'user' => [__('users.cannot_delete_with_properties')],
            ]);
        }

        $target->delete();
    }
}
