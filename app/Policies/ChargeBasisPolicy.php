<?php

namespace App\Policies;

use App\Models\ChargeBasis;
use App\Models\User;

class ChargeBasisPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('charge_basis.viewAny');
    }

    public function view(User $user, ChargeBasis $chargeBasis): bool
    {
        return $user->checkPermissionTo('charge_basis.view');
    }

    public function create(User $user): bool
    {
        return $user->checkPermissionTo('charge_basis.create');
    }

    public function update(User $user, ChargeBasis $chargeBasis): bool
    {
        return $user->checkPermissionTo('charge_basis.update');
    }

    public function delete(User $user, ChargeBasis $chargeBasis): bool
    {
        return $user->checkPermissionTo('charge_basis.delete');
    }
}
