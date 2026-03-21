<?php

namespace App\Policies;

use App\Models\FeeType;
use App\Models\User;

class FeeTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('fee_type.viewAny');
    }

    public function view(User $user, FeeType $feeType): bool
    {
        return $user->checkPermissionTo('fee_type.view');
    }

    public function create(User $user): bool
    {
        return $user->checkPermissionTo('fee_type.create');
    }

    public function update(User $user, FeeType $feeType): bool
    {
        return $user->checkPermissionTo('fee_type.update');
    }

    public function delete(User $user, FeeType $feeType): bool
    {
        return $user->checkPermissionTo('fee_type.delete');
    }
}
