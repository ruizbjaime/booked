<?php

namespace App\Policies;

use App\Models\BedType;
use App\Models\User;

class BedTypePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('bed_type.viewAny');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, BedType $bedType): bool
    {
        return $user->checkPermissionTo('bed_type.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('bed_type.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, BedType $bedType): bool
    {
        return $user->checkPermissionTo('bed_type.update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, BedType $bedType): bool
    {
        return $user->checkPermissionTo('bed_type.delete');
    }
}
