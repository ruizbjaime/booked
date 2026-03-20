<?php

namespace App\Actions\Users;

use App\Domain\Users\RoleNormalizer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UpdateUserAccess
{
    /**
     * @param  array{is_active: bool|int|string, roles: list<string>}  $input
     */
    public function handle(User $actor, User $target, array $input): User
    {
        Gate::forUser($actor)->authorize('update', $target);

        $validated = Validator::make($input, [
            'is_active' => ['required', 'boolean'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', Rule::exists((new Role)->getTable(), 'name')->where('is_active', true)],
        ])->after(function (\Illuminate\Validation\Validator $validator) use ($actor, $target, $input): void {
            if ($actor->is($target) && ! filter_var($input['is_active'], FILTER_VALIDATE_BOOLEAN)) {
                $validator->errors()->add('is_active', __('users.show.validation.cannot_deactivate_self'));
            }
        })->validate();

        $target->update([
            'is_active' => (bool) $validated['is_active'],
        ]);

        $rawRoles = $validated['roles'] ?? [];
        $target->syncRoles(RoleNormalizer::normalize(
            is_array($rawRoles) ? array_values(array_filter($rawRoles, 'is_string')) : [],
        ));

        return $target->refresh()->load('roles');
    }
}
