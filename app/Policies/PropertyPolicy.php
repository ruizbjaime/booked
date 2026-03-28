<?php

namespace App\Policies;

use App\Models\Property;
use App\Models\User;

class PropertyPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasHostPermission($user, 'property.viewAny');
    }

    public function view(User $user, Property $property): bool
    {
        return $this->hasHostPermission($user, 'property.view');
    }

    public function create(User $user): bool
    {
        return $this->hasHostPermission($user, 'property.create');
    }

    public function update(User $user, Property $property): bool
    {
        return $this->hasHostPermission($user, 'property.update');
    }

    public function delete(User $user, Property $property): bool
    {
        return $this->hasHostPermission($user, 'property.delete');
    }

    public function restore(User $user, Property $property): bool
    {
        return $this->hasHostPermission($user, 'property.restore');
    }

    public function forceDelete(User $user, Property $property): bool
    {
        return $this->hasHostPermission($user, 'property.forceDelete');
    }

    private function hasHostPermission(User $user, string $permission): bool
    {
        return $user->hasRole('host') && $user->checkPermissionTo($permission);
    }
}
