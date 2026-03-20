<?php

namespace App\Actions\Users;

use App\Models\User;
use Illuminate\Support\Facades\Gate;

class ToggleUserActiveStatus
{
    public function handle(User $actor, User $target, bool $isActive): void
    {
        abort_if($actor->is($target), 403, __('users.show.validation.cannot_deactivate_self'));

        Gate::forUser($actor)->authorize('update', $target);

        $target->update([
            'is_active' => $isActive,
        ]);
    }
}
