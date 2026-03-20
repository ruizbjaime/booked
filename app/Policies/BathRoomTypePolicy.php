<?php

namespace App\Policies;

use App\Models\BathRoomType;
use App\Models\User;

class BathRoomTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('bath_room_type.viewAny');
    }

    public function view(User $user, BathRoomType $bathRoomType): bool
    {
        return $user->checkPermissionTo('bath_room_type.view');
    }

    public function create(User $user): bool
    {
        return $user->checkPermissionTo('bath_room_type.create');
    }

    public function update(User $user, BathRoomType $bathRoomType): bool
    {
        return $user->checkPermissionTo('bath_room_type.update');
    }

    public function delete(User $user, BathRoomType $bathRoomType): bool
    {
        return $user->checkPermissionTo('bath_room_type.delete');
    }
}
