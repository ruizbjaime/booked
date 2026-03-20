<?php

namespace App\Actions\Roles;

use App\Domain\Users\RoleConfig;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SetDefaultRole
{
    public function handle(User $actor, Role $role): void
    {
        Gate::forUser($actor)->authorize('update', $role);

        abort_if(RoleConfig::isAdminRole($role->name), 422, __('roles.errors.admin_cannot_be_default'));
        abort_unless($role->is_active, 422, __('roles.errors.only_active_as_default'));

        DB::transaction(function () use ($role): void {
            Role::query()
                ->where('guard_name', 'web')
                ->where('is_default', true)
                ->update(['is_default' => false]);

            $role->update(['is_default' => true]);
        });

        RoleConfig::clearCache();
    }
}
