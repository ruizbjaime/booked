<?php

namespace App\Policies;

use App\Models\Platform;
use App\Models\User;

class PlatformPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('platform.viewAny');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Platform $platform): bool
    {
        return $user->checkPermissionTo('platform.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('platform.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Platform $platform): bool
    {
        return $user->checkPermissionTo('platform.update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Platform $platform): bool
    {
        return $user->checkPermissionTo('platform.delete');
    }
}
