<?php

namespace App\Policies;

use App\Models\BathRoomType;
use App\Models\User;

class BathRoomTypePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('bath_room_type.viewAny');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, BathRoomType $bathRoomType): bool
    {
        return $user->checkPermissionTo('bath_room_type.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('bath_room_type.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, BathRoomType $bathRoomType): bool
    {
        return $user->checkPermissionTo('bath_room_type.update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, BathRoomType $bathRoomType): bool
    {
        return $user->checkPermissionTo('bath_room_type.delete');
    }
}
