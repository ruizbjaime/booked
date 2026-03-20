<?php

namespace App\Actions\Roles;

use App\Domain\Users\RoleConfig;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class ToggleRoleActiveStatus
{
    public function handle(User $actor, Role $role, bool $isActive): void
    {
        Gate::forUser($actor)->authorize('update', $role);

        if (! $isActive) {
            abort_if(
                RoleConfig::isSystemRole($role->name),
                403,
                __('roles.errors.system_role_undeactivatable'),
            );

            abort_if(
                $role->users()->exists(),
                409,
                __('roles.errors.role_has_users_undeactivatable'),
            );
        }

        $role->update([
            'is_active' => $isActive,
        ]);
    }
}
