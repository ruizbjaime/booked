<?php

namespace App\Actions\Roles;

use App\Domain\Users\RoleConfig;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class DeleteRole
{
    public function handle(User $actor, Role $role): void
    {
        Gate::forUser($actor)->authorize('delete', $role);

        abort_if(
            RoleConfig::isSystemRole($role->name),
            403,
            __('roles.errors.system_role_undeletable'),
        );

        abort_if(
            $role->users()->exists(),
            409,
            __('roles.errors.role_has_users_undeletable'),
        );

        $role->delete();
    }
}
