<?php

namespace App\Actions\Roles;

use App\Domain\Auth\PermissionRegistry;
use App\Domain\Users\RoleConfig;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UpdateRolePermissions
{
    /**
     * @param  list<string>  $permissionNames
     */
    public function handle(User $actor, Role $role, array $permissionNames): void
    {
        Gate::forUser($actor)->authorize('update', $role);

        $this->validate($permissionNames);

        $finalPermissions = $this->enforceAdminProtection($role, $permissionNames);

        $role->syncPermissions($finalPermissions);
    }

    /**
     * @param  list<string>  $permissionNames
     */
    private function validate(array $permissionNames): void
    {
        Validator::make(
            ['permissions' => $permissionNames],
            ['permissions.*' => ['string', Rule::in(PermissionRegistry::allPermissionNames())]],
        )->validate();
    }

    /**
     * @param  list<string>  $permissionNames
     * @return list<string>
     */
    private function enforceAdminProtection(Role $role, array $permissionNames): array
    {
        if (! RoleConfig::isAdminRole($role->name)) {
            return $permissionNames;
        }

        return array_values(array_unique([
            ...$permissionNames,
            ...PermissionRegistry::adminProtectedPermissions(),
        ]));
    }
}
