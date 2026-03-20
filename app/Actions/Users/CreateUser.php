<?php

namespace App\Actions\Users;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Domain\Users\RoleNormalizer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateUser
{
    use PasswordValidationRules;
    use ProfileValidationRules;

    /**
     * @param  array<string, mixed>  $input
     */
    public function handle(User $actor, array $input): User
    {
        Gate::forUser($actor)->authorize('create', User::class);

        $this->validate($input);

        $rawRoles = $input['roles'] ?? [];
        $roles = RoleNormalizer::normalize(
            is_array($rawRoles) ? array_values(array_filter($rawRoles, 'is_string')) : [],
        );

        $user = new User([
            'name' => $input['name'],
            'email' => $input['email'],
            'is_active' => (bool) ($input['is_active'] ?? false),
        ]);

        $user->forceFill(['password' => $input['password']])->save();

        $user->syncRoles($roles);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function validate(array $input): void
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'is_active' => [
                'required',
                'boolean',
            ],
            'password' => $this->passwordRules(),
            'roles' => [
                'required',
                'array',
                'min:1',
            ],
            'roles.*' => [
                'string',
                Rule::exists((new Role)->getTable(), 'name')->where('is_active', true),
            ],
        ])->validate();
    }
}
