<?php

namespace App\Policies;

use App\Models\SeasonBlock;
use App\Models\User;

class SeasonBlockPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('season_block.viewAny');
    }

    public function view(User $user, SeasonBlock $seasonBlock): bool
    {
        return $user->checkPermissionTo('season_block.view');
    }

    public function create(User $user): bool
    {
        return $user->checkPermissionTo('season_block.create');
    }

    public function update(User $user, SeasonBlock $seasonBlock): bool
    {
        return $user->checkPermissionTo('season_block.update');
    }

    public function delete(User $user, SeasonBlock $seasonBlock): bool
    {
        return $user->checkPermissionTo('season_block.delete');
    }
}
