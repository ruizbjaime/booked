<?php

namespace App\Policies;

use App\Models\ChargeBasis;
use App\Models\User;

class ChargeBasisPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('charge_basis.viewAny');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ChargeBasis $chargeBasis): bool
    {
        return $user->checkPermissionTo('charge_basis.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('charge_basis.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ChargeBasis $chargeBasis): bool
    {
        return $user->checkPermissionTo('charge_basis.update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ChargeBasis $chargeBasis): bool
    {
        return $user->checkPermissionTo('charge_basis.delete');
    }
}
