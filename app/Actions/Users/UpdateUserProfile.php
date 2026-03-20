<?php

namespace App\Actions\Users;

use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class UpdateUserProfile
{
    use ProfileValidationRules;

    /**
     * @param  array{name: string, email: string}  $input
     */
    public function handle(User $actor, User $target, array $input): User
    {
        Gate::forUser($actor)->authorize('update', $target);

        $validated = Validator::make($input, $this->profileRules($target->id))->validate();

        $target->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        return $target->refresh();
    }
}
