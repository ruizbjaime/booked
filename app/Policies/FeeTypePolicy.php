<?php

namespace App\Policies;

use App\Models\FeeType;
use App\Models\User;

class FeeTypePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('fee_type.viewAny');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, FeeType $feeType): bool
    {
        return $user->checkPermissionTo('fee_type.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('fee_type.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, FeeType $feeType): bool
    {
        return $user->checkPermissionTo('fee_type.update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, FeeType $feeType): bool
    {
        return $user->checkPermissionTo('fee_type.delete');
    }
}
