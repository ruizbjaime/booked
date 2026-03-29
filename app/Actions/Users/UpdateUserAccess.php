<?php

namespace App\Actions\Users;

use App\Domain\Users\RoleConfig;
use App\Domain\Users\RoleNormalizer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Query\Builder;
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
            'roles.*' => ['string', Rule::exists((new Role)->getTable(), 'name')->where(function (Builder $query): void {
                $query->where('is_active', true)
                    ->where('guard_name', 'web');
            })],
        ])->after(function (\Illuminate\Validation\Validator $validator) use ($actor, $target, $input): void {
            if ($actor->is($target) && ! filter_var($input['is_active'], FILTER_VALIDATE_BOOLEAN)) {
                $validator->errors()->add('is_active', __('users.show.validation.cannot_deactivate_self'));
            }

            if (! $actor->hasRole(RoleConfig::adminRole()) && collect($input['roles'])->contains(fn ($r) => RoleConfig::isAdminRole($r))) {
                $validator->errors()->add('roles', __('users.show.validation.cannot_assign_admin'));
            }

            if ($actor->is($target)) {
                $currentRoles = $target->roles->pluck('name')->sort()->values()->all();
                $requestedRoles = collect(RoleNormalizer::normalize($input['roles']))->sort()->values()->all();

                if ($currentRoles !== $requestedRoles) {
                    $validator->errors()->add('roles', __('users.show.validation.cannot_change_own_roles'));
                }
            }
        })->validate();

        $target->update([
            'is_active' => (bool) $validated['is_active'],
        ]);

        /** @var list<string> $roles */
        $roles = $validated['roles'];
        $target->syncRoles(RoleNormalizer::normalize($roles));

        return $target->refresh()->load('roles');
    }
}
