<?php

namespace App\Actions\Users;

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class UpdateUserPassword
{
    use PasswordValidationRules;

    /**
     * @param  array{password: string, password_confirmation: string}  $input
     */
    public function handle(User $actor, User $target, array $input): void
    {
        Gate::forUser($actor)->authorize('update', $target);

        Validator::make($input, [
            'password' => $this->passwordRules(),
        ])->validate();

        $target->forceFill([
            'password' => $input['password'],
        ])->save();

        if ($actor->is($target)) {
            session()->regenerate();
        }
    }
}
